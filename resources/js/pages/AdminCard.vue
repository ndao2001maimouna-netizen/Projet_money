<script setup>
import { useForm, router, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminSidebar from './AdminSidebar.vue';
import FormDepotAdmin from './FormDepotAdmin.vue';
import { useTransactionStore } from '@/composables/stores/useTransactionStore.js';

const props = defineProps({
    auth: {
        type: Object,
        default: () => ({ user: null })
    },
    distributeurs: {
        type: Array,
        default: () => []
    }
});

const transactionStores = useTransactionStore();
const formDepot = useForm({
    distributeur_telephone: '',
    montant: '',
});

const formDistributeur = useForm({
    nom: '',
    prenom: '',
    telephone: '',
    code_secret: '',
});

const recherche = ref('');
const pageActuelle = ref(1);

watch(
    () => props.distributeurs,
    (distributeurs) => {
        transactionStores.transacts = distributeurs;
        pageActuelle.value = 1;
    },
    { immediate: true },
);

const distributeursFiltres = computed(() =>
    transactionStores.filterTransacts(transactionStores.transacts, {
        search: recherche.value,
    }),
);
const distributeursPaginees = computed(() =>
    transactionStores.pagination(
        distributeursFiltres.value,
        pageActuelle.value,
        3,
    ),
);

watch(recherche, () => {
    pageActuelle.value = 1;
});

function changerPage(page) {
    pageActuelle.value = page;
}

function executerDepot() {
    formDepot.post('/admin/depot', {
        onSuccess: () => formDepot.reset(),
    });
}

function executerCreationDistributeur() {
    formDistributeur.post('/admin/distributeur', {
        onSuccess: () => formDistributeur.reset(),
    });
}

function deconnexion() {
    router.post('/logout');
}
</script>

<template>
    <div style="display: flex; min-height: 100vh; background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1e293b; margin: 0;">
        
        <AdminSidebar 
            adminName="Maimouna Ndao"
            adminInitials="MN"
            @logout="deconnexion"
        />

        <main style="flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 30px; overflow-y: auto;">
            <header style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px;">
                <div>
                    <span style="font-size: 13px; color: #64748b;">Administration / Tableau de bord</span>
                    <h1 style="margin: 5px 0 0 0; font-size: 28px; font-weight: 700; color: #0f172a;">Vue générale</h1>
                </div>
                <div style="background: white; padding: 8px 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-size: 14px; font-weight: 500; color: #64748b; border: 1px solid #e2e8f0;">
                    ◷ {{ new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date()) }}
                </div>
            </header>

            <section style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(15,23,42,0.08);">
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; tracking: 1px; color: #3b82f6;">Espace Superadmin</span>
                <h2 style="margin: 10px 0; font-size: 24px; font-weight: 700;">Bienvenue, Maimouna Ndao 👋</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 15px; max-width: 600px;">Gérez vos gérants distributeurs, supervisez les comptes et suivez les opérations en temps réel de la plateforme Money.</p>
            </section>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; align-items: start;">
                <FormDepotAdmin :formDepot="formDepot" @submit="executerDepot" />

                <section style="background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                    <h3 style="margin: 0 0 5px 0; font-size: 18px; font-weight: 600; color: #0f172a;">Créer un gérant distributeur</h3>
                    <p style="margin: 0 0 20px 0; font-size: 14px; color: #64748b;">Enregistrez un nouvel agent distributeur officiel sur la plateforme.</p>
                    
                    <form @submit.prevent="executerCreationDistributeur" style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 14px; font-weight: 500; color: #334155;">Prénom</label>
                                <input v-model="formDistributeur.prenom" type="text" placeholder="Ex: Sidy" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <label style="font-size: 14px; font-weight: 500; color: #334155;">Nom</label>
                                <input v-model="formDistributeur.nom" type="text" placeholder="Ex: Ndiaye" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 14px; font-weight: 500; color: #334155;">Numéro de téléphone</label>
                            <input v-model="formDistributeur.telephone" type="text" placeholder="Ex: 77 123 45 67" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 14px; font-weight: 500; color: #334155;">Code secret (4 chiffres)</label>
                            <input v-model="formDistributeur.code_secret" type="password" maxlength="4" placeholder="••••" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; outline: none; background-color: #f8fafc;" required />
                        </div>
                        <button type="submit" :disabled="formDistributeur.processing" style="padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; align-self: flex-start; box-shadow: 0 4px 12px rgba(16,185,129,0.2); margin-top: 5px;">
                            {{ formDistributeur.processing ? 'Création...' : '+ Créer le distributeur' }}
                        </button>
                    </form>
                </section>
            </div>

            <section style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-top: 5px;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #0f172a;">Liste des Gérants Distributeurs</h3>
                    
                    <div style="position: relative; width: 100%; max-width: 300px;">
                        <input 
                            v-model="recherche" 
                            type="text" 
                            placeholder=" Rechercher " 
                            style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; outline: none; background-color: #f8fafc; box-sizing: border-box;"
                        />
                    </div>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #edf2f7; color: #64748b; font-weight: 600;">
                                <th style="padding: 12px 10px;">Nom Complet</th>
                                <th style="padding: 12px 10px;">Téléphone</th>
                                <th style="padding: 12px 10px;">Solde Caisse</th>
                                <th style="padding: 12px 10px;">Date d'ajout</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="distributeur in distributeursPaginees.data" :key="distributeur.id" style="border-bottom: 1px solid #f1f5f9; color: #334155;">
                                <td style="padding: 14px 10px; font-weight: 500; color: #0f172a;">{{ distributeur.prenom }} {{ distributeur.nom }}</td>
                                <td style="padding: 14px 10px;">{{ distributeur.telephone }}</td>
                                <td style="padding: 14px 10px; font-weight: 600; color: #2a6beb;">{{ distributeur.solde }} XOF</td>
                                <td style="padding: 14px 10px; color: #64748b;">{{ distributeur.date_creation }}</td>
                            </tr>
                            <tr v-if="distributeursFiltres.length === 0">
                                <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Aucun résultat trouvé pour votre recherche.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="distributeursPaginees.total_pages > 1" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; color: #64748b; font-size: 13px;">
                    <button
                        type="button"
                        :disabled="distributeursPaginees.page_actuelle <= 1"
                        @click="changerPage(distributeursPaginees.page_actuelle - 1)"
                    >
                        Précédent
                    </button>
                    <span>
                        Page {{ distributeursPaginees.page_actuelle }} sur
                        {{ distributeursPaginees.total_pages }}
                        ({{ (distributeursPaginees.page_actuelle - 1) * 3 + 1 }}–{{ Math.min(distributeursPaginees.page_actuelle * 3, distributeursPaginees.total_elements) }}
                        sur {{ distributeursPaginees.total_elements }})
                    </span>
                    <button
                        type="button"
                        :disabled="distributeursPaginees.page_actuelle >= distributeursPaginees.total_pages"
                        @click="changerPage(distributeursPaginees.page_actuelle + 1)"
                    >
                        Suivant
                    </button>
                </div>
            </section>
        </main>
    </div>
</template>
