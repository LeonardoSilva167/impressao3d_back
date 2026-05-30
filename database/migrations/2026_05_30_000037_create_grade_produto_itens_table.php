<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_produto_itens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_grade_produto');
            $table->string('nome_produto', 255);
            $table->string('sku', 255);
            $table->decimal('peso_total', 10, 2)->default(0);
            $table->string('tempo_total', 8)->default('00:00');
            $table->decimal('custo_total', 15, 4)->default(0);
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_grade_produto', 'gpi_grade_produto_fk')
                ->references('id')
                ->on('grades_produtos');

            $table->index('id_grade_produto', 'gpi_grade_produto_idx');
            $table->index('sku', 'gpi_sku_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_produto_itens');
    }
};
