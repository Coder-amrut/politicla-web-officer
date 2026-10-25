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
        Schema::table('admin_media_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_media_documents', 'file_base64')) {
                $table->longText('file_base64')->nullable()->after('file_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_media_documents', function (Blueprint $table) {
            if (Schema::hasColumn('admin_media_documents', 'file_base64')) {
                $table->dropColumn('file_base64');
            }
        });
    }
};
