<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-cyan-9">
          ❄️ Almacén, Cámaras Frías & Cadena de Frío
        </div>
        <div class="text-caption text-grey-7">
          Gestión de inventario físico, monitoreo de caducidades, cuarentena y liberación clínica
        </div>
      </div>
      <div class="row q-gutter-sm">
        <q-btn-toggle
          v-model="statusFilter"
          toggle-color="cyan-9"
          color="grey-4"
          text-color="grey-9"
          :options="[
            { label: 'Liberados (Disponibles)', value: '1' },
            { label: 'En Cuarentena', value: '0' },
            { label: 'Descartes', value: '2' },
            { label: 'Todos', value: 'all' }
          ]"
          @update:model-value="fetchInventory(1)"
        />
      </div>
    </div>

    <!-- Indicadores de Cadena de Frío -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-4">
        <q-card class="custom-card q-pa-sm bg-blue-1 text-blue-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-bold">Cámara Fría 1 (Hematíes)</div>
              <div class="text-h6 text-weight-bolder">+4.2 °C</div>
              <div class="text-caption text-grey-8">Rango: +2°C a +6°C (Óptimo)</div>
            </div>
            <q-icon name="ac_unit" size="36px" color="blue-8" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="custom-card q-pa-sm bg-indigo-1 text-indigo-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-bold">Ultrafreezer 2 (Plasmas / CRIO)</div>
              <div class="text-h6 text-weight-bolder">-32.5 °C</div>
              <div class="text-caption text-grey-8">Rango: < -30°C (Óptimo)</div>
            </div>
            <q-icon name="kitchen" size="36px" color="indigo-8" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="custom-card q-pa-sm bg-teal-1 text-teal-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-bold">Agitador 1 (Plaquetas)</div>
              <div class="text-h6 text-weight-bolder">+22.1 °C (60 RPM)</div>
              <div class="text-caption text-grey-8">Rango: +20°C a +24°C (Óptimo)</div>
            </div>
            <q-icon name="sync" size="36px" color="teal-8" />
          </div>
        </q-card>
      </div>
    </div>

    <!-- Tabla de Inventario de Hemocomponentes -->
    <q-card class="custom-card">
      <q-table
        flat
        bordered
        :rows="inventory"
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

        <template v-slot:body-cell-grupo_sanguineo="props">
          <q-td :props="props" class="text-center">
            <q-badge color="negative" text-color="white" class="text-weight-bold">
              {{ props.row.grupo_sanguineo || 'S/G' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-estado_vigencia="props">
          <q-td :props="props" class="text-center">
            <q-badge :color="props.row.estado_vigencia === 'VIGENTE' ? 'positive' : (props.row.estado_vigencia === 'POR_VENCER' ? 'warning' : 'negative')">
              {{ props.row.estado_vigencia }}
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

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center">
            <q-btn
              v-if="props.row.estado_almacen === '0'"
              size="sm"
              color="positive"
              icon="verified"
              label="Liberar"
              @click="releaseUnit(props.row.id)"
            />
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
          color="cyan-9"
          @update:model-value="fetchInventory"
        />
      </div>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const loading = ref(false)
const inventory = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const statusFilter = ref('1')

const columns = [
  { name: 'id', label: 'N° Unidad', field: 'id', align: 'left', sortable: true },
  { name: 'producto', label: 'Hemocomponente', field: 'producto', align: 'left' },
  { name: 'grupo_sanguineo', label: 'Grupo Sanguíneo', field: 'grupo_sanguineo', align: 'center' },
  { name: 'fecha_fraccionamiento', label: 'Fecha Prod.', field: 'fecha_fraccionamiento', align: 'left' },
  { name: 'fecha_vencimiento', label: 'Fecha Vencimiento', field: 'fecha_vencimiento', align: 'left' },
  { name: 'volumen_ml', label: 'Volumen', field: 'volumen_ml', align: 'right' },
  { name: 'estado_vigencia', label: 'Vigencia', field: 'estado_vigencia', align: 'center' },
  { name: 'estado_almacen', label: 'Ubicación', field: 'estado_almacen', align: 'center' },
  { name: 'acciones', label: 'Acciones', align: 'center' }
]

async function fetchInventory(page = 1) {
  loading.value = true
  currentPage.value = page
  try {
    const res = await api.get('/almacen/inventario', {
      params: { page, per_page: 20, estado_almacen: statusFilter.value }
    })
    if (res.data.success) {
      inventory.value = res.data.result.data
      lastPage.value = res.data.result.last_page
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar inventario de almacén' })
  } finally {
    loading.value = false
  }
}

async function releaseUnit(id) {
  try {
    const res = await api.post('/almacen/liberar', { unit_id: id })
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      fetchInventory(currentPage.value)
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al liberar unidad' })
  }
}

onMounted(() => {
  fetchInventory()
})
</script>
