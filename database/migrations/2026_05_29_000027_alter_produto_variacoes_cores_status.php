<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('produto_variacoes')->delete();

        Schema::table('produto_variacoes', function (Blueprint $table) {
            $table->dropForeign(['id_cor']);
            $table->dropForeign(['id_parte_base']);
        });

        Schema::table('produto_variacoes', function (Blueprint $table) {
            $table->dropColumn(['id_cor', 'id_parte_base']);
        });

        Schema::table('produto_variacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cor_primaria')->after('id_produto_base');
            $table->unsignedBigInteger('id_cor_secundaria')->nullable()->after('id_cor_primaria');
            $table->unsignedBigInteger('id_cor_terciaria')->nullable()->after('id_cor_secundaria');
            $table->string('status', 20)->default('ATIVA')->after('sku');

            $table->foreign('id_cor_primaria')->references('id')->on('cores');
            $table->foreign('id_cor_secundaria')->references('id')->on('cores');
            $table->foreign('id_cor_terciaria')->references('id')->on('cores');

            $table->unique(
                ['id_produto_base', 'id_cor_primaria', 'id_cor_secundaria', 'id_cor_terciaria'],
                'produto_variacoes_combinacao_unica'
            );
        });
    }

    public function down(): void
    {
        Schema::table('produto_variacoes', function (Blueprint $table) {
            $table->dropUnique('produto_variacoes_combinacao_unica');
            $table->dropForeign(['id_cor_primaria']);
            $table->dropForeign(['id_cor_secundaria']);
            $table->dropForeign(['id_cor_terciaria']);
            $table->dropColumn(['id_cor_primaria', 'id_cor_secundaria', 'id_cor_terciaria', 'status']);
        });

        Schema::table('produto_variacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cor')->after('id_produto_base');
            $table->unsignedBigInteger('id_parte_base')->after('id_cor');

            $table->foreign('id_cor')->references('id')->on('cores');
            $table->foreign('id_parte_base')->references('id')->on('partes_base');
        });
    }
};
