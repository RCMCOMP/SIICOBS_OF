<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-positive">
          🏥 Portal de Servicios Transfusionales, Clínicas & Hospitales
        </div>
        <div class="text-caption text-grey-7">
          Módulo de autoservicio para la solicitud y seguimiento de hemocomponentes en tiempo real
        </div>
      </div>
      <q-btn color="positive" icon="add" label="Nueva Solicitud de Hemocomponentes" @click="requestDialog = true" />
    </div>

    <!-- Tabla de Solicitudes Realizadas -->
    <q-card class="custom-card q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold text-positive">
          Historial de Solicitudes Hospitalarias
        </div>
      </q-card-section>
      <q-separator />

      <q-table
        flat
        bordered
        :rows="myRequests"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-grupo_solicitado="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_solicitado || 'A+' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-estado="props">
          <q-td :props="props" class="text-center">
            <q-badge :color="props.row.estado === 'DESPACHADO' ? 'positive' : 'warning'">
              {{ props.row.estado || 'PENDIENTE' }}
            </q-badge>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Nueva Solicitud -->
    <q-dialog v-model="requestDialog" persistent>
      <q-card style="min-width: 600px; max-width: 700px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-positive">
            Solicitud de Hemocomponentes al Banco de Sangre
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-select
                outlined
                dense
                v-model="form.producto_id"
                :options="[
                  { label: 'PAQUETE GLOBULAR (PG)', value: 3 },
                  { label: 'PLASMA FRESCO CONGELADO (PFC)', value: 6 },
                  { label: 'CONCENTRADO DE PLAQUETAS (CP)', value: 9 },
                  { label: 'CRIOPRECIPITADO (CRIO)', value: 10 }
                ]"
                emit-value
                map-options
                label="Hemocomponente Requerido *"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-select
                outlined
                dense
                v-model="form.grupo_sanguineo_id"
                :options="[
                  { label: 'O Positivo (O+)', value: 1 },
                  { label: 'A Positivo (A+)', value: 2 },
                  { label: 'B Positivo (B+)', value: 3 },
                  { label: 'O Negativo (O-)', value: 4 },
                  { label: 'AB Positivo (AB+)', value: 5 }
                ]"
                emit-value
                map-options
                label="Grupo Sanguíneo del Receptor *"
              />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model.number="form.cantidad" type="number" label="Cantidad de Unidades *" />
            </div>
            <div class="col-12 col-md-6">
              <q-select
                outlined
                dense
                v-model="form.prioridad"
                :options="['URGENTE', 'EMERGENCIA (Inmediata)', 'RUTINA (Programada)']"
                label="Nivel de Prioridad *"
              />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.nombre_paciente" label="Nombre Completo del Paciente *" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.diagnostico" label="Diagnóstico Clínico / Sala / Cama *" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="positive" label="Enviar Solicitud al Banco" @click="submitRequest" :loading="saving" />
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
const myRequests = ref([])
const requestDialog = ref(false)

const form = ref({
  producto_id: 3,
  grupo_sanguineo_id: 1,
  cantidad: 2,
  prioridad: 'URGENTE',
  nombre_paciente: '',
  diagnostico: 'Shock Hipovolémico / Sala de Cirugía 2'
})

const columns = [
  { name: 'id', label: 'N° Pedido', field: 'id', align: 'left' },
  { name: 'fecha', label: 'Fecha Solicitud', field: 'fecha', align: 'left' },
  { name: 'paciente', label: 'Paciente', field: 'paciente', align: 'left' },
  { name: 'producto', label: 'Hemocomponente', field: 'producto', align: 'left' },
  { name: 'grupo_solicitado', label: 'Grupo ABO/Rh', field: 'grupo_solicitado', align: 'center' },
  { name: 'cantidad', label: 'Cant.', field: 'cantidad', align: 'right' },
  { name: 'diagnostico', label: 'Diagnóstico', field: 'diagnostico', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' }
]

async function fetchMyRequests() {
  loading.value = true
  try {
    const res = await api.get('/portal-clinicas/mis-solicitudes')
    if (res.data.success) {
      myRequests.value = res.data.requests
    }
  } catch (e) {}
  finally {
    loading.value = false
  }
}

async function submitRequest() {
  if (!form.value.nombre_paciente) {
    Notify.create({ type: 'warning', message: 'Ingrese el nombre del paciente' })
    return
  }
  saving.value = true
  try {
    const res = await api.post('/portal-clinicas/solicitar', form.value)
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      requestDialog.value = false
      fetchMyRequests()
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al enviar solicitud' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchMyRequests()
})
</script>
