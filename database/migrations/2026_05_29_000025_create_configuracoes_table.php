<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('proximo_codigo_base')->default(1000);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('configuracoes')->insert([
            'proximo_codigo_base' => 1000,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
