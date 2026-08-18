<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-green-9">
          🚚 Despacho & Distribución de Hemocomponentes
        </div>
        <div class="text-caption text-grey-7">
          Entrega y despacho de unidades transfusionales a hospitales, clínicas y servicios de hemoterapia
        </div>
      </div>
      <q-btn color="green-9" icon="send" label="Nuevo Despacho" @click="dispatchDialog = true" />
    </div>

    <!-- Panel de Unidades Disponibles para Despacho Inmediato -->
    <q-card class="custom-card q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold text-green-9 row items-center">
          <q-icon name="bloodtype" size="sm" class="q-mr-xs" />
          Buscador de Unidades Compatibles para Despacho
        </div>
      </q-card-section>
      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-4">
            <q-select
              outlined
              dense
              v-model="filterProd"
              :options="[
                { label: 'PAQUETE GLOBULAR (PG)', value: 3 },
                { label: 'PLASMA FRESCO CONGELADO (PFC)', value: 6 },
                { label: 'CONCENTRADO DE PLAQUETAS (CP)', value: 9 },
                { label: 'CRIOPRECIPITADO (CRIO)', value: 10 }
              ]"
              emit-value
              map-options
              label="Hemocomponente Solicitado"
              @update:model-value="fetchAvailableUnits"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-select
              outlined
              dense
              v-model="filterGroup"
              :options="[
                { label: 'Todos los Grupos', value: 0 },
                { label: 'O Positivo (O+)', value: 1 },
                { label: 'A Positivo (A+)', value: 2 },
                { label: 'B Positivo (B+)', value: 3 },
                { label: 'O Negativo (O-)', value: 4 }
              ]"
              emit-value
              map-options
              label="Grupo Sanguíneo Receptor"
              @update:model-value="fetchAvailableUnits"
            />
          </div>
          <div class="col-12 col-md-4 text-right">
            <q-btn outline color="green-9" icon="refresh" label="Consultar Stock" @click="fetchAvailableUnits" :loading="loadingUnits" />
          </div>
        </div>
      </q-card-section>

      <!-- Tabla de Unidades Disponibles -->
      <q-table
        flat
        bordered
        :rows="availableUnits"
        :columns="unitColumns"
        row-key="id"
        :loading="loadingUnits"
        selection="multiple"
        v-model:selected="selectedUnits"
      >
        <template v-slot:body-cell-id="props">
          <q-td :props="props" class="text-weight-bold text-primary">
            #HC-{{ String(props.row.id).padStart(6, '0') }}
          </q-td>
        </template>

        <template v-slot:body-cell-grupo_sanguineo="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_sanguineo }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Despacho -->
    <q-dialog v-model="dispatchDialog" persistent>
      <q-card style="min-width: 600px; max-width: 700px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-green-9">
            Generar Despacho a Centro Transfusional
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-select
            outlined
            dense
            v-model="form.servicio_transfusion_id"
            :options="transfusionCenters"
            option-value="id"
            option-label="nombre"
            emit-value
            map-options
            label="Centro Hospitalario / Clínica *"
          />

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.nombre_paciente" label="Nombre del Paciente Receptor *" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.diagnostico" label="Diagnóstico Médico *" />
            </div>
          </div>

          <div class="q-pa-sm bg-green-1 text-green-10 rounded-borders">
            <strong>Unidades Seleccionadas para Entrega:</strong> {{ selectedUnits.length }} unidades
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="green-9" label="Confirmar Entrega y Despacho" @click="confirmDispatch" :loading="savingDispatch" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const filterProd = ref(3)
const filterGroup = ref(0)
const loadingUnits = ref(false)
const savingDispatch = ref(false)
const availableUnits = ref([])
const selectedUnits = ref([])
const transfusionCenters = ref([])
const dispatchDialog = ref(false)

const form = ref({
  servicio_transfusion_id: 1,
  nombre_paciente: '',
  diagnostico: 'Anemia Severa / Quirúrgico'
})

const unitColumns = [
  { name: 'id', label: 'N° Hemocomponente', field: 'id', align: 'left', sortable: true },
  { name: 'producto', label: 'Hemocomponente', field: 'producto', align: 'left' },
  { name: 'grupo_sanguineo', label: 'Grupo ABO/Rh', field: 'grupo_sanguineo', align: 'center' },
  { name: 'fecha_vencimiento', label: 'Vencimiento', field: 'fecha_vencimiento', align: 'left' },
  { name: 'volumen_ml', label: 'Volumen (ml)', field: 'volumen_ml', align: 'right' },
]

async function fetchAvailableUnits() {
  loadingUnits.value = true
  try {
    const res = await api.get('/despacho/disponibles', {
      params: { producto_id: filterProd.value, grupo_id: filterGroup.value }
    })
    if (res.data.success) {
      availableUnits.value = res.data.units
    }
  } catch (e) {}
  finally {
    loadingUnits.value = false
  }
}

async function fetchCenters() {
  try {
    const res = await api.get('/despacho/centros')
    if (res.data.success) {
      transfusionCenters.value = res.data.data
      if (transfusionCenters.value.length > 0) {
        form.value.servicio_transfusion_id = transfusionCenters.value[0].id
      }
    }
  } catch (e) {}
}

async function confirmDispatch() {
  if (selectedUnits.value.length === 0) {
    Notify.create({ type: 'warning', message: 'Seleccione al menos una unidad de la tabla' })
    return
  }
  savingDispatch.value = true
  try {
    const res = await api.post('/despacho/entregar', {
      servicio_transfusion_id: form.value.servicio_transfusion_id,
      nombre_paciente: form.value.nombre_paciente,
      diagnostico: form.value.diagnostico,
      unit_ids: selectedUnits.value.map(u => u.id)
    })
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      dispatchDialog.value = false
      selectedUnits.value = []
      fetchAvailableUnits()
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al procesar despacho' })
  } finally {
    savingDispatch.value = false
  }
}

onMounted(() => {
  fetchAvailableUnits()
  fetchCenters()
})
</script>
