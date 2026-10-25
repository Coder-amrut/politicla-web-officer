<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'geographic_scope')) {
                $table->string('geographic_scope', 30)->nullable();
            }
            if (! Schema::hasColumn('projects', 'geographic_code')) {
                $table->string('geographic_code', 100)->nullable();
            }
            if (! Schema::hasColumn('projects', 'geographic_name_en')) {
                $table->string('geographic_name_en', 255)->nullable();
            }
            if (! Schema::hasColumn('projects', 'geographic_name_mr')) {
                $table->string('geographic_name_mr', 255)->nullable();
            }
            if (! Schema::hasColumn('projects', 'geographic_context')) {
                $table->json('geographic_context')->nullable();
            }
        });

        Schema::table('districts', function (Blueprint $table) {
            if (! Schema::hasColumn('districts', 'district_name_mr')) {
                $table->string('district_name_mr', 150)->nullable();
            }
        });

        Schema::table('lok_sabha_vidhan_sabha', function (Blueprint $table) {
            if (! Schema::hasColumn('lok_sabha_vidhan_sabha', 'lok_sabha_name_mr')) {
                $table->string('lok_sabha_name_mr', 255)->nullable();
            }
            if (! Schema::hasColumn('lok_sabha_vidhan_sabha', 'vidhan_sabha_name_mr')) {
                $table->string('vidhan_sabha_name_mr', 255)->nullable();
            }
        });

        if (! Schema::hasTable('polling_stations')) {
            Schema::create('polling_stations', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('election_year');
                $table->string('district_code', 30);
                $table->unsignedSmallInteger('assembly_no');
                $table->string('part_no', 30);
                $table->string('name_en', 255);
                $table->string('name_mr', 255)->nullable();
                $table->timestamps();
                $table->unique(['election_year', 'assembly_no', 'part_no'], 'polling_station_election_assembly_part_unique');
                $table->index(['district_code', 'assembly_no'], 'polling_station_context_index');
            });
        }

        $this->importDistrictMarathiNames();
    }

    private function importDistrictMarathiNames(): void
    {
        $csvPath = base_path('../Work/06_Master Data/lgd_districts.csv');
        if (! is_file($csvPath) || ! ($handle = fopen($csvPath, 'r'))) {
            return;
        }

        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            [$code, $english, $marathi] = array_pad($row, 3, null);
            if ($code && $marathi) {
                DB::table('districts')
                    ->where('district_code', trim($code))
                    ->update(['district_name_mr' => trim($marathi)]);
            }
        }
        fclose($handle);
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_stations');

        Schema::table('lok_sabha_vidhan_sabha', function (Blueprint $table) {
            $table->dropColumn(['lok_sabha_name_mr', 'vidhan_sabha_name_mr']);
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('district_name_mr');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'geographic_scope', 'geographic_code', 'geographic_name_en',
                'geographic_name_mr', 'geographic_context',
            ]);
        });
    }
};
