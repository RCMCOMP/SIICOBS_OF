<template>
  <q-page class="q-pa-md">
    <!-- Encabezado -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-deep-orange-9">
          💉 Flebotomía, Extracción & Asignación de Bolsas
        </div>
        <div class="text-caption text-grey-7">
          Registro de punción, control de volumen y emisión de códigos de barras / QR
        </div>
      </div>
      <q-btn color="deep-orange-9" icon="add" label="Nueva Extracción" @click="openExtractionDialog" />
    </div>

    <!-- Buscador -->
    <q-card class="custom-card q-mb-md">
      <q-card-section class="q-py-sm">
        <div class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-6">
            <q-input
              outlined
              dense
              v-model="searchTerm"
              placeholder="Buscar extracción por C.I., Donante o N° Extracción..."
              @keyup.enter="fetchExtractions(1)"
              clearable
              @clear="fetchExtractions(1)"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6 text-right">
            <q-btn outline color="deep-orange-9" icon="refresh" label="Recargar" @click="fetchExtractions(currentPage)" :loading="loading" />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabla de Extracciones -->
    <q-card class="custom-card">
      <q-table
        flat
        bordered
        :rows="extractions"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-id="props">
          <q-td :props="props" class="text-weight-bold text-primary">
            #EX-{{ String(props.row.id).padStart(6, '0') }}
          </q-td>
        </template>

        <template v-slot:body-cell-grupo_sanguineo="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_sanguineo || 'Pendiente' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-volumen_ml="props">
          <q-td :props="props" class="text-right">
            {{ props.row.volumen_ml }} ml
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center">
            <q-btn color="deep-orange-9" flat dense round icon="qr_code" @click="printLabel(props.row.id)">
              <q-tooltip>Imprimir Etiqueta con Código de Barras & QR</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>

      <!-- Paginación -->
      <div class="row items-center justify-between q-pa-md">
        <div class="text-caption text-grey-7">
          Página {{ currentPage }} de {{ lastPage }}
        </div>
        <q-pagination
          v-model="currentPage"
          :max="lastPage"
          :max-pages="7"
          direction-links
          boundary-links
          color="deep-orange-9"
          @update:model-value="fetchExtractions"
        />
      </div>
    </q-card>

    <!-- Modal Nueva Extracción -->
    <q-dialog v-model="extractionDialog" persistent>
      <q-card style="min-width: 600px; max-width: 700px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-deep-orange-9">
            Registrar Nueva Flebotomía / Extracción
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input
            outlined
            dense
            v-model="donorSearchInput"
            label="Buscar Donante Aprobado (C.I. o Nombre) *"
            @keyup.enter="searchApprovedDonor"
          >
            <template v-slot:append>
              <q-btn flat round dense icon="search" color="deep-orange-9" @click="searchApprovedDonor" />
            </template>
          </q-input>

          <div v-if="selectedDonorForExt" class="q-pa-sm bg-orange-1 text-orange-10 rounded-borders row items-center justify-between">
            <div>
              <div class="text-weight-bold">{{ selectedDonorForExt.nombre }} {{ selectedDonorForExt.primer_apellido }}</div>
              <div class="text-caption">C.I.: {{ selectedDonorForExt.doc_identidad }}</div>
            </div>
            <q-badge color="positive">Apto</q-badge>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-select
                outlined
                dense
                v-model="form.tipo_bolsa_id"
                :options="bagOptions"
                option-value="id"
                option-label="descripcion"
                emit-value
                map-options
                label="Tipo de Bolsa de Sangre *"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-select
                outlined
                dense
                v-model="form.grupo_sanguineo_id"
                :options="groupOptions"
                option-value="id"
                option-label="nombre"
                emit-value
                map-options
                label="Grupo Sanguíneo ABO/Rh *"
              />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model.number="form.volumen_ml" type="number" label="Volumen Extraído (ml) *" suffix="ml" />
            </div>
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model="form.hora_inicio" label="Hora Inicio" />
            </div>
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model="form.hora_fin" label="Hora Finalización" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="deep-orange-9" label="Guardar & Generar Códigos" @click="saveExtraction" :loading="saving" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Impresión de Etiqueta con Código de Barras y QR -->
    <q-dialog v-model="labelDialog">
      <q-card style="min-width: 420px; max-width: 480px;" class="q-pa-md" v-if="currentLabel">
        <div class="text-center" id="printable-label">
          <div class="text-caption text-weight-bold text-uppercase">BANCO DE SANGRE DEPARTAMENTAL ORURO</div>
          <div class="text-h6 text-weight-bolder text-primary q-my-xs">
            {{ currentLabel.grupo_sanguineo || 'S/G' }}
          </div>

          <!-- Código de barras SVG -->
          <svg id="barcode-svg" class="q-my-sm"></svg>

          <!-- Código QR Canvas -->
          <div class="row justify-center q-my-xs">
            <canvas id="qr-canvas"></canvas>
          </div>

          <div class="text-caption text-grey-8">
            <div><strong>Código:</strong> {{ currentLabel.barcode }}</div>
            <div><strong>Donante:</strong> {{ currentLabel.donante }} (C.I. {{ currentLabel.doc_identidad }})</div>
            <div><strong>Fecha:</strong> {{ currentLabel.fecha }} | <strong>Vol:</strong> {{ currentLabel.volumen }} ml</div>
            <div><strong>Bolsa:</strong> {{ currentLabel.tipo_bolsa || 'Estándar' }}</div>
          </div>
        </div>

        <q-separator class="q-my-md" />

        <q-card-actions align="between">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup />
          <q-btn color="primary" icon="print" label="Imprimir Etiqueta" @click="printCurrentWindow" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'
