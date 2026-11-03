<?php

use App\Models\Depot;
use App\Models\Portefeuille;
use App\Models\Transfert;
use App\Models\Utilisateur;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function creerUtilisateurPourTransfert(string $telephone, string $solde = '0.00'): array
{
    $utilisateur = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => $telephone,
        'code_secret' => Hash::make('1234'),
        'role' => 'client',
    ]);

    $portefeuille = Portefeuille::create([
        'utilisateur_id' => $utilisateur->getKey(),
        'solde' => $solde,
        'devise' => 'XOF',
    ]);

    return [$utilisateur, $portefeuille];
}

test('an authenticated user can transfer money to another wallet', function () {
    [$expediteur, $portefeuilleExpediteur] = creerUtilisateurPourTransfert('771234567', '10000.00');
    [$destinataire, $portefeuilleDestinataire] = creerUtilisateurPourTransfert('779876543');

    $this->actingAs($expediteur)
        ->post(route('transfert.store'), [
            'destinataire_telephone' => '77 987 65 43',
            'montant' => '2500.25',
            'code_secret' => '1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Transfert effectué avec succès.');

    expect((float) $portefeuilleExpediteur->fresh()->solde)->toBe(7499.75)
        ->and((float) $portefeuilleDestinataire->fresh()->solde)->toBe(2500.25)
        ->and(Transfert::query()->count())->toBe(1);

    $this->assertDatabaseHas('transferts', [
        'montant' => '2500.25',
        'expediteur_id' => $portefeuilleExpediteur->getKey(),
        'destinataire_id' => $portefeuilleDestinataire->getKey(),
    ]);

    $this->actingAs($expediteur)
        ->get(route('ClientCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('ClientCard')
            ->has('transactions', 1)
            ->where('transactions.0.type', 'envoi')
            ->where('transactions.0.description', 'Transfert envoyé à Maimouna Ndao')
        );

    $this->actingAs($destinataire)
        ->get(route('ClientCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('ClientCard')
            ->has('transactions', 1)
            ->where('transactions.0.type', 'reception')
            ->where('transactions.0.description', 'Transfert reçu de Maimouna Ndao')
        );
});

test('a transfer is rejected when the secret code is incorrect', function () {
    [$expediteur, $portefeuilleExpediteur] = creerUtilisateurPourTransfert('771234567', '10000.00');
    [, $portefeuilleDestinataire] = creerUtilisateurPourTransfert('779876543');

    $this->actingAs($expediteur)
        ->post(route('transfert.store'), [
            'destinataire_telephone' => '779876543',
            'montant' => '2500',
            'code_secret' => '0000',
        ])
        ->assertSessionHasErrors('code_secret');

    expect((float) $portefeuilleExpediteur->fresh()->solde)->toBe(10000.0)
        ->and((float) $portefeuilleDestinataire->fresh()->solde)->toBe(0.0)
        ->and(Transfert::query()->count())->toBe(0);
});

test('a transfer is rejected when the sender has insufficient funds', function () {
    [$expediteur, $portefeuilleExpediteur] = creerUtilisateurPourTransfert('771234567', '100.00');
    [, $portefeuilleDestinataire] = creerUtilisateurPourTransfert('779876543');

    $this->actingAs($expediteur)
        ->post(route('transfert.store'), [
            'destinataire_telephone' => '779876543',
            'montant' => '2500',
            'code_secret' => '1234',
        ])
        ->assertSessionHasErrors('montant');

    expect((float) $portefeuilleExpediteur->fresh()->solde)->toBe(100.0)
        ->and((float) $portefeuilleDestinataire->fresh()->solde)->toBe(0.0)
        ->and(Transfert::query()->count())->toBe(0);
});

test('a user cannot transfer money to their own number', function () {
    [$utilisateur, $portefeuille] = creerUtilisateurPourTransfert('771234567', '10000.00');

    $this->actingAs($utilisateur)
        ->post(route('transfert.store'), [
            'destinataire_telephone' => '771234567',
            'montant' => '2500',
            'code_secret' => '1234',
        ])
        ->assertSessionHasErrors('destinataire_telephone');

    expect((float) $portefeuille->fresh()->solde)->toBe(10000.0)
        ->and(Transfert::query()->count())->toBe(0);
});

test('transfers require an authenticated user', function () {
    $this->post(route('transfert.store'), [
        'destinataire_telephone' => '779876543',
        'montant' => '2500',
        'code_secret' => '1234',
    ])->assertRedirect(route('ClientCard'));
});

test('registration creates an XOF wallet for the new user', function () {
    $this->post(route('inscription.store'), [
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => '1234',
    ])->assertRedirect(route('ClientCard'));

    $utilisateur = Utilisateur::query()->where('telephone', '771234567')->firstOrFail();

    $this->assertDatabaseHas('portefeuilles', [
        'utilisateur_id' => $utilisateur->getKey(),
        'solde' => '0.00',
        'devise' => 'XOF',
    ]);
});

test('successful login authenticates the user for transfers', function () {
    [$utilisateur] = creerUtilisateurPourTransfert('771234567');

    $this->post(route('connexion.store'), [
        'telephone' => '771234567',
        'code_secret' => '1234',
    ])->assertRedirect(route('ClientCard'));

    $this->assertAuthenticatedAs($utilisateur);

    $this->get(route('ClientCard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('ClientCard'));
});

test('a distributor deposit debits the distributor and credits the client', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);
    $portefeuilleDistributeur = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '5000.00',
        'devise' => 'XOF',
    ]);
    [$client, $portefeuilleClient] = creerUtilisateurPourTransfert('771234567');

    $this->actingAs($distributeur)
        ->post(route('depot.store'), [
            'client_telephone' => '77 123 45 67',
            'montant' => '1500',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Dépôt effectué avec succès.');

    expect((float) $portefeuilleDistributeur->fresh()->solde)->toBe(3500.0)
        ->and((float) $portefeuilleClient->fresh()->solde)->toBe(1500.0)
        ->and(Depot::query()->count())->toBe(1);

    $this->assertDatabaseHas('depots', [
        'montant' => '1500',
        'distributeur_id' => $portefeuilleDistributeur->getKey(),
        'client_id' => $portefeuilleClient->getKey(),
    ]);

    $this->actingAs($distributeur)
        ->get(route('DistributeurCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DistributeurCard')
            ->has('operations', 1)
            ->where('operations.0.type', 'depot')
            ->where('operations.0.description', 'Dépôt effectué pour Maimouna Ndao')
        );

    $this->actingAs($client)
        ->get(route('ClientCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('ClientCard')
            ->where('auth.user.solde', 1500)
            ->has('transactions', 1)
            ->where('transactions.0.type', 'reception')
            ->where('transactions.0.description', 'Dépôt reçu de Ibrahima Ndiaye')
        );
});

test('a distributor deposit is rejected when the distributor has insufficient funds', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);
    $portefeuilleDistributeur = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '500.00',
        'devise' => 'XOF',
    ]);
    [, $portefeuilleClient] = creerUtilisateurPourTransfert('771234567');

    $this->actingAs($distributeur)
        ->post(route('depot.store'), [
            'client_telephone' => '771234567',
            'montant' => '1500',
        ])
        ->assertSessionHasErrors('montant');

    expect((float) $portefeuilleDistributeur->fresh()->solde)->toBe(500.0)
        ->and((float) $portefeuilleClient->fresh()->solde)->toBe(0.0)
        ->and(Depot::query()->count())->toBe(0);
});

