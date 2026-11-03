<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import FormTransfert from './FormTransfert.vue';
import QrcodeVue from 'qrcode.vue';
import QrScanner from '@/components/QrScanner.vue';

const props = defineProps({
    auth: {
        type: Object,
        default: () => ({ user: null })
    },
    transactions: {
        type: Array,
        default: () => []
    }
});

const formTransfert = useForm({
    destinataire_telephone: '',
    montant: '',
    code_secret: '',
});

const afficherScanner = ref(false);
const formulaireRef = ref(null); 

const qrValue = ref('');
let minuteurQr = null;

function rafraichirCodeQR() {
    if (props.auth?.user?.telephone) {
        const timestampMinute = Math.floor(Date.now() / 60000);
        qrValue.value = `${props.auth.user.telephone}|${timestampMinute}`;
    }
}

onMounted(() => {
    rafraichirCodeQR();
    minuteurQr = setInterval(rafraichirCodeQR, 60000);
});

onUnmounted(() => {
    if (minuteurQr) clearInterval(minuteurQr);
});


function remplirFormulaireViaQR(donneesScannees) {
    const numeroExtrait = donneesScannees.split('|')[0];
    
    formTransfert.destinataire_telephone = numeroExtrait;
    
    afficherScanner.value = false;
}



function effectuerTransfert() {
    formTransfert.post('/transfert', {
        onSuccess: () => {
            formTransfert.reset();
        },
    });
}

function deconnexion() {
    router.post('/logout');
}
</script>

<template>
  <div class="global-wrapper">
    <div class="bg-decoration"><div class="circle circle-1"></div><div class="circle circle-2"></div></div>

    <div class="app-container">
      <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
          <div class="avatar" style="width: 44px; height: 44px; background: #e0eafd; color: #2a6beb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 15px;">
            {{ auth?.user?.prenom?.charAt(0) }}{{ auth?.user?.nom?.charAt(0) }}
          </div>
          <div>
            <h3 style="margin: 0; font-size: 16px; color: #1e293b; font-weight: 600;">
              {{ auth?.user?.prenom }} {{ auth?.user?.nom }}
            </h3>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 13px; font-weight: 500;">
              {{ auth?.user?.telephone }}
            </p>
          </div>
        </div>
        <div class="actions-zone" style="display: flex; align-items: center; gap: 10px;">
          <button @click="deconnexion" style="background: #fef2f2; border: 1px solid #fee2e2; color: #ef4444; font-size: 12px; font-weight: 600; cursor: pointer; padding: 8px 14px; border-radius: 8px; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
            Se déconnecter ↩
          </button>
        </div>
      </div>

      <div class="card-solde solde-bleu">
        <div class="solde-header"><p class="solde-label">Solde disponible</p><span class="currency-badge">XOF</span></div>
        <h2 class="solde-montant">{{ auth?.user?.solde ?? 0 }}</h2>
        <div class="card-footer-decoration"><span class="card-chip"></span><span class="card-logo">•• Money</span></div>
      </div>

      <div class="card-historique" style="margin-top: 20px; margin-bottom: 20px; padding: 20px; text-align: center; background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
        <h3 class="section-title title-bleu" style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">Mon Code QR Sécurisé</h3>
        <p style="margin: 0 0 20px 0; font-size: 13px; color: #64748b; font-weight: 500;">Scannez le code d'un proche pour remplir instantanément le formulaire de transfert.</p>
        
        <div style="display: flex; flex-direction: column; align-items: center; gap: 20px;">
            <div style="display: inline-block; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
              <qrcode-vue 
                v-if="qrValue"
                :value="qrValue" 
                :size="130" 
                level="M" 
                foreground="#1e293b"
              />
            </div>

            <button 
                type="button" 
                @click="afficherScanner = !afficherScanner" 
                style="background: #2a6beb; color: white; border: none; padding: 11px 22px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; box-shadow: 0 4px 12px rgba(42,107,235,0.15);"
            >
                <span></span> {{ afficherScanner ? 'Fermer la caméra' : 'Scanner un Code QR pour envoyer' }}
            </button>
        </div>

        <div v-if="afficherScanner" style="margin-top: 20px;">
            <QrScanner @scanned="remplirFormulaireViaQR" />
        </div>
      </div>

      <div ref="formulaireRef">
          <FormTransfert
            :formTransfert="formTransfert"
            @submit="effectuerTransfert"
          />
      </div>

      <div class="card-historique">
        <div class="historique-header"><h3 class="section-title title-bleu">Dernières opérations</h3></div>
        <div class="liste-transactions">
          <div v-for="tx in transactions" :key="tx.id" class="transaction-item">
            <div class="tx-info">
              <span class="tx-icon">{{ tx.type === 'envoi' ? '⬆️' : '⬇️' }}</span>
              <div>
                <p class="tx-name">{{ tx.description }}</p>
                <p class="tx-date">{{ tx.date }}</p>
              </div>
            </div>
            <span class="tx-amount" :class="{ 'negative': tx.type === 'envoi', 'positive': tx.type === 'reception' }">
              {{ tx.type === 'envoi' ? '-' : '+' }} {{ tx.montant }} XOF
            </span>
          </div>

          <div v-if="!transactions || transactions.length === 0" style="text-align: center; color: gray; padding: 20px;">
            Aucune opération pour le moment.
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
