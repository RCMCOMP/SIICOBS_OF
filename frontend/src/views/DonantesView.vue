<template>
  <q-page class="q-pa-md">
    <!-- Encabezado -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-teal-9">
          👥 Donantes & Registro de Filiación
        </div>
        <div class="text-caption text-grey-7">
          Padrón general de donantes de sangre ({{ totalDonantes.toLocaleString() }} registros)
        </div>
      </div>
      <q-btn color="teal" icon="person_add" label="Nuevo Donante" @click="openCreateDialog" />
    </div>

    <!-- Buscador y Filtros -->
    <q-card class="custom-card q-mb-md">
      <q-card-section class="q-py-sm">
        <div class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-6">
            <q-input
              outlined
              dense
              v-model="searchTerm"
              placeholder="Buscar por C.I., Nombres, Apellidos o Teléfono..."
              @keyup.enter="fetchDonors(1)"
              clearable
              @clear="fetchDonors(1)"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
              <template v-slot:append>
                <q-btn flat round dense icon="arrow_forward" color="teal" @click="fetchDonors(1)" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6 text-right">
            <q-btn outline color="teal" icon="refresh" label="Recargar" @click="fetchDonors(currentPage)" :loading="loading" />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabla de Donantes -->
    <q-card class="custom-card">
      <q-table
        flat
        bordered
        :rows="donors"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-grupo_sanguineo="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold" v-if="props.row.grupo_sanguineo">
              {{ props.row.grupo_sanguineo }}
            </q-badge>
            <span v-else class="text-grey-5">No asignado</span>
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center q-gutter-xs">
            <q-btn flat round dense color="primary" icon="visibility" @click="viewDonor(props.row.id)">
              <q-tooltip>Ver Historial</q-tooltip>
            </q-btn>
            <q-btn flat round dense color="teal" icon="edit" @click="editDonor(props.row)">
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>

      <!-- Paginación -->
      <div class="row items-center justify-between q-pa-md">
        <div class="text-caption text-grey-7">
          Página {{ currentPage }} de {{ lastPage }} (Total: {{ totalDonantes.toLocaleString() }})
        </div>
        <q-pagination
          v-model="currentPage"
          :max="lastPage"
          :max-pages="7"
          direction-links
          boundary-links
          color="teal"
          @update:model-value="fetchDonors"
        />
      </div>
    </q-card>

    <!-- Modal Nuevo/Editar Donante -->
    <q-dialog v-model="donorDialog" persistent>
      <q-card style="min-width: 600px; max-width: 750px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-teal">
            {{ isEditing ? 'Editar Datos del Donante' : 'Registro de Nuevo Donante' }}
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.doc_identidad" label="Documento de Identidad / C.I. *" :rules="[val => !!val || 'Requerido']" />
            </div>
            <div class="col-12 col-md-6">
              <q-select outlined dense v-model="form.sexo" :options="['M', 'F']" label="Sexo *" />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model="form.nombre" label="Nombres *" :rules="[val => !!val || 'Requerido']" />
            </div>
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model="form.primer_apellido" label="Primer Apellido" />
            </div>
            <div class="col-12 col-md-4">
              <q-input outlined dense v-model="form.segundo_apellido" label="Segundo Apellido" />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.fecha_nacimiento" type="date" label="Fecha de Nacimiento" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.celular" label="Teléfono / Celular" />
            </div>
          </div>

          <q-input outlined dense v-model="form.direccion" label="Dirección / Domicilio" />
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="teal" :label="isEditing ? 'Guardar Cambios' : 'Registrar Donante'" @click="saveDonor" :loading="saving" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Ver Detalle & Historial del Donante -->
    <q-dialog v-model="detailDialog">
      <q-card style="min-width: 650px;" class="q-pa-sm" v-if="selectedDonor">
        <q-card-section class="row items-center justify-between">
          <div class="row items-center">
            <q-avatar size="44px" color="teal" text-color="white" icon="person" class="q-mr-sm" />
            <div>
              <div class="text-h6 text-weight-bold">
                {{ selectedDonor.vdonNombre }} {{ selectedDonor.vdonPriApe }} {{ selectedDonor.vdonSegApe }}
              </div>
              <div class="text-caption text-grey-7">C.I.: {{ selectedDonor.vdonDocIde }} | Donante #{{ selectedDonor.vdonNroDon }}</div>
            </div>
          </div>
          <q-badge color="negative" class="text-subtitle2 q-pa-xs" v-if="selectedDonor.grupo_sanguineo">
            {{ selectedDonor.grupo_sanguineo }}
          </q-badge>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="text-subtitle2 text-weight-bold text-teal q-mb-sm">
            Historial de Donaciones Realizadas:
          </div>
          <div v-if="donorExtractions.length > 0">
            <q-list bordered separator class="rounded-borders">
              <q-item v-for="ext in donorExtractions" :key="ext.id">
                <q-item-section avatar>
                  <q-icon name="colorize" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium">Extracción #{{ ext.id }} - {{ ext.tipo_bolsa || 'Bolsa Estándar' }}</q-item-label>
                  <q-item-label caption>Fecha: {{ ext.fecha }} | Volumen: {{ ext.volumen }} ml</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="ext.estado === '1' ? 'positive' : 'grey-7'">
                    {{ ext.estado === '1' ? 'Procesada' : 'Registrada' }}
                  </q-badge>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
          <div v-else class="text-grey-6 text-caption text-center q-pa-md">
            No se registran donaciones previas para este donante.
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const loading = ref(false)
const saving = ref(false)
const donors = ref([])
const totalDonantes = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const searchTerm = ref('')

