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
      <div class="row q-gutter-sm">
        <q-btn outline color="primary" icon="search" label="Buscar / Imprimir Nota de Remisión" @click="openNotaSearchDialog" />
        <q-btn color="green-9" icon="send" label="Nuevo Despacho" @click="dispatchDialog = true" />
      </div>
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

    <!-- Modal Nuevo Despacho -->
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

    <!-- Modal Buscar e Imprimir Nota de Remisión por Código de Despacho (Equivalente exacto a notsel.php del sistema legado) -->
    <q-dialog v-model="notaSearchDialog">
      <q-card style="min-width: 450px; max-width: 500px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-primary">
            Impresión Nota de Remisión
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md text-center">
          <div class="text-subtitle2 text-grey-8">Ingrese el Código de Despacho:</div>
          <q-input
            outlined
            dense
            v-model="codigoDespachoInput"
            label="Código Despacho *"
            placeholder="Ej: 177709"
            @keyup.enter="buscarNotaRemision"
            class="text-center"
          >
            <template v-slot:append>
              <q-icon name="tag" color="primary" />
            </template>
          </q-input>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Volver" color="grey-7" v-close-popup />
          <q-btn color="primary" label="Aceptar / Ver Nota" @click="buscarNotaRemision" :loading="loadingNota" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Visualización e Impresión Oficial de Nota de Remisión -->
    <q-dialog v-model="notaPrintDialog" maximized>
      <q-card class="q-pa-md" v-if="currentNota">
        <div class="row items-center justify-between q-mb-md no-print">
          <div class="text-h6 text-weight-bold text-primary">
            Vista Previa de Nota de Remisión
          </div>
          <div>
            <q-btn color="primary" icon="print" label="Imprimir Nota (A5)" class="q-mr-sm" @click="imprimirNota" />
            <q-btn flat round dense icon="close" v-close-popup />
          </div>
        </div>

        <!-- Documento Oficial para Impresión -->
        <div class="nota-remision-doc q-pa-lg bg-white text-black" style="max-width: 800px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px;">
          <!-- Encabezado -->
          <div class="row items-center justify-between q-mb-sm">
            <div class="col-8">
              <div class="text-weight-bolder text-subtitle1" style="color: #b71c1c;">BANCO DE SANGRE DE REFERENCIA DEPARTAMENTAL ORURO</div>
              <div class="text-caption text-grey-8">Calle Tte. León / Brasil y Tejerina | Tel: 25278000 - 25269000</div>
              <div class="text-caption text-grey-8">Oruro - Bolivia</div>
            </div>
            <div class="col-4 text-right">
              <div class="text-h6 text-weight-bold" style="border: 2px solid #333; padding: 4px 8px; display: inline-block;">
                NOTA DE REMISIÓN<br>
                <span class="text-primary">N° {{ currentNota.nota.nro_nota || currentNota.nota.vvenNroVen }}</span>
              </div>
            </div>
          </div>

          <q-separator class="q-my-sm" />

          <!-- Datos de Despacho -->
          <div class="row q-col-gutter-sm text-caption q-mb-md">
            <div class="col-6">
              <div><strong>Fecha y Hora:</strong> {{ currentNota.nota.fecha }}</div>
              <div><strong>Unidad Transfusional:</strong> {{ currentNota.nota.hospital || 'SERVICIO GENERAL' }}</div>
              <div><strong>N° Despacho:</strong> {{ currentNota.nota.vvenNroVen }}</div>
            </div>
            <div class="col-6">
              <div><strong>Paciente Receptor:</strong> {{ currentNota.nota.paciente || 'PACIENTE INSTITUCIONAL' }}</div>
              <div><strong>Recibido por:</strong> {{ currentNota.nota.recibe || 'PERSONAL DE TURNO' }}</div>
              <div><strong>Responsable:</strong> {{ currentNota.nota.responsable || 'RESPONSABLE DE TURNO' }}</div>
            </div>
          </div>

          <!-- Tabla de Hemocomponentes -->
          <table class="full-width q-mb-md" style="border-collapse: collapse; border: 1px solid #444; font-size: 12px;">
            <thead>
              <tr style="background-color: #f0f0f0; border-bottom: 2px solid #333;">
                <th style="border: 1px solid #ccc; padding: 6px; text-align: center;">N°</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: left;">Hemocomponente</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: center;">Grupo Sanguíneo</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: center;">N° Tubuladura</th>
                <th style="border: 1px solid #ccc; padding: 6px; text-align: right;">Costo (Bs.)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in currentNota.items" :key="idx">
                <td style="border: 1px solid #ccc; padding: 4px; text-align: center;">{{ idx + 1 }}</td>
                <td style="border: 1px solid #ccc; padding: 4px;">{{ item.producto }}</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: center; font-weight: bold;">{{ item.grupo_sanguineo || 'O+' }}</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: center;">{{ item.tubuladura || item.codigo_extraccion }}</td>
                <td style="border: 1px solid #ccc; padding: 4px; text-align: right;">{{ Number(item.precio || 0).toFixed(2) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr style="font-weight: bold; background: #fafafa;">
                <td colspan="4" style="border: 1px solid #ccc; padding: 6px; text-align: right;">TOTAL GENERAL:</td>
                <td style="border: 1px solid #ccc; padding: 6px; text-align: right; color: #b71c1c;">
                  Bs. {{ Number(currentNota.nota.total || 0).toFixed(2) }}
                </td>
              </tr>
            </tfoot>
          </table>

          <!-- Pie y Firmas -->
          <div class="row items-center justify-between q-mt-lg">
            <div class="col-4 text-center">
              <div style="border-top: 1px solid #333; margin-top: 50px; padding-top: 4px; font-size: 11px;">
                Entregado por (Banco de Sangre)
              </div>
            </div>
            <div class="col-4 text-center">
              <canvas id="nota-remision-qr"></canvas>
            </div>
            <div class="col-4 text-center">
              <div style="border-top: 1px solid #333; margin-top: 50px; padding-top: 4px; font-size: 11px;">
                Recibido Conforme (Unidad Transfusional)
              </div>
            </div>
          </div>
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'
import QRCode from 'qrcode'

const filterProd = ref(3)
const filterGroup = ref(0)
const loadingUnits = ref(false)
const savingDispatch = ref(false)
const availableUnits = ref([])
const selectedUnits = ref([])
const transfusionCenters = ref([])
const dispatchDialog = ref(false)

// Estados para Nota de Remisión
const notaSearchDialog = ref(false)
const notaPrintDialog = ref(false)
const codigoDespachoInput = ref('')
const loadingNota = ref(false)
const currentNota = ref(null)

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

function openNotaSearchDialog() {
  codigoDespachoInput.value = ''
  notaSearchDialog.value = true
}

async function buscarNotaRemision() {
  const code = codigoDespachoInput.value.trim()
  if (!code) {
    Notify.create({ type: 'warning', message: 'Por favor ingrese el Código de Despacho' })
    return
  }
  loadingNota.value = true
  try {
    const res = await api.get(`/despacho/nota-remision/${code}`)
    if (res.data.success) {
      currentNota.value = res.data
      notaSearchDialog.value = false
      notaPrintDialog.value = true

      await nextTick()
      const canvas = document.getElementById('nota-remision-qr')
      if (canvas) {
        const qrData = `NR:${res.data.nota.nro_nota}|DSP:${res.data.nota.vvenNroVen}|HOSP:${res.data.nota.hospital}|TOTAL:${res.data.nota.total}`
        QRCode.toCanvas(canvas, qrData, { width: 100 })
      }
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se encontró la Nota de Remisión con ese Código de Despacho' })
  } finally {
    loadingNota.value = false
  }
}

function imprimirNota() {
  window.print()
}

onMounted(() => {
  fetchAvailableUnits()
  fetchCenters()
})
</script>