test('a distributor cannot deposit to another distributor', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);
    $portefeuilleDistributeur = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '5000.00',
        'devise' => 'XOF',
    ]);
    $autreDistributeur = Utilisateur::create([
        'nom' => 'Diop',
        'prenom' => 'Amadou',
        'telephone' => '761234589',
        'code_secret' => Hash::make('4321'),
        'role' => 'distributeur',
    ]);
    $portefeuilleAutreDistributeur = Portefeuille::create([
        'utilisateur_id' => $autreDistributeur->getKey(),
        'solde' => '1000.00',
        'devise' => 'XOF',
    ]);

    $this->actingAs($distributeur)
        ->post(route('depot.store'), [
            'client_telephone' => $autreDistributeur->telephone,
            'montant' => '500',
        ])
        ->assertSessionHasErrors('client_telephone');

    expect((float) $portefeuilleDistributeur->fresh()->solde)->toBe(5000.0)
        ->and((float) $portefeuilleAutreDistributeur->fresh()->solde)->toBe(1000.0)
        ->and(Depot::query()->count())->toBe(0);
});

test('a distributor cannot create or fund another distributor account', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);

    $this->actingAs($distributeur)
        ->post(route('admin.distributeur.store'), [])
        ->assertForbidden();

    $this->post(route('admin.depot.store'), [])
        ->assertForbidden();

    $this->post(route('transfert.store'), [
        'destinataire_telephone' => '771234567',
        'montant' => '500',
        'code_secret' => '1234',
    ])->assertForbidden();

    $this->get(route('AdminCard'))
        ->assertForbidden();
});

