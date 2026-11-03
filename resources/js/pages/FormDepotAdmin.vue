<script setup>
defineProps({
    formDepot: Object
});

const emit = defineEmits(['submit']);
</script>

<template>
    <section style="background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; max-width: 500px;">
        <h3 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 600; color: #0f172a;">Faire un dépôt au distributeur</h3>
        <p style="margin: 0 0 20px 0; font-size: 14px; color: #64748b;">Saisissez les coordonnées de l'agent pour mettre à jour son solde.</p>
        
        <form @submit.prevent="emit('submit')" style="display: flex; flex-direction: column; gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: #334155;">Numéro du distributeur</label>
                <input v-model="formDepot.distributeur_telephone" type="text" placeholder="Ex: 78 987 65 43" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                <p v-if="formDepot.errors?.distributeur_telephone" style="color: #ef4444; font-size: 13px; margin: 4px 0 0 0;">{{ formDepot.errors.distributeur_telephone }}</p>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: #334155;">Montant (XOF)</label>
                <input v-model="formDepot.montant" type="number" placeholder="Ex: 50000" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                <p v-if="formDepot.errors?.montant" style="color: #ef4444; font-size: 13px; margin: 4px 0 0 0;">{{ formDepot.errors.montant }}</p>
            </div>
            <button type="submit" :disabled="formDepot.processing" style="padding: 12px 24px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; align-self: flex-start; box-shadow: 0 4px 12px rgba(59,130,246,0.2);">
                {{ formDepot.processing ? 'Chargement...' : '＋ Confirmer le dépôt' }}
            </button>
        </form>
    </section>
</template>
