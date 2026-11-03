<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/inscription', 'Inscription')->name('Inscription');

Route::post('/', [AuthController::class, 'connecter'])->name('connexion.store');

Route::middleware('guest')->group(function () {
    Route::get('/utilisateurcard', [AuthController::class, 'afficherInscription'])->name('UtilisateurCard.afficherForm');
    Route::post('/utilisateurcard', [AuthController::class, 'inscrire'])->name('inscription.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/clientcard', [AuthController::class, 'afficherClient'])->name('ClientCard');
    Route::get('/distributeurcard', [AuthController::class, 'afficherDistributeur'])->name('DistributeurCard');
    Route::get('/admincard', [AuthController::class, 'afficherAdmin'])->name('AdminCard');

    Route::post('/transfert', [TransactionController::class, 'store'])->name('transfert.store');
    Route::post('/depot', [TransactionController::class, 'depot'])->name('depot.store');

    Route::post('/admin/distributeur', [AuthController::class, 'creerDistributeur'])->name('admin.distributeur.store');
    Route::post('/admin/depot', [TransactionController::class, 'depotAdmin'])->name('admin.depot.store');
    Route::post('/distributeur/sous-gerant', [AuthController::class, 'creerSousDistributeur'])->name('distributeur.sous-gerant.store');

    Route::post('/logout', [AuthController::class, 'deconnecter'])->name('logout');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [AuthController::class, 'redirigerDashboard'])->name('dashboard');
});

require __DIR__.'/settings.php';
