<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_produto_combinacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_grade_produto');
            $table->string('descricao', 120);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_grade_produto', 'gpc_grade_produto_fk')
                ->references('id')
                ->on('grades_produtos');

            $table->index('id_grade_produto', 'gpc_grade_produto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_produto_combinacoes');
    }
};
