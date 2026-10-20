<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->after('username')->index();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('claim_number');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });
    }
};
