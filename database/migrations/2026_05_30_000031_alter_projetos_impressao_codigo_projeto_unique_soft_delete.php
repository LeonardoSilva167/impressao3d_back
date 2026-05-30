<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $oldUniqueExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao'
              AND index_name = 'projetos_impressao_codigo_projeto_unique'
        ");

        if ($oldUniqueExists) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->dropUnique('projetos_impressao_codigo_projeto_unique');
            });
        }

        $newUniqueExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao'
              AND index_name = 'projetos_impressao_codigo_projeto_unico'
        ");

        if (!$newUniqueExists) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->unique(['codigo_projeto', 'deleted_at'], 'projetos_impressao_codigo_projeto_unico');
            });
        }
    }

    public function down(): void
    {
        $newUniqueExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao'
              AND index_name = 'projetos_impressao_codigo_projeto_unico'
        ");

        if ($newUniqueExists) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->dropUnique('projetos_impressao_codigo_projeto_unico');
            });
        }

        $oldUniqueExists = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'projetos_impressao'
              AND index_name = 'projetos_impressao_codigo_projeto_unique'
        ");

        if (!$oldUniqueExists) {
            Schema::table('projetos_impressao', function (Blueprint $table) {
                $table->unique('codigo_projeto', 'projetos_impressao_codigo_projeto_unique');
            });
        }
    }
};
