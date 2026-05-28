<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos_impressao', function (Blueprint $table) {
            $table->string('tempo_total_horas', 5)->change();
        });

        $registros = DB::table('projetos_impressao')
            ->whereNotNull('tempo_total_horas')
            ->get();

        foreach ($registros as $projeto) {
                $valor = (string) $projeto->tempo_total_horas;

                if (preg_match('/^\d{2}:\d{2}$/', $valor)) {
                    return;
                }

                if (!is_numeric($valor)) {
                    return;
                }

                $horasDecimais = (float) $valor;
                $horas         = (int) floor($horasDecimais);
                $minutos       = (int) round(($horasDecimais - $horas) * 60);

                if ($minutos >= 60) {
                    $horas  += intdiv($minutos, 60);
                    $minutos = $minutos % 60;
                }

                DB::table('projetos_impressao')
                    ->where('id', $projeto->id)
                    ->update(['tempo_total_horas' => sprintf('%02d:%02d', $horas, $minutos)]);
        }
    }

    public function down(): void
    {
        Schema::table('projetos_impressao', function (Blueprint $table) {
            $table->decimal('tempo_total_horas', 8, 2)->change();
        });
    }
};
