<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->decimal('custo_energia_kwh', 10, 4)->default(1.039)->after('proximo_codigo_base');
            $table->decimal('custo_desgaste_hora', 10, 4)->default(1.20)->after('custo_energia_kwh');
        });

        DB::table('configuracoes')
            ->whereNull('deleted_at')
            ->update([
                'custo_energia_kwh'   => 1.039,
                'custo_desgaste_hora' => 1.20,
                'updated_at'          => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table) {
            $table->dropColumn(['custo_energia_kwh', 'custo_desgaste_hora']);
        });
    }
};
