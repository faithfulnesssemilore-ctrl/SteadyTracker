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
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['user_id', 'due_at'], 'activities_user_due_index');
            $table->index(['due_at', 'activity_status'], 'activities_due_status_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_owner_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_user_due_index');
            $table->dropIndex('activities_due_status_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_owner_created_index');
        });
    }
};
