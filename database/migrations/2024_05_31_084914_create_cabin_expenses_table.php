<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCabinExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cabin_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('cabin_name');
            $table->decimal('cleaning_cost', 8, 2);
            $table->decimal('electricity_cost', 8, 2);
            $table->decimal('internet_cost', 8, 2);
            $table->decimal('extra_house_light_cost', 8, 2);
            $table->decimal('other_expenses', 8, 2);
            $table->date('month_year');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cabin_expenses');
    }
}
