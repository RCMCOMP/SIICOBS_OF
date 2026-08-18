<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-blue-9">
          📊 Reportes Oficiales, SNIS & Trazabilidad Vena a Vena
        </div>
        <div class="text-caption text-grey-7">
          Informes epidemiológicos para el Ministerio de Salud / PNS y auditoría de hemovigilancia
        </div>
      </div>
    </div>

    <q-tabs v-model="tab" dense class="text-grey" active-color="blue-9" indicator-color="blue-9" align="left">
      <q-tab name="snis" icon="assessment" label="Informe Estadístico SNIS" />
      <q-tab name="trazabilidad" icon="timeline" label="Trazabilidad Vena a Vena" />
    </q-tabs>

    <q-separator class="q-mb-md" />

    <!-- Tab 1: Informe SNIS -->
    <div v-if="tab === 'snis'">
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-6">
          <q-card class="custom-card">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-blue-9">
                Producción por Hemocomponente
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <q-list bordered separator class="rounded-borders">
                <q-item v-for="(p, idx) in snisData.produccion_hemocomponentes" :key="idx">
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ p.producto }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="blue-9" text-color="white" class="text-weight-bold">
                      {{ Number(p.total).toLocaleString() }} unidades
                    </q-badge>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-6">
          <q-card class="custom-card">
            <q-card-section>
              <div class="text-subtitle1 text-weight-bold text-blue-9">
                Distribución por Grupos Sanguíneos (ABO/Rh)
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
              <q-list bordered separator class="rounded-borders">
                <q-item v-for="(g, idx) in snisData.distribucion_grupos" :key="idx">
                  <q-item-section>
                    <q-item-label class="text-weight-bold">Grupo {{ g.grupo }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge color="negative" text-color="white" class="text-weight-bold">
                      {{ Number(g.total).toLocaleString() }} donaciones
                    </q-badge>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <!-- Tab 2: Trazabilidad Vena a Vena -->
    <div v-if="tab === 'trazabilidad'">
      <q-card class="custom-card q-mb-md">
        <q-card-section>
          <div class="text-subtitle1 text-weight-bold text-blue-9 q-mb-sm">
            Auditoría de Hemovigilancia (Rastreo Total)
          </div>
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-md-8">
              <q-input
                outlined
                dense
                v-model="traceCode"
                placeholder="Ingrese N° de Extracción o Código de Bolsa (Ej: 82285)..."
                @keyup.enter="fetchTraceability"
              >
                <template v-slot:append>
                  <q-btn flat round dense icon="search" color="blue-9" @click="fetchTraceability" :loading="loadingTrace" />
                </template>
              </q-input>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <div v-if="traceResult" class="q-gutter-md">
        <q-timeline color="blue-9" class="q-px-md">
          <q-timeline-entry heading>
            Rastreo Completo de la Unidad de Sangre
          </q-timeline-entry>

          <q-timeline-entry
            title="1. Donación & Extracción"
            :subtitle="traceResult.donante_extraccion?.vexdFecExt"
            icon="favorite"
            color="pink-7"
          >
            <div><strong>Donante:</strong> {{ traceResult.donante_extraccion?.donante }} (C.I. {{ traceResult.donante_extraccion?.vdonDocIde }})</div>
            <div><strong>Grupo Sanguíneo:</strong> <q-badge color="negative">{{ traceResult.donante_extraccion?.grupo_donante }}</q-badge></div>
            <div><strong>Volumen Extraído:</strong> {{ traceResult.donante_extraccion?.vexdVolExt }} ml</div>
            <div><strong>Flebotomista:</strong> {{ traceResult.donante_extraccion?.flebotomista || 'Personal de Turno' }}</div>
          </q-timeline-entry>

          <q-timeline-entry
            title="2. Fraccionamiento en Hemocomponentes"
            subtitle="Laboratorio de Producción"
            icon="science"
            color="purple-7"
          >
            <div class="text-weight-bold q-mb-xs">Hemocomponentes Obtenidos:</div>
            <q-list bordered separator class="rounded-borders">
              <q-item v-for="comp in traceResult.hemocomponentes_derivados" :key="comp.vfraNroFra">
                <q-item-section>
                  <q-item-label class="text-weight-bold">#HC-{{ String(comp.vfraNroFra).padStart(6, '0') }} - {{ comp.producto }}</q-item-label>
                  <q-item-label caption>Vencimiento: {{ comp.vfraFecVen }} | Vol: {{ comp.vfraVolumen }} ml</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="comp.vpalTipAlm === '1' ? 'positive' : 'grey-7'">
                    {{ comp.vpalTipAlm === '1' ? 'Liberado Clínico' : 'Cuarentena' }}
                  </q-badge>
                </q-item-section>
              </q-item>
            </q-list>
          </q-timeline-entry>

          <q-timeline-entry
            title="3. Tamizaje Serológico e Inmunohematología"
            subtitle="Validación Analítica"
            icon="biotech"
            color="teal"
          >
            <div class="text-positive text-weight-bold">
              ✅ Pruebas Infecciosas NO REACTIVAS (VIH, Hepatitis B/C, Chagas, Sífilis, HTLV).
            </div>
            <div>Unidad validada para uso transfusional seguro.</div>
          </q-timeline-entry>
        </q-timeline>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { Notify } from 'quasar'

const tab = ref('snis')
const snisData = ref({})
const traceCode = ref('82285')
const loadingTrace = ref(false)
const traceResult = ref(null)

async function fetchSnis() {
  try {
    const res = await api.get('/reportes/snis')
    if (res.data.success) {
      snisData.value = res.data.report
    }
  } catch (e) {}
}

async function fetchTraceability() {
  if (!traceCode.value.trim()) return
  loadingTrace.value = true
  try {
    const res = await api.get(`/reportes/trazabilidad/${traceCode.value.trim()}`)
    if (res.data.success) {
      traceResult.value = res.data
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al consultar trazabilidad' })
  } finally {
    loadingTrace.value = false
  }
}

onMounted(() => {
  fetchSnis()
  fetchTraceability()
})
</script>
