<template>
  <q-page class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold text-brown-9">
          🛡️ Gestión de Usuarios & Matriz de Control de Acceso (ACL)
        </div>
        <div class="text-caption text-grey-7">
          Asignación directa y granular de permisos por usuario e instituciones externas (Clínicas / Hospitales)
        </div>
      </div>
      <q-btn color="brown-9" icon="person_add" label="Nuevo Usuario / Clínica" @click="openCreateUserDialog" />
    </div>

    <!-- Tabla de Usuarios -->
    <q-card class="custom-card q-mb-md">
      <q-card-section class="q-py-sm">
        <div class="row q-col-gutter-md items-center">
          <div class="col-12 col-md-6">
            <q-input
              outlined
              dense
              v-model="searchTerm"
              placeholder="Buscar por Nombre, Usuario o C.I...."
              @keyup.enter="fetchUsers"
              clearable
              @clear="fetchUsers"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6 text-right">
            <q-btn outline color="brown-9" icon="refresh" label="Recargar" @click="fetchUsers" :loading="loading" />
          </div>
        </div>
      </q-card-section>

      <q-table
        flat
        bordered
        :rows="users"
        :columns="columns"
        row-key="id"
        :loading="loading"
        hide-pagination
      >
        <template v-slot:body-cell-is_admin="props">
          <q-td :props="props" class="text-center">
            <q-badge :color="props.row.is_admin ? 'primary' : 'grey-7'">
              {{ props.row.is_admin ? 'Super Administrador' : 'Operador / Clínica' }}
            </q-badge>
          </q-td>
        </template>

        <template v-slot:body-cell-acciones="props">
          <q-td :props="props" class="text-center q-gutter-xs">
            <q-btn color="brown-9" icon="security" label="Configurar ACL" size="sm" @click="openAclMatrix(props.row)">
              <q-tooltip>Modificar Matriz de Permisos (ACL)</q-tooltip>
            </q-btn>
            <q-btn flat round dense color="teal" icon="edit" @click="openEditUser(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal de Configuración de Matriz ACL Granular -->
    <q-dialog v-model="aclDialog" persistent>
      <q-card style="min-width: 750px; max-width: 900px;" class="q-pa-sm" v-if="selectedUserForAcl">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6 text-weight-bold text-brown-9">
              Matriz de Permisos ACL: {{ selectedUserForAcl.name }}
            </div>
            <div class="text-caption text-grey-7">
              Usuario: <strong>{{ selectedUserForAcl.username }}</strong> | Estado: {{ selectedUserForAcl.isAdmin ? 'Super Administrador (Acceso Total)' : 'Control Granular Activo' }}
            </div>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section>
          <!-- Presets Rápidos -->
          <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">Plantillas Rápidas de Permisos:</div>
          <div class="row q-gutter-xs q-mb-md">
            <q-btn size="sm" outline color="primary" label="Acceso Total (Admin)" @click="applyPreset('all')" />
            <q-btn size="sm" outline color="pink-7" label="Médico / Triaje" @click="applyPreset('medico')" />
            <q-btn size="sm" outline color="deep-orange-8" label="Flebotomista" @click="applyPreset('flebotomia')" />
            <q-btn size="sm" outline color="purple-7" label="Laboratorio & Serología" @click="applyPreset('lab')" />
            <q-btn size="sm" outline color="green-8" label="Despacho & Facturación" @click="applyPreset('despacho')" />
            <q-btn size="sm" outline color="teal-8" label="Clínica / Hospital Externo" @click="applyPreset('clinica')" />
          </div>

          <q-separator class="q-mb-md" />

          <!-- Lista de Recursos con Switches -->
          <div class="q-gutter-sm" style="max-height: 420px; overflow-y: auto;">
            <q-card v-for="res in systemResources" :key="res.key" flat bordered class="q-pa-xs">
              <div class="row items-center justify-between">
                <div class="row items-center">
                  <q-icon :name="res.icon || 'circle'" color="brown-8" size="sm" class="q-mr-sm" />
                  <div>
                    <div class="text-weight-bold">{{ res.label }}</div>
                    <div class="text-caption text-grey-6">{{ res.category }} ({{ res.key }})</div>
                  </div>
                </div>

                <div class="row q-gutter-md items-center">
                  <q-toggle
                    v-model="userPermissions[res.key].can_view"
                    label="Ver / Acceder"
                    color="positive"
                    dense
                  />
                  <q-toggle
                    v-model="userPermissions[res.key].can_create"
                    label="Crear"
                    color="primary"
                    dense
                  />
                  <q-toggle
                    v-model="userPermissions[res.key].can_edit"
                    label="Modificar"
                    color="amber-9"
                    dense
                  />
                  <q-toggle
                    v-model="userPermissions[res.key].can_delete"
                    label="Eliminar"
                    color="negative"
                    dense
                  />
                </div>
              </div>
            </q-card>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="brown-9" label="Guardar Matriz ACL" @click="saveAcl" :loading="savingAcl" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Crear / Editar Usuario -->
    <q-dialog v-model="userDialog" persistent>
      <q-card style="min-width: 550px;" class="q-pa-sm">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6 text-weight-bold text-brown-9">
            {{ isEditingUser ? 'Editar Usuario / Clínica' : 'Registrar Nuevo Usuario / Clínica' }}
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-separator />

        <q-card-section class="q-gutter-md">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.nombre" label="Nombre Completo / Institución *" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.username" label="Nombre de Usuario (Login) *" :disable="isEditingUser" />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.password" type="password" :label="isEditingUser ? 'Nueva Contraseña (Opcional)' : 'Contraseña *'" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.doc_identidad" label="C.I. / NIT" />
            </div>
          </div>

          <div class="row q-col-gutter-sm">
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.telefono" label="Teléfono" />
            </div>
            <div class="col-12 col-md-6">
              <q-input outlined dense v-model="userForm.celular" label="Celular" />
            </div>
          </div>

          <q-input outlined dense v-model="userForm.institution_name" label="Nombre de la Institución / Clínica Externa (Opcional)" />

          <q-toggle v-model="userForm.is_admin" label="Es Super Administrador (Acceso Total a todo)" color="primary" />
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="grey-7" v-close-popup />
          <q-btn color="brown-9" :label="isEditingUser ? 'Guardar Cambios' : 'Crear Usuario'" @click="saveUser" :loading="savingUser" />
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
const savingAcl = ref(false)
const savingUser = ref(false)
const users = ref([])
const searchTerm = ref('')
const systemResources = ref([])

