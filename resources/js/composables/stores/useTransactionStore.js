import { ref } from 'vue';
import { defineStore } from 'pinia';
export const useTransactionStore = defineStore('transaction', () => {

    const transacts = ref([]);

    function filterTransacts(listeTransactions, elements) {
        if (!elements?.search) return listeTransactions;
        
        const searchLower = elements.search.toLowerCase();
        
        return listeTransactions.filter((transact) => {
            return (
                (transact.nom && transact.nom.toLowerCase().includes(searchLower)) || 
                (transact.prenom && transact.prenom.toLowerCase().includes(searchLower)) || 
                (transact.telephone && transact.telephone.includes(searchLower))
            );
        });
    }

    function pagination(listeTransacts, pageActuelle = 1, elementsParPage = 10) {
        const longueurTab = listeTransacts.length;
        const nbrPage = Math.ceil(longueurTab / elementsParPage); 
        
        let page = pageActuelle;
        if (page > nbrPage) {
            page = nbrPage;
        }
        if (page < 1) {
            page = 1;
        }

        const indexDebut = (page - 1) * elementsParPage;
        const indexFin = indexDebut + elementsParPage;

        const donneesPaginees = listeTransacts.slice(indexDebut, indexFin);

        return {
            data: donneesPaginees,
            page_actuelle: page,
            total_pages: nbrPage,
            total_elements: longueurTab
        };
    }

    return {
        transacts,
        filterTransacts,
        pagination
    };

});
