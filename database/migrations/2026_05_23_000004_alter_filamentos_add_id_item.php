<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_item')->nullable()->after('id_marca');
        });

        $this->syncExistingFilamentosWithItens();

        DB::statement('ALTER TABLE filamentos MODIFY id_item BIGINT UNSIGNED NOT NULL');

        Schema::table('filamentos', function (Blueprint $table) {
            $table->foreign('id_item')->references('id')->on('itens');
            $table->unique('id_item', 'filamentos_id_item_unique');
        });
    }

    public function down(): void
    {
        Schema::table('filamentos', function (Blueprint $table) {
            $table->dropForeign(['id_item']);
            $table->dropUnique('filamentos_id_item_unique');
            $table->dropColumn('id_item');
        });
    }

    private function syncExistingFilamentosWithItens(): void
    {
        $idCategoriaFilamento = DB::table('categorias_itens')
            ->where('descricao', 'FILAMENTO')
            ->whereNull('deleted_at')
            ->value('id');

        if ($idCategoriaFilamento === null) {
            throw new RuntimeException('Categoria FILAMENTO não encontrada em categorias_itens.');
        }

        $filamentos = DB::table('filamentos')->orderBy('id')->get();
        $now        = now();

        foreach ($filamentos as $filamento) {
            $itemId = DB::table('itens')->insertGetId([
                'id_categoria_item' => $idCategoriaFilamento,
                'descricao'         => $filamento->resumo,
                'codigo'            => $filamento->codigo,
                'unidade_medida'    => 'g',
                'controla_estoque'  => true,
                'gera_custo'        => true,
                'ativo'             => true,
                'created_at'        => $filamento->created_at ?? $now,
                'updated_at'        => $filamento->updated_at ?? $now,
                'deleted_at'        => $filamento->deleted_at,
            ]);

            DB::table('filamentos')
                ->where('id', $filamento->id)
                ->update(['id_item' => $itemId]);
        }
    }
};
