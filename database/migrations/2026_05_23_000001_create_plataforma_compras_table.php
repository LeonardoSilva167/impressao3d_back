<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plataforma_compras', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 120);
            $table->string('url', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('plataforma_compras')->insert([
            ['descricao' => 'Shopee',        'url' => 'https://shopee.com.br',        'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'Mercado Livre', 'url' => 'https://mercadolivre.com.br',  'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'Amazon',        'url' => 'https://amazon.com.br',        'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'Voolt3D',      'url' => 'https://voolt3d.com.br',       'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'GTMax3D',       'url' => 'https://gtmax3d.com.br',       'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plataforma_compras');
    }
};
