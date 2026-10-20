<?php

namespace App\Http\Controllers;

use App\Mail\ClaimApprovedMail;
use App\Mail\ClaimRejectedMail;
use App\Mail\ClaimReviewedMail;
use App\Mail\ClaimSubmittedMail;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\ClaimRevision;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ClaimController extends Controller
{
    public function index(Request $request): View
    {
        // Filter diambil hanya dari query string agar bisa dibagikan melalui URL.
        $filters = Validator::make($request->query(), [
            'vendor' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['submitted', 'reviewed', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ])->validate();

        $claims = Claim::with(['project', 'vendor'])
            ->when($request->user()->role === 'vendor', fn ($query) => $query->where('vendor_id', $request->user()->id))
            ->when($filters['vendor'] ?? null, function ($query, string $vendor): void {
                $query->whereHas('vendor', function ($vendorQuery) use ($vendor): void {
                    $vendorQuery->where('company_name', 'like', '%'.$vendor.'%')
                        ->orWhere('username', 'like', '%'.$vendor.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('claims.index', compact('claims', 'filters'));
    }

    public function create(): View
    {
        $projects = Project::orderBy('name')->get();

        return view('claims.create', compact('projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $creatingProject = $request->boolean('create_project');
        $data = $request->validate([
            'create_project' => ['required', 'boolean'],
            'project_id' => $creatingProject
                ? ['nullable']
                : ['required', 'exists:projects,id'],
            'new_project_name' => $creatingProject
                ? ['required', 'string', 'max:255']
                : ['nullable', 'prohibited'],
            'new_project_description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.claimed_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.claimed_unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        // Pilih hanya kolom yang boleh ditentukan vendor, terutama tanpa nilai realisasi.
        $items = collect($data['items'])->map(fn (array $item): array => [
            'item_name' => $item['item_name'],
            'unit' => $item['unit'],
            'claimed_quantity' => $item['claimed_quantity'],
            'claimed_unit_price' => $item['claimed_unit_price'],
        ])->all();

        $claim = DB::transaction(function () use ($data, $items, $creatingProject, $request): Claim {
            // Proyek baru disimpan sebagai proyek bersama yang dapat dipilih semua vendor.
            $project = $creatingProject
                ? Project::create([
                    'name' => $data['new_project_name'],
                    'description' => $data['new_project_description'] ?? null,
                ])
                : Project::findOrFail($data['project_id']);

            $claim = Claim::create([
                'project_id' => $project->id,
                'vendor_id' => $request->user()->id,
                'claim_number' => 'CLM-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'status' => 'submitted',
                'total_claim_amount' => collect($items)->sum(
                    fn (array $item): float => (float) $item['claimed_quantity'] * (float) $item['claimed_unit_price']
                ),
            ]);

            $claim->items()->createMany($items);

            ActivityLog::create([
                'claim_id' => $claim->id,
                'user_id' => $request->user()->id,
                'action' => 'submitted',
                'notes' => 'Vendor mengajukan klaim.',
            ]);

            return $claim;
        });

        $this->queueClaimNotification($claim, 'submitted');

        return redirect()->route('claims.show', $claim)->with('status', 'Klaim berhasil diajukan.');
    }

    public function show(Request $request, Claim $claim): View
    {
        $this->authorizeClaimView($request, $claim);

        $claim->load([
            'project',
            'vendor',
            'items',
            'activityLogs.user',
            'attachments',
            'comments.user',
            'revisions.creator',
        ]);

        return view('claims.show', compact('claim'));
    }

    public function exportPdf(Request $request, Claim $claim)
    {
        $this->authorizeClaimView($request, $claim);
        $claim->load(['project', 'vendor', 'items', 'revisions', 'activityLogs.user']);

        $latestActivity = fn (array $actions, string $role) => $claim->activityLogs->first(
            fn (ActivityLog $activity): bool => in_array($activity->action, $actions, true)
                && $activity->user?->role === $role
        );
        $vendorSignature = $latestActivity(['submitted', 'revised'], 'vendor') ?? (object) [
            'user' => $claim->vendor,
            'created_at' => $claim->created_at,
        ];
        $signatures = [
            'vendor' => $vendorSignature,
            'admin' => in_array($claim->status, ['reviewed', 'approved', 'rejected'], true)
                ? $latestActivity(['reviewed'], 'admin')
                : null,
            'owner' => $claim->status === 'approved'
                ? $latestActivity(['approved'], 'owner')
                : null,
        ];

        return Pdf::loadView('claims.pdf', compact('claim', 'signatures'))
            ->setPaper('a4', 'portrait')
            ->download($claim->claim_number.'.pdf');
    }

    public function revisionForm(Request $request, Claim $claim): View
    {
        abort_unless($request->user()->role === 'vendor' && $claim->vendor_id === $request->user()->id, 403);
        abort_unless($claim->status === 'rejected', 404);

        $claim->load('items');

        return view('claims.revise', compact('claim'));
    }

    public function revise(Request $request, Claim $claim): RedirectResponse
    {
        abort_unless($request->user()->role === 'vendor' && $claim->vendor_id === $request->user()->id, 403);
        abort_unless($claim->status === 'rejected', 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.claimed_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.claimed_unit_price' => ['required', 'numeric', 'min:0'],
        ]);
        $items = collect($data['items'])->map(fn (array $item): array => [
            'item_name' => $item['item_name'],
            'unit' => $item['unit'],
            'claimed_quantity' => $item['claimed_quantity'],
            'claimed_unit_price' => $item['claimed_unit_price'],
        ])->all();

        DB::transaction(function () use ($claim, $items, $request): void {
            $claim->items()->delete();
            $claim->items()->createMany($items);
            $claim->update([
                'version' => $claim->version + 1,
                'status' => 'submitted',
                'total_claim_amount' => collect($items)->sum(
                    fn (array $item): float => (float) $item['claimed_quantity'] * (float) $item['claimed_unit_price']
                ),
                'total_actual_amount' => 0,
                'rejection_reason' => null,
            ]);

            ActivityLog::create([
                'claim_id' => $claim->id,
                'user_id' => $request->user()->id,
                'action' => 'revised',
                'notes' => 'Vendor mengirim revisi klaim versi '.$claim->version.'.',
            ]);
        });

        $this->queueClaimNotification($claim->fresh(), 'submitted');

        return redirect()->route('claims.show', $claim)->with('status', 'Revisi klaim berhasil dikirim.');
    }

    public function storeComment(Request $request, Claim $claim): RedirectResponse
    {
        $this->authorizeClaimView($request, $claim);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        // Komentar hanya bisa ditambahkan; tidak ada endpoint untuk mengubah atau menghapusnya.
        $claim->comments()->create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return redirect()->route('claims.show', $claim)->with('status', 'Komentar berhasil dikirim.');
    }

    public function review(Claim $claim): View
    {
        abort_unless($claim->status === 'submitted', 404);

        $claim->load(['project', 'vendor', 'items']);

        return view('claims.review', compact('claim'));
    }

    public function updateReview(Request $request, Claim $claim): RedirectResponse
    {
        abort_unless($claim->status === 'submitted', 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.actual_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.actual_unit_price' => ['required', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $claim->load('items');
        // ID dari input HTML selalu berupa teks, sedangkan ID model berupa integer.
        $submittedIds = collect($data['items'])->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values();
        $claimItemIds = $claim->items->pluck('id')->sort()->values();

        if ($submittedIds->all() !== $claimItemIds->all()) {
            throw ValidationException::withMessages([
                'items' => 'Data realisasi harus mencakup seluruh item klaim ini.',
            ]);
        }

        $uploadedPaths = [];

        try {
            DB::transaction(function () use ($claim, $data, $request, &$uploadedPaths): void {
                $totalActual = 0;

                foreach ($data['items'] as $actual) {
                    $item = $claim->items->firstWhere('id', $actual['id']);
                    $item->update([
                        'actual_quantity' => $actual['actual_quantity'],
                        'actual_unit_price' => $actual['actual_unit_price'],
                    ]);
                    $totalActual += (float) $actual['actual_quantity'] * (float) $actual['actual_unit_price'];
                }

                $claim->update([
                    'total_actual_amount' => $totalActual,
                    'status' => 'reviewed',
                ]);

                foreach ($data['attachments'] ?? [] as $file) {
                    $path = 'claim-attachments/'.$file->hashName();
                    $stored = Storage::disk('public')->put($path, $file->get());

                    if ($stored === false) {
                        throw new RuntimeException('File lampiran gagal disimpan ke penyimpanan publik.');
                    }

                    $uploadedPaths[] = $path;
                    $claim->attachments()->create([
                        'file_path' => $path,
                        'uploaded_by' => $request->user()->id,
                    ]);
                }

                ActivityLog::create([
                    'claim_id' => $claim->id,
                    'user_id' => $request->user()->id,
                    'action' => 'reviewed',
                    'notes' => 'Admin menyimpan realisasi lapangan.',
                ]);
            });
        } catch (Throwable $exception) {
            // Bersihkan foto yang sudah terunggah bila penyimpanan data klaim gagal.
            if ($uploadedPaths !== []) {
                Storage::disk('public')->delete($uploadedPaths);
            }

            throw $exception;
        }

        $this->queueClaimNotification($claim->fresh(), 'reviewed');

        return redirect()->route('claims.show', $claim)->with('status', 'Realisasi lapangan berhasil disimpan.');
    }

    public function approve(Request $request, Claim $claim): RedirectResponse
    {
        abort_unless($claim->status === 'reviewed', 404);

        DB::transaction(function () use ($claim, $request): void {
            $claim->update(['status' => 'approved', 'rejection_reason' => null]);
            ActivityLog::create([
                'claim_id' => $claim->id,
                'user_id' => $request->user()->id,
                'action' => 'approved',
                'notes' => 'Owner menyetujui klaim.',
            ]);
        });

        $this->queueClaimNotification($claim->fresh(), 'approved');

        return back()->with('status', 'Klaim berhasil disetujui.');
    }

    public function reject(Request $request, Claim $claim): RedirectResponse
    {
        abort_unless($claim->status === 'reviewed', 404);
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:5000'],
        ]);

        $claim->load(['project', 'vendor', 'items', 'attachments']);

        DB::transaction(function () use ($claim, $data, $request): void {
            // Snapshot versi lama disimpan sebelum status klaim diubah menjadi ditolak.
            ClaimRevision::create([
                'claim_id' => $claim->id,
                'version' => $claim->version,
                'total_amount' => $claim->total_claim_amount,
                'status_previous' => $claim->status,
                'rejection_reason' => $data['rejection_reason'],
                'created_by' => $request->user()->id,
                'data_snapshot' => [
                    'claim_number' => $claim->claim_number,
                    'version' => $claim->version,
                    'project' => $claim->project->name,
                    'vendor' => $claim->vendor->company_name ?: $claim->vendor->username,
                    'total_claim_amount' => $claim->total_claim_amount,
                    'total_actual_amount' => $claim->total_actual_amount,
                    'items' => $claim->items->map(fn ($item): array => [
                        'item_name' => $item->item_name,
                        'unit' => $item->unit,
                        'claimed_quantity' => $item->claimed_quantity,
                        'claimed_unit_price' => $item->claimed_unit_price,
                        'actual_quantity' => $item->actual_quantity,
                        'actual_unit_price' => $item->actual_unit_price,
                    ])->all(),
                    'attachments' => $claim->attachments->pluck('file_path')->all(),
                ],
            ]);

            $claim->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
            ]);
            ActivityLog::create([
                'claim_id' => $claim->id,
                'user_id' => $request->user()->id,
                'action' => 'rejected',
                'notes' => $data['rejection_reason'],
            ]);
        });

        $this->queueClaimNotification($claim->fresh(), 'rejected');

        return back()->with('status', 'Klaim berhasil ditolak.');
    }

    /**
     * Kirim email ke Admin, Owner, Vendor sesuai tahap verifikasi yang berubah.
     */
    private function queueClaimNotification(Claim $claim, string $event): void
    {
        $claim->loadMissing('vendor');
        $adminEmails = User::where('role', 'admin')->whereNotNull('email')->pluck('email')->filter()->unique()->values();
        $ownerEmails = User::where('role', 'owner')->whereNotNull('email')->pluck('email')->filter()->unique()->values();
        $vendorEmails = collect([$claim->vendor->email])->filter()->unique()->values();

        [$mail, $to, $cc, $missingRoles] = match ($event) {
            'submitted' => [
                new ClaimSubmittedMail($claim),
                $adminEmails,
                $vendorEmails,
                [
                    ...($adminEmails->isEmpty() ? ['admin'] : []),
                    ...($vendorEmails->isEmpty() ? ['vendor'] : []),
                ],
            ],
            'reviewed' => [
                new ClaimReviewedMail($claim),
                $ownerEmails,
                $vendorEmails,
                [
                    ...($ownerEmails->isEmpty() ? ['owner'] : []),
                    ...($vendorEmails->isEmpty() ? ['vendor'] : []),
                ],
            ],
            'approved' => [
                new ClaimApprovedMail($claim),
                $vendorEmails,
                collect(),
                $vendorEmails->isEmpty() ? ['vendor'] : [],
            ],
            'rejected' => [
                new ClaimRejectedMail($claim),
                $vendorEmails,
                $adminEmails,
                [
                    ...($vendorEmails->isEmpty() ? ['vendor'] : []),
                    ...($adminEmails->isEmpty() ? ['admin'] : []),
                ],
            ],
            default => throw new \InvalidArgumentException('Jenis notifikasi klaim tidak dikenal.'),
        };

        if ($missingRoles !== []) {
            Log::warning('Alamat email penerima notifikasi klaim belum diatur.', [
                'claim_id' => $claim->id,
                'event' => $event,
                'missing_roles' => $missingRoles,
            ]);
        }

        if ($to->isEmpty()) {
            Log::warning('Notifikasi klaim tidak dimasukkan ke antrean karena penerima utama tidak memiliki email.', [
                'claim_id' => $claim->id,
                'event' => $event,
            ]);

            return;
        }

        $pendingMail = Mail::to($to->all());
        $cc = $cc->diff($to)->values();

        if ($cc->isNotEmpty()) {
            $pendingMail->cc($cc->all());
        }

        $pendingMail->queue($mail);
    }

    private function authorizeClaimView(Request $request, Claim $claim): void
    {
        $user = $request->user();

        if ($user->role === 'vendor') {
            abort_unless($claim->vendor_id === $user->id, 403);
        }

        if ($user->role === 'owner') {
            abort_unless($claim->status !== 'submitted', 403);
        }
    }
}
