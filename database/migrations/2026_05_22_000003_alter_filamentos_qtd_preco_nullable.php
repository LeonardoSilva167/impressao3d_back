<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE filamentos MODIFY qtd DECIMAL(15, 2) NULL DEFAULT 0');
        DB::statement('ALTER TABLE filamentos MODIFY preco_medio_grama DECIMAL(15, 4) NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE filamentos MODIFY qtd DECIMAL(15, 2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE filamentos MODIFY preco_medio_grama DECIMAL(15, 4) NOT NULL DEFAULT 0');
    }
};
