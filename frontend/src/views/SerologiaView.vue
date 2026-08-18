<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-red-9">
          🔬 Tamizaje Serológico & Control Infeccioso
        </div>
        <div class="text-caption text-grey-7">
          Validación de marcadores virales y descarte automático de unidades reactivas
        </div>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <!-- Panel de Registro de Resultados -->
      <div class="col-12 col-md-7">
        <q-card class="custom-card">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold text-red-9 row items-center">
              <q-icon name="biotech" size="sm" class="q-mr-xs" />
              1. Carga de Resultados de Marcadores Obligatorios
            </div>
          </q-card-section>
          <q-separator />

          <q-card-section class="q-gutter-md">
            <q-input
              outlined
              dense
              v-model.number="form.extraccion_id"
              type="number"
              label="N° de Extracción / Código de Tubo Piloto *"
              placeholder="Ej: 82285"
            />

            <div class="text-subtitle2 text-weight-bold text-grey-8">
              Marcadores Serológicos:
            </div>

            <q-list bordered separator class="rounded-borders">
              <q-item v-for="t in tests" :key="t.id" class="q-py-sm">
                <q-item-section avatar>
                  <q-icon name="coronavirus" color="red-9" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold">{{ t.nombre }}</q-item-label>
                  <q-item-label caption>Método: {{ t.metodo }}</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn-toggle
                    v-model="t.reactivo"
                    toggle-color="negative"
                    color="grey-4"
                    text-color="grey-9"
                    :options="[
                      { label: 'NO REACTIVO', value: false },
                      { label: 'REACTIVO ⚠️', value: true }
                    ]"
                  />
                </q-item-section>
              </q-item>
            </q-list>

            <q-input outlined dense v-model="form.observaciones" label="Observaciones de Laboratorio" />

            <q-btn
              color="red-9"
              class="full-width q-py-sm text-weight-bold shadow-2"
              icon="verified_user"
              label="Validar y Guardar Resultados"
              :loading="saving"
              @click="saveScreening"
            />
          </q-card-section>
        </q-card>
      </div>

      <!-- Protocolo de Bioseguridad y Alertas -->
      <div class="col-12 col-md-5">
        <q-card class="custom-card bg-red-1 text-red-10 q-mb-md">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold row items-center">
              <q-icon name="warning" size="sm" class="q-mr-xs text-negative" />
              Protocolo de Bloqueo Automático
            </div>
            <div class="text-caption q-mt-sm">
              Cualquier marcador marcado como <strong>REACTIVO</strong> provocará inmediatamente:
              <ul class="q-pl-md q-my-xs">
                <li>Bloqueo y descarte de todos los hemocomponentes derivados.</li>
                <li>Inhabilitación / Diferimiento del donante en el padrón.</li>
                <li>Registro en el libro epidemiológico del PNS / SNIS.</li>
              </ul>
            </div>
          </q-card-section>
        </q-card>

        <q-card class="custom-card" v-if="lastResult">
          <q-card-section class="text-center">
            <q-avatar size="60px" :color="lastResult.status === 'REACTIVE' ? 'negative' : 'positive'" text-color="white">
              <q-icon :name="lastResult.status === 'REACTIVE' ? 'block' : 'check_circle'" size="36px" />
            </q-avatar>
            <div class="text-h6 text-weight-bold q-mt-sm" :class="lastResult.status === 'REACTIVE' ? 'text-negative' : 'text-positive'">
              {{ lastResult.status === 'REACTIVE' ? 'DESCARTE REGISTRADO' : 'LIBERACIÓN CONFORME' }}
            </div>
            <div class="text-caption text-grey-8 q-mt-xs">
              {{ lastResult.message }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const tests = ref([])
const saving = ref(false)
const lastResult = ref(null)

const form = ref({
  extraccion_id: 82285,
  observaciones: ''
})

async function fetchTests() {
  try {
    const res = await api.get('/serologia/pruebas')
    if (res.data.success) {
      tests.value = res.data.tests.map(t => ({ ...t, reactivo: false }))
    }
  } catch (e) {}
}

async function saveScreening() {
  if (!form.value.extraccion_id) {
    Notify.create({ type: 'warning', message: 'Ingrese el número de extracción' })
    return
  }
  saving.value = true
  try {
    const res = await api.post('/serologia/guardar', {
      extraccion_id: form.value.extraccion_id,
      results: tests.value,
      observaciones: form.value.observaciones
    })
    if (res.data.success) {
      lastResult.value = res.data
      Notify.create({
        type: res.data.status === 'REACTIVE' ? 'warning' : 'positive',
        message: res.data.message
      })
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al registrar serología' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchTests()
})
</script>
