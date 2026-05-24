<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_itens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_compra');
            $table->unsignedBigInteger('id_item');
            $table->decimal('qtd', 15, 2);
            $table->decimal('valor_unitario', 15, 2);
            $table->decimal('valor_total', 15, 2);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_compra')->references('id')->on('compras');
            $table->foreign('id_item')->references('id')->on('itens');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_itens');
    }
};
