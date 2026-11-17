<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // `address` stays as the street line (jalan, RT/RW, nomor).
            $table->string('province_code', 2)->nullable()->after('address');
            $table->string('regency_code', 5)->nullable()->after('province_code');
            $table->string('district_code', 8)->nullable()->after('regency_code');
            $table->string('village_code', 13)->nullable()->after('district_code');
            $table->string('postal_code', 5)->nullable()->after('village_code');

            $table->index('province_code');
            $table->index('regency_code');
            $table->index('district_code');
            $table->index('village_code');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['province_code']);
            $table->dropIndex(['regency_code']);
            $table->dropIndex(['district_code']);
            $table->dropIndex(['village_code']);
            $table->dropColumn(['province_code', 'regency_code', 'district_code', 'village_code', 'postal_code']);
        });
    }
};