const aclDialog = ref(false)
const userDialog = ref(false)
const isEditingUser = ref(false)
const selectedUserForAcl = ref(null)
const userPermissions = ref({})

const userForm = ref({
  id: null,
  nombre: '',
  username: '',
  password: '',
  doc_identidad: '',
  telefono: '',
  celular: '',
  institution_name: '',
  is_admin: false
})

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'nombre', label: 'Nombre / Institución', field: 'nombre', align: 'left', sortable: true },
  { name: 'username', label: 'Usuario', field: 'username', align: 'left' },
  { name: 'doc_identidad', label: 'C.I. / NIT', field: 'doc_identidad', align: 'left' },
  { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
  { name: 'is_admin', label: 'Rol', field: 'is_admin', align: 'center' },
  { name: 'acciones', label: 'Acciones & ACL', align: 'center' }
]

async function fetchUsers() {
  loading.value = true
  try {
    const res = await api.get('/admin/usuarios', { params: { search: searchTerm.value } })
    if (res.data.success) {
      users.value = res.data.data
    }
  } catch (e) {}
  finally {
    loading.value = false
  }
}

async function fetchResources() {
  try {
    const res = await api.get('/admin/recursos')
    if (res.data.success) {
      systemResources.value = res.data.resources
    }
  } catch (e) {}
}

