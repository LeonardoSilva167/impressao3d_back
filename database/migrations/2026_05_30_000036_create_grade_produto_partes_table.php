<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_produto_partes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_grade_produto');
            $table->unsignedBigInteger('id_parte_projeto');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_grade_produto', 'gpp_grade_produto_fk')
                ->references('id')
                ->on('grades_produtos');

            $table->foreign('id_parte_projeto', 'gpp_parte_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_partes');

            $table->unique(
                ['id_grade_produto', 'id_parte_projeto'],
                'gpp_grade_parte_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_produto_partes');
    }
};
