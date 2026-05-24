<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_plataforma_compra');
            $table->date('data_compra');
            $table->string('numero_pedido', 100)->nullable();
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2);
            $table->text('observacao')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_plataforma_compra')->references('id')->on('plataforma_compras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
