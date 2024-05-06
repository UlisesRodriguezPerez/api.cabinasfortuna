<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phoneNumber');
            $table->date('date');
            $table->integer('adults');
            $table->integer('children');
            $table->integer('cabin');
            $table->integer('nights');
            $table->decimal('amountUSD', 8, 2)->nullable();
            $table->decimal('amountCRC', 8, 2)->nullable();
            $table->string('agency')->nullable();
            $table->decimal('commission', 8, 2)->nullable();
            $table->decimal('paidToUlisesUSD', 8, 2)->nullable();
            $table->decimal('paidToDeyaniraUSD', 8, 2)->nullable();
            $table->decimal('paidToUlisesCRC', 8, 2)->nullable();
            $table->decimal('paidToDeyaniraCRC', 8, 2)->nullable();
            $table->boolean('invoiceNeeded')->default(false);
            $table->boolean('paidToDeyanira')->default(false);
            $table->boolean('pendingToPay')->default(false);
            $table->decimal('pendingAmountUSD', 8, 2)->nullable();
            $table->decimal('pendingAmountCRC', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
