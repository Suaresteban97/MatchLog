<template>
  <!-- Inline trigger only — takes zero layout space -->
  <button class="cf-trigger" @click="open = true" :aria-label="`Proponer corrección en ${resourceLabel}`">
    <i class="fas fa-pen-to-square"></i>
    Proponer corrección
  </button>

  <!-- Modal teleported to <body> — fully decoupled from document flow -->
  <Teleport to="body">
    <Transition name="cf-modal">
      <div v-if="open" class="cf-backdrop" @click.self="reset" role="dialog" aria-modal="true" aria-labelledby="cf-modal-title">

        <div class="cf-modal">
          <!-- Header -->
          <div class="cf-modal__header">
            <div class="cf-modal__pretitle">
              <i class="fas fa-pen-to-square"></i> Proponer corrección
            </div>
            <h3 id="cf-modal-title" class="cf-modal__title">
              {{ resourceLabel }}
            </h3>
            <button class="cf-modal__close" @click="reset" aria-label="Cerrar modal">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Body -->
          <div class="cf-modal__body">

            <!-- ── Success state ──────────────────────────────────── -->
            <div v-if="sent" class="cf-feedback cf-feedback--success">
              <span class="cf-feedback__icon">✅</span>
              <div>
                <strong>¡Contribución enviada!</strong>
                <p>Tu propuesta está pendiente de revisión por el equipo de moderación.</p>
              </div>
            </div>

            <!-- ── Form state ─────────────────────────────────────── -->
            <form v-else @submit.prevent="handleSubmit" novalidate>

              <!-- Field selector -->
              <div class="cf-field">
                <label class="cf-label" for="cf-select-field">
                  Campo a modificar <span class="cf-required">*</span>
                </label>
                <select id="cf-select-field" v-model="form.field" class="cf-select" :disabled="loading" required>
                  <option value="" disabled>Selecciona un campo…</option>
                  <option v-for="opt in availableFields" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <!-- Current value snapshot -->
              <div v-if="form.field && currentData[form.field] !== undefined" class="cf-field">
                <label class="cf-label">Valor actual</label>
                <div class="cf-snapshot">{{ currentData[form.field] || '(vacío)' }}</div>
              </div>

              <!-- Proposed value -->
              <div class="cf-field">
                <label class="cf-label" for="cf-proposed-value">
                  Valor propuesto <span class="cf-required">*</span>
                </label>
                <textarea
                  id="cf-proposed-value"
                  v-model="form.proposedValue"
                  class="cf-textarea"
                  rows="4"
                  placeholder="Escribe el nuevo valor aquí…"
                  :disabled="loading"
                  required
                  maxlength="5000"
                />
                <span class="cf-char-count">{{ form.proposedValue.length }} / 5000</span>
              </div>

              <!-- Error -->
              <div v-if="error" class="cf-feedback cf-feedback--error">
                <span class="cf-feedback__icon">⚠️</span>
                <p>{{ error }}</p>
              </div>

              <!-- Actions -->
              <div class="cf-actions">
                <button type="button" class="cf-btn cf-btn--ghost" @click="reset" :disabled="loading">
                  Cancelar
                </button>
                <button type="submit" class="cf-btn cf-btn--primary"
                  :disabled="loading || !form.field || !form.proposedValue.trim()">
                  <span v-if="loading" class="cf-spinner" aria-hidden="true" />
                  {{ loading ? 'Enviando…' : 'Enviar propuesta' }}
                </button>
              </div>
            </form>

          </div><!-- /.cf-modal__body -->
        </div><!-- /.cf-modal -->

      </div><!-- /.cf-backdrop -->
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { useContributions } from '../../../Composables/useContributions';

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps({
  /** 'game' | 'genre' | 'platform' */
  resourceType: {
    type: String,
    required: true,
    validator: (v) => ['game', 'genre', 'platform'].includes(v),
  },
  resourceId: {
    type: Number,
    required: true,
  },
  resourceLabel: {
    type: String,
    default: 'recurso',
  },
  currentData: {
    type: Object,
    default: () => ({}),
  },
});

// ── Composable ────────────────────────────────────────────────────────────────

const { submitContribution, loading, error } = useContributions();

// ── Local state ───────────────────────────────────────────────────────────────

const open = ref(false);
const sent = ref(false);

const form = reactive({
  field: '',
  proposedValue: '',
});

// Lock body scroll while modal is open
watch(open, (val) => {
  document.body.style.overflow = val ? 'hidden' : '';
});

// ── Computed ──────────────────────────────────────────────────────────────────

const FIELD_LABELS = {
  name: 'Nombre',
  description: 'Descripción',
  cover_image_url: 'URL de portada',
  release_date: 'Fecha de lanzamiento',
  developer: 'Desarrollador',
  publisher: 'Publisher',
  metacritic_score: 'Puntuación Metacritic',
  max_players: 'Jugadores máximos',
  slug: 'Slug',
};

const availableFields = computed(() =>
  Object.keys(props.currentData).map((key) => ({
    value: key,
    label: FIELD_LABELS[key] ?? key,
  }))
);

// ── Methods ───────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!form.field || !form.proposedValue.trim()) return;
  try {
    await submitContribution(props.resourceType, props.resourceId, form.field, form.proposedValue.trim());
    sent.value = true;
  } catch (err) {
    console.error('ContributionForm submit error:', err);
  }
}

function reset() {
  open.value = false;
  sent.value = false;
  form.field = '';
  form.proposedValue = '';
}
</script>