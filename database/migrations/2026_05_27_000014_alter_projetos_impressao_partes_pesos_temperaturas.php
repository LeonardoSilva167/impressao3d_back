<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            $table->decimal('peso_suporte', 10, 2)->default(0)->after('peso_parte');
            $table->decimal('peso_corado', 10, 2)->default(0)->after('peso_suporte');
            $table->decimal('peso_torre', 10, 2)->default(0)->after('peso_corado');
        });

        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            $table->integer('temperatura_bico')->default(210)->change();
            $table->integer('temperatura_mesa')->default(75)->change();
        });
    }

    public function down(): void
    {
        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            $table->dropColumn(['peso_suporte', 'peso_corado', 'peso_torre']);
        });

        Schema::table('projetos_impressao_partes', function (Blueprint $table) {
            $table->integer('temperatura_bico')->default(null)->change();
            $table->integer('temperatura_mesa')->default(null)->change();
        });
    }
};
