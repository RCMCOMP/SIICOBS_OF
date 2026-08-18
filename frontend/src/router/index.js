import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import MainLayout from '../layouts/MainLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { public: true }
  },
  {
    path: '/',
    component: MainLayout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('../views/DashboardView.vue'),
        meta: { title: 'Tablero & Stock en Vivo', icon: 'dashboard', resource: 'dashboard' }
      },
      {
        path: 'donantes',
        name: 'donantes',
        component: () => import('../views/DonantesView.vue'),
        meta: { title: 'Donantes & Filiación', icon: 'people', resource: 'donantes' }
      },
      {
        path: 'triaje',
        name: 'triaje',
        component: () => import('../views/TriajeView.vue'),
        meta: { title: 'Triaje & Cuestionario', icon: 'favorite', resource: 'triaje' }
      },
      {
        path: 'flebotomia',
        name: 'flebotomia',
        component: () => import('../views/FlebotomiaView.vue'),
        meta: { title: 'Flebotomía & Extracción', icon: 'colorize', resource: 'flebotomia' }
      },
      {
        path: 'fraccionamiento',
        name: 'fraccionamiento',
        component: () => import('../views/FraccionamientoView.vue'),
        meta: { title: 'Fraccionamiento & Producción', icon: 'science', resource: 'fraccionamiento' }
      },
      {
        path: 'serologia',
        name: 'serologia',
        component: () => import('../views/SerologiaView.vue'),
        meta: { title: 'Tamizaje Serológico (Infecciosas)', icon: 'biotech', resource: 'serologia' }
      },
      {
        path: 'inmunohematologia',
        name: 'inmunohematologia',
        component: () => import('../views/InmunohematologiaView.vue'),
        meta: { title: 'Inmunohematología & PCC', icon: 'bloodtype', resource: 'inmunohematologia' }
      },
      {
        path: 'almacen',
        name: 'almacen',
        component: () => import('../views/AlmacenView.vue'),
        meta: { title: 'Almacén & Cadena de Frío', icon: 'inventory_2', resource: 'almacen' }
      },
      {
        path: 'despacho',
        name: 'despacho',
        component: () => import('../views/DespachoView.vue'),
        meta: { title: 'Despacho & Distribución', icon: 'local_shipping', resource: 'despacho' }
      },
      {
        path: 'facturacion',
        name: 'facturacion',
        component: () => import('../views/FacturacionView.vue'),
        meta: { title: 'Facturación & Comprobantes', icon: 'receipt_long', resource: 'facturacion' }
      },
      {
        path: 'reportes',
        name: 'reportes',
        component: () => import('../views/ReportesView.vue'),
        meta: { title: 'Reportes Oficiales & SNIS', icon: 'analytics', resource: 'reportes' }
      },
      {
        path: 'portal-clinicas',
        name: 'portal-clinicas',
        component: () => import('../views/PortalClinicasView.vue'),
        meta: { title: 'Portal Clínicas & Hospitales', icon: 'local_hospital', resource: 'portal_clinicas' }
      },
      {
        path: 'admin-acl',
        name: 'admin-acl',
        component: () => import('../views/AdminAclView.vue'),
        meta: { title: 'Usuarios & Matriz ACL', icon: 'admin_panel_settings', resource: 'admin_acl' }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.public) {
    if (authStore.isAuthenticated && to.name === 'login') {
      return next({ name: 'dashboard' })
    }
    return next()
  }

  if (!authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Comprobar ACL
  const resource = to.meta.resource
  if (resource && !authStore.hasPermission(resource, 'can_view')) {
    // Si no tiene acceso al recurso solicitado, buscar la primera ruta permitida
    const allowedRoute = routes[1].children.find(r => r.meta?.resource && authStore.hasPermission(r.meta.resource, 'can_view'))
    if (allowedRoute) {
      return next({ name: allowedRoute.name })
    } else {
      return next({ name: 'login' })
    }
  }

  next()
})

export default router
