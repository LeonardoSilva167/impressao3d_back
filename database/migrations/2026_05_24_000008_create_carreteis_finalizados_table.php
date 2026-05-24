<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreteis_finalizados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_item');
            $table->unsignedSmallInteger('gramatura');
            $table->unsignedInteger('quantidade');
            $table->decimal('qtd_total_consumida', 15, 4);
            $table->text('observacao')->nullable();
            $table->dateTime('data_finalizacao');
            $table->timestamps();

            $table->foreign('id_item')->references('id')->on('itens');
            $table->index(['id_item', 'data_finalizacao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreteis_finalizados');
    }
};
