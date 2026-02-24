<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    module: Number,
});
</script>

<template>
    <Head title="Social Dashboard" />

    <AppLayout v-cloak>
        <div class="row pt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white">
                        <i class="fas fa-chart-line me-2"></i>Dashboard Social
                    </div>
                    <div class="card-body">
                        <h3>Bienvenido a MyBackLog!</h3>
                        <p class="text-muted">Módulo Actual: {{ module }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
