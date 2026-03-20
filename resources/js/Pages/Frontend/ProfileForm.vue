<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProfileSettingsTab from './Components/ProfileSettingsTab.vue';
import DevicesTab from './Components/DevicesTab.vue';
import SocialProfilesTab from './Components/SocialProfilesTab.vue';
import GamesTab from './Components/GamesTab.vue';

defineProps({
    module: Number,
});

const activeTab = ref('profile');
</script>

<template>

    <Head title="Mi Espacio" />

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
                    <li class="nav-item">
                        <button class="nav-link profile-tab" :class="{ 'tab-active': activeTab === 'social_profiles' }"
                            @click="activeTab = 'social_profiles'">
                            <i class="fas fa-users me-2"></i>Perfiles Sociales
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link profile-tab" :class="{ 'tab-active': activeTab === 'games' }"
                            @click="activeTab = 'games'">
                            <i class="fas fa-gamepad me-2 text-primary"></i>Mis Juegos
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

                <!-- ==================== TAB: PERFILES SOCIALES ==================== -->
                <div v-show="activeTab === 'social_profiles'">
                    <SocialProfilesTab />
                </div>

                <!-- ==================== TAB: MIS JUEGOS ==================== -->
                <div v-show="activeTab === 'games'">
                    <GamesTab />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
