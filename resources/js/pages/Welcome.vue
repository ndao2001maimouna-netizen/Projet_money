<script setup>
import { Link } from '@inertiajs/vue3';
import LoginCard from './LoginCard.vue';
</script>

<template>
    <div class="global-wrapper">
        <div class="bg-decoration">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
        </div>

        <div class="app-container">
            <div class="login-header">
                <div class="logo-wrapper">
                    <span class="logo-icon"></span>
                </div>
                <h1>Money</h1>
                <p class="subtitle">
                    Connectez-vous pour accéder à votre espace sécurisé
                </p>
            </div>

            <div class="card-login">
                <div class="card-indicator"></div>
                <LoginCard />
            </div>

            <div class="login-footer">
                <p>
                    Pas encore de compte ?
                    <Link href="/inscription" class="link">S'inscrire</Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.erreur-text {
    color: #ef4444;
    font-size: 0.8rem;
    margin-top: 4px;
    font-weight: 600;
}
</style>
