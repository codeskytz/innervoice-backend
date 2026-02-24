<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('email');
            $table->string('otp_code')->nullable()->after('is_verified');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->string('api_token', 80)->nullable()->unique()->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_verified', 'otp_code', 'otp_expires_at', 'api_token']);
        });
    }
};
