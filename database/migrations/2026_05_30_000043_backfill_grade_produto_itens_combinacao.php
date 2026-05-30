<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE grade_produto_itens gpi
            INNER JOIN (
                SELECT id_grade_produto, MIN(id) AS id_combinacao
                FROM grade_produto_combinacoes
                WHERE deleted_at IS NULL
                GROUP BY id_grade_produto
                HAVING COUNT(*) = 1
            ) unica ON unica.id_grade_produto = gpi.id_grade_produto
            SET gpi.id_grade_produto_combinacao = unica.id_combinacao
            WHERE gpi.id_grade_produto_combinacao IS NULL
              AND gpi.deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        // Não reverte — combinação inferida pode ter sido correta para grades com única combinação.
    }
};
