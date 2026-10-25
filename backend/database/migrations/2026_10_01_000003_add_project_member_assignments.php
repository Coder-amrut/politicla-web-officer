<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_project_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('user_project_assignments', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable();
            }
            if (! Schema::hasColumn('user_project_assignments', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (! Schema::hasColumn('user_project_assignments', 'assigned_by')) {
                $table->unsignedBigInteger('assigned_by')->nullable();
            }
            if (! Schema::hasColumn('user_project_assignments', 'created_at')) {
                $table->timestamps();
            }
        });

        // Keep this migration compatible with databases that already have the
        // assignment columns from the reference production schema.
        if (! $this->hasIndex('user_project_assignments', 'user_project_assignments_project_user_unique')) {
            Schema::table('user_project_assignments', function (Blueprint $table) {
                $table->unique(['project_id', 'user_id'], 'user_project_assignments_project_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_project_assignments', function (Blueprint $table) {
            $table->dropUnique('user_project_assignments_project_user_unique');
            foreach (['project_id', 'user_id', 'assigned_by', 'created_at', 'updated_at'] as $column) {
                if (Schema::hasColumn('user_project_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $definition) => $definition['name'] === $index);
    }
};
