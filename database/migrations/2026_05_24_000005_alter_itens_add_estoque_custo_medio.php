<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens', function (Blueprint $table) {
            $table->decimal('estoque', 15, 4)->default(0)->after('unidade_medida');
            $table->decimal('custo_medio', 15, 4)->default(0)->after('estoque');
        });
    }

    public function down(): void
    {
        Schema::table('itens', function (Blueprint $table) {
            $table->dropColumn(['estoque', 'custo_medio']);
        });
    }
};
