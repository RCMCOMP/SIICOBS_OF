<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-indigo-9">
          🩸 Inmunohematología & Pruebas Cruzadas (PCC)
        </div>
        <div class="text-caption text-grey-7">
          Tipificación ABO/Rh, detección de anticuerpos irregulares y pruebas de compatibilidad mayor y menor
        </div>
      </div>
    </div>

    <!-- Tabla de Solicitudes Hospitalarias Pendientes de PCC -->
    <q-card class="custom-card q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold text-indigo-9 row items-center">
          <q-icon name="assignment" size="sm" class="q-mr-xs" />
          Solicitudes Transfusionales para Pruebas Cruzadas
        </div>
      </q-card-section>
      <q-separator />

      <q-table
        flat
        bordered
        :rows="requests"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-grupo_receptor="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_receptor || 'A+' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center">
            <q-btn color="indigo-9" icon="biotech" label="Realizar PCC" size="sm" @click="openPccDialog(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Ejecución de Pruebas Cruzadas -->
    <q-dialog v-model="pccDialog" persistent>
      <q-card style="min-width: 600px; max-width: 700px;" class="q-pa-sm" v-if="selectedRequest">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-indigo-9">
            Protocolo de Pruebas Cruzadas (PCC)
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <div class="q-pa-sm bg-indigo-1 text-indigo-10 rounded-borders">
            <div><strong>Paciente:</strong> {{ selectedRequest.paciente || 'PACIENTE HOSPITAL' }}</div>
            <div><strong>Servicio Solicitante:</strong> {{ selectedRequest.servicio_transfusion || 'Hospital General' }}</div>
            <div><strong>Grupo Receptor:</strong> {{ selectedRequest.grupo_receptor || 'A+' }} | <strong>Diagnóstico:</strong> {{ selectedRequest.diagnostico || 'Anemia Severa' }}</div>
          </div>

          <q-input
            outlined
            dense
            v-model.number="pccForm.unit_id"
            type="number"
            label="N° de Unidad de Hemocomponente Candidata *"
            hint="Ej: 224222"
          />

          <div class="text-subtitle2 text-weight-bold text-grey-8">
            Resultados de Compatibilidad Inmunohematológica:
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-toggle v-model="pccForm.pcc_mayor_compatible" label="PCC Mayor: COMPATIBLE" color="positive" class="text-weight-bold" />
            </div>
            <div class="col-6">
              <q-toggle v-model="pccForm.pcc_menor_compatible" label="PCC Menor: COMPATIBLE" color="positive" class="text-weight-bold" />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-select outlined dense v-model="pccForm.coombs_directo" :options="['Negativo', 'Positivo (+)']" label="Coombs Directo" />
            </div>
            <div class="col-6">
              <q-select outlined dense v-model="pccForm.coombs_indirecto" :options="['Negativo', 'Positivo (+)']" label="Coombs Indirecto (RAI)" />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="indigo-9" label="Registrar PCC & Reservar" @click="savePcc" :loading="saving" />
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
const requests = ref([])
const pccDialog = ref(false)
const selectedRequest = ref(null)

const pccForm = ref({
  solicitud_id: null,
  unit_id: 224222,
  pcc_mayor_compatible: true,
  pcc_menor_compatible: true,
  coombs_directo: 'Negativo',
  coombs_indirecto: 'Negativo'
})

const columns = [
  { name: 'id', label: 'N° Solicitud', field: 'id', align: 'left' },
  { name: 'fecha', label: 'Fecha Solicitud', field: 'fecha', align: 'left' },
  { name: 'paciente', label: 'Paciente Receptor', field: 'paciente', align: 'left' },
  { name: 'grupo_receptor', label: 'Grupo Receptor', field: 'grupo_receptor', align: 'center' },
  { name: 'producto', label: 'Hemocomponente', field: 'producto', align: 'left' },
  { name: 'servicio_transfusion', label: 'Servicio / Hospital', field: 'servicio_transfusion', align: 'left' },
  { name: 'acciones', label: 'Acciones', align: 'center' }
]

async function fetchRequests() {
  loading.value = true
  try {
    const res = await api.get('/inmunohematologia/solicitudes')
    if (res.data.success) {
      requests.value = res.data.result.data
    }
  } catch (e) {}
  finally {
    loading.value = false
  }
}

function openPccDialog(row) {
  selectedRequest.value = row
  pccForm.value.solicitud_id = row.id
  pccDialog.value = true
}

async function savePcc() {
  saving.value = true
  try {
    const res = await api.post('/inmunohematologia/pcc', pccForm.value)
    if (res.data.success) {
      Notify.create({
        type: res.data.compatible ? 'positive' : 'negative',
        message: res.data.message
      })
      pccDialog.value = false
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al registrar PCC' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchRequests()
})
</script>