import JsBarcode from 'jsbarcode'
import QRCode from 'qrcode'

const loading = ref(false)
const saving = ref(false)
const extractions = ref([])
const totalExtractions = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const searchTerm = ref('')

const extractionDialog = ref(false)
const labelDialog = ref(false)
const currentLabel = ref(null)

const donorSearchInput = ref('')
const selectedDonorForExt = ref(null)
const bagOptions = ref([])
const groupOptions = ref([])

const form = ref({
  donor_id: null,
  tipo_bolsa_id: 1,
  grupo_sanguineo_id: 1,
  volumen_ml: 450,
  hora_inicio: '10:00',
  hora_fin: '10:12'
})

const columns = [
  { name: 'id', label: 'N° Extracción', field: 'id', align: 'left', sortable: true },
  { name: 'fecha', label: 'Fecha Extracción', field: 'fecha', align: 'left' },
  { name: 'donante', label: 'Donante', field: 'donante', align: 'left' },
  { name: 'doc_identidad', label: 'C.I.', field: 'doc_identidad', align: 'left' },
  { name: 'grupo_sanguineo', label: 'Grupo Sanguíneo', field: 'grupo_sanguineo', align: 'center' },
  { name: 'tipo_bolsa', label: 'Tipo de Bolsa', field: 'tipo_bolsa', align: 'left' },
  { name: 'volumen_ml', label: 'Volumen', field: 'volumen_ml', align: 'right' },
  { name: 'flebotomista', label: 'Flebotomista', field: 'flebotomista', align: 'left' },
  { name: 'acciones', label: 'Etiqueta QR', align: 'center' }
]

async function fetchExtractions(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const res = await api.get('/flebotomia', {
      params: { page, per_page: 20, search: searchTerm.value }
    })
    if (res.data.success) {
      extractions.value = res.data.result.data
      lastPage.value = res.data.result.last_page
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar extracciones' })
  } finally {
    loading.value = false
  }
}

async function loadOptions() {
  try {
    const [bagRes, grpRes] = await Promise.all([
      api.get('/flebotomia/bolsas'),
      api.get('/flebotomia/grupos')
    ])
    if (bagRes.data.success) bagOptions.value = bagRes.data.data
    if (grpRes.data.success) groupOptions.value = grpRes.data.data
  } catch (e) {}
}

function openExtractionDialog() {
  selectedDonorForExt.value = null
  donorSearchInput.value = ''
  form.value = {
    donor_id: null,
    tipo_bolsa_id: bagOptions.value[0]?.id || 1,
    grupo_sanguineo_id: groupOptions.value[0]?.id || 1,
    volumen_ml: 450,
    hora_inicio: new Date().toLocaleTimeString().slice(0, 5),
    hora_fin: new Date(Date.now() + 10 * 60000).toLocaleTimeString().slice(0, 5)
  }
  extractionDialog.value = true
}

async function searchApprovedDonor() {
  if (!donorSearchInput.value.trim()) return
  try {
    const res = await api.get('/donantes', { params: { search: donorSearchInput.value.trim(), per_page: 1 } })
    if (res.data.success && res.data.result.data.length > 0) {
      selectedDonorForExt.value = res.data.result.data[0]
      form.value.donor_id = selectedDonorForExt.value.id
      Notify.create({ type: 'positive', message: 'Donante asignado a la extracción' })
    } else {
      Notify.create({ type: 'warning', message: 'No se encontró el donante' })
    }
  } catch (e) {}
}

async function saveExtraction() {
  if (!form.value.donor_id) {
    Notify.create({ type: 'warning', message: 'Seleccione un donante' })
    return
  }
  saving.value = true
  try {
    const res = await api.post('/flebotomia', form.value)
    if (res.data.success) {
      Notify.create({ type: 'positive', message: 'Extracción registrada exitosamente' })
      extractionDialog.value = false
      fetchExtractions(1)
      printLabel(res.data.extraction_id)
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: e.response?.data?.message || 'Error al guardar extracción' })
  } finally {
    saving.value = false
  }
}

async function printLabel(id) {
  try {
    const res = await api.get(`/flebotomia/etiqueta/${id}`)
    if (res.data.success) {
      currentLabel.value = res.data.label
      labelDialog.value = true

      await nextTick()
      // Renderizar código de barras Code128
      JsBarcode('#barcode-svg', currentLabel.value.barcode, {
        format: 'CODE128',
        width: 1.8,
        height: 45,
        displayValue: true,
        fontSize: 12
      })

      // Renderizar código QR
      const canvas = document.getElementById('qr-canvas')
      QRCode.toCanvas(canvas, currentLabel.value.qr_data, { width: 120 })
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al generar etiqueta' })
  }
}

function printCurrentWindow() {
  window.print()
}

onMounted(() => {
  fetchExtractions()
  loadOptions()
})
</script>
