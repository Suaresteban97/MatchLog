<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProfileSettingsTab from './Components/ProfileSettingsTab.vue';
import DevicesTab from './Components/DevicesTab.vue';

defineProps({
    module: Number,
});

const activeTab = ref('profile');
</script>

<template>

    <Head title="Perfil" />

    <AppLayout v-cloak>
        <div class="row pt-4 justify-content-center">
            <div class="col-12 col-md-10">

                <!-- Tabs nav -->
                <ul class="nav nav-tabs mb-3" style="border-bottom: 2px solid var(--neon-cyan);">
                    <li class="nav-item">
                        <button class="nav-link profile-tab" :class="{ 'tab-active': activeTab === 'profile' }"
                            @click="activeTab = 'profile'">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link profile-tab" :class="{ 'tab-active': activeTab === 'devices' }"
                            @click="activeTab = 'devices'">
                            <i class="fas fa-gamepad me-2"></i>Mis Dispositivos
                        </button>
                    </li>
                </ul>

                <!-- ==================== TAB: PERFIL ==================== -->
                <div v-show="activeTab === 'profile'">
                    <ProfileSettingsTab />
                </div>

                <!-- ==================== TAB: DISPOSITIVOS ==================== -->
                <div v-show="activeTab === 'devices'">
                    <DevicesTab />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
