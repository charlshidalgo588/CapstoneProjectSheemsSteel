import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import '@fortawesome/fontawesome-free/css/all.min.css'

import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'

import './assets/main.css'
import '@/styles/theme.css'

// 🔥 ADD THIS LINE (IMPORTANT)
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)

// 🔥 create store AFTER pinia
const auth = useAuthStore(pinia)

// 🔥 check login state on app start
auth.checkAuth()

app.use(router)
app.use(ElementPlus)

app.mount('#app')

