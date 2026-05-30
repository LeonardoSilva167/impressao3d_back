<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partes_base', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 120);
            $table->string('codigo', 20);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('codigo', 'partes_base_codigo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partes_base');
    }
};
