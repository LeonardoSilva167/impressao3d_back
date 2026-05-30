<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos_base', function (Blueprint $table) {
            $table->dropForeign(['id_linha']);
        });

        Schema::table('produtos_base', function (Blueprint $table) {
            $table->unsignedBigInteger('id_linha')->nullable()->change();
            $table->foreign('id_linha')->references('id')->on('linhas_produtos');
        });
    }

    public function down(): void
    {
        Schema::table('produtos_base', function (Blueprint $table) {
            $table->dropForeign(['id_linha']);
        });

        Schema::table('produtos_base', function (Blueprint $table) {
            $table->unsignedBigInteger('id_linha')->nullable(false)->change();
            $table->foreign('id_linha')->references('id')->on('linhas_produtos');
        });
    }
};
