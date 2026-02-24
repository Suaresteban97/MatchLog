<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    module: Number,
});
</script>

<template>
    <Head title="Mi Espacio" />

    <AppLayout v-cloak>
        <div class="row pt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white">
                        <i class="fas fa-user-astronaut me-2"></i>Mi Espacio
                    </div>
                    <div class="card-body">
                        <h3>Bienvenido a Mi Espacio!</h3>
                        <p class="text-muted">Módulo Actual: {{ module }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
