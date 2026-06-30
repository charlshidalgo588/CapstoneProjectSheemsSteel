<template>
  <Layout title="Edit Category">
    <div class="cat-form-page">

      <!-- PAGE HEADER -->
      <div class="page-header">
        <div class="breadcrumb">
          <RouterLink to="/categories" class="breadcrumb-link">
            <i class="fa-solid fa-arrow-left"></i> Categories
          </RouterLink>
        </div>
        <div>
          <p class="page-eyebrow">Edit Record</p>
          <h1 class="page-title">Edit Category</h1>
        </div>
      </div>

      <!-- ALERTS -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <i class="fa-solid fa-circle-check"></i> {{ successMessage }}
          <button class="alert-close" @click="successMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>
      <transition name="alert-fade">
        <div v-if="errorMessage" class="alert alert--error">
          <i class="fa-solid fa-circle-exclamation"></i> {{ errorMessage }}
          <button class="alert-close" @click="errorMessage = ''"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </transition>

      <!-- SKELETON -->
      <div v-if="pageLoading" class="form-card skeleton-card">
        <div class="skeleton-header">
          <div class="skeleton-icon"></div>
          <div class="skeleton-lines">
            <div class="skeleton-line skeleton-line--wide"></div>
            <div class="skeleton-line skeleton-line--narrow"></div>
          </div>
        </div>
        <div class="skeleton-body">
          <div class="skeleton-field"></div>
        </div>
      </div>

      <!-- FORM -->
      <form v-else @submit.prevent="updateCategory" class="cat-form">
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-icon">
              <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
              <h2 class="form-card-title">Category Information</h2>
              <p class="form-card-sub">Update the name for this category.</p>
            </div>
          </div>

          <div class="form-body">
            <div class="form-field">
              <label class="form-label">Category Name <span class="form-required">*</span></label>
              <div class="input-wrap">
                <i class="fa-solid fa-tag input-icon"></i>
                <input
                  v-model="categoryName"
                  type="text"
                  class="form-input"
                  placeholder="e.g. Roofing Materials"
                  required
                  autofocus
                />
              </div>
              <p class="form-hint">This name will appear in product filters and reports.</p>
            </div>
          </div>
        </div>

        <!-- ACTIONS -->
        <div class="form-actions">
          <RouterLink to="/categories" class="btn btn--ghost">
            <i class="fa-solid fa-arrow-left"></i> Cancel
          </RouterLink>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            <i v-if="saving" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-floppy-disk"></i>
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>
      </form>

    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'
import Layout from '@/components/Layout.vue'

const route      = useRoute()
const router     = useRouter()
const categoryId = route.params.id

const categoryName   = ref('')
const errorMessage   = ref('')
const successMessage = ref('')
const saving         = ref(false)
const pageLoading    = ref(true)

onMounted(async () => {
  try {
    const res = await api.get(`/api/categories/${categoryId}`, { withCredentials: true })
    categoryName.value = res.data.category.CategoryName
  } catch (err) {
    console.error(err)
    errorMessage.value = 'Failed to load category.'
  } finally {
    pageLoading.value = false
  }
})

