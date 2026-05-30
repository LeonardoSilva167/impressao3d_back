<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_produto_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('id_grade_produto_combinacao')
                ->nullable()
                ->after('id_grade_produto');

            $table->foreign('id_grade_produto_combinacao', 'gpi_combinacao_fk')
                ->references('id')
                ->on('grade_produto_combinacoes');

            $table->index('id_grade_produto_combinacao', 'gpi_combinacao_idx');
        });
    }

    public function down(): void
    {
        Schema::table('grade_produto_itens', function (Blueprint $table) {
            $table->dropForeign('gpi_combinacao_fk');
            $table->dropIndex('gpi_combinacao_idx');
            $table->dropColumn('id_grade_produto_combinacao');
        });
    }
};
