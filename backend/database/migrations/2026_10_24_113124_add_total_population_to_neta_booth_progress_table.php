<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('neta_booth_progress')) {
            Schema::table('neta_booth_progress', function (Blueprint $table) {
                if (!Schema::hasColumn('neta_booth_progress', 'total_population')) {
                    $table->unsignedInteger('total_population')->default(1500)->after('total_voters');
                }
            });

            // Set accurate demographic population numbers for all seeded booths
            $villagePopulations = [
                'माधवनगर पूर्व' => 1750,
                'माधवनगर पश्चिम' => 1510,
                'वसंत कॉलनी प्रभाग ४' => 1920,
                'गावभाग प्रभाग ७' => 2030,
                'कुपवाड प्रभाग ११' => 2170,
                'गणपती पेठ व्यापारी पेठ' => 1820,
                'बुधगाव ग्रामपंचायत' => 1980,
                'विश्रामबाग प्रभाग २' => 2230,
                'बामणी ग्रामपंचायत' => 1370,
                'संजयनगर प्रभाग ९' => 2120,
                'मिरज पश्चिम प्रभाग' => 1780,
                'स्टेशन रोड प्रभाग ३' => 2060,
                'इस्लामपूर नगरपरिषद प्रभाग ५' => 1970,
                'उरुण ग्रामपंचायत' => 1710,
                'तासगाव शहर प्रभाग २' => 2180,
                'राजारामपुरी प्रभाग ८' => 2290,
                'उंब्रज प्रभाग १' => 2090,
            ];

            foreach ($villagePopulations as $village => $pop) {
                DB::table('neta_booth_progress')
                    ->where('village_mr', $village)
                    ->update(['total_population' => $pop]);
            }

            // Ensure any booths without explicit population have a realistic calculated population
            DB::statement('UPDATE neta_booth_progress SET total_population = ROUND(total_voters * 1.54) WHERE total_population IS NULL OR total_population = 1500');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('neta_booth_progress') && Schema::hasColumn('neta_booth_progress', 'total_population')) {
            Schema::table('neta_booth_progress', function (Blueprint $table) {
                $table->dropColumn('total_population');
            });
        }
    }
};
