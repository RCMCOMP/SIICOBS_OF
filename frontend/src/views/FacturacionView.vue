<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-amber-9">
          🧾 Facturación & Emisión de Comprobantes
        </div>
        <div class="text-caption text-grey-7">
          Registro de facturación oficial, notas de entrega, cálculo de código de control y código QR
        </div>
      </div>
      <q-btn color="amber-9" icon="receipt" label="Nueva Factura / Nota" @click="openInvoiceDialog" />
    </div>

    <!-- Panel de Emisión de Factura -->
    <q-card class="custom-card q-mb-md">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold text-amber-9">
          Comprobantes Recientes Emitidos
        </div>
      </q-card-section>
      <q-separator />

      <q-table
        flat
        bordered
        :rows="invoices"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-monto_total="props">
          <q-td :props="props" class="text-right text-weight-bold">
            Bs. {{ Number(props.row.monto_total || 250).toFixed(2) }}
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center">
            <q-btn flat round dense color="primary" icon="print" @click="printInvoice(props.row)">
              <q-tooltip>Imprimir Factura</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Nueva Factura -->
    <q-dialog v-model="invoiceDialog" persistent>
      <q-card style="min-width: 600px; max-width: 700px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-amber-9">
            Emitir Factura / Nota de Cobro
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.razon_social" label="Razón Social / Nombre *" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="form.nit" label="NIT / C.I. *" />
            </div>
          </div>

          <div class="text-subtitle2 text-weight-bold text-grey-8">Detalle de Hemocomponentes / Servicios:</div>

          <div class="row q-col-gutter-sm items-center">
            <div class="col-6">
              <q-select
                outlined
                dense
                v-model="selectedItem.producto"
                :options="[
                  'PAQUETE GLOBULAR (PG) - Bs. 250',
                  'PLASMA FRESCO CONGELADO (PFC) - Bs. 180',
                  'CONCENTRADO DE PLAQUETAS (CP) - Bs. 150',
                  'CRIOPRECIPITADO (CRIO) - Bs. 120',
                  'PRUEBA CRUZADA DE COMPATIBILIDAD - Bs. 80'
                ]"
                label="Concepto"
              />
            </div>
            <div class="col-3">
              <q-input outlined dense v-model.number="selectedItem.cantidad" type="number" label="Cantidad" />
            </div>
            <div class="col-3">
              <q-input outlined dense v-model.number="selectedItem.precio" type="number" label="Precio Unitario" suffix="Bs." />
            </div>
          </div>

          <div class="q-pa-md bg-amber-1 text-amber-10 rounded-borders row items-center justify-between text-h6 text-weight-bolder">
            <span>Total a Cobrar:</span>
            <span>Bs. {{ totalCalculated.toFixed(2) }}</span>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="amber-9" label="Emitir Factura & Generar QR" @click="emitInvoice" :loading="saving" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Factura Generada -->
    <q-dialog v-model="receiptDialog">
      <q-card style="min-width: 420px; max-width: 480px;" class="q-pa-md" v-if="currentReceipt">
        <div class="text-center" id="printable-invoice">
          <div class="text-caption text-weight-bold">BANCO DE SANGRE DE REFERENCIA DEPARTAMENTAL ORURO</div>
          <div class="text-caption text-grey-7">NIT: 458291024 | FACTURA N° {{ currentReceipt.nro_factura }}</div>
          <div class="text-caption text-grey-7">Autorización: 781923019001</div>

          <q-separator class="q-my-sm" />

          <div class="text-left text-caption text-grey-9 q-mb-sm">
            <div><strong>Señor(es):</strong> {{ currentReceipt.razon_social }}</div>
            <div><strong>NIT/CI:</strong> {{ currentReceipt.nit }}</div>
            <div><strong>Fecha:</strong> {{ currentReceipt.fecha }}</div>
          </div>

          <q-separator class="q-my-sm" />

          <div class="text-h6 text-weight-bolder text-amber-10 q-my-sm">
            TOTAL: Bs. {{ Number(currentReceipt.total).toFixed(2) }}
          </div>

          <div class="text-caption text-grey-8">
            <strong>Código de Control:</strong> {{ currentReceipt.codigo_control }}
          </div>

          <div class="row justify-center q-my-sm">
            <canvas id="invoice-qr-canvas"></canvas>
          </div>

          <div class="text-caption text-grey-7" style="font-size: 10px;">
            "ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LA LEY"
          </div>
        </div>

        <q-separator class="q-my-md" />

        <q-card-actions align="between">
          <q-btn flat label="Cerrar" color="grey-7" v-close-popup />
          <q-btn color="amber-9" icon="print" label="Imprimir Factura" @click="printCurrentWindow" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'
import QRCode from 'qrcode'

const loading = ref(false)
const saving = ref(false)
const invoices = ref([])
const invoiceDialog = ref(false)
const receiptDialog = ref(false)
const currentReceipt = ref(null)

const form = ref({
  razon_social: 'HOSPITAL GENERAL SAN JUAN DE DIOS',
  nit: '102938475'
})

const selectedItem = ref({
  producto: 'PAQUETE GLOBULAR (PG) - Bs. 250',
  cantidad: 2,
  precio: 250
})

const totalCalculated = computed(() => {
  return (selectedItem.value.cantidad || 0) * (selectedItem.value.precio || 0)
})

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left' },
  { name: 'nro_factura', label: 'N° Factura', field: 'nro_factura', align: 'left' },
  { name: 'fecha', label: 'Fecha Emisión', field: 'fecha', align: 'left' },
  { name: 'razon_social', label: 'Razón Social', field: 'razon_social', align: 'left' },
  { name: 'nit', label: 'NIT / CI', field: 'nit', align: 'left' },
  { name: 'monto_total', label: 'Monto Total', field: 'monto_total', align: 'right' },
  { name: 'codigo_control', label: 'Cód. Control', field: 'codigo_control', align: 'center' },
  { name: 'acciones', label: 'Acciones', align: 'center' }
]

async function fetchInvoices() {
  loading.value = true
  try {
    const res = await api.get('/facturacion/facturas')
    if (res.data.success) {
      invoices.value = res.data.result.data
      if (invoices.value.length === 0) {
        // Datos demostrativos de facturas
        invoices.value = [
          { id: 1, nro_factura: '4891', fecha: '2026-08-17 11:30', razon_social: 'CLINICA URBARI ORURO', nit: '45819230', monto_total: 500.0, codigo_control: '8A-F2-91-3C' },
          { id: 2, nro_factura: '4892', fecha: '2026-08-17 12:15', razon_social: 'HOSPITAL OBRERO N° 4', nit: '10928374', monto_total: 750.0, codigo_control: 'B3-11-AA-4F' },
        ]
      }
    }
  } catch (e) {}
  finally {
    loading.value = false
  }
}

function openInvoiceDialog() {
  invoiceDialog.value = true
}

async function emitInvoice() {
  saving.value = true
  try {
    const res = await api.post('/facturacion/crear', {
      razon_social: form.value.razon_social,
      nit: form.value.nit,
      total: totalCalculated.value
    })
    if (res.data.success) {
      currentReceipt.value = res.data.factura
      invoiceDialog.value = false
      receiptDialog.value = true

      await nextTick()
      const canvas = document.getElementById('invoice-qr-canvas')
      QRCode.toCanvas(canvas, currentReceipt.value.qr_data, { width: 120 })
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al emitir factura' })
  } finally {
    saving.value = false
  }
}

function printCurrentWindow() {
  window.print()
}

onMounted(() => {
  fetchInvoices()
})
</script>
