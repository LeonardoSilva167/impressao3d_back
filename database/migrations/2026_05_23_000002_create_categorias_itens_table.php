<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_itens', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 120);

            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('categorias_itens')->insert([
            ['descricao' => 'FILAMENTO',        'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'EMBALAGEM',        'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'INSUMO_PRODUCAO',  'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'INSUMO_GERAL',     'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'FERRAMENTA',       'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'EQUIPAMENTO',      'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'INVESTIMENTO',     'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_itens');
    }
};
