<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos_impressao_cores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_projeto_impressao')
                ->constrained('projetos_impressao')
                ->cascadeOnDelete();

            $table->foreignId('id_cor')
                ->constrained('cores')
                ->restrictOnDelete();

            $table->decimal('peso_gramas', 10, 2);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id_projeto_impressao', 'id_cor'], 'projetos_impressao_cores_projeto_cor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos_impressao_cores');
    }
};
