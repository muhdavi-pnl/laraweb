<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indonesian administrative regions (Kemendagri codes): provinsi → kabupaten/kota → kecamatan → kelurahan/desa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name', 100)->index();
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->string('province_code', 2)->index();
            $table->string('name', 100)->index();

            $table->foreign('province_code')->references('code')->on('provinces')->cascadeOnDelete();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->string('code', 8)->primary();
            $table->string('regency_code', 5)->index();
            $table->string('name', 100)->index();

            $table->foreign('regency_code')->references('code')->on('regencies')->cascadeOnDelete();
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->string('code', 13)->primary();
            $table->string('district_code', 8)->index();
            $table->string('name', 100)->index();

            $table->foreign('district_code')->references('code')->on('districts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
