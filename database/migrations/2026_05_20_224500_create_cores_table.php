<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cores', function (Blueprint $table) {
            $table->id();

            $table->string('descricao', 20);
            $table->string('codigo');
            $table->string('hexadecimal')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cores');
    }
};
