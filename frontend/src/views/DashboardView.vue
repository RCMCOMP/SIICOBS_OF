<template>
  <q-page class="q-pa-md">
    <!-- Encabezado de Página -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-primary">
          🩸 Tablero de Control & Monitor de Hemocomponentes
        </div>
        <div class="text-caption text-grey-7">
          Balance de existencias en tiempo real por grupo sanguíneo y estado de laboratorio
        </div>
      </div>
      <div class="row q-gutter-sm">
        <q-btn outline color="primary" icon="refresh" label="Actualizar Stock" @click="fetchData" :loading="loading" />
        <q-btn color="primary" icon="add" label="Registrar Donante" to="/donantes" />
      </div>
    </div>

    <!-- Tarjetas de KPIs -->
    <div class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-red-1 text-red-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">Donantes Totales</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.total_donantes?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="red-2" text-color="red-10" icon="people" size="42px" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-teal-1 text-teal-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">Extracciones</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.total_extracciones?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="teal-2" text-color="teal-10" icon="colorize" size="42px" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-purple-1 text-purple-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">Hemocomponentes</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.total_hemocomponentes?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="purple-2" text-color="purple-10" icon="science" size="42px" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-green-1 text-green-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">Stock Liberado</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.stock_liberado?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="green-2" text-color="green-10" icon="verified" size="42px" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-amber-1 text-amber-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">En Cuarentena</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.stock_cuarentena?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="amber-2" text-color="amber-10" icon="hourglass_top" size="42px" />
          </div>
        </q-card>
      </div>

      <div class="col-12 col-sm-6 col-md-4 col-lg-2">
        <q-card class="custom-card q-pa-sm bg-blue-1 text-blue-10">
          <div class="row items-center justify-between">
            <div>
              <div class="text-caption text-weight-medium">Solicitudes</div>
              <div class="text-h6 text-weight-bolder">{{ kpis.solicitudes_activas?.toLocaleString() || '0' }}</div>
            </div>
            <q-avatar color="blue-2" text-color="blue-10" icon="local_shipping" size="42px" />
          </div>
        </q-card>
      </div>
    </div>

    <!-- Matriz de Stock en Vivo -->
    <q-card class="custom-card q-mb-md">
      <q-card-section class="row items-center justify-between q-pb-none">
        <div class="text-subtitle1 text-weight-bold row items-center">
          <q-icon name="bloodtype" color="primary" size="sm" class="q-mr-xs" />
          Matriz de Stock Disponible por Grupo Sanguíneo (Uso Clínico)
        </div>
        <q-badge color="positive" class="q-pa-xs">
          <q-icon name="check" size="14px" class="q-mr-xs" />
          Unidades con Serología No Reactiva & Liberadas
        </q-badge>
      </q-card-section>

      <q-card-section>
        <q-table
          flat
          bordered
          :rows="stockData"
          :columns="stockColumns"
          row-key="id"
          :loading="loading"
          :pagination="{ rowsPerPage: 15 }"
          separator="cell"
        >
          <template v-slot:body-cell-producto="props">
            <q-td :props="props" class="text-weight-bold">
              {{ props.row.producto }}
            </q-td>
          </template>

          <template v-slot:body-cell-total="props">
            <q-td :props="props" class="text-center">
              <q-badge color="primary" text-color="white" class="text-weight-bold q-px-sm q-py-xs">
                {{ props.row.total }}
              </q-badge>
            </q-td>
          </template>

          <template v-for="g in groups" :key="g" v-slot:[`body-cell-${g}`]="props">
            <q-td :props="props" class="text-center">
              <span v-if="props.row[g] > 0" class="text-weight-bold" :class="props.row[g] > 5 ? 'text-positive' : 'text-amber-9'">
                {{ props.row[g] }}
              </span>
              <span v-else class="text-grey-4">-</span>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Actividades Recientes y Accesos Directos -->
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-8">
        <q-card class="custom-card">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold row items-center">
              <q-icon name="history" color="teal" size="sm" class="q-mr-xs" />
              Últimas Extracciones Realizadas
            </div>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <q-table
              flat
              dense
              :rows="recentActivities"
              :columns="activityColumns"
              row-key="id"
              hide-pagination
              :rows-per-page-options="[8]"
            >
              <template v-slot:body-cell-grupo_sanguineo="props">
                <q-td :props="props" class="text-center">
                  <q-badge color="negative" text-color="white">
                    {{ props.row.grupo_sanguineo || 'Pendiente' }}
                  </q-badge>
                </q-td>
              </template>

              <template v-slot:body-cell-volumen_ml="props">
                <q-td :props="props">
                  {{ props.row.volumen_ml }} ml
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="custom-card">
          <q-card-section>
            <div class="text-subtitle1 text-weight-bold row items-center">
              <q-icon name="bolt" color="amber-9" size="sm" class="q-mr-xs" />
              Acciones Rápidas del Banco
            </div>
          </q-card-section>
          <q-separator />
          <q-card-section class="q-gutter-sm">
            <q-btn to="/donantes" color="teal" class="full-width q-py-xs" align="left" icon="person_add" label="1. Nuevo Donante (Filiación)" />
            <q-btn to="/triaje" color="pink-7" class="full-width q-py-xs" align="left" icon="medical_information" label="2. Triaje & Signos Vitales" />
            <q-btn to="/flebotomia" color="deep-orange-8" class="full-width q-py-xs" align="left" icon="colorize" label="3. Flebotomía & Etiquetas QR" />
            <q-btn to="/fraccionamiento" color="purple-7" class="full-width q-py-xs" align="left" icon="science" label="4. Fraccionar Sangre Total" />
            <q-btn to="/serologia" color="red-9" class="full-width q-py-xs" align="left" icon="biotech" label="5. Validar Tamizaje Serológico" />
            <q-btn to="/despacho" color="green-8" class="full-width q-py-xs" align="left" icon="local_shipping" label="6. Despacho a Hospitales" />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const loading = ref(false)
const kpis = ref({})
const groups = ref([])
const stockData = ref([])
const recentActivities = ref([])

const stockColumns = computed(() => {
  const cols = [
    { name: 'producto', label: 'Hemocomponente', field: 'producto', align: 'left', sortable: true },
    { name: 'total', label: 'Total Unid.', field: 'total', align: 'center', sortable: true },
  ]
  groups.value.forEach(g => {
    cols.push({
      name: g,
      label: g,
      field: g,
      align: 'center',
      sortable: true
    })
  })
  return cols
})

const activityColumns = [
  { name: 'id', label: 'N° Extr.', field: 'id', align: 'left' },
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left' },
  { name: 'donante', label: 'Donante', field: 'donante', align: 'left' },
  { name: 'grupo_sanguineo', label: 'Grupo', field: 'grupo_sanguineo', align: 'center' },
  { name: 'volumen_ml', label: 'Volumen', field: 'volumen_ml', align: 'right' },
]

async function fetchData() {
  loading.value = true
  try {
    const [kpiRes, stockRes, actRes] = await Promise.all([
      api.get('/dashboard/kpis'),
      api.get('/dashboard/stock'),
      api.get('/dashboard/actividades')
    ])

    if (kpiRes.data.success) kpis.value = kpiRes.data.kpis
    if (stockRes.data.success) {
      groups.value = stockRes.data.groups
      stockData.value = stockRes.data.data
    }
    if (actRes.data.success) {
      recentActivities.value = actRes.data.data
    }
  } catch (e) {
    console.error('Error fetching dashboard data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