async function openAclMatrix(user) {
  selectedUserForAcl.value = user
  try {
    const res = await api.get(`/admin/acl/${user.id}`)
    if (res.data.success) {
      const existing = res.data.acl || {}
      const perms = {}

      systemResources.value.forEach(r => {
        perms[r.key] = {
          can_view: existing[r.key]?.can_view ?? false,
          can_create: existing[r.key]?.can_create ?? false,
          can_edit: existing[r.key]?.can_edit ?? false,
          can_delete: existing[r.key]?.can_delete ?? false,
        }
      })

      userPermissions.value = perms
      aclDialog.value = true
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar permisos ACL' })
  }
}

function applyPreset(type) {
  systemResources.value.forEach(r => {
    userPermissions.value[r.key] = { can_view: false, can_create: false, can_edit: false, can_delete: false }
  })

  if (type === 'all') {
    systemResources.value.forEach(r => {
      userPermissions.value[r.key] = { can_view: true, can_create: true, can_edit: true, can_delete: true }
    })
  } else if (type === 'medico') {
    ['dashboard', 'donantes', 'triaje'].forEach(k => {
      if (userPermissions.value[k]) userPermissions.value[k] = { can_view: true, can_create: true, can_edit: true, can_delete: false }
    })
  } else if (type === 'flebotomia') {
    ['dashboard', 'donantes', 'flebotomia'].forEach(k => {
      if (userPermissions.value[k]) userPermissions.value[k] = { can_view: true, can_create: true, can_edit: true, can_delete: false }
    })
  } else if (type === 'lab') {
    ['dashboard', 'fraccionamiento', 'serologia', 'inmunohematologia', 'calidad'].forEach(k => {
      if (userPermissions.value[k]) userPermissions.value[k] = { can_view: true, can_create: true, can_edit: true, can_delete: false }
    })
  } else if (type === 'despacho') {
    ['dashboard', 'almacen', 'despacho', 'facturacion'].forEach(k => {
      if (userPermissions.value[k]) userPermissions.value[k] = { can_view: true, can_create: true, can_edit: true, can_delete: false }
    })
  } else if (type === 'clinica') {
    ['dashboard', 'portal_clinicas', 'solicitudes'].forEach(k => {
      if (userPermissions.value[k]) userPermissions.value[k] = { can_view: true, can_create: true, can_edit: false, can_delete: false }
    })
  }
  Notify.create({ type: 'info', message: 'Plantilla de permisos aplicada. Haga clic en Guardar.' })
}

async function saveAcl() {
  savingAcl.value = true
  try {
    const res = await api.post(`/admin/acl/${selectedUserForAcl.value.id}`, {
      permissions: userPermissions.value
    })
    if (res.data.success) {
      Notify.create({ type: 'positive', message: res.data.message })
      aclDialog.value = false
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al guardar permisos ACL' })
  } finally {
    savingAcl.value = false
  }
}

function openCreateUserDialog() {
  isEditingUser.value = false
  userForm.value = {
    id: null,
    nombre: '',
    username: '',
    password: '',
    doc_identidad: '',
    telefono: '',
    celular: '',
    institution_name: '',
    is_admin: false
  }
  userDialog.value = true
}

function openEditUser(user) {
  isEditingUser.value = true
  userForm.value = {
    id: user.id,
    nombre: user.nombre,
    username: user.username,
    password: '',
    doc_identidad: user.doc_identidad,
    telefono: user.telefono,
    celular: user.celular,
    institution_name: user.direccion || '',
    is_admin: !!user.is_admin
  }
  userDialog.value = true
}

async function saveUser() {
  savingUser.value = true
  try {
    if (isEditingUser.value) {
      const res = await api.put(`/admin/usuarios/${userForm.value.id}`, userForm.value)
      if (res.data.success) {
        Notify.create({ type: 'positive', message: 'Usuario actualizado correctamente' })
        userDialog.value = false
        fetchUsers()
      }
    } else {
      const res = await api.post('/admin/usuarios', userForm.value)
      if (res.data.success) {
        Notify.create({ type: 'positive', message: 'Usuario creado exitosamente' })
        userDialog.value = false
        fetchUsers()
      }
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: e.response?.data?.message || 'Error al procesar usuario' })
  } finally {
    savingUser.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchResources()
})
</script>
