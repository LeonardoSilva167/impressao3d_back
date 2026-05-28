<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos_impressao_partes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_projeto_impressao')
                ->constrained('projetos_impressao')
                ->cascadeOnDelete();

            $table->string('nome_parte');
            $table->decimal('altura_camada', 5, 2)->default(0.20);
            $table->integer('temperatura_bico');
            $table->integer('temperatura_mesa');
            $table->string('tempo_impressao', 5);
            $table->decimal('peso_parte', 10, 2);
            $table->boolean('usa_suporte');
            $table->decimal('angulo_suporte', 5, 2)->nullable();
            $table->enum('tipo_suporte', ['ARVORE_PADRAO', 'ARVORE_FORTE'])->nullable();
            $table->decimal('distancia_z_inferior', 8, 2)->nullable();
            $table->integer('quantidade_voltas_suporte')->nullable();
            $table->boolean('usa_brim');
            $table->boolean('usa_engomagem');
            $table->decimal('velocidade_engomagem', 8, 2)->nullable();
            $table->decimal('fluxo_engomagem', 8, 2)->nullable();
            $table->integer('loops_parede')->default(2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos_impressao_partes');
    }
};
