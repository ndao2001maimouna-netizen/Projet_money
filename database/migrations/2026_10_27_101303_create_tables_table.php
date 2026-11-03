<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->unique();
            $table->string('code_secret');
            $table->enum('role', ['client', 'admin', 'distributeur'])->default('client');
            $table->foreignId('createur_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
            
            $table->timestamps();
        });

        Schema::create('portefeuilles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->decimal('solde', 12, 2)->default(0.00);
            $table->string('devise')->default('XOF'); 
            $table->timestamps();
        });

        Schema::create('transferts', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 10, 2);
            $table->foreignId('expediteur_id')->constrained('portefeuilles')->cascadeOnDelete();
            $table->foreignId('destinataire_id')->constrained('portefeuilles')->cascadeOnDelete();
            $table->string('reference_transfert')->unique(); 
            $table->timestamps();
        });

        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 10, 2);
            $table->foreignId('distributeur_id')->constrained('portefeuilles')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('portefeuilles')->cascadeOnDelete();
            $table->string('reference_depot')->unique(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depots');
        Schema::dropIfExists('transferts');
        Schema::dropIfExists('portefeuilles');
        Schema::dropIfExists('utilisateurs');
    }
};
