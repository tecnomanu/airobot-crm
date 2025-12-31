<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Users can have different roles and optionally be sellers.
     * - role: admin (root access), supervisor (manage sellers), user (basic access)
     * - is_seller: flag to indicate if user can receive lead assignments
     * - client_id: optional association with a client (null = root/global user)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email');
            $table->boolean('is_seller')->default(false)->after('role');
            $table->uuid('client_id')->nullable()->after('is_seller');

            // Foreign key to clients table
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();

            // Index for common queries
            $table->index(['client_id', 'is_seller']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropIndex(['client_id', 'is_seller']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_seller', 'client_id']);
        });
    }
};

