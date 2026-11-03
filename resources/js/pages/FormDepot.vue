<script setup>
defineProps({
    formDepot: Object
});

const emit = defineEmits(['submit']);

function soumettre() {
    emit('submit');
}
</script>

<template>
    <div class="card-depot" style="background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 15px; color: #333;">Faire un dépôt</h3>
        <form @submit.prevent="soumettre">
            <div class="input-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Numéro de téléphone du client</label>
                <input 
                    v-model="formDepot.client_telephone" 
                    type="text" 
                    placeholder="Ex: 77 123 45 67" 
                    required 
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" 
                />
                <p v-if="formDepot.errors.client_telephone" style="color: red; font-size: 14px; margin-top: 5px;">
                    {{ formDepot.errors.client_telephone }}
                </p>
            </div>

            <div class="input-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Montant (XOF)</label>
                <input 
                    v-model="formDepot.montant" 
                    type="number" 
                    placeholder="Ex: 5000" 
                    required 
                    style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" 
                />
                <p v-if="formDepot.errors.montant" style="color: red; font-size: 14px; margin-top: 5px;">
                    {{ formDepot.errors.montant }}
                </p>
            </div>

            <button type="submit" class="btn-submit" style="width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                Confirmer le dépôt
            </button>
        </form>
    </div>
</template>
