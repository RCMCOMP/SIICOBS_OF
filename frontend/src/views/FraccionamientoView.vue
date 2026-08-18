<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-purple-9">
          🧪 Fraccionamiento, Producción & Reconversión
        </div>
        <div class="text-caption text-grey-7">
          Separación de Sangre Total en Paquete Globular, Plasma Fresco, Plaquetas y Crioprecipitado
        </div>
      </div>
      <q-btn color="purple-9" icon="call_split" label="Nuevo Fraccionamiento" @click="openFractionDialog" />
    </div>

    <!-- Tabla de Unidades Fraccionadas -->
    <q-card class="custom-card">
      <q-table
        flat
        bordered
        :rows="fractions"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-id="props">
          <q-td :props="props" class="text-weight-bold text-primary">
            #HC-{{ String(props.row.id).padStart(6, '0') }}
          </q-td>
        </template>

        <template v-slot:body-cell-producto="props">
          <q-td :props="props" class="text-weight-bold">
            <q-icon name="bloodtype" color="negative" class="q-mr-xs" />
            {{ props.row.producto }}
          </q-td>
        </template>

        <template v-slot:body-cell-grupo_sanguineo="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_sanguineo || 'S/G' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-estado_almacen="props">
          <q-td :props="props" class="text-center">
            <q-badge :color="props.row.estado_almacen === '1' ? 'positive' : (props.row.estado_almacen === '2' ? 'negative' : 'amber-9')">
              {{ props.row.estado_almacen === '1' ? 'LIBERADO' : (props.row.estado_almacen === '2' ? 'DESCARTE' : 'CUARENTENA') }}
            </q-badge>
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
          color="purple-9"
          @update:model-value="fetchFractions"
        />
      </div>
    </q-card>

    <!-- Modal Fraccionar Lote -->
    <q-dialog v-model="fractionDialog" persistent>
      <q-card style="min-width: 600px; max-width: 750px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-purple-9">
            Proceso de Fraccionamiento de Hemocomponentes
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input
            outlined
            dense
            v-model.number="form.extraccion_id"
            type="number"
            label="N° de Extracción Madre (Bolsa Primaria) *"
            hint="Ej: 82285"
          />

          <div class="text-subtitle2 text-weight-bold text-grey-8 q-mt-md">
            Seleccione los Hemocomponentes a Obtener:
          </div>

          <div class="q-gutter-sm">
            <div class="row items-center q-col-gutter-sm q-pa-xs rounded-borders bg-grey-2" v-for="(comp, idx) in availableComponents" :key="comp.id">
              <div class="col-1">
                <q-checkbox v-model="comp.selected" color="purple" />
              </div>
              <div class="col-5 text-weight-bold">
                {{ comp.descripcion }}
              </div>
              <div class="col-3">
                <q-input outlined dense v-model.number="comp.volumen_ml" type="number" label="Volumen (ml)" suffix="ml" :disable="!comp.selected" />
              </div>
              <div class="col-3">
                <q-input outlined dense v-model.number="comp.dias_vencimiento" type="number" label="Vida Útil (días)" suffix="días" :disable="!comp.selected" />
              </div>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="purple-9" label="Completar Fraccionamiento" @click="saveFractionation" :loading="saving" />
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
const fractions = ref([])
const currentPage = ref(1)
const lastPage = ref(1)

const fractionDialog = ref(false)
const form = ref({ extraccion_id: null })

const availableComponents = ref([
  { id: 3, producto_id: 3, descripcion: 'PAQUETE GLOBULAR (PG)', volumen_ml: 250, dias_vencimiento: 35, selected: true },
  { id: 6, producto_id: 6, descripcion: 'PLASMA FRESCO CONGELADO (PFC)', volumen_ml: 200, dias_vencimiento: 365, selected: true },
  { id: 9, producto_id: 9, descripcion: 'CONCENTRADO DE PLAQUETAS (CP)', volumen_ml: 50, dias_vencimiento: 5, selected: true },
  { id: 10, producto_id: 10, descripcion: 'CRIOPRECIPITADO (CRIO)', volumen_ml: 20, dias_vencimiento: 365, selected: false },
])

const columns = [
  { name: 'id', label: 'N° Hemocomponente', field: 'id', align: 'left', sortable: true },
  { name: 'extraccion_id', label: 'Extracción Origen', field: 'extraccion_id', align: 'left' },
  { name: 'producto', label: 'Tipo de Hemocomponente', field: 'producto', align: 'left', sortable: true },
  { name: 'grupo_sanguineo', label: 'Grupo Sanguíneo', field: 'grupo_sanguineo', align: 'center' },
  { name: 'fecha_vencimiento', label: 'Fecha Vencimiento', field: 'fecha_vencimiento', align: 'left' },
  { name: 'volumen_ml', label: 'Volumen (ml)', field: 'volumen_ml', align: 'right' },
  { name: 'estado_almacen', label: 'Estado', field: 'estado_almacen', align: 'center' },
]

async function fetchFractions(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const res = await api.get('/fraccionamiento', { params: { page, per_page: 20 } })
    if (res.data.success) {
      fractions.value = res.data.result.data
      lastPage.value = res.data.result.last_page
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar fraccionamiento' })
  } finally {
    loading.value = false
  }
}

function openFractionDialog() {
  form.value.extraccion_id = 82285
  fractionDialog.value = true
}

async function saveFractionation() {
  if (!form.value.extraccion_id) {
    Notify.create({ type: 'warning', message: 'Ingrese el número de extracción' })
    return
  }

  const selectedComps = availableComponents.value.filter(c => c.selected)
  if (selectedComps.length === 0) {
    Notify.create({ type: 'warning', message: 'Seleccione al menos un hemocomponente' })
    return
  }

  saving.value = true
  try {
    const res = await api.post('/fraccionamiento/guardar', {
      extraccion_id: form.value.extraccion_id,
      components: selectedComps
    })
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      fractionDialog.value = false
      fetchFractions(1)
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: e.response?.data?.message || 'Error en fraccionamiento' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchFractions()
})
</script>
