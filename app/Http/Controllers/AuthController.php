<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnexionRequest;
use App\Http\Requests\InscriptionRequest;
use App\Models\Depot;
use App\Models\Portefeuille;
use App\Models\Transfert;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function afficherInscription()
    {
        return Inertia::render('Inscription');
    }

    public function inscrire(InscriptionRequest $request)
    {
        $donneesValides = $request->validated();

        $utilisateur = DB::transaction(function () use ($donneesValides) {
            $user = Utilisateur::create([
                'nom' => $donneesValides['nom'],
                'prenom' => $donneesValides['prenom'],
                'telephone' => $donneesValides['telephone'],
                'code_secret' => Hash::make($donneesValides['code_secret']),
                'role' => 'client',
            ]);

            Portefeuille::create([
                'utilisateur_id' => $user->id,
                'solde' => 0.00,
                'devise' => 'XOF',
            ]);

            return $user;
        });

        Auth::login($utilisateur);
        $request->session()->regenerate();

        return redirect()->route('ClientCard');
    }

    public function afficherlogin()
    {
        return Inertia::render('LoginCard');
    }

    public function connecter(ConnexionRequest $request)
    {
        $donneesValides = $request->validated();

        $utilisateur = Utilisateur::where('telephone', $donneesValides['telephone'])->first();

        if (! $utilisateur || ! Hash::check($donneesValides['code_secret'], $utilisateur->code_secret)) {
            return back()->withErrors([
                'telephone' => 'Le numéro de téléphone ou le code secret est incorrect.',
            ]);
        }

        Auth::login($utilisateur);

        $request->session()->regenerate();

        $route = match ($utilisateur->role) {
            'client' => 'ClientCard',
            'distributeur' => 'DistributeurCard',
            'admin' => 'AdminCard',
            default => abort(403),
        };

        return redirect()->route($route);
    }

    public function afficherClient()
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = Auth::user();
        $portefeuille = Portefeuille::where('utilisateur_id', $utilisateur->id)->first();

        return Inertia::render('ClientCard', [
            'auth' => [
                'user' => [
                    'id' => $utilisateur->id,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'telephone' => $utilisateur->telephone,
                    'role' => $utilisateur->role,
                    'solde' => $portefeuille ? $portefeuille->solde : 0,
                ],
            ],
            'transactions' => $portefeuille
                ? $this->operationsPourPortefeuille($portefeuille, 'client')
                : [],
        ]);
    }

    public function afficherDistributeur()
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = Auth::user();
        $portefeuille = Portefeuille::where('utilisateur_id', $utilisateur->id)->first();

        return Inertia::render('DistributeurCard', [
            'auth' => [
                'user' => [
                    'id' => $utilisateur->id,
                    'nom' => $utilisateur->nom,
                    'prenom' => $utilisateur->prenom,
                    'telephone' => $utilisateur->telephone,
                    'role' => $utilisateur->role,
                    'solde' => $portefeuille ? $portefeuille->solde : 0,
                ],
            ],
            'operations' => $portefeuille
                ? $this->operationsPourPortefeuille($portefeuille, 'distributeur')
                : [],
        ]);
    }

    public function afficherAdmin()
    {
        abort_unless(Auth::user()?->role === 'admin', 403);

        $distributeurs = Utilisateur::where('role', 'distributeur')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                $portefeuille = Portefeuille::where('utilisateur_id', $user->id)->first();

                return [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'telephone' => $user->telephone,
                    'solde' => $portefeuille ? $portefeuille->solde : 0,
                    'date_creation' => $user->created_at->format('d/m/Y à H:i'),
                ];
            });

        $statistiques = [
            'total_clients' => Utilisateur::where('role', 'client')->count(),
            'total_distributeurs' => Utilisateur::where('role', 'distributeur')->count(),
            'volume_total' => Portefeuille::sum('solde'),
        ];

        $transferts = Transfert::query()
            ->with(['expediteur.utilisateur', 'destinataire.utilisateur'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Transfert $transfert): array => [
                'id' => 'transfert-'.$transfert->id,
                'type' => 'transfert',
                'description' => sprintf(
                    'Transfert de %s vers %s',
                    $this->nomUtilisateur($transfert->expediteur),
                    $this->nomUtilisateur($transfert->destinataire),
                ),
                'montant' => (float) $transfert->montant,
                'date' => $transfert->created_at->format('d/m/Y H:i'),
                'timestamp' => $transfert->created_at->toIso8601String(),
                'reference' => $transfert->reference_transfert,
            ]);

        $depots = Depot::query()
            ->with(['distributeur.utilisateur', 'client.utilisateur'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Depot $depot): array => [
                'id' => 'depot-'.$depot->id,
                'type' => 'depot',
                'description' => sprintf(
                    'Dépôt de %s vers %s',
                    $this->nomUtilisateur($depot->distributeur),
                    $this->nomUtilisateur($depot->client),
                ),
                'montant' => (float) $depot->montant,
                'date' => $depot->created_at->format('d/m/Y H:i'),
                'timestamp' => $depot->created_at->toIso8601String(),
                'reference' => $depot->reference_depot,
            ]);

        return Inertia::render('AdminCard', [
            'auth' => [
                'user' => Auth::user(),
            ],
            'distributeurs' => $distributeurs,
            'statistiques' => $statistiques,
            'operations' => $transferts
                ->concat($depots)
                ->sortByDesc('timestamp')
                ->take(50)
                ->values(),
        ]);
    }

    public function redirigerDashboard()
    {
        $route = match (Auth::user()?->role) {
            'client' => 'ClientCard',
            'distributeur' => 'DistributeurCard',
            'admin' => 'AdminCard',
            default => abort(403),
        };

        return redirect()->route($route);
    }

    private function operationsPourPortefeuille(Portefeuille $portefeuille, string $role): array
    {
        $transferts = Transfert::query()
            ->with(['expediteur.utilisateur', 'destinataire.utilisateur'])
            ->where(fn ($query) => $query
                ->where('expediteur_id', $portefeuille->getKey())
                ->orWhere('destinataire_id', $portefeuille->getKey()))
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (Transfert $transfert) use ($portefeuille): array {
                $isSender = $transfert->expediteur_id === $portefeuille->getKey();
                $autrePortefeuille = $isSender ? $transfert->destinataire : $transfert->expediteur;

                return [
                    'id' => 'transfert-'.$transfert->id,
                    'type' => $isSender ? 'envoi' : 'reception',
                    'description' => sprintf(
                        '%s %s',
                        $isSender ? 'Transfert envoyé à' : 'Transfert reçu de',
                        $this->nomUtilisateur($autrePortefeuille),
                    ),
                    'montant' => (float) $transfert->montant,
                    'date' => $transfert->created_at->format('d/m/Y H:i'),
                    'timestamp' => $transfert->created_at->toIso8601String(),
                    'reference' => $transfert->reference_transfert,
                ];
            });

        $queryDepot = Depot::query()
            ->with(['distributeur.utilisateur', 'client.utilisateur'])
            ->where(function ($query) use ($role, $portefeuille): void {
                if ($role === 'client') {
                    $query->where('client_id', $portefeuille->getKey());

                    return;
                }

                $query
                    ->where('distributeur_id', $portefeuille->getKey())
                    ->orWhere('client_id', $portefeuille->getKey());
            })
            ->latest()
            ->limit(50);

        $depots = $queryDepot->get()->map(function (Depot $depot) use ($portefeuille): array {
            $isSender = $depot->distributeur_id === $portefeuille->getKey();
            $autrePortefeuille = $isSender ? $depot->client : $depot->distributeur;

            return [
                'id' => 'depot-'.$depot->id,
                'type' => $isSender ? 'depot' : 'reception',
                'description' => $isSender
                    ? 'Dépôt effectué pour '.$this->nomUtilisateur($autrePortefeuille)
                    : 'Dépôt reçu de '.$this->nomUtilisateur($autrePortefeuille),
                'montant' => (float) $depot->montant,
                'date' => $depot->created_at->format('d/m/Y H:i'),
                'timestamp' => $depot->created_at->toIso8601String(),
                'reference' => $depot->reference_depot,
            ];
        });

        return $transferts
            ->concat($depots)
            ->sortByDesc('timestamp')
            ->take(50)
            ->values()
            ->all();
    }

    private function nomUtilisateur(?Portefeuille $portefeuille): string
    {
        $utilisateur = $portefeuille?->utilisateur;

        return $utilisateur
            ? trim($utilisateur->prenom.' '.$utilisateur->nom)
            : 'Compte inconnu';
    }

    public function creerDistributeur(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        return $this->enregistrerDistributeur($request);
    }

    public function creerSousDistributeur(Request $request)
    {
        abort_unless($request->user()?->role === 'distributeur', 403);

        return $this->enregistrerDistributeur($request);
    }

    private function enregistrerDistributeur(Request $request)
    {
        $donneesValides = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'unique:utilisateurs,telephone', 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'code_secret' => ['required', 'digits:4'],
        ]);

        $createur = $request->user();

        DB::transaction(function () use ($donneesValides, $createur) {
            $distributeur = Utilisateur::create([
                'nom' => $donneesValides['nom'],
                'prenom' => $donneesValides['prenom'],
                'telephone' => $donneesValides['telephone'],
                'code_secret' => Hash::make($donneesValides['code_secret']),
                'role' => 'distributeur',
                'createur_id' => $createur ? $createur->id : null,
            ]);

            Portefeuille::create([
                'utilisateur_id' => $distributeur->id,
                'solde' => 0.00,
                'devise' => 'XOF',
            ]);
        });

        return back()->with('success', 'Gérant distributeur et son portefeuille créés avec succès.');
    }

    public function deconnecter(Request $request)
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }
}
