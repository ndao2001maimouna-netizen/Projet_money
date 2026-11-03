<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Portefeuille;
use App\Models\Transfert;
use App\Models\Utilisateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $telephone = $request->input('destinataire_telephone');

        if (is_string($telephone)) {
            $request->merge([
                'destinataire_telephone' => preg_replace('/\s+/', '', $telephone),
            ]);
        }

        $donnees = $request->validate([
            'destinataire_telephone' => ['required', 'string', 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'montant' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'code_secret' => ['required', 'digits:4'],
        ], [
            'destinataire_telephone.required' => 'Le numéro du destinataire est obligatoire.',
            'destinataire_telephone.regex' => 'Entrez un numéro mobile sénégalais valide de 9 chiffres.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.decimal' => 'Le montant ne peut pas dépasser deux chiffres après la virgule.',
            'montant.gt' => 'Le montant doit être supérieur à zéro.',
            'montant.max' => 'Le montant dépasse la limite autorisée.',
            'code_secret.required' => 'Le code secret est obligatoire.',
            'code_secret.digits' => 'Votre code doit contenir 4 chiffres.',
        ]);

        /** @var Utilisateur $expediteur */
        $expediteur = $request->user();

        if ($expediteur->role !== 'client') {
            abort(403);
        }

        if (! Hash::check($donnees['code_secret'], $expediteur->code_secret)) {
            throw ValidationException::withMessages([
                'code_secret' => 'Le code secret est incorrect.',
            ]);
        }

        $destinataire = Utilisateur::query()
            ->where('telephone', $donnees['destinataire_telephone'])
            ->first();

        if (! $destinataire) {
            throw ValidationException::withMessages([
                'destinataire_telephone' => 'Aucun compte ne correspond à ce numéro.',
            ]);
        }

        if ($destinataire->is($expediteur)) {
            throw ValidationException::withMessages([
                'destinataire_telephone' => 'Vous ne pouvez pas vous envoyer de l’argent.',
            ]);
        }

        DB::transaction(function () use ($donnees, $destinataire, $expediteur): void {
            Utilisateur::query()
                ->whereKey([$expediteur->getKey(), $destinataire->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $portefeuilles = Portefeuille::query()
                ->whereIn('utilisateur_id', [$expediteur->getKey(), $destinataire->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('utilisateur_id');

            $portefeuilleExpediteur = $portefeuilles->get($expediteur->getKey());

            if (! $portefeuilleExpediteur) {
                throw ValidationException::withMessages([
                    'montant' => 'Le solde de votre compte est insuffisant .',
                ]);
            }

            $portefeuilleDestinataire = $portefeuilles->get($destinataire->getKey());

            if (! $portefeuilleDestinataire) {
                $portefeuilleDestinataire = Portefeuille::create([
                    'utilisateur_id' => $destinataire->getKey(),
                    'solde' => 0,
                    'devise' => $portefeuilleExpediteur->devise,
                ]);
            }

            if ($portefeuilleExpediteur->devise !== $portefeuilleDestinataire->devise) {
                throw ValidationException::withMessages([
                    'destinataire_telephone' => 'Le portefeuille du destinataire utilise une devise différente.',
                ]);
            }

            $debites = Portefeuille::query()
                ->whereKey($portefeuilleExpediteur->getKey())
                ->where('solde', '>=', $donnees['montant'])
                ->decrement('solde', $donnees['montant']);

            if ($debites !== 1) {
                throw ValidationException::withMessages([
                    'montant' => 'Le solde de votre portefeuille est insuffisant.',
                ]);
            }

            Portefeuille::query()
                ->whereKey($portefeuilleDestinataire->getKey())
                ->increment('solde', $donnees['montant']);

            Transfert::create([
                'montant' => $donnees['montant'],
                'expediteur_id' => $portefeuilleExpediteur->getKey(),
                'destinataire_id' => $portefeuilleDestinataire->getKey(),
                'reference_transfert' => (string) Str::uuid(),
            ]);
        });

        return back()->with('success', 'Transfert effectué avec succès.');
    }

    public function depot(Request $request): RedirectResponse
    {
        $telephone = $request->input('client_telephone');

        if (is_string($telephone)) {
            $request->merge([
                'client_telephone' => preg_replace('/\s+/', '', $telephone),
            ]);
        }

        $donnees = $request->validate([
            'client_telephone' => ['required', 'string', 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'montant' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'type_destinataire' => ['nullable', 'in:client,distributeur'],
        ], [
            'client_telephone.required' => 'Le numéro du client est obligatoire.',
            'client_telephone.regex' => 'Entrez un numéro mobile sénégalais valide de 9 chiffres.',
            'montant.required' => 'Le montant du dépôt est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.decimal' => 'Le montant ne peut pas dépasser deux chiffres après la virgule.',
            'montant.gt' => 'Le montant doit être supérieur à zéro.',
            'montant.max' => 'Le montant dépasse la limite autorisée.',
        ]);

        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        if ($utilisateur->role !== 'distributeur') {
            abort(403);
        }

        $destinataire = Utilisateur::query()
            ->where('telephone', $donnees['client_telephone'])
            ->first();

        if (! $destinataire) {
            throw ValidationException::withMessages([
                'client_telephone' => 'Aucun compte ne correspond à ce numéro.',
            ]);
        }

        $typeDestinataire = $donnees['type_destinataire'] ?? 'client';

        if ($typeDestinataire === 'client' && $destinataire->role !== 'client') {
            throw ValidationException::withMessages([
                'client_telephone' => 'Le numéro indiqué ne correspond pas à un compte client.',
            ]);
        }

        if ($typeDestinataire === 'distributeur'
            && ($destinataire->role !== 'distributeur' || $destinataire->createur_id !== $utilisateur->getKey())) {
            throw ValidationException::withMessages([
                'client_telephone' => 'Vous pouvez créditer uniquement un sous-distributeur que vous avez créé.',
            ]);
        }

        DB::transaction(function () use ($donnees, $utilisateur, $destinataire): void {
            Utilisateur::query()
                ->whereKey([$utilisateur->getKey(), $destinataire->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $portefeuilles = Portefeuille::query()
                ->whereIn('utilisateur_id', [$utilisateur->getKey(), $destinataire->getKey()])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('utilisateur_id');

            $portefeuilleDistributeur = $portefeuilles->get($utilisateur->getKey());

            if (! $portefeuilleDistributeur) {
                throw ValidationException::withMessages([
                    'montant' => 'Le solde du distributeur est insuffisant.',
                ]);
            }

            $portefeuilleDestinataire = $portefeuilles->get($destinataire->getKey());

            if (! $portefeuilleDestinataire) {
                $portefeuilleDestinataire = Portefeuille::create([
                    'utilisateur_id' => $destinataire->getKey(),
                    'solde' => 0,
                    'devise' => $portefeuilleDistributeur->devise,
                ]);
            }

            if ($portefeuilleDistributeur->devise !== $portefeuilleDestinataire->devise) {
                throw ValidationException::withMessages([
                    'client_telephone' => 'Le portefeuille du destinataire utilise une devise différente.',
                ]);
            }

            $debite = Portefeuille::query()
                ->whereKey($portefeuilleDistributeur->getKey())
                ->where('solde', '>=', $donnees['montant'])
                ->decrement('solde', $donnees['montant']);

            if ($debite !== 1) {
                throw ValidationException::withMessages([
                    'montant' => 'Le solde du distributeur est insuffisant.',
                ]);
            }

            Portefeuille::query()
                ->whereKey($portefeuilleDestinataire->getKey())
                ->increment('solde', $donnees['montant']);

            Depot::create([
                'montant' => $donnees['montant'],
                'distributeur_id' => $portefeuilleDistributeur->getKey(),
                'client_id' => $portefeuilleDestinataire->getKey(),
                'reference_depot' => 'DEP-'.(string) Str::uuid(),
            ]);
        });

        return back()->with('success', 'Dépôt effectué avec succès.');
    }

    public function depotAdmin(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $telephone = $request->input('distributeur_telephone');

        if (is_string($telephone)) {
            $request->merge([
                'distributeur_telephone' => preg_replace('/\s+/', '', $telephone),
            ]);
        }

        $donnees = $request->validate([
            'distributeur_telephone' => ['required', 'string', 'regex:/^(70|75|76|77|78)[0-9]{7}$/'],
            'montant' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
        ], [
            'distributeur_telephone.required' => 'Le numéro du distributeur est obligatoire.',
            'distributeur_telephone.regex' => 'Entrez un numéro mobile sénégalais valide de 9 chiffres.',
            'montant.required' => 'Le montant du dépôt est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.decimal' => 'Le montant ne peut pas dépasser deux chiffres après la virgule.',
            'montant.gt' => 'Le montant doit être supérieur à zéro.',
            'montant.max' => 'Le montant dépasse la limite autorisée.',
        ]);

        $distributeur = Utilisateur::where('telephone', $donnees['distributeur_telephone'])
            ->where('role', 'distributeur')
            ->first();

        if (! $distributeur) {
            throw ValidationException::withMessages([
                'distributeur_telephone' => 'Aucun gérant distributeur ne correspond à ce numéro.',
            ]);
        }

        DB::transaction(function () use ($donnees, $distributeur): void {
            Utilisateur::query()
                ->whereKey($distributeur->getKey())
                ->lockForUpdate()
                ->first();

            $portefeuille = Portefeuille::query()
                ->where('utilisateur_id', $distributeur->getKey())
                ->lockForUpdate()
                ->first();

            if (! $portefeuille) {
                Portefeuille::create([
                    'utilisateur_id' => $distributeur->getKey(),
                    'solde' => $donnees['montant'],
                    'devise' => 'XOF',
                ]);
            } else {
                Portefeuille::query()
                    ->whereKey($portefeuille->getKey())
                    ->increment('solde', $donnees['montant']);
            }
        });

        return back()->with('success', 'Le compte du gérant distributeur a été rechargé avec succès.');
    }
}
