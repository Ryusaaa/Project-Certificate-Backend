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
        Schema::table('data_activity', function (Blueprint $table) {
            $table->unsignedBigInteger('sertifikat_id')->nullable()->after('instruktur_id');
            $table->foreign('sertifikat_id')->references('id')->on('sertifikats')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_activity', function (Blueprint $table) {
            $table->dropForeign(['sertifikat_id']);
            $table->dropColumn('sertifikat_id');
        });
    }
};
