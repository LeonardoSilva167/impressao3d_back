<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_itens', function (Blueprint $table) {
            $table->decimal('qtd_interna', 15, 4)->default(1)->after('qtd');
            $table->decimal('valor_unitario_real', 15, 4)->default(0)->after('valor_total');
        });

        DB::table('compras_itens')->update([
            'qtd_interna'         => DB::raw('qtd'),
            'valor_unitario_real' => DB::raw('CASE WHEN qtd > 0 THEN valor_total / qtd ELSE 0 END'),
        ]);

        Schema::table('compras_itens', function (Blueprint $table) {
            $table->renameColumn('qtd', 'qtd_compra');
            $table->renameColumn('valor_unitario', 'valor_unitario_compra');
        });
    }

    public function down(): void
    {
        Schema::table('compras_itens', function (Blueprint $table) {
            $table->renameColumn('qtd_compra', 'qtd');
            $table->renameColumn('valor_unitario_compra', 'valor_unitario');
        });

        Schema::table('compras_itens', function (Blueprint $table) {
            $table->dropColumn(['qtd_interna', 'valor_unitario_real']);
        });
    }
};
