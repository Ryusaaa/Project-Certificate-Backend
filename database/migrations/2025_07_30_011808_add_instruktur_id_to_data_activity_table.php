<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('data_activity', function (Blueprint $table) {
            $table->foreignId('instruktur_id')->nullable()->constrained('instrukturs');
        });
    }

    public function down()
    {
        Schema::table('data_activity', function (Blueprint $table) {
            $table->dropForeign(['instruktur_id']);
            $table->dropColumn('instruktur_id');
        });
    }
};
