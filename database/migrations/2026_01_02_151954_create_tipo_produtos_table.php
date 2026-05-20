<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->boolean('ativo')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        
        Schema::create('subtipo_produtos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_produtos_id')->nullable();            
            $table->string('nome', 100);
            $table->boolean('ativo')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tipo_produtos_id')->references('id')->on('tipo_produtos');

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_produtos');
        Schema::dropIfExists('subtipo_produtos');
    }
};