async function updateCategory() {
  errorMessage.value   = ''
  successMessage.value = ''

  if (!categoryName.value.trim()) {
    errorMessage.value = 'Category name is required.'
    return
  }

  saving.value = true
  try {
    await api.put(`/api/categories/${categoryId}`, { CategoryName: categoryName.value }, { withCredentials: true })
    successMessage.value = 'Category updated successfully!'
    setTimeout(() => router.push('/categories'), 800)
  } catch (err) {
    console.error(err)
    errorMessage.value =
      err.response?.data?.message ||
      err.response?.data?.errors?.CategoryName?.[0] ||
      'Failed to update category.'
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.cat-form-page {
  --f-bg:             var(--c-bg);
  --f-surface:        var(--c-surface);
  --f-surface-raised: var(--c-surface-raised);
  --f-surface-sunken: var(--c-surface-sunken);
  --f-border:         var(--c-border);
  --f-border-strong:  var(--c-border-strong);
  --f-text-primary:   var(--c-text-primary);
  --f-text-secondary: var(--c-text-secondary);
  --f-text-muted:     var(--c-text-muted);
  --f-text-faint:     var(--c-text-faint);
  --f-accent:         var(--c-accent);
  --f-accent-soft:    var(--c-accent-soft);
  --f-accent-border:  var(--c-accent-border);
  --f-ring:           var(--c-accent-ring);
  --f-green:          #10b981;
  --f-red:            #f43f5e;
  --radius: 16px;

  min-height: 100%;
  background: var(--f-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 20px;
  max-width: 640px; margin: 0 auto;
  transition: background-color .22s;
}

.breadcrumb { margin-bottom: 10px; }
.breadcrumb-link {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12.5px; font-weight: 600;
  color: var(--f-text-muted); text-decoration: none; transition: color .15s;
}
.breadcrumb-link:hover { color: var(--f-accent); }

.page-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: var(--f-accent); text-transform: uppercase; margin: 0 0 5px; }
.page-title   { font-size: 26px; font-weight: 800; color: var(--f-text-primary); letter-spacing: -.03em; margin: 0; transition: color .22s; }

.alert {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500; border: 1px solid transparent;
}
.alert--success { background: rgba(16,185,129,.10); border-color: rgba(16,185,129,.25); color: var(--f-green); }
.alert--error   { background: rgba(244,63,94,.09);  border-color: rgba(244,63,94,.22);  color: var(--f-red); }
html[data-theme="dark"] .alert--success { background: rgba(16,185,129,.14); color: #4ADE80; }
html[data-theme="dark"] .alert--error   { background: rgba(244,63,94,.14);  color: #F87171; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: .6; margin-left: auto; font-size: 13px; padding: 2px; transition: opacity .15s; }
.alert-close:hover { opacity: 1; }
.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s, transform .22s; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* SKELETON */
.skeleton-card { padding: 24px; }
.skeleton-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.skeleton-icon  { width: 40px; height: 40px; border-radius: 11px; background: var(--f-border); animation: shimmer 1.4s infinite; flex-shrink: 0; }
.skeleton-lines { display: flex; flex-direction: column; gap: 8px; }
.skeleton-line  { height: 12px; border-radius: 6px; background: var(--f-border); animation: shimmer 1.4s infinite; }
.skeleton-line--wide   { width: 160px; }
.skeleton-line--narrow { width: 100px; }
.skeleton-field { height: 44px; border-radius: 10px; background: var(--f-border); animation: shimmer 1.4s infinite; }
@keyframes shimmer { 0%,100%{opacity:.6} 50%{opacity:.3} }

.cat-form { display: flex; flex-direction: column; gap: 20px; }

.form-card {
  background: var(--f-surface); border: 1px solid var(--f-border);
  border-radius: var(--radius); box-shadow: var(--c-shadow-sm); overflow: hidden;
  transition: background-color .22s, border-color .22s;
}
.form-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 20px 24px 16px; border-bottom: 1px solid var(--f-border);
  transition: border-color .22s;
}
.form-card-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: var(--f-accent-soft); color: var(--f-accent);
  display: flex; align-items: center; justify-content: center; font-size: 16px;
  border: 1px solid var(--f-accent-border);
}
.form-card-title { font-size: 15px; font-weight: 700; color: var(--f-text-primary); margin: 0 0 3px; transition: color .22s; }
.form-card-sub   { font-size: 12.5px; color: var(--f-text-muted); margin: 0; }

.form-body { padding: 24px; }
.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--f-text-secondary); letter-spacing: .01em; transition: color .22s; }
.form-required { color: var(--f-accent); }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
  position: absolute; left: 13px; font-size: 12px;
  color: var(--f-text-faint); pointer-events: none; z-index: 1; transition: color .22s;
}
.form-input {
  width: 100%; height: 44px;
  padding: 0 14px 0 36px;
  border: 1.5px solid var(--f-border-strong); border-radius: 10px;
  background: var(--f-surface-sunken); color: var(--f-text-primary);
  font-size: 14px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--f-text-faint); }
.form-input:focus { border-color: var(--f-accent); box-shadow: 0 0 0 3px var(--f-ring); background: var(--f-surface); }
.form-hint { font-size: 11.5px; color: var(--f-text-faint); margin: 2px 0 0; }

.form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }

.btn {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 13.5px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 20px; cursor: pointer;
  border: 1.5px solid transparent; text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, transform .15s;
}
.btn:active { transform: scale(.98); }
.btn--ghost { background: var(--f-surface); border-color: var(--f-border-strong); color: var(--f-text-secondary); }
.btn--ghost:hover { background: var(--f-surface-raised); border-color: var(--f-accent-border); color: var(--f-text-primary); }
.btn--primary {
  background: linear-gradient(135deg, var(--f-accent) 0%, var(--c-accent-deep) 100%);
  color: #fff; box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover { filter: brightness(1.08); box-shadow: var(--c-shadow-accent-h); transform: translateY(-1px); }
.btn--primary:disabled { opacity: .6; cursor: not-allowed; filter: none; transform: none; box-shadow: none; }

@media (max-width: 600px) {
  .cat-form-page { padding: 16px 16px 48px; }
  .form-actions  { flex-direction: column-reverse; align-items: stretch; }
  .btn           { justify-content: center; }
}
</style>