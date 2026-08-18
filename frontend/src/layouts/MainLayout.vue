<template>
  <q-layout view="lHh Lpr lFf">
    <!-- Barra Superior -->
    <q-header elevated class="bg-gradient-header text-white">
      <q-toolbar class="q-py-xs">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
        />

        <div class="q-ml-sm row items-center no-wrap cursor-pointer" @click="$router.push('/dashboard')">
          <q-avatar size="36px" font-size="24px" color="white" text-color="negative" class="q-mr-sm shadow-2">
            🩸
          </q-avatar>
          <div>
            <div class="text-subtitle1 text-weight-bold" style="letter-spacing: 0.5px; line-height: 1.1;">
              SIICOBS <span class="text-amber-4 text-caption text-weight-bold">PRO</span>
            </div>
            <div class="text-caption text-grey-3" style="font-size: 10px;">
              Banco de Sangre Departamental
            </div>
          </div>
        </div>

        <q-space />

        <!-- Acceso rápido / Buscador -->
        <q-input
          dense
          outlined
          v-model="quickSearch"
          placeholder="Buscar donante o bolsa..."
          class="desktop-only q-mr-md bg-white-translucent text-white"
          dark
          style="width: 260px;"
          @keyup.enter="handleQuickSearch"
        >
          <template v-slot:prepend>
            <q-icon name="search" color="white" size="xs" />
          </template>
        </q-input>

        <!-- Indicador de Alerta / Solicitudes Activas -->
        <q-btn flat round dense icon="notifications" class="q-mr-xs">
          <q-badge color="amber-9" floating rounded>3</q-badge>
          <q-tooltip>Notificaciones de banco de sangre</q-tooltip>
        </q-btn>

        <!-- Modo Oscuro / Claro Toggle -->
        <q-btn flat round dense :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'" @click="$q.dark.toggle()" class="q-mr-sm">
          <q-tooltip>{{ $q.dark.isActive ? 'Modo Claro' : 'Modo Oscuro' }}</q-tooltip>
        </q-btn>

        <!-- Perfil de Usuario Dropdown -->
        <q-btn-dropdown flat no-caps class="q-px-sm">
          <template v-slot:label>
            <div class="row items-center no-wrap">
              <q-avatar size="32px" color="amber-8" text-color="white" class="text-weight-bold q-mr-xs shadow-1">
                {{ authStore.user?.initials || 'U' }}
              </q-avatar>
              <div class="text-left desktop-only">
                <div class="text-body2 text-weight-medium ellipsis" style="max-width: 140px;">
                  {{ authStore.userFullName }}
                </div>
                <div class="text-caption text-grey-4" style="font-size: 10px;">
                  {{ authStore.isAdmin ? 'Administrador' : 'Operador' }}
                </div>
              </div>
            </div>
          </template>

          <q-list style="min-width: 220px;">
            <q-item clickable v-close-popup to="/admin-acl" v-if="authStore.isAdmin">
              <q-item-section avatar>
                <q-icon name="admin_panel_settings" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label>Gestión de Usuarios & ACL</q-item-label>
                <q-item-label caption>Permisos y Accesos</q-item-label>
              </q-item-section>
            </q-item>

            <q-separator />

            <q-item clickable v-close-popup @click="handleLogout">
              <q-item-section avatar>
                <q-icon name="logout" color="negative" />
              </q-item-section>
              <q-item-section class="text-negative text-weight-bold">
                Cerrar Sesión
              </q-item-section>
            </q-item>
          </q-list>
        </q-btn-dropdown>
      </q-toolbar>
    </q-header>

    <!-- Barra Lateral de Navegación con Filtro Dinámico ACL -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      :width="280"
      :class="$q.dark.isActive ? 'bg-dark text-white' : 'bg-grey-1 text-grey-9'"
    >
      <q-scroll-area class="fit">
        <div class="q-pa-md text-center">
          <div class="text-overline text-grey-6">Módulos del Sistema</div>
          <div class="text-caption text-weight-bold" :class="$q.dark.isActive ? 'text-grey-3' : 'text-grey-8'">
            {{ authStore.isAdmin ? 'Acceso Total (Admin)' : 'Vista Personalizada (ACL)' }}
          </div>
        </div>

        <q-separator />

        <q-list padding class="menu-list">
          <template v-for="(group, gIdx) in menuGroups" :key="gIdx">
            <template v-if="getVisibleItems(group.items).length > 0">
              <q-item-label header class="text-weight-bold text-caption text-uppercase q-pt-md q-pb-xs" :class="$q.dark.isActive ? 'text-grey-5' : 'text-grey-7'">
                {{ group.title }}
              </q-item-label>

              <q-item
                v-for="item in getVisibleItems(group.items)"
                :key="item.path"
                clickable
                v-ripple
                :to="item.path"
                exact
                active-class="active-menu-item"
                class="q-my-xs q-mx-sm rounded-borders"
              >
                <q-item-section avatar>
                  <q-icon :name="item.icon" :color="item.color || 'primary'" size="22px" />
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ item.title }}</q-item-label>
                  <q-item-label caption v-if="item.subtitle" :class="$q.dark.isActive ? 'text-grey-5' : 'text-grey-6'">
                    {{ item.subtitle }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side v-if="item.badge">
                  <q-badge :color="item.badgeColor || 'primary'" rounded>
                    {{ item.badge }}
                  </q-badge>
                </q-item-section>
              </q-item>
            </template>
          </template>
        </q-list>
      </q-scroll-area>
    </q-drawer>

    <!-- Contenedor Principal de la Página -->
    <q-page-container :class="$q.dark.isActive ? 'bg-dark-page' : 'bg-grey-2'">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const leftDrawerOpen = ref(false)
const quickSearch = ref('')

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function handleQuickSearch() {
  if (quickSearch.value.trim()) {
    router.push({ path: '/donantes', query: { search: quickSearch.value.trim() } })
  }
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}

// Estructura completa de menú organizada por categorías médicas
const menuGroups = [
  {
    title: 'Principal',
    items: [
      { title: 'Tablero & Stock', subtitle: 'Hemocomponentes en vivo', icon: 'dashboard', path: '/dashboard', resource: 'dashboard', color: 'primary' },
    ]
  },
  {
    title: 'Donación & Selección',
    items: [
      { title: 'Donantes & Filiación', subtitle: 'Registro y búsqueda', icon: 'people', path: '/donantes', resource: 'donantes', color: 'teal' },
      { title: 'Triaje & Cuestionario', subtitle: 'Signos vitales y aptitud', icon: 'favorite', path: '/triaje', resource: 'triaje', color: 'pink-7' },
      { title: 'Flebotomía & Extracción', subtitle: 'Bolsas y etiquetas QR', icon: 'colorize', path: '/flebotomia', resource: 'flebotomia', color: 'deep-orange-8' },
    ]
  },
  {
    title: 'Laboratorio & Producción',
    items: [
      { title: 'Fraccionamiento', subtitle: 'PG, PFC, Plaquetas, CRIO', icon: 'science', path: '/fraccionamiento', resource: 'fraccionamiento', color: 'purple-7' },
      { title: 'Tamizaje Serológico', subtitle: 'VIH, VHB, VHC, Chagas', icon: 'biotech', path: '/serologia', resource: 'serologia', color: 'red-9' },
      { title: 'Inmunohematología', subtitle: 'ABO/Rh y Pruebas Cruzadas', icon: 'bloodtype', path: '/inmunohematologia', resource: 'inmunohematologia', color: 'indigo-7' },
    ]
  },
  {
    title: 'Almacén & Distribución',
    items: [
      { title: 'Almacén & Cadena Frío', subtitle: 'Cámaras y cuarentena', icon: 'inventory_2', path: '/almacen', resource: 'almacen', color: 'cyan-8' },
      { title: 'Despacho & Entregas', subtitle: 'Atención a hospitales', icon: 'local_shipping', path: '/despacho', resource: 'despacho', color: 'green-8' },
      { title: 'Facturación & Cobros', subtitle: 'Comprobantes y notas', icon: 'receipt_long', path: '/facturacion', resource: 'facturacion', color: 'amber-9' },
    ]
  },
  {
    title: 'Gestión & Externo',
    items: [
      { title: 'Reportes & SNIS', subtitle: 'Estadísticas y trazabilidad', icon: 'analytics', path: '/reportes', resource: 'reportes', color: 'blue-8' },
      { title: 'Portal Clínicas', subtitle: 'Pedidos de centros externos', icon: 'local_hospital', path: '/portal-clinicas', resource: 'portal_clinicas', color: 'positive' },
      { title: 'Usuarios & ACL', subtitle: 'Permisos granulares', icon: 'admin_panel_settings', path: '/admin-acl', resource: 'admin_acl', color: 'brown-7' },
    ]
  }
]

// Filtrar ítems del menú según los permisos ACL del usuario
function getVisibleItems(items) {
  return items.filter(item => authStore.hasPermission(item.resource, 'can_view'))
}
</script>

<style scoped>
.bg-gradient-header {
  background: linear-gradient(135deg, #880e4f 0%, #b71c1c 60%, #c62828 100%);
}

.bg-white-translucent {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
}

.active-menu-item {
  background: rgba(183, 28, 28, 0.12) !important;
  color: #b71c1c !important;
  font-weight: 700 !important;
  border-left: 4px solid #b71c1c;
}

.body--dark .active-menu-item {
  background: rgba(239, 83, 80, 0.2) !important;
  color: #ef5350 !important;
  border-left: 4px solid #ef5350;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
