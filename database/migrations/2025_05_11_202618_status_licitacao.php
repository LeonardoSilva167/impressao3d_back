<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
          // Tabela: status_licitacoes
          Schema::create('status_licitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('status_licitacoes')->insert([
            ['nome' => 'Proposta', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Disputa', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Seleção de Fornecedores', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Finalizada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tabela: status_compra
        Schema::create('status_compras', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('status_compras')->insert([
            ['nome' => 'Compra Revogada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Compra Suspensa', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Compra Cancelada', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tabela: status_cotacao
        Schema::create('status_cotacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('status_cotacoes')->insert([
            ['nome' => 'Aguardando Cotação', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Cotação Realizada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Desistência', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tabela: status_item
        Schema::create('status_itens', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('status_itens')->insert([
            ['nome' => 'Proposta', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Aguardando Julgamento', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Desclassificada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Homologado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tabela: status_classificacao
        Schema::create('status_classificacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('status_classificacoes')->insert([
            ['nome' => 'Proposta', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Aguardando Julgamento', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Desclassificada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Aceita', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Aceita e Habilitada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Adjudicada', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Cancelado', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Revogado', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Fracassado', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Tabela: status_item
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('modalidades')->insert([
            ['nome' => 'Dispensa Eletrônica', 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Pregão Eletrônico', 'created_at' => now(), 'updated_at' => now()],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_classificacao');
        Schema::dropIfExists('status_item');
        Schema::dropIfExists('status_cotacao');
        Schema::dropIfExists('status_compra');
        Schema::dropIfExists('status_licitacao');
    }
};
