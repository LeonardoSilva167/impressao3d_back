<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_materiais', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 120);

            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('tipos_materiais')->insert([
            ['descricao' => 'ABS',       'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'PETG',      'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'PETG HF',   'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'PLA',       'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_materiais');
    }
};
