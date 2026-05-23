<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('linhas_marcas', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 120);

            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('linhas_marcas')->insert([
            ['descricao' => 'HIGH SPEED PREMIUM',         'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'VELVET HIGH SPEED PREMIUM',  'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'V-SILK HIGH SPEED PREMIUM',  'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'PREMIUM',                      'created_at' => $now, 'updated_at' => $now],
            ['descricao' => 'HIGH FLUIDITY PREMIUM',        'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('linhas_marcas');
    }
};
