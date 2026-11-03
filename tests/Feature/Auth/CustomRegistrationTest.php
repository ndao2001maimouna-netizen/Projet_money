<?php

use App\Models\Utilisateur;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('custom registration screens can be rendered', function () {
    $this->get('/inscription')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Inscription'));

    $this->get(route('UtilisateurCard.afficherForm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Inscription'));
});

test('the login URL displays the custom login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('logging out returns to the custom home page', function () {
    $utilisateur = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => Hash::make('1234'),
        'role' => 'client',
    ]);

    $this->actingAs($utilisateur)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('a user can register through the custom registration form', function () {
    $this->post(route('inscription.store'), [
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => '1234',
        'code_secret_confirmation' => '1234',
    ])->assertRedirect(route('ClientCard'));

    $this->assertAuthenticated();

    $utilisateur = Utilisateur::where('telephone', '771234567')->firstOrFail();

    expect($utilisateur->nom)->toBe('Ndao')
        ->and($utilisateur->prenom)->toBe('Maimouna')
        ->and($utilisateur->role)->toBe('client')
        ->and(Hash::check('1234', $utilisateur->code_secret))->toBeTrue();

    $this->get(route('ClientCard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ClientCard')
            ->where('auth.user.role', 'client')
        );
});

test('each newly registered client sees their own account page', function () {
    foreach ([
        ['nom' => 'Ndao', 'prenom' => 'Maimouna', 'telephone' => '771234567'],
        ['nom' => 'Diop', 'prenom' => 'Awa', 'telephone' => '781234567'],
    ] as $client) {
        $this->post(route('inscription.store'), [
            ...$client,
            'code_secret' => '1234',
            'code_secret_confirmation' => '1234',
        ])->assertRedirect(route('ClientCard'));

        $this->get(route('ClientCard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ClientCard')
                ->where('auth.user.nom', $client['nom'])
                ->where('auth.user.prenom', $client['prenom'])
                ->where('auth.user.telephone', $client['telephone'])
            );

        $this->post(route('logout'))->assertRedirect(route('home'));
    }
});

test('a client visiting dashboard is redirected to the client interface', function () {
    $client = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => Hash::make('1234'),
        'role' => 'client',
    ]);

    $this->actingAs($client)
        ->get(route('dashboard'))
        ->assertRedirect(route('ClientCard'));
});

test('an authenticated distributor cannot create a client account', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndiaye',
        'prenom' => 'Ibrahima',
        'telephone' => '789876543',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);

    $this->actingAs($distributeur)
        ->get(route('Inscription'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inscription')
            ->where('auth.user.role', 'distributeur')
        );

    $this->actingAs($distributeur)
        ->post(route('inscription.store'), [
            'nom' => 'Client',
            'prenom' => 'Nouveau',
            'telephone' => '771234567',
            'code_secret' => '1234',
            'code_secret_confirmation' => '1234',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($distributeur);
    $this->assertDatabaseMissing('utilisateurs', ['telephone' => '771234567']);

    $this->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->post(route('inscription.store'), [
        'nom' => 'Client',
        'prenom' => 'Nouveau',
        'telephone' => '771234567',
        'code_secret' => '1234',
        'code_secret_confirmation' => '1234',
    ])->assertRedirect(route('ClientCard'));

    $this->assertAuthenticatedAs(
        Utilisateur::query()->where('telephone', '771234567')->firstOrFail(),
    );
});

test('registration fields are validated with their custom messages', function () {
    $this->post(route('inscription.store'), [])
        ->assertSessionHasErrors([
            'nom' => 'Le champ du nom est obligatoire.',
            'prenom' => 'Le champ du prénom est obligatoire.',
            'telephone' => 'Le champ du téléphone est obligatoire.',
            'code_secret' => 'Veuillez entrer votre code secret.',
        ]);
});

test('registration rejects invalid names and phone numbers', function () {
    $this->post(route('inscription.store'), [
        'nom' => 'Ndao123',
        'prenom' => 'Mai_mouna',
        'telephone' => 'abcdefgh9',
        'code_secret' => '1234',
    ])->assertSessionHasErrors([
        'nom' => 'Le nom ne peut contenir que des lettres, espaces, apostrophes ou tirets.',
        'prenom' => 'Le prénom ne peut contenir que des lettres, espaces, apostrophes ou tirets.',
        'telephone' => 'Entrez un numéro mobile sénégalais valide de 9 chiffres.',
    ]);
});

test('registration accepts a valid phone number with spaces', function () {
    $this->post(route('inscription.store'), [
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '77 123 45 67',
        'code_secret' => '1234',
        'code_secret_confirmation' => '1234',
    ])->assertRedirect(route('ClientCard'));

    expect(Utilisateur::where('telephone', '771234567')->exists())->toBeTrue();
});

test('registration rejects a mismatched secret code confirmation', function () {
    $this->post(route('inscription.store'), [
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => '1234',
        'code_secret_confirmation' => '4321',
    ])->assertSessionHasErrors([
        'code_secret_confirmation' => 'Les deux codes secrets doivent être identiques.',
    ]);

    $this->assertDatabaseMissing('utilisateurs', ['telephone' => '771234567']);
});

test('login fields are validated with their custom messages', function () {
    $this->post(route('connexion.store'), [])
        ->assertSessionHasErrors([
            'telephone' => 'Le numéro de téléphone est obligatoire pour se connecter.',
            'code_secret' => 'Le code secret est obligatoire.',
        ]);
});

test('login rejects invalid phone numbers and secret codes', function () {
    $this->post(route('connexion.store'), [
        'telephone' => '77abc4567',
        'code_secret' => '12.4',
    ])->assertSessionHasErrors([
        'telephone' => 'Entrez un numéro mobile sénégalais valide de 9 chiffres.',
        'code_secret' => 'Votre code doit contenir 4 chiffres.',
    ]);
});

test('a distributor is redirected to the distributor page after login', function () {
    $distributeur = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '771234567',
        'code_secret' => Hash::make('1234'),
        'role' => 'distributeur',
    ]);

    $this->post(route('connexion.store'), [
        'telephone' => '771234567',
        'code_secret' => '1234',
    ])
        ->assertRedirect(route('DistributeurCard'))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($distributeur);

    $this->get(route('DistributeurCard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('DistributeurCard'));
});

test('an admin is redirected to the admin page after login', function () {
    $admin = Utilisateur::create([
        'nom' => 'Ndao',
        'prenom' => 'Maimouna',
        'telephone' => '700000000',
        'code_secret' => Hash::make('0000'),
        'role' => 'admin',
    ]);

    $this->post(route('connexion.store'), [
        'telephone' => '700000000',
        'code_secret' => '0000',
    ])
        ->assertRedirect(route('AdminCard'))
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($admin);

    $this->get(route('AdminCard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('AdminCard'));
});
