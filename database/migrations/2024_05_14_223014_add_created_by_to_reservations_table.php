<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('note'); // Asumiendo que 'note' es una de las últimas columnas
    
            // Opcional: agregar clave foránea si deseas asegurar la integridad referencial
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Opcional: eliminar la clave foránea
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
}
