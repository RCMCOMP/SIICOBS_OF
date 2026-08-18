# Guía de Despliegue en la Nube Gratuita: SIICOBS Moderno

Esta guía te explica cómo subir tu proyecto **Todo-en-Uno (Frontend Vue 3 + Backend Laravel 13)** a la nube de **Render.com** (100% gratis).

---

## 📌 Paso 1: Subir tu carpeta `MODERNO` a GitHub

1. Abre tu terminal de Git Bash, PowerShell o VS Code en la carpeta `C:\Users\ronal\SIICOBS\MODERNO`.
2. Ejecuta los siguientes comandos:

```bash
git init
git add .
git commit -m "Inicializar SIICOBS Moderno Todo-en-Uno"
```

3. Ve a tu cuenta de **[GitHub](https://github.com/new)** y crea un nuevo repositorio (por ejemplo: `siicobs-moderno`).
4. Enlaza y sube tu código con los comandos que te da GitHub:

```bash
git branch -M main
git remote add origin https://github.com/TU_USUARIO/siicobs-moderno.git
git push -u origin main
```

---

## 🚀 Paso 2: Desplegar en Render.com (100% Gratis)

1. Entra a **[Render.com](https://dashboard.render.com)** e inicia sesión con tu cuenta de GitHub.
2. En el Dashboard, haz clic en el botón azul **"New +"** y selecciona **"Web Service"**.
3. Elige la opción **"Build and deploy from a Git repository"** y selecciona tu repositorio `siicobs-moderno`.
4. Configura los siguientes campos:
   * **Name:** `siicobs-demo` (o el nombre que prefieras).
   * **Region:** Oregon (US West) o Frankfurt (EU Central).
   * **Language / Environment:** **Docker** (Render detectará automáticamente el `Dockerfile`).
   * **Instance Type:** **Free** ($0 / mes).
5. En la sección **Environment Variables** (opcional para personalizar):
   * `APP_KEY`: *(Render lo genera automáticamente o puedes poner uno nuevo)*
   * `DB_CONNECTION`: `sqlite`
   * `DB_DATABASE`: `demo_bdvampiro.sqlite`
6. Haz clic en **"Deploy Web Service"**.

---

## ⏱️ ¿Qué pasará a continuación?
* Render descargará tu repositorio, compilará automáticamente el Frontend con Node.js y preparará el servidor Laravel 13 con Nginx en menos de 3 minutos.
* Al terminar, te entregará una URL pública segura con candado SSL (ejemplo: `https://siicobs-demo.onrender.com`).
* ¡Listo! Al abrir esa URL verás tu sistema funcionando con su login, dashboard y todos los módulos clínicos listos para mostrar.