test('a distributor can create and fund their own sub-distributor', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);
    $portefeuilleDistributeur = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '5000.00',
        'devise' => 'XOF',
    ]);

    $this->actingAs($distributeur)
        ->post(route('distributeur.sous-gerant.store'), [
            'nom' => 'Diop',
            'prenom' => 'Sidy',
            'telephone' => '771234567',
            'code_secret' => '4321',
        ])
        ->assertRedirect();

    $sousDistributeur = Utilisateur::query()->where('telephone', '771234567')->firstOrFail();
    $this->assertDatabaseHas('utilisateurs', [
        'id' => $sousDistributeur->getKey(),
        'role' => 'distributeur',
        'createur_id' => $distributeur->getKey(),
    ]);

    $portefeuilleSousDistributeur = Portefeuille::query()
        ->where('utilisateur_id', $sousDistributeur->getKey())
        ->firstOrFail();

    $this->post(route('depot.store'), [
        'type_destinataire' => 'distributeur',
        'client_telephone' => '77 123 45 67',
        'montant' => '1500',
    ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Dépôt effectué avec succès.');

    expect((float) $portefeuilleDistributeur->fresh()->solde)->toBe(3500.0)
        ->and((float) $portefeuilleSousDistributeur->fresh()->solde)->toBe(1500.0)
        ->and(Depot::query()->count())->toBe(1);

    $this->actingAs($sousDistributeur)
        ->get(route('DistributeurCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('DistributeurCard')
            ->has('operations', 1)
            ->where('operations.0.type', 'reception')
            ->where('operations.0.description', 'Dépôt reçu de Ibrahima Ndiaye')
        );
});

test('an admin can credit an existing distributor wallet', function () {
    $admin = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '700000000',
        'code_secret' => Hash::make('1234'),
        'role' => 'admin',
    ]);
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('4321'),
        'role' => 'distributeur',
    ]);
    $portefeuille = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '5000.00',
        'devise' => 'XOF',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.depot.store'), [
            'distributeur_telephone' => '78 987 65 43',
            'montant' => '1500',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Le compte du gérant distributeur a été rechargé avec succès.');

    expect((float) $portefeuille->fresh()->solde)->toBe(6500.0);
});

test('admin can list the latest transfers and deposits', function () {
    $admin = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '700000000',
        'code_secret' => Hash::make('1234'),
        'role' => 'admin',
    ]);
    [, $portefeuilleExpediteur] = creerUtilisateurPourTransfert('771234567', '5000.00');
    [, $portefeuilleDestinataire] = creerUtilisateurPourTransfert('779876543');
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('4321'),
        'role' => 'distributeur',
    ]);
    $portefeuilleDistributeur = Portefeuille::create([
        'utilisateur_id' => $distributeur->getKey(),
        'solde' => '5000.00',
        'devise' => 'XOF',
    ]);

    $transfert = Transfert::create([
        'montant' => '500',
        'expediteur_id' => $portefeuilleExpediteur->getKey(),
        'destinataire_id' => $portefeuilleDestinataire->getKey(),
        'reference_transfert' => 'history-transfer-reference',
    ]);
    $transfert->forceFill(['created_at' => now()->subMinute()])->save();
    $depot = Depot::create([
        'montant' => '1000',
        'distributeur_id' => $portefeuilleDistributeur->getKey(),
        'client_id' => $portefeuilleDestinataire->getKey(),
        'reference_depot' => 'history-deposit-reference',
    ]);
    $depot->forceFill(['created_at' => now()])->save();

    $this->actingAs($admin)
        ->get(route('AdminCard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminCard')
            ->has('operations', 2)
            ->where('operations.0.type', 'depot')
            ->where('operations.0.reference', 'history-deposit-reference')
            ->where('operations.1.type', 'transfert')
            ->where('operations.1.reference', 'history-transfer-reference')
        );
});

test('the client page displays the authenticated user wallet and recent transfers', function () {
    [$client, $portefeuilleClient] = creerUtilisateurPourTransfert('771234567', '7500.00');
    [$destinataire, $portefeuilleDestinataire] = creerUtilisateurPourTransfert('779876543', '2500.00');
    $destinataire->forceFill([
        'nom' => 'Diop',
        'prenom' => 'Omar',
    ])->save();

    Transfert::create([
        'montant' => '2500.00',
        'expediteur_id' => $portefeuilleClient->getKey(),
        'destinataire_id' => $portefeuilleDestinataire->getKey(),
        'reference_transfert' => 'test-transfer-reference',
    ]);

    $this->actingAs($client)
        ->get(route('ClientCard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ClientCard')
            ->where('client.nom', 'Maimouna Ndao')
            ->where('client.telephone', '771234567')
            ->where('portefeuille.solde', 7500)
            ->where('portefeuille.devise', 'XOF')
            ->has('transferts', 1)
            ->where('transferts.0.direction', 'sortant')
            ->where('transferts.0.nom', 'Omar Diop')
        );
});
