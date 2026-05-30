<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos_impressao_parte_itens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_projeto_impressao_parte');
            $table->unsignedBigInteger('id_cor');
            $table->string('nome_item');

            $table->decimal('altura_camada', 5, 2)->default(0.20);
            $table->integer('temperatura_bico')->default(210);
            $table->integer('temperatura_mesa')->default(75);
            $table->integer('loops_parede')->default(2);
            $table->string('tempo_impressao', 5);

            $table->decimal('peso_parte', 10, 2);
            $table->decimal('peso_suporte', 10, 2)->default(0);
            $table->decimal('peso_corado', 10, 2)->default(0);
            $table->decimal('peso_torre', 10, 2)->default(0);

            $table->boolean('usa_suporte');
            $table->decimal('angulo_suporte', 5, 2)->nullable();
            $table->enum('tipo_suporte', ['ARVORE_PADRAO', 'ARVORE_FORTE', 'NORMAL'])->nullable();
            $table->decimal('distancia_z_inferior', 8, 2)->nullable();
            $table->integer('quantidade_voltas_suporte')->nullable();

            $table->boolean('usa_brim');
            $table->boolean('usa_engomagem');
            $table->decimal('velocidade_engomagem', 8, 2)->nullable();
            $table->decimal('fluxo_engomagem', 8, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_projeto_impressao_parte', 'pi_itens_parte_fk')
                ->references('id')
                ->on('projetos_impressao_partes')
                ->cascadeOnDelete();

            $table->foreign('id_cor', 'pi_itens_cor_fk')
                ->references('id')
                ->on('cores')
                ->restrictOnDelete();
        });

        if (Schema::hasColumn('projetos_impressao_partes', 'altura_camada')) {
            $this->migrarPartesParaItens();
            $this->removerColunasParte();
        }

        if (Schema::hasTable('projetos_impressao_cores')) {
            Schema::dropIfExists('projetos_impressao_cores');
        }

        Schema::table('projetos_impressao', function (Blueprint $table) {
            $colunas = ['bico_padrao', 'tempo_total_horas', 'peso_total_gramas', 'custo_estimado_projeto'];

            foreach ($colunas as $coluna) {
                if (Schema::hasColumn('projetos_impressao', $coluna)) {
                    $table->dropColumn($coluna);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('projetos_impressao', function (Blueprint $table) {
            if (!Schema::hasColumn('projetos_impressao', 'bico_padrao')) {
                $table->enum('bico_padrao', ['0.2', '0.4', '0.6', '0.8'])->default('0.4')->after('descricao_projeto');
            }
            if (!Schema::hasColumn('projetos_impressao', 'tempo_total_horas')) {
                $table->string('tempo_total_horas', 5)->default('00:00')->after('bico_padrao');
            }
            if (!Schema::hasColumn('projetos_impressao', 'peso_total_gramas')) {
                $table->decimal('peso_total_gramas', 10, 2)->default(0)->after('tempo_total_horas');
            }
            if (!Schema::hasColumn('projetos_impressao', 'custo_estimado_projeto')) {
                $table->decimal('custo_estimado_projeto', 15, 4)->nullable()->after('peso_total_gramas');
            }
        });

        Schema::create('projetos_impressao_cores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_projeto_impressao')->constrained('projetos_impressao')->cascadeOnDelete();
            $table->foreignId('id_cor')->constrained('cores')->restrictOnDelete();
            $table->foreignId('id_filamento')->constrained('filamentos')->restrictOnDelete();
            $table->decimal('peso_gramas', 10, 2);
            $table->decimal('preco_medio_grama', 15, 4)->nullable();
            $table->decimal('custo_estimado', 15, 4)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('projetos_impressao_parte_itens');

        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            if (!Schema::hasColumn('projetos_impressao_partes', 'altura_camada')) {
                $table->decimal('altura_camada', 5, 2)->default(0.20)->after('nome_parte');
                $table->integer('temperatura_bico')->default(210);
                $table->integer('temperatura_mesa')->default(75);
                $table->string('tempo_impressao', 5);
                $table->decimal('peso_parte', 10, 2);
                $table->decimal('peso_suporte', 10, 2)->default(0);
                $table->decimal('peso_corado', 10, 2)->default(0);
                $table->decimal('peso_torre', 10, 2)->default(0);
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
            }
        });
    }

    private function migrarPartesParaItens(): void
    {
        $idCorPadrao = DB::table('cores')->whereNull('deleted_at')->value('id');

        if (!$idCorPadrao) {
            return;
        }

        $partes = DB::table('projetos_impressao_partes')
            ->whereNull('deleted_at')
            ->get();

        foreach ($partes as $parte) {
            $idCor = DB::table('projetos_impressao_cores')
                ->where('id_projeto_impressao', $parte->id_projeto_impressao)
                ->whereNull('deleted_at')
                ->value('id_cor') ?? $idCorPadrao;

            DB::table('projetos_impressao_parte_itens')->insert([
                'id_projeto_impressao_parte' => $parte->id,
                'nome_item'                  => $parte->nome_parte,
                'id_cor'                     => $idCor,
                'altura_camada'              => $parte->altura_camada ?? 0.20,
                'temperatura_bico'           => $parte->temperatura_bico ?? 210,
                'temperatura_mesa'           => $parte->temperatura_mesa ?? 75,
                'loops_parede'               => $parte->loops_parede ?? 2,
                'tempo_impressao'            => $parte->tempo_impressao ?? '00:01',
                'peso_parte'                 => $parte->peso_parte ?? 0.01,
                'peso_suporte'               => $parte->peso_suporte ?? 0,
                'peso_corado'                => $parte->peso_corado ?? 0,
                'peso_torre'                 => $parte->peso_torre ?? 0,
                'usa_suporte'                => $parte->usa_suporte ?? false,
                'angulo_suporte'             => $parte->angulo_suporte,
                'tipo_suporte'               => $parte->tipo_suporte,
                'distancia_z_inferior'       => $parte->distancia_z_inferior,
                'quantidade_voltas_suporte'  => $parte->quantidade_voltas_suporte,
                'usa_brim'                   => $parte->usa_brim ?? false,
                'usa_engomagem'              => $parte->usa_engomagem ?? false,
                'velocidade_engomagem'       => $parte->velocidade_engomagem,
                'fluxo_engomagem'            => $parte->fluxo_engomagem,
                'created_at'                 => $parte->created_at ?? now(),
                'updated_at'                 => $parte->updated_at ?? now(),
            ]);
        }
    }

    private function removerColunasParte(): void
    {
        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            $table->dropColumn([
                'altura_camada',
                'temperatura_bico',
                'temperatura_mesa',
                'tempo_impressao',
                'peso_parte',
                'peso_suporte',
                'peso_corado',
                'peso_torre',
                'usa_suporte',
                'angulo_suporte',
                'tipo_suporte',
                'distancia_z_inferior',
                'quantidade_voltas_suporte',
                'usa_brim',
                'usa_engomagem',
                'velocidade_engomagem',
                'fluxo_engomagem',
                'loops_parede',
            ]);
        });
    }
};
