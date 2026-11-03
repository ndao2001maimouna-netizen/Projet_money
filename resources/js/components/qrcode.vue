<script setup>
import { ref } from 'vue'
import QrcodeVue from 'qrcode.vue'

const donneesTransfert = ref('ID-TRANSACTION-12345')
const taille = ref(200)
</script>

<template>
  <div style="text-align: center; margin-top: 20px;">
    <h3>Scannez pour valider le transfert</h3>
    
    <qrcode-vue :value="donneesTransfert" :size="taille" level="H" />
    
    <p>Données encodées : {{ donneesTransfert }}</p>
  </div>
</template>
