<script setup>
import { ref } from 'vue';

const props = defineProps({
    form: Object,
});

const emit = defineEmits(['ajouter']);
const codeSecretVisible = ref(false);
const confirmationVisible = ref(false);

function ajouterFormulaire() {
    emit('ajouter');
}
</script>

<template>
    <form @submit.prevent="ajouterFormulaire">
        <div class="input-row">
            <div class="input-group">
                <label>Prénom</label>
                <input
                    v-model="props.form.prenom"
                    type="text"
                    placeholder="Maimouna"
                    required
                />
                <p v-if="props.form.errors.prenom" class="erreur-text">
                    {{ props.form.errors.prenom }}
                </p>
            </div>
            <div class="input-group">
                <label>Nom</label>
                <input
                    v-model="props.form.nom"
                    type="text"
                    placeholder="Ndao"
                    required
                />
                <p v-if="props.form.errors.nom" class="erreur-text">
                    {{ props.form.errors.nom }}
                </p>
            </div>
        </div>

        <div class="input-group">
            <label>Numéro de téléphone</label>
            <div class="input-with-icon">
                <span class="field-icon"></span>
                <input
                    v-model="props.form.telephone"
                    type="text"
                    placeholder="Ex: 77 123 45 67"
                    required
                />
                <p v-if="props.form.errors.telephone" class="erreur-text">
                    {{ props.form.errors.telephone }}
                </p>
            </div>
        </div>

        <div class="input-group">
            <label>Définir un code secret (4 chiffres)</label>
            <div class="input-with-icon">
                <span class="field-icon"></span>
                <input
                    v-model="props.form.code_secret"
                    :type="codeSecretVisible ? 'text' : 'password'"
                    placeholder="••••••••"
                   
                    inputmode="numeric"
                    style="padding-right: 48px;"
                />
                <button
                    type="button"
                    :aria-label="codeSecretVisible ? 'Masquer le code secret' : 'Afficher le code secret'"
                    :aria-pressed="codeSecretVisible"
                    @click="codeSecretVisible = !codeSecretVisible"
                    style="position: absolute; right: 12px; display: flex; align-items: center; justify-content: center; padding: 6px; border: 0; background: transparent; color: #64748b; cursor: pointer;"
                >
                    <EyeOff v-if="codeSecretVisible" :size="18" />
                    <Eye v-else :size="18" />
                </button>
                <p v-if="props.form.errors.code_secret" class="erreur-text">
                    {{ props.form.errors.code_secret }}
                </p>
            </div>
        </div>

        <div class="input-group">
            <label>Confirmer le code secret</label>
            <div class="input-with-icon">
                <span class="field-icon"></span>
                <input
                    v-model="props.form.code_secret_confirmation"
                    :type="confirmationVisible ? 'text' : 'password'"
                    placeholder="••••••••"
                    inputmode="numeric"
                    autocomplete="new-password"
                    style="padding-right: 48px;"
                />
                <button
                    type="button"
                    :aria-label="confirmationVisible ? 'Masquer la confirmation' : 'Afficher la confirmation'"
                    :aria-pressed="confirmationVisible"
                    @click="confirmationVisible = !confirmationVisible"
                    style="position: absolute; right: 12px; display: flex; align-items: center; justify-content: center; padding: 6px; border: 0; background: transparent; color: #64748b; cursor: pointer;"
                >
                    <EyeOff v-if="confirmationVisible" :size="18" />
                    <Eye v-else :size="18" />
                </button>
                <p v-if="props.form.errors.code_secret_confirmation" class="erreur-text">
                    {{ props.form.errors.code_secret_confirmation }}
                </p>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <span>Créer un compte </span>
            <span class="btn-arrow">→</span>
        </button>
    </form>
</template>

<style scoped></style>
