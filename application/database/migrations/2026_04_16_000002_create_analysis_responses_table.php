<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protocol_id')->constrained('triggers');
            $table->enum('status', [
                'RECEBIDO',
                'EM_PROCESSAMENTO',
                'ERRO',
                'SUCESSO',
                'ERRO_PROCESSAMENTO',
            ]);
            $table->json('content')->nullable();
            $table->timestamp('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_responses');
    }
};
