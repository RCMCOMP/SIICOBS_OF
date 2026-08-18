<template>
  <div class="login-page row items-center justify-center">
    <q-card class="login-card shadow-10 q-pa-lg">
      <div class="text-center q-mb-md">
        <q-avatar size="64px" font-size="40px" color="primary" text-color="white" class="shadow-3 q-mb-sm">
          🩸
        </q-avatar>
        <div class="text-h5 text-weight-bold text-primary" style="letter-spacing: 0.5px;">
          SIICOBS <span class="text-amber-9 text-subtitle2">MODERNO</span>
        </div>
        <div class="text-caption text-grey-7">
          Banco de Sangre de Referencia Departamental
        </div>
      </div>

      <q-separator class="q-my-md" />

      <q-form @submit.prevent="handleLogin" class="q-gutter-md">
        <q-input
          outlined
          v-model="username"
          label="Nombre de Usuario"
          placeholder="Ej: ADMI, cdaza, jvillca"
          :rules="[val => !!val || 'El usuario es obligatorio']"
        >
          <template v-slot:prepend>
            <q-icon name="person" color="primary" />
          </template>
        </q-input>

        <q-input
          outlined
          v-model="password"
          :type="showPassword ? 'text' : 'password'"
          label="Contraseña"
          :rules="[val => !!val || 'La contraseña es obligatoria']"
        >
          <template v-slot:prepend>
            <q-icon name="lock" color="primary" />
          </template>
          <template v-slot:append>
            <q-icon
              :name="showPassword ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="showPassword = !showPassword"
            />
          </template>
        </q-input>

        <q-btn
          type="submit"
          color="primary"
          class="full-width q-py-sm text-weight-bold text-subtitle2 shadow-2"
          :loading="loading"
          icon-right="login"
        >
          Ingresar al Sistema
        </q-btn>
      </q-form>

      <div class="q-mt-lg">
        <div class="text-caption text-grey-6 text-center q-mb-xs">Accesos Rápidos de Prueba:</div>
        <div class="row q-gutter-xs justify-center">
          <q-btn size="sm" outline color="primary" label="Admin (ADMI)" @click="fillCredentials('ADMI', 'QR183bnm')" />
          <q-btn size="sm" outline color="teal" label="Dra. Daza (cdaza)" @click="fillCredentials('cdaza', '02019O0')" />
          <q-btn size="sm" outline color="deep-orange" label="Lic. Villca (jvillca)" @click="fillCredentials('jvillca', '02019O0')" />
        </div>
      </div>
    </q-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const username = ref('ADMI')
const password = ref('QR183bnm')
const showPassword = ref(false)
const loading = ref(false)

const router = useRouter()
const authStore = useAuthStore()

function fillCredentials(u, p) {
  username.value = u
  password.value = p
}

async function handleLogin() {
  loading.value = true
  const success = await authStore.login(username.value, password.value)
  loading.value = false
  if (success) {
    router.push('/dashboard')
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: radial-gradient(circle at top right, #37474f 0%, #1a237e 40%, #0d1117 100%);
  padding: 16px;
}

.login-card {
  width: 100%;
  max-width: 420px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

.body--dark .login-card {
  background: rgba(18, 24, 38, 0.95);
}
</style>
