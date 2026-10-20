<?php

namespace Tests\Feature;

use App\Mail\ClaimApprovedMail;
use App\Mail\ClaimRejectedMail;
use App\Mail\ClaimReviewedMail;
use App\Mail\ClaimSubmittedMail;
use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\ClaimAttachment;
use App\Models\ClaimComment;
use App\Models\ClaimRevision;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_registration_waits_for_admin_approval_before_login(): void
    {
        $this->post(route('register.store'), [
            'username' => 'vendor-baru',
            'company_name' => 'Perusahaan Baru',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'username' => 'vendor-baru',
            'role' => 'vendor',
            'status' => 'pending',
        ]);

        $this->post(route('login.store'), [
            'username' => 'vendor-baru',
            'password' => 'password-aman',
        ])->assertSessionHasErrors([
            'username' => 'Akun Anda sedang ditinjau Admin',
        ]);
    }

    public function test_demo_seeder_creates_active_role_accounts_and_example_project(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['username' => 'owner', 'role' => 'owner', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'admin', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['username' => 'vendor', 'role' => 'vendor', 'status' => 'active']);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('claims', 1);
        $this->assertDatabaseHas('projects', ['name' => 'Penerangan Area Kerja PT Jaya']);
        $this->assertDatabaseHas('claims', [
            'claim_number' => 'CLM-DEMO-20261020',
            'vendor_id' => User::where('username', 'vendor')->value('id'),
            'version' => 1,
            'status' => 'submitted',
        ]);
    }

    public function test_claim_can_be_submitted_reviewed_and_rejected_with_reason(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $admin = $this->makeUser('admin', 'active');
        $owner = $this->makeUser('owner', 'active');
        $project = Project::create(['name' => 'Proyek Uji']);

        $this->actingAs($vendor)->post(route('claims.store'), [
            'create_project' => '0',
            'project_id' => $project->id,
            'items' => [
                ['item_name' => 'Lampu LED', 'unit' => 'unit', 'claimed_quantity' => 4, 'claimed_unit_price' => 25000],
                [
                    'item_name' => 'Kabel',
                    'unit' => 'meter',
                    'claimed_quantity' => 10,
                    'claimed_unit_price' => 5000,
                    'actual_quantity' => 999,
                    'actual_unit_price' => 1,
                ],
            ],
        ])->assertRedirect();

        $claim = Claim::firstOrFail();
        $this->assertSame('submitted', $claim->status);
        $this->assertSame('150000.00', $claim->total_claim_amount);
        $this->assertNull($claim->items[1]->actual_quantity);
        $this->assertNull($claim->items[1]->actual_unit_price);
        $this->assertDatabaseHas('activity_logs', ['claim_id' => $claim->id, 'action' => 'submitted']);

        // Browser mengirim nilai hidden input sebagai string.
        $this->actingAs($admin)->put(route('claims.review.update', $claim), [
            'items' => [
                ['id' => (string) $claim->items[0]->id, 'actual_quantity' => 3, 'actual_unit_price' => 24000],
                ['id' => (string) $claim->items[1]->id, 'actual_quantity' => 10, 'actual_unit_price' => 4500],
            ],
        ])->assertRedirect(route('claims.show', $claim));

        $claim->refresh();
        $this->assertSame('reviewed', $claim->status);
        $this->assertSame('117000.00', $claim->total_actual_amount);

        $this->actingAs($owner)->get(route('claims.show', $claim))->assertOk();
        $this->actingAs($owner)->patch(route('claims.reject', $claim), [])
            ->assertSessionHasErrors('rejection_reason');

        $this->patch(route('claims.reject', $claim), [
            'rejection_reason' => 'Kuantitas aktual belum sesuai dokumen pendukung.',
        ])->assertRedirect();

        $claim->refresh();
        $this->assertSame('rejected', $claim->status);
        $this->assertSame('Kuantitas aktual belum sesuai dokumen pendukung.', $claim->rejection_reason);
        $this->assertSame(3, ActivityLog::where('claim_id', $claim->id)->count());
        $revision = ClaimRevision::where('claim_id', $claim->id)->firstOrFail();
        $this->assertSame(1, $revision->version);
        $this->assertSame('reviewed', $revision->status_previous);
        $this->assertSame('Lampu LED', $revision->data_snapshot['items'][0]['item_name']);
        $this->assertSame('Kuantitas aktual belum sesuai dokumen pendukung.', $revision->rejection_reason);

        $this->actingAs($vendor)
            ->get(route('claims.revise', $claim))
            ->assertOk()
            ->assertSee('Revisi klaim');
        $this->put(route('claims.revise.store', $claim), [
            'items' => [
                ['item_name' => 'Lampu LED revisi', 'unit' => 'unit', 'claimed_quantity' => 3, 'claimed_unit_price' => 24000],
            ],
        ])->assertRedirect(route('claims.show', $claim));

        $claim->refresh();
        $this->assertSame(2, $claim->version);
        $this->assertSame('submitted', $claim->status);
        $this->assertSame('72000.00', $claim->total_claim_amount);
        $this->assertSame('0.00', $claim->total_actual_amount);
        $this->assertNull($claim->rejection_reason);
        $this->assertSame(1, ClaimRevision::where('claim_id', $claim->id)->count());
        $this->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Riwayat Revisi')
            ->assertSee('Lampu LED')
            ->assertSee('Kuantitas aktual belum sesuai dokumen pendukung.');
    }

    public function test_claim_status_notifications_are_queued_to_the_requested_recipients(): void
    {
        Mail::fake();

        $vendor = $this->makeUser('vendor', 'active');
        $vendor->update(['email' => 'vendor@tracking.test']);
        $admin = $this->makeUser('admin', 'active');
        $admin->update(['email' => 'admin@tracking.test']);
        $owner = $this->makeUser('owner', 'active');
        $owner->update(['email' => 'owner@tracking.test']);
        $project = Project::create(['name' => 'Proyek Notifikasi']);

        $this->actingAs($vendor)->post(route('claims.store'), [
            'create_project' => '0',
            'project_id' => $project->id,
            'items' => [
                ['item_name' => 'Panel lampu', 'unit' => 'unit', 'claimed_quantity' => 2, 'claimed_unit_price' => 100000],
            ],
        ])->assertRedirect();

        $claim = Claim::where('vendor_id', $vendor->id)->firstOrFail();
        Mail::assertQueued(ClaimSubmittedMail::class, fn (ClaimSubmittedMail $mail): bool => $mail->hasTo('admin@tracking.test')
            && $mail->hasCc('vendor@tracking.test'));

        $this->actingAs($admin)->put(route('claims.review.update', $claim), [
            'items' => [
                ['id' => (string) $claim->items[0]->id, 'actual_quantity' => 2, 'actual_unit_price' => 90000],
            ],
        ])->assertRedirect();
        Mail::assertQueued(ClaimReviewedMail::class, fn (ClaimReviewedMail $mail): bool => $mail->hasTo('owner@tracking.test')
            && $mail->hasCc('vendor@tracking.test'));

        $this->actingAs($owner)->patch(route('claims.approve', $claim))->assertRedirect();
        Mail::assertQueued(ClaimApprovedMail::class, fn (ClaimApprovedMail $mail): bool => $mail->hasTo('vendor@tracking.test')
            && ! $mail->hasCc('admin@tracking.test'));

        $secondClaim = $this->makeClaim($vendor, $project, 'reviewed', 'CLM-NOTIF-REJECT');
        $this->patch(route('claims.reject', $secondClaim), ['rejection_reason' => 'Perlu dokumen pendukung.'])->assertRedirect();
        Mail::assertQueued(ClaimRejectedMail::class, fn (ClaimRejectedMail $mail): bool => $mail->hasTo('vendor@tracking.test')
            && $mail->hasCc('admin@tracking.test'));
    }

    public function test_claim_pdf_can_be_downloaded_by_an_authorized_vendor(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek PDF']),
            'submitted',
            'CLM-PDF-1'
        );

        $this->actingAs($vendor)
            ->get(route('claims.pdf', $claim))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=CLM-PDF-1.pdf');
    }

    public function test_claim_pdf_signatures_follow_the_current_workflow_stage(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $admin = $this->makeUser('admin', 'active');
        $owner = $this->makeUser('owner', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek Tanda Tangan']),
            'submitted',
            'CLM-PDF-SIGN'
        );
        $renderedViews = [];
        $pdf = \Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->times(3)->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('download')->times(3)->with('CLM-PDF-SIGN.pdf')->andReturn(response('PDF'));

        Pdf::shouldReceive('loadView')
            ->times(3)
            ->with('claims.pdf', \Mockery::on(function (array $data) use (&$renderedViews): bool {
                $renderedViews[] = view('claims.pdf', $data)->render();

                return true;
            }))
            ->andReturn($pdf);

        $this->actingAs($vendor);

        $this->get(route('claims.pdf', $claim))->assertOk();

        $claim->update(['status' => 'reviewed']);
        ActivityLog::create([
            'claim_id' => $claim->id,
            'user_id' => $admin->id,
            'action' => 'reviewed',
            'notes' => 'Admin menyelesaikan verifikasi.',
        ]);
        $this->get(route('claims.pdf', $claim))->assertOk();

        $claim->update(['status' => 'approved']);
        ActivityLog::create([
            'claim_id' => $claim->id,
            'user_id' => $owner->id,
            'action' => 'approved',
            'notes' => 'Owner menyetujui klaim.',
        ]);
        $this->get(route('claims.pdf', $claim))->assertOk();

        $this->assertStringContainsString('Diajukan secara elektronik', $renderedViews[0]);
        $this->assertStringNotContainsString('Diverifikasi secara elektronik', $renderedViews[0]);
        $this->assertStringNotContainsString('Disetujui secara elektronik', $renderedViews[0]);

        $this->assertStringContainsString('Diajukan secara elektronik', $renderedViews[1]);
        $this->assertStringContainsString('Diverifikasi secara elektronik', $renderedViews[1]);
        $this->assertStringNotContainsString('Disetujui secara elektronik', $renderedViews[1]);

        $this->assertStringContainsString('Diajukan secara elektronik', $renderedViews[2]);
        $this->assertStringContainsString('Diverifikasi secara elektronik', $renderedViews[2]);
        $this->assertStringContainsString('Disetujui secara elektronik', $renderedViews[2]);
    }

    public function test_vendor_can_create_a_shared_project_while_submitting_a_claim(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $anotherVendor = $this->makeUser('vendor', 'active');

        $this->actingAs($vendor)
            ->get(route('claims.create'))
            ->assertOk()
            ->assertSee('Buat proyek baru')
            ->assertSee('Belum ada proyek.');

        $this->post(route('claims.store'), [
            'create_project' => '1',
            'new_project_name' => 'Renovasi Gudang Bersama',
            'new_project_description' => 'Pekerjaan renovasi area gudang.',
            'items' => [
                ['item_name' => 'Pintu besi', 'unit' => 'unit', 'claimed_quantity' => 2, 'claimed_unit_price' => 1500000],
            ],
        ])->assertRedirect();

        $project = Project::where('name', 'Renovasi Gudang Bersama')->firstOrFail();
        $claim = Claim::where('project_id', $project->id)->firstOrFail();

        $this->assertSame($vendor->id, $claim->vendor_id);
        $this->assertSame('3000000.00', $claim->total_claim_amount);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'description' => 'Pekerjaan renovasi area gudang.',
        ]);

        $this->actingAs($anotherVendor)
            ->get(route('claims.create'))
            ->assertOk()
            ->assertSee('Renovasi Gudang Bersama');
    }

    public function test_admin_can_filter_claims_by_vendor_status_and_date(): void
    {
        $admin = $this->makeUser('admin', 'active');
        $vendorOne = $this->makeUser('vendor', 'active', 'vendor-satu');
        $vendorOne->update(['company_name' => 'PT Cakrawala']);
        $vendorTwo = $this->makeUser('vendor', 'active', 'vendor-dua');
        $vendorTwo->update(['company_name' => 'PT Nusantara']);
        $project = Project::create(['name' => 'Proyek Filter']);

        $matchingClaim = $this->makeClaim($vendorOne, $project, 'reviewed', 'CLM-FILTER-1');
        $matchingClaim->forceFill(['created_at' => '2026-10-15 10:00:00'])->save();
        $otherClaim = $this->makeClaim($vendorTwo, $project, 'submitted', 'CLM-FILTER-2');
        $otherClaim->forceFill(['created_at' => '2026-10-18 10:00:00'])->save();

        $this->actingAs($admin)
            ->get(route('claims.index', [
                'vendor' => 'Cakrawala',
                'status' => 'reviewed',
                'date_from' => '2026-10-15',
                'date_to' => '2026-10-16',
            ]))
            ->assertOk()
            ->assertSee('CLM-FILTER-1')
            ->assertDontSee('CLM-FILTER-2')
            ->assertSee('Cakrawala');
    }

    public function test_admin_can_upload_multiple_images_and_vendor_can_view_them(): void
    {
        Storage::fake('public');

        $admin = $this->makeUser('admin', 'active');
        $vendor = $this->makeUser('vendor', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek Lampiran']),
            'submitted',
            'CLM-LAMPIRAN-1'
        );

        $response = $this->actingAs($admin)->put(route('claims.review.update', $claim), [
            'items' => [
                ['id' => (string) $claim->items[0]->id, 'actual_quantity' => 1, 'actual_unit_price' => 25000],
            ],
            'attachments' => [
                $this->fakePng('foto-lokasi.png'),
                $this->fakePng('foto-material.png'),
            ],
        ]);

        $response->assertRedirect(route('claims.show', $claim));
        $this->assertSame(2, ClaimAttachment::where('claim_id', $claim->id)->count());
        $this->assertDatabaseHas('claim_attachments', [
            'claim_id' => $claim->id,
            'uploaded_by' => $admin->id,
        ]);

        foreach (ClaimAttachment::where('claim_id', $claim->id)->get() as $attachment) {
            Storage::disk('public')->assertExists($attachment->file_path);
        }

        $this->actingAs($vendor)
            ->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSee('Foto bukti lapangan')
            ->assertSee('target="_blank"', false);

        $attachment = ClaimAttachment::where('claim_id', $claim->id)->firstOrFail();
        $this->get(route('claims.show', $claim))
            ->assertSee('/storage/'.$attachment->file_path, false);
    }

    public function test_review_rejects_non_images_and_images_over_five_megabytes(): void
    {
        Storage::fake('public');

        $admin = $this->makeUser('admin', 'active');
        $vendor = $this->makeUser('vendor', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek Validasi Lampiran']),
            'submitted',
            'CLM-LAMPIRAN-2'
        );
        $item = $claim->items[0];
        $validItems = [
            ['id' => (string) $item->id, 'actual_quantity' => 1, 'actual_unit_price' => 25000],
        ];

        $this->actingAs($admin)->put(route('claims.review.update', $claim), [
            'items' => $validItems,
            'attachments' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('attachments.0');

        $this->put(route('claims.review.update', $claim), [
            'items' => $validItems,
            'attachments' => [$this->fakePng('foto-besar.png', 5121 * 1024)],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertSame('submitted', $claim->fresh()->status);
        $this->assertDatabaseCount('claim_attachments', 0);
    }

    public function test_claim_participants_can_add_permanent_comments_in_order(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $admin = $this->makeUser('admin', 'active');
        $owner = $this->makeUser('owner', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek Diskusi']),
            'reviewed',
            'CLM-DISKUSI-1'
        );

        $this->actingAs($vendor)
            ->post(route('claims.comments.store', $claim), ['message' => 'Mohon periksa lampiran.'])
            ->assertRedirect(route('claims.show', $claim));
        $this->actingAs($admin)
            ->post(route('claims.comments.store', $claim), ['message' => 'Lampiran sedang diperiksa.'])
            ->assertRedirect(route('claims.show', $claim));
        $this->actingAs($owner)
            ->post(route('claims.comments.store', $claim), ['message' => 'Terima kasih atas informasinya.'])
            ->assertRedirect(route('claims.show', $claim));

        $this->assertSame(3, ClaimComment::where('claim_id', $claim->id)->count());
        $this->actingAs($vendor)
            ->get(route('claims.show', $claim))
            ->assertOk()
            ->assertSeeInOrder([
                'Mohon periksa lampiran.',
                'Lampiran sedang diperiksa.',
                'Terima kasih atas informasinya.',
            ])
            ->assertSee('Vendor')
            ->assertSee('Admin')
            ->assertSee('Owner');
    }

    public function test_comment_is_required_and_limited_to_one_thousand_characters(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $claim = $this->makeClaim(
            $vendor,
            Project::create(['name' => 'Proyek Validasi Komentar']),
            'submitted',
            'CLM-KOMENTAR-1'
        );

        $this->actingAs($vendor)
            ->post(route('claims.comments.store', $claim), ['message' => ''])
            ->assertSessionHasErrors('message');
        $this->post(route('claims.comments.store', $claim), ['message' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('claim_comments', 0);
    }

    public function test_vendor_cannot_view_another_vendors_claim_or_admin_pages(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $otherVendor = $this->makeUser('vendor', 'active', 'vendor-lain');
        $claim = Claim::create([
            'project_id' => Project::create(['name' => 'Proyek A'])->id,
            'vendor_id' => $otherVendor->id,
            'claim_number' => 'CLM-UJI-1',
            'status' => 'submitted',
            'total_claim_amount' => 0,
            'total_actual_amount' => 0,
        ]);

        $this->actingAs($vendor)->get(route('claims.show', $claim))->assertForbidden();
        $this->get(route('vendors.index'))->assertForbidden();
    }

    public function test_admin_can_approve_and_reject_pending_vendor_accounts(): void
    {
        $admin = $this->makeUser('admin', 'active');
        $approvedVendor = $this->makeUser('vendor', 'pending');
        $rejectedVendor = $this->makeUser('vendor', 'pending');

        $this->actingAs($admin)
            ->patch(route('vendors.approve', $approvedVendor))
            ->assertRedirect();
        $this->patch(route('vendors.reject', $rejectedVendor))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $approvedVendor->id, 'status' => 'active']);
        $this->assertDatabaseHas('users', ['id' => $rejectedVendor->id, 'status' => 'rejected']);
    }

    public function test_dashboards_and_role_pages_render_for_each_role(): void
    {
        $vendor = $this->makeUser('vendor', 'active');
        $admin = $this->makeUser('admin', 'active');
        $owner = $this->makeUser('owner', 'active');
        $project = Project::create(['name' => 'Proyek Tampilan']);

        $submittedClaim = $this->makeClaim($vendor, $project, 'submitted', 'CLM-TAMPIL-S');
        $reviewedClaim = $this->makeClaim($vendor, $project, 'reviewed', 'CLM-TAMPIL-R');

        $this->actingAs($vendor);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('claims.index'))->assertOk();
        $this->get(route('claims.create'))->assertOk();
        $this->get(route('claims.show', $submittedClaim))->assertOk();

        $this->actingAs($admin);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('vendors.index'))->assertOk();
        $this->get(route('claims.index'))->assertOk();
        $this->get(route('claims.show', $submittedClaim))->assertOk();
        $this->get(route('claims.review', $submittedClaim))->assertOk();

        $this->actingAs($owner);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('claims.index'))->assertOk();
        $this->get(route('claims.show', $reviewedClaim))->assertOk();
    }

    private function makeUser(string $role, string $status, ?string $username = null): User
    {
        static $sequence = 0;
        $sequence++;

        return User::create([
            'username' => $username ?? $role.'-'.$sequence,
            'password' => 'password-aman',
            'role' => $role,
            'status' => $status,
            'company_name' => $role === 'vendor' ? 'Perusahaan '.$sequence : null,
        ]);
    }

    private function makeClaim(User $vendor, Project $project, string $status, string $claimNumber): Claim
    {
        $claim = Claim::create([
            'project_id' => $project->id,
            'vendor_id' => $vendor->id,
            'claim_number' => $claimNumber,
            'status' => $status,
            'total_claim_amount' => 25000,
            'total_actual_amount' => $status === 'reviewed' ? 20000 : 0,
        ]);
        $claim->items()->create([
            'item_name' => 'Material uji',
            'unit' => 'unit',
            'claimed_quantity' => 1,
            'claimed_unit_price' => 25000,
            'actual_quantity' => $status === 'reviewed' ? 1 : null,
            'actual_unit_price' => $status === 'reviewed' ? 20000 : null,
        ]);

        return $claim;
    }

    private function fakePng(string $filename, int $paddingBytes = 0): UploadedFile
    {
        // Gunakan PNG kecil yang valid agar pengujian tidak bergantung pada ekstensi GD.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');

        return UploadedFile::fake()->createWithContent($filename, $png.str_repeat('a', $paddingBytes));
    }
}
