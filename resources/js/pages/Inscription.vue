<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import UtilisateurCard from './UtilisateurCard.vue';

const form = useForm({
    nom: '',
    prenom: '',
    telephone: '',
    code_secret: '',
    code_secret_confirmation: '',
});

function remplireFormulaire() {
    form.post('/utilisateurcard', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <div class="global-wrapper">
        <div class="bg-decoration">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
        </div>

        <div class="register-container">
            <div class="register-header">
                <div class="logo-wrapper">
                    <span class="logo-icon"></span>
                </div>
                <h1>Créer un compte</h1>
                <p class="subtitle">
                    Rejoignez Money et commencez à envoyer de l'argent en toute
                    simplicité
                </p>
            </div>

            <div class="card-register">
                <div class="card-indicator"></div>
                <UtilisateurCard :form="form" @ajouter="remplireFormulaire" />
            </div>

            <div class="register-footer">
                <p>
                    Vous avez déjà un compte ?
                    <Link class="link" href="/">Se connecter</Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style></style>
