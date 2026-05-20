<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licitacoes', function (Blueprint $table) {
            
            $table->id();            
            $table->unsignedBigInteger('unidade_compradoras_id')->nullable();
            $table->unsignedBigInteger('status_licitacoes_id')->nullable();
            $table->unsignedBigInteger('status_compra_id')->nullable();
            $table->unsignedBigInteger('status_cotacao_id')->nullable();
            $table->unsignedBigInteger('modalidade_id')->nullable();
            $table->integer('num_compra');
            $table->integer('exercicio');
            $table->datetime('data_limite_proposta');
            $table->text('link_pcnp')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('unidade_compradoras_id')->references('id')->on('unidade_compradoras');
            $table->foreign('status_licitacoes_id')->references('id')->on('status_licitacoes');
            $table->foreign('status_compra_id')->references('id')->on('status_compras');
            $table->foreign('status_cotacao_id')->references('id')->on('status_cotacoes');
            $table->foreign('modalidade_id')->references('id')->on('modalidades');
        }); 

        Schema::create('licitacao_itens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('licitacao_id'); // relacionamento
                $table->unsignedBigInteger('status_itens_id')->nullable();
                $table->unsignedBigInteger('status_classificacoes_id')->nullable();
                $table->integer('num_item');
                $table->text('descricao');
                $table->integer('qtd');
                $table->decimal('preco_teto', 10, 2);
                $table->decimal('total_teto', 10, 2);
                $table->decimal('valor_lance', 10, 2)->nullable();
                $table->decimal('total_lance', 10, 2)->nullable();
                $table->decimal('valor_negociado', 10, 2)->nullable();
                $table->decimal('total_negociado', 10, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();

    
                $table->foreign('licitacao_id')->references('id')->on('licitacoes')->onDelete('cascade');
                $table->foreign('status_itens_id')->references('id')->on('status_itens')->onDelete('cascade');
                $table->foreign('status_classificacoes_id')->references('id')->on('status_classificacoes')->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitacao_itens');
        Schema::dropIfExists('licitacoes');
    }
};
