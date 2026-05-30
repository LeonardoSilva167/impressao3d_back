<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades_produtos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produto_base');
            $table->string('descricao', 120);
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_produto_base', 'gp_produto_base_fk')
                ->references('id')
                ->on('produtos_base');

            $table->index('id_produto_base', 'gp_produto_base_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades_produtos');
    }
};
