<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->renameColumn('desconto', 'valor_desconto');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->decimal('valor_taxa', 15, 2)->default(0)->after('valor_desconto');
            $table->decimal('valor_imposto', 15, 2)->default(0)->after('valor_taxa');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['valor_taxa', 'valor_imposto']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->renameColumn('valor_desconto', 'desconto');
        });
    }
};
