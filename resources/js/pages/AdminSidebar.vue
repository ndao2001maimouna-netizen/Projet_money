<script setup>
defineProps({
    adminInitials: { 
        type: String, 
        default: 'MN' 
    },
    adminName: { 
        type: String, 
        default: 'Maimouna Ndao' 
    }
});

const emit = defineEmits(['logout']);
</script>

<template>
    <aside style="width: 280px; background-color: #0f172a; color: #ffffff; padding: 24px; display: flex; flex-direction: column; gap: 30px;">
        <div>
            <strong style="font-size: 22px; color: #3b82f6; display: block; letter-spacing: -0.5px;">Money</strong>
            <span style="font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Administration</span>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.03); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
            <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white;">
                {{ adminInitials }}
            </div>
            <div>
                <strong style="font-size: 14px; display: block;">{{ adminName }}</strong>
                <span style="font-size: 12px; color: #94a3b8;">Super Admin</span>
            </div>
        </div>

        <nav style="display: flex; flex-direction: column; gap: 8px;">
            <p style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.5px;">MENU PRINCIPAL</p>
            <a href="#" style="display: flex; align-items: center; gap: 10px; color: white; background: #1e293b; padding: 12px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;">
                <span>▦</span> Tableau de bord
            </a>
           
        </nav>

        <div style="margin-top: auto; display: flex; flex-direction: column; gap: 15px;">
            <div style="font-size: 12px; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2); font-weight: 500;">
                ✓ Espace sécurisé
            </div>
            <button type="button" @click="emit('logout')" style="width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                Déconnexion ↩
            </button>
        </div>
    </aside>
</template>
