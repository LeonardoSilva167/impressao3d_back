<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projetos_impressao_parte_itens')) {
            return;
        }

        if (Schema::hasColumn('projetos_impressao_parte_itens', 'nome_item')) {
            return;
        }

        Schema::table('projetos_impressao_parte_itens', function (Blueprint $table) {
            $table->string('nome_item')->after('id_cor');
        });

        DB::table('projetos_impressao_parte_itens as ent')
            ->join('projetos_impressao_partes as parte', 'parte.id', '=', 'ent.id_projeto_impressao_parte')
            ->whereNull('ent.deleted_at')
            ->update([
                'ent.nome_item' => DB::raw('parte.nome_parte'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('projetos_impressao_parte_itens', 'nome_item')) {
            Schema::table('projetos_impressao_parte_itens', function (Blueprint $table) {
                $table->dropColumn('nome_item');
            });
        }
    }
};