const donorDialog = ref(false)
const detailDialog = ref(false)
const isEditing = ref(false)
const selectedDonor = ref(null)
const donorExtractions = ref([])

const form = ref({
  id: null,
  doc_identidad: '',
  nombre: '',
  primer_apellido: '',
  segundo_apellido: '',
  sexo: 'M',
  fecha_nacimiento: '1990-01-01',
  celular: '',
  direccion: ''
})

const columns = [
  { name: 'id', label: 'N° Donante', field: 'id', align: 'left', sortable: true },
  { name: 'doc_identidad', label: 'C.I. / Documento', field: 'doc_identidad', align: 'left', sortable: true },
  { name: 'nombre', label: 'Nombres', field: 'nombre', align: 'left' },
  { name: 'primer_apellido', label: 'Primer Apellido', field: 'primer_apellido', align: 'left' },
  { name: 'segundo_apellido', label: 'Segundo Apellido', field: 'segundo_apellido', align: 'left' },
  { name: 'sexo', label: 'Sexo', field: 'sexo', align: 'center' },
  { name: 'celular', label: 'Celular/Teléfono', field: 'celular', align: 'left' },
  { name: 'grupo_sanguineo', label: 'Grupo ABO/Rh', field: 'grupo_sanguineo', align: 'center' },
  { name: 'acciones', label: 'Acciones', align: 'center' }
]

async function fetchDonors(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const res = await api.get('/donantes', {
      params: { page, per_page: 20, search: searchTerm.value }
    })
    if (res.data.success) {
      donors.value = res.data.result.data
      totalDonantes.value = res.data.result.total
      lastPage.value = res.data.result.last_page
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar lista de donantes' })
  } finally {
    loading.value = false
  }
}

function openCreateDialog() {
  isEditing.value = false
  form.value = {
    id: null,
    doc_identidad: '',
    nombre: '',
    primer_apellido: '',
    segundo_apellido: '',
    sexo: 'M',
    fecha_nacimiento: '1990-01-01',
    celular: '',
    direccion: ''
  }
  donorDialog.value = true
}

function editDonor(row) {
  isEditing.value = true
  form.value = { ...row }
  donorDialog.value = true
}

async function saveDonor() {
  saving.value = true
  try {
    if (isEditing.value) {
      const res = await api.put(`/donantes/${form.value.id}`, form.value)
      if (res.data.success) {
        Notify.create({ type: 'positive', message: 'Donante actualizado correctamente' })
        donorDialog.value = false
        fetchDonors(currentPage.value)
      }
    } else {
      const res = await api.post('/donantes', form.value)
      if (res.data.success) {
        Notify.create({ type: 'positive', message: 'Donante registrado exitosamente' })
        donorDialog.value = false
        fetchDonors(1)
      }
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: e.response?.data?.message || 'Error al guardar donante' })
  } finally {
    saving.value = false
  }
}

async function viewDonor(id) {
  try {
    const res = await api.get(`/donantes/${id}`)
    if (res.data.success) {
      selectedDonor.value = res.data.donor
      donorExtractions.value = res.data.extractions || []
      detailDialog.value = true
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar detalle del donante' })
  }
}

onMounted(() => {
  fetchDonors()
})
</script>
