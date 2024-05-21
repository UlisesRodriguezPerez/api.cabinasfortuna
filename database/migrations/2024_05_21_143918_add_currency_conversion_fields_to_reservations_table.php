<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyConversionFieldsToReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('amountCRCToUSD', 10, 2)->nullable();
            $table->decimal('amountUSDToCRC', 10, 2)->nullable();
            $table->decimal('CHANGE_DOLLAR_TO_COLON', 10, 2)->default(530);
            $table->decimal('CHANGE_COLON_TO_DOLLAR', 10, 2)->default(500);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('amountCRCToUSD');
            $table->dropColumn('amountUSDToCRC');
            $table->dropColumn('CHANGE_DOLLAR_TO_COLON');
            $table->dropColumn('CHANGE_COLON_TO_DOLLAR');
        });
    }
}
