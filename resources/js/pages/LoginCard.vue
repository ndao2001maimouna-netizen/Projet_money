<script setup>
import { useForm } from '@inertiajs/vue3';

const formLogn = useForm({
    telephone: '',
    code_secret: '',
});

function connecter() {
    // console.log(formLogn);
    
    formLogn.post('/', {
        onSuccess: () => formLogn.reset('code_secret'),
    });
}
</script>

<template>
   
                
                <form @submit.prevent="connecter">
                    <div class="input-group">
                        <label>Numéro de téléphone</label>
                        <div class="input-with-icon">
                            <span class="field-icon"></span>
                            <input
                                v-model="formLogn.telephone"
                                type="text"
                                placeholder="Ex: 77 123 45 67"
                                required
                            />
                        </div>
                        <p v-if="formLogn.errors.telephone" class="erreur-text">
                            {{ formLogn.errors.telephone }}
                        </p>
                    </div>

                    <div class="input-group" style="margin-top: 15px;">
                        <label>Code secret</label>
                        <div class="input-with-icon">
                            <span class="field-icon"></span>
                            <input
                                v-model="formLogn.code_secret"
                                type="password"
                                placeholder="••••••••"
                                maxlength="4"
                                required
                            />
                        </div>
                        <p v-if="formLogn.errors.code_secret" class="erreur-text">
                            {{ formLogn.errors.code_secret }}
                        </p>
                    </div>

                    <button type="submit" :disabled="formLogn.processing" class="btn-submit" style="margin-top: 25px;">
                        <span>{{ formLogn.processing ? 'Connexion...' : 'Se connecter' }}</span>
                        <span class="btn-arrow">→</span>
                    </button>
                </form>
     
</template>

<style scoped></style>
