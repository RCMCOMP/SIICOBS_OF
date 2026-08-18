<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-pink-9">
          🩺 Triaje, Signos Vitales & Cuestionario Confidencial
        </div>
        <div class="text-caption text-grey-7">
          Evaluación clínica y entrevista médica previa a la extracción de sangre
        </div>
      </div>
      <q-btn outline color="pink-9" icon="history" label="Ver Donantes Diferidos/Rechazados" @click="tab = 'rechazos'" />
    </div>

    <q-tabs v-model="tab" dense class="text-grey" active-color="pink-9" indicator-color="pink-9" align="left">
      <q-tab name="evaluacion" icon="medical_services" label="Nueva Evaluación Clínica" />
      <q-tab name="rechazos" icon="block" label="Historial de Rechazos & Diferimientos" />
    </q-tabs>

    <q-separator class="q-mb-md" />

    <!-- Tab 1: Formulario de Evaluación -->
    <div v-if="tab === 'evaluacion'">
      <q-card class="custom-card q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold text-pink-9 q-mb-sm">
            1. Selección de Donante a Evaluar
          </div>
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-md-8">
              <q-input
                outlined
                dense
                v-model="donorSearch"
                placeholder="Ingrese C.I. o Nombre del donante..."
                @keyup.enter="searchDonor"
              >
                <template v-slot:append>
                  <q-btn flat round dense icon="search" color="pink-9" @click="searchDonor" :loading="searchingDonor" />
                </template>
              </q-input>
            </div>
            <div class="col-12 col-md-4" v-if="selectedDonor">
              <q-chip color="pink-1" text-color="pink-10" icon="check_circle">
                {{ selectedDonor.nombre }} {{ selectedDonor.primer_apellido }} (C.I.: {{ selectedDonor.doc_identidad }})
              </q-chip>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <div class="row q-col-gutter-md" v-if="selectedDonor">
        <!-- Signos Vitales -->
        <div class="col-12 col-md-6">
          <q-card class="custom-card fit">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-pink-9 row items-center">
                <q-icon name="favorite" size="sm" class="q-mr-xs" />
                2. Examen Físico & Signos Vitales
              </div>
            </q-card-section>
            <q-card-section class="q-gutter-sm">
              <div class="row q-col-gutter-sm">
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.peso" type="number" label="Peso (Kg) *" suffix="kg" />
                </div>
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.talla" type="number" label="Talla (cm)" suffix="cm" />
                </div>
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.presion_sistolica" type="number" label="P.A. Sistólica *" suffix="mmHg" />
                </div>
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.presion_diastolica" type="number" label="P.A. Diastólica *" suffix="mmHg" />
                </div>
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.pulso" type="number" label="Pulso / F.C." suffix="ppm" />
                </div>
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.temperatura" type="number" step="0.1" label="Temperatura" suffix="°C" />
                </div>
              </div>

              <div class="row q-col-gutter-sm">
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.hemoglobina" type="number" step="0.1" label="Hemoglobina (Hb) *" suffix="g/dL" />
                </div>
                <div class="col-6">
                  <q-input outlined dense v-model.number="form.hematocrito" type="number" step="0.1" label="Hematocrito (Hto) *" suffix="%" />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Cuestionario y Dictamen -->
        <div class="col-12 col-md-6">
          <q-card class="custom-card fit">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-pink-9 row items-center">
                <q-icon name="assignment" size="sm" class="q-mr-xs" />
                3. Dictamen de Aptitud Clínica
              </div>
            </q-card-section>
            <q-card-section class="q-gutter-sm">
              <div class="q-pa-sm rounded-borders" :class="form.apto ? 'bg-green-1 text-green-10' : 'bg-red-1 text-red-10'">
                <q-toggle
                  v-model="form.apto"
                  :label="form.apto ? 'DONANTE APTO PARA FLEBOTOMÍA' : 'DONANTE NO APTO / DIFERIDO'"
                  :color="form.apto ? 'positive' : 'negative'"
                  class="text-weight-bold"
                />
              </div>

              <div v-if="!form.apto" class="q-mt-sm">
                <q-input
                  outlined
                  dense
                  v-model="form.motivo_rechazo"
                  type="textarea"
                  rows="3"
                  label="Motivo del Diferimiento / Rechazo *"
                  placeholder="Ej: Hemoglobina baja (< 13.5 g/dL), Tratamiento antibiótico reciente, Cirugía..."
                />
              </div>

              <div class="q-mt-md">
                <q-btn
                  color="pink-9"
                  class="full-width q-py-sm text-weight-bold shadow-2"
                  label="Guardar Evaluación Médica"
                  icon="save"
                  :loading="saving"
                  @click="saveEvaluation"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Tab 2: Historial de Rechazos -->
    <div v-if="tab === 'rechazos'">
      <q-card class="custom-card">
        <q-table
          flat
          bordered
          :rows="rejections"
          :columns="rejectionColumns"
          row-key="id"
          :loading="loadingRejections"
        >
          <template v-slot:body-cell-motivo="props">
            <q-td :props="props">
              <span class="text-weight-bold text-negative">{{ props.row.motivo }}</span>
            </q-td>
          </template>
        </q-table>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const tab = ref('evaluacion')
const donorSearch = ref('')
const searchingDonor = ref(false)
const selectedDonor = ref(null)
const saving = ref(false)
const loadingRejections = ref(false)
const rejections = ref([])

const form = ref({
  donor_id: null,
  peso: 68,
  talla: 170,
  presion_sistolica: 120,
  presion_diastolica: 80,
  pulso: 75,
  temperatura: 36.5,
  hemoglobina: 15.2,
  hematocrito: 46.0,
  apto: true,
  motivo_rechazo: ''
})

const rejectionColumns = [
  { name: 'id', label: 'N°', field: 'id', align: 'left' },
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
  { name: 'donante', label: 'Donante', field: 'donante', align: 'left' },
  { name: 'doc_identidad', label: 'C.I.', field: 'doc_identidad', align: 'left' },
  { name: 'motivo', label: 'Causa de Rechazo / Diferimiento', field: 'motivo', align: 'left' },
  { name: 'evaluador', label: 'Médico Evaluador', field: 'evaluador', align: 'left' }
]

async function searchDonor() {
  if (!donorSearch.value.trim()) return
  searchingDonor.value = true
  try {
    const res = await api.get('/donantes', { params: { search: donorSearch.value.trim(), per_page: 1 } })
    if (res.data.success && res.data.result.data.length > 0) {
      selectedDonor.value = res.data.result.data[0]
      form.value.donor_id = selectedDonor.value.id
      Notify.create({ type: 'positive', message: `Donante seleccionado: ${selectedDonor.value.nombre}` })
    } else {
      Notify.create({ type: 'warning', message: 'No se encontró ningún donante con ese criterio' })
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error en la búsqueda de donante' })
  } finally {
    searchingDonor.value = false
  }
}

async function saveEvaluation() {
  if (!form.value.donor_id) {
    Notify.create({ type: 'warning', message: 'Seleccione un donante primero' })
    return
  }
  saving.value = true
  try {
    const res = await api.post('/triaje/evaluar', form.value)
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      selectedDonor.value = null
      donorSearch.value = ''
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: e.response?.data?.message || 'Error al registrar evaluación' })
  } finally {
    saving.value = false
  }
}

async function fetchRejections() {
  loadingRejections.value = true
  try {
    const res = await api.get('/triaje/rechazos')
    if (res.data.success) {
      rejections.value = res.data.result.data
    }
  } catch (e) {}
  finally {
    loadingRejections.value = false
  }
}

onMounted(() => {
  fetchRejections()
})
</script>
