<script setup>
import { router, useForm } from '@inertiajs/vue3';

defineProps({
    auth: {
        type: Object,
        default: () => ({ user: null }),
    },
    operations: {
        type: Array,
        default: () => [],
    },
});

const formDepotClient = useForm({
    type_destinataire: 'client',
    client_telephone: '',
    montant: '',
});

const formDepotSousDistributeur = useForm({
    type_destinataire: 'distributeur',
    client_telephone: '',
    montant: '',
});

const formSousDistributeur = useForm({
    nom: '',
    prenom: '',
    telephone: '',
    code_secret: '',
});

function effectuerDepot() {
    formDepotClient.post('/depot', {
        onSuccess: () => formDepotClient.reset(),
    });
}

function crediterSousDistributeur() {
    formDepotSousDistributeur.post('/depot', {
        onSuccess: () => formDepotSousDistributeur.reset(),
    });
}

function creerSousDistributeur() {
    formSousDistributeur.post('/distributeur/sous-gerant', {
        onSuccess: () => formSousDistributeur.reset(),
    });
}

function deconnexion() {
    router.post('/logout');
}
</script>

<template>
    <div class="global-wrapper" style="min-height: 100vh; background: #f4f7fe; padding: 40px 20px; font-family: 'Inter', sans-serif;">
        <div class="app-container" style="max-width: 450px; margin: 0 auto; display: flex; flex-direction: column; gap: 25px;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 18px 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                <div class="user-info" style="display: flex; align-items: center; gap: 14px;">
                    <div class="avatar" style="width: 46px; height: 46px; background: linear-gradient(135deg, #2a6beb, #184fa2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 15px;">
                        {{ auth?.user?.prenom?.charAt(0) }}{{ auth?.user?.nom?.charAt(0) }}
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; color: #0f172a; font-weight: 600;">
                            {{ auth?.user?.prenom }} {{ auth?.user?.nom }}
                        </h3>
                        <span style="font-size: 13px; color: #64748b;">
                            {{ auth?.user?.telephone }}
                        </span>
                    </div>
                </div>
                <span style="color: #2a6beb; background: rgba(42, 107, 235, 0.08); padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600;">
                    Gérant Distributeur
                </span>
            </div>

            <div class="card-solde solde-bleu" style="background: linear-gradient(135deg, #2a6beb, #1a4fa8); color: white; padding: 30px; border-radius: 20px; box-shadow: 0 15px 30px rgba(42, 107, 235, 0.25);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <p style="margin: 0; font-size: 14px; opacity: 0.85;">Caisse Distributeur</p>
                    <span style="background: rgba(255, 255, 255, 0.18); padding: 4px 10px; border-radius: 6px; font-size: 11px;">XOF</span>
                </div>
                <h2 style="margin: 0; font-size: 36px; font-weight: 700;">{{ auth?.user?.solde ?? 0 }}</h2>
            </div>

            <section style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                <h3 style="margin: 0 0 5px; font-size: 16px; font-weight: 600; color: #0f172a;">Effectuer un dépôt</h3>
                <p style="margin: 0 0 20px; font-size: 13px; color: #64748b;">
                    Le dépôt Client.
                </p>

                <form @submit.prevent="effectuerDepot" style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="client-telephone" style="font-size: 13px; font-weight: 500; color: #334155;">Téléphone du client</label>
                        <input
                            id="client-telephone"
                            v-model="formDepotClient.client_telephone"
                            type="text"
                            placeholder="Ex: 77 123 45 67"
                            required
                            style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc;"
                        />
                        <p v-if="formDepotClient.errors.client_telephone" style="margin: 4px 0 0; color: #ef4444; font-size: 12px;">
                            {{ formDepotClient.errors.client_telephone }}
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="depot-montant" style="font-size: 13px; font-weight: 500; color: #334155;">Montant (XOF)</label>
                        <input
                            id="depot-montant"
                            v-model="formDepotClient.montant"
                            type="number"
                            min="1"
                            step="0.01"
                            placeholder="Ex: 5000"
                            required
                            style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc;"
                        />
                        <p v-if="formDepotClient.errors.montant" style="margin: 4px 0 0; color: #ef4444; font-size: 12px;">
                            {{ formDepotClient.errors.montant }}
                        </p>
                    </div>

                    <button type="submit" :disabled="formDepotClient.processing" style="width: 100%; padding: 12px; background: #22c55e; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        {{ formDepotClient.processing ? 'Traitement...' : 'Confirmer le dépôt client' }}
                    </button>
                </form>
            </section>

            <section style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                <h3 style="margin: 0 0 5px; font-size: 16px; font-weight: 600; color: #0f172a;">Créer un sous-distributeur</h3>
                <p style="margin: 0 0 20px; font-size: 13px; color: #64748b;">
                    Le sous-distributeur doit avoir été créé par vous.
                </p>

                <form @submit.prevent="crediterSousDistributeur" style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="sous-distributeur-telephone" style="font-size: 13px; font-weight: 500; color: #334155;">Téléphone du sous-distributeur</label>
                        <input
                            id="sous-distributeur-telephone"
                            v-model="formDepotSousDistributeur.client_telephone"
                            type="text"
                            placeholder="Ex: 77 123 45 67"
                            required
                            style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc;"
                        />
                        <p v-if="formDepotSousDistributeur.errors.client_telephone" style="margin: 4px 0 0; color: #ef4444; font-size: 12px;">
                            {{ formDepotSousDistributeur.errors.client_telephone }}
                        </p>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="sous-distributeur-montant" style="font-size: 13px; font-weight: 500; color: #334155;">Montant (XOF)</label>
                        <input
                            id="sous-distributeur-montant"
                            v-model="formDepotSousDistributeur.montant"
                            type="number"
                            min="1"
                            step="0.01"
                            placeholder="Ex: 5000"
                            required
                            style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: #f8fafc;"
                        />
                        <p v-if="formDepotSousDistributeur.errors.montant" style="margin: 4px 0 0; color: #ef4444; font-size: 12px;">
                            {{ formDepotSousDistributeur.errors.montant }}
                        </p>
                    </div>

                    <button type="submit" :disabled="formDepotSousDistributeur.processing" style="width: 100%; padding: 12px; background: #2a6beb; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        {{ formDepotSousDistributeur.processing ? 'Traitement...' : 'Confirmer le crédit' }}
                    </button>
                </form>
            </section>

            <section style="background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
                <h3 style="margin: 0 0 5px; font-size: 16px; font-weight: 600; color: #0f172a;">Créer un sous-distributeur</h3>
                <p style="margin: 0 0 20px; font-size: 13px; color: #64748b;">
                    Ajouter un sous-distributeur
                </p>

                <form @submit.prevent="creerSousDistributeur" style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #334155;">
                            Prénom
                            <input v-model="formSousDistributeur.prenom" type="text" required style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" />
                            <span v-if="formSousDistributeur.errors.prenom" style="color: #ef4444;">{{ formSousDistributeur.errors.prenom }}</span>
                        </label>
                        <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #334155;">
                            Nom
                            <input v-model="formSousDistributeur.nom" type="text" required style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" />
                            <span v-if="formSousDistributeur.errors.nom" style="color: #ef4444;">{{ formSousDistributeur.errors.nom }}</span>
                        </label>
                    </div>
                    <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #334155;">
                        Téléphone
                        <input v-model="formSousDistributeur.telephone" type="tel" placeholder="77 123 45 67" required style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" />
                        <span v-if="formSousDistributeur.errors.telephone" style="color: #ef4444;">{{ formSousDistributeur.errors.telephone }}</span>
                    </label>
                    <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #334155;">
                        Code secret (4 chiffres)
                        <input v-model="formSousDistributeur.code_secret" type="password" inputmode="numeric" maxlength="4" required style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;" />
                        <span v-if="formSousDistributeur.errors.code_secret" style="color: #ef4444;">{{ formSousDistributeur.errors.code_secret }}</span>
                    </label>
                    <button type="submit" :disabled="formSousDistributeur.processing" style="padding: 12px; background: #2a6beb; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        {{ formSousDistributeur.processing ? 'Création...' : 'Créer le sous-distributeur' }}
                    </button>
                </form>
            </section>

            <section style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <h3 style="margin: 0 0 15px; color: #333;">Derniers dépôts</h3>
                <div v-for="operation in operations" :key="operation.id" style="display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                    <div>
                        <p style="margin: 0; color: #1e293b; font-weight: 600;">{{ operation.description }}</p>
                        <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">{{ operation.date }}</p>
                    </div>
                    <span
                        :style="{
                            whiteSpace: 'nowrap',
                            color: operation.type === 'reception' ? '#16a34a' : '#dc2626',
                            fontWeight: 600,
                        }"
                    >
                        {{ operation.type === 'reception' ? '+' : '-' }} {{ operation.montant }} XOF
                    </span>
                </div>
                <p v-if="operations.length === 0" style="margin: 0; padding: 12px 0; text-align: center; color: #94a3b8;">
                    Aucun dépôt pour le moment.
                </p>
            </section>

            <button @click="deconnexion" style="background: #fef2f2; border: 1px solid #fee2e2; color: #ef4444; font-size: 13px; font-weight: 600; cursor: pointer; padding: 10px 20px; border-radius: 10px; width: 100%;">
                Se déconnecter
            </button>
        </div>
    </div>
</template>
