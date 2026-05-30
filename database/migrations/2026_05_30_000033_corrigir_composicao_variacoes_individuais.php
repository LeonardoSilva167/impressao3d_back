<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('produto_variacao_filamentos');
        Schema::dropIfExists('produto_variacoes');
        Schema::dropIfExists('produto_composicao_cores');
        Schema::dropIfExists('produto_composicao_itens');

        Schema::create('produto_composicao_cores', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_composicao');
            $table->unsignedBigInteger('id_parte');
            $table->unsignedBigInteger('id_item_projeto');
            $table->unsignedBigInteger('id_cor');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_composicao', 'pcc_composicao_fk')
                ->references('id')
                ->on('produto_composicoes');

            $table->foreign('id_parte', 'pcc_parte_fk')
                ->references('id')
                ->on('projetos_impressao_partes');

            $table->foreign('id_item_projeto', 'pcc_item_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_parte_itens');

            $table->foreign('id_cor', 'pcc_cor_fk')
                ->references('id')
                ->on('cores');

            $table->unique(
                ['id_composicao', 'id_item_projeto', 'id_cor'],
                'pcc_composicao_item_cor_unico'
            );
        });

        Schema::create('produto_variacoes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_composicao');
            $table->unsignedBigInteger('id_parte');
            $table->unsignedBigInteger('id_item_projeto');
            $table->unsignedBigInteger('id_cor');
            $table->unsignedBigInteger('id_composicao_cor');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_composicao', 'pv_composicao_fk')
                ->references('id')
                ->on('produto_composicoes');

            $table->foreign('id_parte', 'pv_parte_fk')
                ->references('id')
                ->on('projetos_impressao_partes');

            $table->foreign('id_item_projeto', 'pv_item_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_parte_itens');

            $table->foreign('id_cor', 'pv_cor_fk')
                ->references('id')
                ->on('cores');

            $table->foreign('id_composicao_cor', 'pv_composicao_cor_fk')
                ->references('id')
                ->on('produto_composicao_cores');

            $table->unique(
                ['id_composicao', 'id_item_projeto', 'id_cor'],
                'pv_composicao_item_cor_unico'
            );
        });

        Schema::create('produto_variacao_filamentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_variacao');
            $table->unsignedBigInteger('id_filamento');
            $table->decimal('preco_medio_grama', 15, 4);
            $table->decimal('peso_item', 10, 2);
            $table->decimal('custo_item', 15, 4);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_variacao', 'pvf_variacao_fk')
                ->references('id')
                ->on('produto_variacoes');

            $table->foreign('id_filamento', 'pvf_filamento_fk')
                ->references('id')
                ->on('filamentos');

            $table->unique('id_variacao', 'pvf_variacao_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_variacao_filamentos');
        Schema::dropIfExists('produto_variacoes');
        Schema::dropIfExists('produto_composicao_cores');

        Schema::create('produto_composicao_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_composicao');
            $table->unsignedBigInteger('id_parte');
            $table->unsignedBigInteger('id_item_projeto');
            $table->unsignedSmallInteger('qtd_cor_primaria')->default(0);
            $table->unsignedSmallInteger('qtd_cor_secundaria')->default(0);
            $table->unsignedSmallInteger('qtd_cor_terciaria')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_composicao', 'pci_composicao_fk')
                ->references('id')
                ->on('produto_composicoes');
            $table->foreign('id_parte', 'pci_parte_fk')
                ->references('id')
                ->on('projetos_impressao_partes');
            $table->foreign('id_item_projeto', 'pci_item_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_parte_itens');
            $table->unique(['id_composicao', 'id_item_projeto'], 'pci_composicao_item_unico');
        });

        Schema::create('produto_composicao_cores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_composicao_item');
            $table->string('tipo_cor', 20);
            $table->unsignedBigInteger('id_cor');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_composicao_item', 'pcc_composicao_item_fk')
                ->references('id')
                ->on('produto_composicao_itens');
            $table->foreign('id_cor', 'pcc_cor_fk')
                ->references('id')
                ->on('cores');
            $table->unique(['id_composicao_item', 'tipo_cor', 'id_cor'], 'pcc_item_tipo_cor_unico');
        });

        Schema::create('produto_variacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_composicao');
            $table->unsignedBigInteger('id_composicao_item');
            $table->unsignedBigInteger('id_item_projeto');
            $table->string('tipo_cor', 20);
            $table->unsignedBigInteger('id_cor');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_composicao', 'pv_composicao_fk')
                ->references('id')
                ->on('produto_composicoes');
            $table->foreign('id_composicao_item', 'pv_composicao_item_fk')
                ->references('id')
                ->on('produto_composicao_itens');
            $table->foreign('id_item_projeto', 'pv_item_projeto_fk')
                ->references('id')
                ->on('projetos_impressao_parte_itens');
            $table->foreign('id_cor', 'pv_cor_fk')
                ->references('id')
                ->on('cores');
            $table->unique(
                ['id_composicao', 'id_item_projeto', 'tipo_cor', 'id_cor'],
                'pv_composicao_item_cor_unico'
            );
        });

        Schema::create('produto_variacao_filamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_variacao');
            $table->unsignedBigInteger('id_filamento');
            $table->decimal('preco_medio_grama', 15, 4);
            $table->decimal('peso_item', 10, 2);
            $table->decimal('custo_item', 15, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_variacao', 'pvf_variacao_fk')
                ->references('id')
                ->on('produto_variacoes');
            $table->foreign('id_filamento', 'pvf_filamento_fk')
                ->references('id')
                ->on('filamentos');
            $table->unique('id_variacao', 'pvf_variacao_unico');
        });
    }
};
