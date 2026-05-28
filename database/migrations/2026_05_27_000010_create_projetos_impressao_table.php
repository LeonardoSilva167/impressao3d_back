<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos_impressao', function (Blueprint $table) {
            $table->id();

            $table->text('url_projeto');
            $table->string('nome_original_projeto');
            $table->string('codigo_projeto')->unique();
            $table->text('descricao_projeto');
            $table->enum('bico_padrao', ['0.2', '0.4', '0.6', '0.8'])->default('0.4');
            $table->decimal('tempo_total_horas', 8, 2);
            $table->decimal('peso_total_gramas', 10, 2);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos_impressao');
    }
};
