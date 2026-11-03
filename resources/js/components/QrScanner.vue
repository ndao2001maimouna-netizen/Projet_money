<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Html5QrcodeScanner } from 'html5-qrcode';

const emit = defineEmits(['scanned']);
const scannerRef = ref(null);
let html5QrcodeScanner = null;

onMounted(() => {
    html5QrcodeScanner = new Html5QrcodeScanner(
        "qr-reader",
        { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true
        },
       
    );
    
    html5QrcodeScanner.render((decodedText) => {
        emit('scanned', decodedText);
        html5QrcodeScanner.clear();
    }, (error) => {
    });
});

onUnmounted(() => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(error => console.error("Erreur fermeture scanner", error));
    }
});
</script>

<template>
    <div style="background: white; padding: 15px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div id="qr-reader" style="width: 100%; border: none !important;"></div>
    </div>
</template>

<style>
#qr-reader __dashboard_section_swap_camera_button, 
#qr-reader button {
    background: #2a6beb !important;
    color: white !important;
    border: none !important;
    padding: 10px 18px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    margin-top: 10px !important;
}
#qr-reader select {
    padding: 8px !important;
    border-radius: 8px !important;
    border: 1px solid #cbd5e1 !important;
    margin-bottom: 10px !important;
}
</style>
