<script setup>
defineProps({
    formTransfert: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['submit']);
</script>

<template>
  <div class="card-action">
    <h3 class="section-title title-bleu">Faire un transfert</h3>
    
    <form @submit.prevent="emit('submit')">
      <div class="input-group">
        <label>Numéro du destinataire</label>
        <div class="input-with-icon">
          <span class="field-icon"></span>
          <input v-model="formTransfert.destinataire_telephone" type="text" placeholder="Ex: 77 987 65 43" required />
        </div>
        <p v-if="formTransfert.errors.destinataire_telephone" class="erreur-text">{{ formTransfert.errors.destinataire_telephone }}</p>
      </div>

      <div class="input-group">
        <label>Montant à envoyer</label>
        <div class="input-with-icon">
          <span class="field-icon"></span>
          <input v-model="formTransfert.montant" type="number" placeholder="0.00" required />
        </div>
        <p v-if="formTransfert.errors.montant" class="erreur-text">{{ formTransfert.errors.montant }}</p>
      </div>

      <div class="input-group">
        <label>Code secret de validation</label>
        <div class="input-with-icon">
          <span class="field-icon"></span>
          <input v-model="formTransfert.code_secret" type="password" placeholder="••••" minlength="4" maxlength="20" required />
        </div>
        <p v-if="formTransfert.errors.code_secret" class="erreur-text">{{ formTransfert.errors.code_secret }}</p>
      </div>

      <button type="submit" class="btn-submit btn-bleu" :disabled="formTransfert.processing">
        <span>{{ formTransfert.processing ? 'Envoi en cours...' : 'Envoyer ' }}</span>
        <span class="btn-arrow">→</span>
      </button>
    </form>
  </div>
</template>

<style scoped>
.erreur-text { color: #ef4444; font-size: 0.8rem; margin-top: 4px; font-weight: 600; text-align: left; }
</style>
