<template>
  <Layout title="Settings">
    <div class="settings-page">

      <!-- HEADER (same pattern as Home) -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Settings</h1>
          <p class="page-sub">
            <span>Manage your account, appearance<template v-if="isAdmin"> and team</template>.</span>
            <span v-if="dirty" class="status status--warn"><i class="fa-solid fa-pen"></i>Unsaved changes</span>
            <span v-else-if="lastUpdated" class="status"><i class="live-dot"></i>Saved {{ lastUpdated }}</span>
          </p>
        </div>
        <div class="page-header-right">
          <RouterLink to="/home" class="btn"><i class="fa-solid fa-arrow-left"></i>Dashboard</RouterLink>
          <button type="button" class="btn btn--primary" :disabled="saving || !dirty" @click="saveChanges">
            <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>{{ saving ? 'Saving…' : 'Save changes' }}
          </button>
        </div>
      </div>

      <!-- NOTICES -->
      <div v-if="successMessage || errors.length" class="notices">
        <transition name="fade">
          <div v-if="successMessage" class="notice notice--success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ successMessage }}</span>
            <button type="button" class="notice-btn" @click="successMessage = ''">Dismiss</button>
          </div>
        </transition>
        <transition name="fade">
          <div v-if="errors.length" class="notice notice--error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul class="notice-list"><li v-for="(e, i) in errors" :key="i">{{ e }}</li></ul>
            <button type="button" class="notice-btn" @click="errors = []">Dismiss</button>
          </div>
        </transition>
      </div>

      <!-- ACCOUNT + APPEARANCE -->
      <div class="row-two">
        <section class="card card--hero" style="--d: 0ms" aria-labelledby="st-account">
          <div class="profile">
            <span class="profile-avatar" :class="roleClass(currentUser?.role)">{{ initials(form.name) || '?' }}</span>
            <div class="profile-text">
              <h2 id="st-account" class="profile-name">{{ form.name || 'Your account' }}</h2>
              <p class="profile-email">{{ form.email || '—' }}</p>
              <span v-if="currentUser?.role" class="team-badge" :class="roleClass(currentUser.role)">{{ currentUser.role }}</span>
            </div>
          </div>
          <dl class="mini-stats">
            <div class="ms"><dt><i class="fa-solid fa-user-shield"></i>Access</dt><dd>{{ currentUser?.role || '—' }}</dd></div>
            <div class="ms"><dt><i class="fa-solid fa-circle-check"></i>Status</dt><dd class="green">Active</dd></div>
            <div v-if="isAdmin" class="ms"><dt><i class="fa-solid fa-users"></i>Team size</dt><dd>{{ teamLoading ? '…' : users.length }}</dd></div>
          </dl>
        </section>

        <section class="card" style="--d: 70ms" aria-labelledby="st-appearance">
          <header class="card-header">
            <div class="card-head-l">
              <span class="card-icon card-icon--violet"><i class="fa-solid fa-palette"></i></span>
              <div>
                <h2 id="st-appearance" class="card-title">Appearance</h2>
                <p class="card-sub">Choose how Sheem Steel looks on this device.</p>
              </div>
            </div>
          </header>
          <div class="theme-choices" role="group" aria-label="Theme">
            <button type="button" class="theme-opt" :class="{ 'theme-opt--on': !darkMode }" :aria-pressed="!darkMode" @click="setTheme(false)">
              <span class="theme-prev theme-prev--light"><i></i><i></i><i></i></span>
              <span class="theme-name"><i class="fa-solid fa-sun"></i>Light</span>
            </button>
            <button type="button" class="theme-opt" :class="{ 'theme-opt--on': darkMode }" :aria-pressed="darkMode" @click="setTheme(true)">
              <span class="theme-prev theme-prev--dark"><i></i><i></i><i></i></span>
              <span class="theme-name"><i class="fa-solid fa-moon"></i>Dark</span>
            </button>
          </div>
        </section>
      </div>

      <!-- FORM -->
      <form @submit.prevent="saveChanges" class="settings-form" novalidate>
        <div class="row-two row-two--even">

          <section class="card" style="--d: 140ms" aria-labelledby="st-personal">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon card-icon--blue"><i class="fa-solid fa-user"></i></span>
                <div>
                  <h2 id="st-personal" class="card-title">Personal information</h2>
                  <p class="card-sub">Update your name and email address.</p>
                </div>
              </div>
            </header>
            <div class="form-stack">
              <div class="form-field">
                <label class="form-label" for="st-name">Full name</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-user input-icon"></i>
                  <input id="st-name" type="text" v-model="form.name" class="form-input" placeholder="Enter your full name" autocomplete="name" />
                </div>
              </div>
              <div class="form-field">
                <label class="form-label" for="st-email">Email address</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-envelope input-icon"></i>
                  <input id="st-email" type="email" v-model="form.email" class="form-input" placeholder="Enter your email" autocomplete="email" />
                </div>
              </div>
            </div>
          </section>

          <section class="card" style="--d: 210ms" aria-labelledby="st-security">
            <header class="card-header">
              <div class="card-head-l">
                <span class="card-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <div>
                  <h2 id="st-security" class="card-title">Security</h2>
                  <p class="card-sub">Change your password. Leave blank to keep the current one.</p>
                </div>
              </div>
            </header>
            <div class="form-stack">
              <div class="form-field">
                <label class="form-label" for="st-cur">Current password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-lock input-icon"></i>
                  <input id="st-cur" :type="visibility.current ? 'text' : 'password'" v-model="form.current_password" class="form-input form-input--pw" placeholder="Enter your current password" autocomplete="current-password" />
                  <button type="button" class="pw-toggle" :aria-label="visibility.current ? 'Hide password' : 'Show password'" @click="toggle('current')">
                    <i :class="visibility.current ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
              </div>

              <div class="form-field">
                <label class="form-label" for="st-new">New password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input id="st-new" :type="visibility.new ? 'text' : 'password'" v-model="form.new_password" class="form-input form-input--pw" placeholder="Enter new password" autocomplete="new-password" />
                  <button type="button" class="pw-toggle" :aria-label="visibility.new ? 'Hide password' : 'Show password'" @click="toggle('new')">
                    <i :class="visibility.new ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <div class="pw-strength" :class="pwStrength.cls" v-if="form.new_password">
                  <div class="pw-strength-bar"><div class="pw-strength-fill" :style="{ width: pwStrength.pct }"></div></div>
                  <span class="pw-strength-label">{{ pwStrength.label }}</span>
                </div>
                <p v-else class="form-hint">Use 12+ characters with numbers and symbols.</p>
              </div>

              <div class="form-field">
                <label class="form-label" for="st-conf">Confirm new password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input id="st-conf" :type="visibility.confirm ? 'text' : 'password'" v-model="form.new_password_confirmation" class="form-input form-input--pw" :class="{ 'form-input--mismatch': pwMismatch }" placeholder="Confirm new password" autocomplete="new-password" />
                  <button type="button" class="pw-toggle" :aria-label="visibility.confirm ? 'Hide password' : 'Show password'" @click="toggle('confirm')">
                    <i :class="visibility.confirm ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <p class="form-hint form-hint--error" v-if="pwMismatch">Passwords do not match.</p>
              </div>
            </div>
          </section>
        </div>

        <!-- ACTIONS -->
        <div class="form-actions">
          <span class="form-actions-note" v-if="dirty"><i class="fa-solid fa-circle-info"></i>You have unsaved changes.</span>
          <button type="button" class="btn" :disabled="!dirty || saving" @click="discardChanges"><i class="fa-solid fa-rotate-left"></i>Discard</button>
          <button type="submit" class="btn btn--primary" :disabled="saving || !dirty">
            <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>{{ saving ? 'Saving…' : 'Save changes' }}
          </button>
        </div>
      </form>

      <!-- TEAM (admin only; the real gate is UserController@authorizeAdmin) -->
      <section class="card" style="--d: 280ms" v-if="isAdmin" aria-labelledby="st-team">
        <header class="card-header">
          <div class="card-head-l">
            <span class="card-icon card-icon--green"><i class="fa-solid fa-users"></i></span>
            <div>
              <h2 id="st-team" class="card-title">Team</h2>
              <p class="card-sub">{{ users.length }} account{{ users.length === 1 ? '' : 's' }} with access to this system.</p>
            </div>
          </div>
          <div class="team-tools">
            <label v-if="users.length > 4" class="team-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="search" type="search" placeholder="Search team" aria-label="Search team" />
            </label>
            <button type="button" class="btn btn--primary" @click="openAddModal"><i class="fa-solid fa-plus"></i>Add user</button>
          </div>
        </header>

        <div v-if="teamLoading" class="team-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading team…</p></div>
        <div v-else-if="users.length === 0" class="team-state"><i class="fa-regular fa-user"></i><p>No accounts yet. Add the first one.</p></div>
        <div v-else-if="filteredUsers.length === 0" class="team-state"><i class="fa-solid fa-magnifying-glass"></i><p>No one matches “{{ search }}”.</p></div>

        <div v-else class="team-scroll">
          <table class="team-table">
            <thead>
              <tr><th>Name</th><th>Role</th><th>Status</th><th>Last active</th><th class="th-actions"></th></tr>
            </thead>
            <tbody>
              <tr v-for="u in filteredUsers" :key="u.id">
                <td>
                  <div class="team-user-cell">
                    <span class="team-avatar" :class="roleClass(u.role)">{{ initials(u.name) }}</span>
                    <div class="team-user-text">
                      <p class="team-user-name">{{ u.name }}<span v-if="u.id === currentUser?.id" class="you-tag">You</span></p>
                      <p class="team-user-email">{{ u.email }}</p>
                    </div>
                  </div>
                </td>
                <td><span class="team-badge" :class="roleClass(u.role)">{{ u.role }}</span></td>
                <td><span class="team-badge" :class="u.status === 'active' ? 'team-badge--active' : 'team-badge--disabled'">{{ u.status === 'active' ? 'Active' : 'Disabled' }}</span></td>
                <td class="team-muted">{{ timeAgo(u.last_active_at) }}</td>
                <td class="team-actions-cell">
                  <div class="team-row-menu-wrap">
                    <button type="button" class="icon-btn-sm" :ref="el => setRowButtonRef(u.id, el)" @click.stop="toggleRowMenu(u.id)" aria-label="Actions" :aria-expanded="openMenuId === u.id">
                      <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <Teleport to="body">
                      <transition name="dropdown">
                        <div v-if="openMenuId === u.id" class="team-row-menu" :style="rowMenuStyle" @click.stop>
                          <button type="button" @click="openEditModal(u)"><i class="fa-solid fa-pen"></i> Edit role</button>
                          <button type="button" @click="confirmResetPassword(u)"><i class="fa-solid fa-key"></i> Reset password</button>
                          <div class="team-row-menu-divider"></div>
                          <button v-if="u.status === 'active'" type="button" class="team-row-menu-item--danger" @click="confirmToggleStatus(u)"><i class="fa-solid fa-user-slash"></i> Disable</button>
                          <button v-else type="button" @click="confirmToggleStatus(u)"><i class="fa-solid fa-user-check"></i> Enable</button>
                        </div>
                      </transition>
                    </Teleport>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </div>

    <!-- ADD USER MODAL -->
    <transition name="fade">
      <div v-if="isAdmin && showAddModal" class="team-modal-backdrop" @click.self="closeAddModal">
        <div class="team-modal" role="dialog" aria-modal="true">
          <template v-if="!addSuccess">
            <div class="team-modal-icon"><i class="fa-solid fa-user-plus"></i></div>
            <h2 class="team-modal-title">Add a team member</h2>
            <p class="team-modal-desc">We’ll generate a temporary password. They’ll set their own after signing in.</p>

            <form @submit.prevent="submitAddUser" novalidate>
              <div class="form-field modal-field">
                <label class="form-label">Full name</label>
                <input v-model="addForm.name" class="form-input form-input--plain" :class="{ 'form-input--mismatch': addErrors.name }" placeholder="Maria Santos" @input="addErrors.name = ''" />
                <p v-if="addErrors.name" class="form-hint form-hint--error">{{ addErrors.name }}</p>
              </div>
              <div class="form-field modal-field">
                <label class="form-label">Email address</label>
                <input v-model="addForm.email" type="email" class="form-input form-input--plain" :class="{ 'form-input--mismatch': addErrors.email }" placeholder="maria@sheems.example" @input="addErrors.email = ''" />
                <p v-if="addErrors.email" class="form-hint form-hint--error">{{ addErrors.email }}</p>
              </div>
              <div class="form-field modal-field">
                <label class="form-label">Role</label>
                <div class="role-dd" :class="{ 'role-dd--open': addRoleOpen }" ref="addRoleRoot">
                  <button type="button" class="role-dd-trigger" aria-haspopup="listbox" :aria-expanded="addRoleOpen" @click.stop="toggleAddRole">
                    <span class="role-dd-icon" :class="roleClass(addForm.role)"><i class="fa-solid" :class="roleIcon(addForm.role)"></i></span>
                    <span class="role-dd-value">{{ addForm.role || 'Select a role' }}</span>
                    <i class="fa-solid fa-chevron-down role-dd-chevron"></i>
                  </button>
                  <transition name="dropdown">
                    <ul v-if="addRoleOpen" class="role-dd-menu" role="listbox" @click.stop>
                      <li v-for="r in ROLE_OPTIONS" :key="r" role="option" :aria-selected="addForm.role === r" class="role-dd-option" :class="{ 'role-dd-option--active': addForm.role === r }" @click="selectAddRole(r)">
                        <span class="role-dd-icon" :class="roleClass(r)"><i class="fa-solid" :class="roleIcon(r)"></i></span>
                        <span class="role-dd-option-text">
                          <span class="role-dd-option-name">{{ r }}</span>
                          <span class="role-dd-option-desc">{{ roleDesc(r) }}</span>
                        </span>
                        <i v-if="addForm.role === r" class="fa-solid fa-check role-dd-check"></i>
                      </li>
                    </ul>
                  </transition>
                </div>
              </div>

              <p v-if="addErrors.form" class="form-hint form-hint--error form-hint--center">{{ addErrors.form }}</p>

              <div class="team-modal-actions">
                <button type="button" class="btn" :disabled="addLoading" @click="closeAddModal">Cancel</button>
                <button type="submit" class="btn btn--primary btn--min" :disabled="addLoading">
                  <i v-if="addLoading" class="fa-solid fa-spinner fa-spin"></i>
                  <span>{{ addLoading ? 'Creating…' : 'Create account' }}</span>
                </button>
              </div>
            </form>
          </template>

          <template v-else>
            <div class="team-modal-icon team-modal-icon--success"><i class="fa-solid fa-circle-check"></i></div>
            <h2 class="team-modal-title">{{ addSuccess.name }}’s account is ready</h2>
            <p class="team-modal-desc">Share this password with them directly. It won’t be shown again.</p>
            <div class="temp-password-box" :class="{ 'temp-password-box--copied': copiedKey === 'add' && !copyError }">
              <p class="temp-password-label">Temporary password</p>
              <div class="temp-password-row">
                <code class="temp-password-value">{{ addSuccess.tempPassword }}</code>
                <button type="button" class="copy-btn" :class="copyBtnClass('add')" @click="copyPassword(addSuccess.tempPassword, 'add')" :aria-label="copiedKey === 'add' && !copyError ? 'Password copied' : 'Copy password'">
                  <i class="fa-solid" :class="copyIcon('add')"></i><span>{{ copyLabel('add') }}</span>
                </button>
              </div>
              <p class="copy-status" :class="{ 'copy-status--error': copiedKey === 'add' && copyError }" role="status" aria-live="polite">
                <template v-if="copiedKey === 'add'">
                  <i class="fa-solid" :class="copyError ? 'fa-triangle-exclamation' : 'fa-circle-check'"></i>{{ copyMessage }}
                </template>
              </p>
            </div>
            <button type="button" class="btn btn--primary btn--block" @click="closeAddModal">Done</button>
          </template>
        </div>
      </div>
    </transition>

    <!-- EDIT ROLE MODAL -->
    <transition name="fade">
      <div v-if="isAdmin && editModal" class="team-modal-backdrop" @click.self="closeEditModal">
        <div class="team-modal team-modal--edit-role" role="dialog" aria-modal="true">
          <div class="team-modal-icon"><i class="fa-solid fa-user-gear"></i></div>
          <h2 class="team-modal-title">Edit role</h2>
          <div class="edit-role-subject">
            <span class="team-avatar" :class="roleClass(editModal.user.role)">{{ initials(editModal.user.name) }}</span>
            <div class="edit-role-subject-text">
              <p class="edit-role-subject-name">{{ editModal.user.name }}</p>
              <p class="edit-role-subject-email">{{ editModal.user.email }}</p>
            </div>
          </div>
          <p class="team-modal-desc">Changes apply the next time {{ editModal.user.name }} loads the app.</p>

          <div class="form-field modal-field">
            <label class="form-label">Role</label>
            <div class="role-dd" :class="{ 'role-dd--open': editRoleOpen }" ref="editRoleRoot">
              <button type="button" class="role-dd-trigger" aria-haspopup="listbox" :aria-expanded="editRoleOpen" @click.stop="toggleEditRole">
                <span class="role-dd-icon" :class="roleClass(editModal.role)"><i class="fa-solid" :class="roleIcon(editModal.role)"></i></span>
                <span class="role-dd-value">{{ editModal.role || 'Select a role' }}</span>
                <i class="fa-solid fa-chevron-down role-dd-chevron"></i>
              </button>
              <transition name="dropdown">
                <ul v-if="editRoleOpen" class="role-dd-menu" role="listbox" @click.stop>
                  <li v-for="r in ROLE_OPTIONS" :key="r" role="option" :aria-selected="editModal.role === r" class="role-dd-option" :class="{ 'role-dd-option--active': editModal.role === r }" @click="selectEditRole(r)">
                    <span class="role-dd-icon" :class="roleClass(r)"><i class="fa-solid" :class="roleIcon(r)"></i></span>
                    <span class="role-dd-option-text">
                      <span class="role-dd-option-name">{{ r }}</span>
                      <span class="role-dd-option-desc">{{ roleDesc(r) }}</span>
                    </span>
                    <i v-if="editModal.role === r" class="fa-solid fa-check role-dd-check"></i>
                  </li>
                </ul>
              </transition>
            </div>
          </div>

          <div class="team-modal-actions">
            <button type="button" class="btn" :disabled="editLoading" @click="closeEditModal">Cancel</button>
            <button type="button" class="btn btn--primary btn--min" :disabled="editLoading || editModal.role === editModal.user.role" @click="submitEditRole">
              <i v-if="editLoading" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ editLoading ? 'Saving…' : 'Save role' }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- CONFIRM MODAL -->
    <transition name="fade">
      <div v-if="isAdmin && confirmModal" class="team-modal-backdrop" @click.self="closeConfirmModal">
        <div class="team-modal" role="dialog" aria-modal="true">
          <template v-if="confirmModal.type === 'reset' && resetResult">
            <div class="team-modal-icon team-modal-icon--success"><i class="fa-solid fa-circle-check"></i></div>
            <h2 class="team-modal-title">Password reset</h2>
            <p class="team-modal-desc">Share this with {{ confirmModal.user.name }}. It won’t be shown again.</p>
            <div class="temp-password-box" :class="{ 'temp-password-box--copied': copiedKey === 'reset' && !copyError }">
              <p class="temp-password-label">New temporary password</p>
              <div class="temp-password-row">
                <code class="temp-password-value">{{ resetResult.tempPassword }}</code>
                <button type="button" class="copy-btn" :class="copyBtnClass('reset')" @click="copyPassword(resetResult.tempPassword, 'reset')" :aria-label="copiedKey === 'reset' && !copyError ? 'Password copied' : 'Copy password'">
                  <i class="fa-solid" :class="copyIcon('reset')"></i><span>{{ copyLabel('reset') }}</span>
                </button>
              </div>
              <p class="copy-status" :class="{ 'copy-status--error': copiedKey === 'reset' && copyError }" role="status" aria-live="polite">
                <template v-if="copiedKey === 'reset'">
                  <i class="fa-solid" :class="copyError ? 'fa-triangle-exclamation' : 'fa-circle-check'"></i>{{ copyMessage }}
                </template>
              </p>
            </div>
            <button type="button" class="btn btn--primary btn--block" @click="closeConfirmModal">Done</button>
          </template>

          <template v-else>
            <div class="team-modal-icon" :class="{ 'team-modal-icon--danger': confirmModal.type === 'disable' }"><i class="fa-solid" :class="confirmIcon"></i></div>
            <h2 class="team-modal-title">{{ confirmTitle }}</h2>
            <p class="team-modal-desc">{{ confirmDesc }}</p>
            <p v-if="confirmError" class="form-hint form-hint--error form-hint--center">{{ confirmError }}</p>
            <div class="team-modal-actions">
              <button type="button" class="btn" :disabled="confirmLoading" @click="closeConfirmModal">Cancel</button>
              <button type="button" class="btn btn--min" :class="confirmModal.type === 'disable' ? 'btn--danger' : 'btn--primary'" :disabled="confirmLoading" @click="runConfirmedAction">
                <i v-if="confirmLoading" class="fa-solid fa-spinner fa-spin"></i>
                <span>{{ confirmLoading ? 'Working…' : confirmActionLabel }}</span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </transition>

  </Layout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import Layout from '@/components/Layout.vue'
import api from '@/api/axios'
import { useDarkMode } from '@/composables/useDarkMode'

const { darkMode, toggleDark } = useDarkMode()

/* Only flips when the chosen theme differs from the current one. */
function setTheme(dark) {
  if (darkMode.value !== dark) toggleDark()
}

/* ══ ROLE DROPDOWN (themed replacement for native <select>) ══ */
const ROLE_OPTIONS = ['Administrator', 'Cashier', 'Inventory Staff']
const ROLE_META = {
  Administrator: { icon: 'fa-user-shield', desc: 'Full access, including team & settings' },
  Cashier: { icon: 'fa-cash-register', desc: 'Point of sale and daily transactions' },
  'Inventory Staff': { icon: 'fa-boxes-stacked', desc: 'Manage stock and inventory records' },
}
const roleIcon = (role) => ROLE_META[role]?.icon || 'fa-user'
const roleDesc = (role) => ROLE_META[role]?.desc || ''

const addRoleOpen  = ref(false)
const editRoleOpen = ref(false)
const addRoleRoot  = ref(null)
const editRoleRoot = ref(null)

function toggleAddRole()  { addRoleOpen.value = !addRoleOpen.value; editRoleOpen.value = false }
function selectAddRole(r) { addForm.value.role = r; addRoleOpen.value = false }
function toggleEditRole() { editRoleOpen.value = !editRoleOpen.value; addRoleOpen.value = false }
function selectEditRole(r) { if (editModal.value) editModal.value.role = r; editRoleOpen.value = false }

/* ══ ACCOUNT FORM ══ */
const lastUpdated    = ref('')
const successMessage = ref('')
const errors         = ref([])
const saving         = ref(false)

// Drives whether the Team card renders at all. Real enforcement is
// UserController@authorizeAdmin on the server.
const currentUser = ref(null)
const isAdmin     = computed(() => currentUser.value?.role === 'Administrator')

const form = ref({ name: '', email: '', current_password: '', new_password: '', new_password_confirmation: '' })
const original = ref({ name: '', email: '' })

const visibility = ref({ current: false, new: false, confirm: false })
const toggle = (f) => { visibility.value[f] = !visibility.value[f] }

const dirty = computed(() =>
  form.value.name !== original.value.name ||
  form.value.email !== original.value.email ||
  !!form.value.new_password || !!form.value.current_password || !!form.value.new_password_confirmation
)

const pwStrength = computed(() => {
  const pw = form.value.new_password
  if (!pw) return { pct: '0%', cls: '', label: '' }
  let score = 0
  if (pw.length >= 8)           score++
  if (pw.length >= 12)          score++
  if (/[A-Z]/.test(pw))         score++
  if (/[0-9]/.test(pw))         score++
  if (/[^A-Za-z0-9]/.test(pw))  score++
  if (score <= 1) return { pct: '25%',  cls: 'pw-strength--weak',   label: 'Weak' }
  if (score <= 2) return { pct: '50%',  cls: 'pw-strength--fair',   label: 'Fair' }
  if (score <= 3) return { pct: '75%',  cls: 'pw-strength--good',   label: 'Good' }
  return              { pct: '100%', cls: 'pw-strength--strong', label: 'Strong' }
})

const pwMismatch = computed(() =>
  form.value.new_password_confirmation && form.value.new_password !== form.value.new_password_confirmation
)

const nowLabel = () => new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' })

async function loadUser() {
  try {
    const res = await api.get('/api/user')
    form.value.name  = res.data.name
    form.value.email = res.data.email
    original.value   = { name: res.data.name, email: res.data.email }
    // If `role` is missing here, check for a $hidden/$visible array on the User model.
    currentUser.value = res.data
  } catch (e) { console.error(e) }
}

function clearPasswords() {
  form.value.current_password = ''
  form.value.new_password = ''
  form.value.new_password_confirmation = ''
}

function discardChanges() {
  form.value.name  = original.value.name
  form.value.email = original.value.email
  clearPasswords()
  errors.value = []
}

async function saveChanges() {
  if (!dirty.value || saving.value) return
  successMessage.value = ''
  errors.value = []

  if (pwMismatch.value) { errors.value = ['New password and confirmation do not match.']; return }
  if (form.value.new_password && !form.value.current_password) {
    errors.value = ['Enter your current password to set a new one.']
    return
  }

  saving.value = true
  try {
    await api.put('/api/user', { name: form.value.name, email: form.value.email })
    original.value = { name: form.value.name, email: form.value.email }
    if (form.value.new_password) {
      await api.put('/api/user/password', {
        current_password:          form.value.current_password,
        new_password:              form.value.new_password,
        new_password_confirmation: form.value.new_password_confirmation,
      })
    }
    successMessage.value = 'Your changes have been saved.'
    clearPasswords()
    lastUpdated.value = nowLabel()
  } catch (err) {
    errors.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat()
      : [err.response?.data?.message || 'Something went wrong. Please try again.']
  } finally {
    saving.value = false
  }
}

/* ══ TEAM ══ */
const users       = ref([])
const teamLoading = ref(true)
const search      = ref('')

const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return users.value
  return users.value.filter((u) =>
    [u.name, u.email, u.role].some((v) => (v || '').toLowerCase().includes(q))
  )
})

async function fetchUsers() {
  teamLoading.value = true
  try {
    const { data } = await api.get('/api/users')
    users.value = data.users ?? data
  } catch (err) {
    console.error('Failed to load team', err)
  } finally {
    teamLoading.value = false
  }
}

function initials(name) {
  return (name || '').split(' ').filter(Boolean).slice(0, 2).map((w) => w[0].toUpperCase()).join('')
}

function roleClass(role) {
  const key = (role || '').toLowerCase().replace(/\s+/g, '-')
  if (key === 'administrator')   return 'role--admin'
  if (key === 'cashier')         return 'role--cashier'
  if (key === 'inventory-staff') return 'role--inventory'
  return 'role--default'
}

function timeAgo(dateStr) {
  if (!dateStr) return 'Never'
  const diff = (Date.now() - new Date(dateStr)) / 1000
  if (diff < 60) return 'Just now'
  if (diff < 3600) return Math.floor(diff / 60) + 'm ago'
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago'
  return Math.floor(diff / 86400) + 'd ago'
}

/* ══ COPY TEMP PASSWORD ══ */
const copiedKey = ref(null)
const copyError = ref(false)
let copyTimer   = null

const copyMessage = computed(() =>
  copyError.value
    ? 'Couldn’t copy automatically. Select the password and copy it manually.'
    : 'Copied to clipboard. Paste it into a message to share.'
)

function legacyCopy(text) {
  const ta = document.createElement('textarea')
  ta.value = text
  ta.setAttribute('readonly', '')
  ta.style.position = 'fixed'
  ta.style.opacity = '0'
  document.body.appendChild(ta)
  ta.select()
  let ok = false
  try { ok = document.execCommand('copy') } catch { ok = false }
  document.body.removeChild(ta)
  return ok
}

async function copyPassword(pw, key) {
  let ok = false
  try {
    if (navigator.clipboard?.writeText) { await navigator.clipboard.writeText(pw); ok = true }
  } catch { ok = false }
  if (!ok) ok = legacyCopy(pw)
  copyError.value = !ok
  copiedKey.value = key
  clearTimeout(copyTimer)
  copyTimer = setTimeout(resetCopyState, ok ? 2500 : 4000)
}
function resetCopyState() {
  clearTimeout(copyTimer)
  copiedKey.value = null
  copyError.value = false
}
function copyBtnClass(key) {
  if (copiedKey.value !== key) return ''
  return copyError.value ? 'copy-btn--error' : 'copy-btn--done'
}
function copyIcon(key) {
  if (copiedKey.value !== key) return 'fa-copy'
  return copyError.value ? 'fa-triangle-exclamation' : 'fa-check'
}
function copyLabel(key) {
  if (copiedKey.value !== key) return 'Copy'
  return copyError.value ? 'Failed' : 'Copied!'
}

/* ══ ROW MENU (teleported to <body> so card overflow can't clip it) ══ */
const openMenuId     = ref(null)
const openMenuCoords = ref({ top: 0, left: 0 })
const rowButtonEls   = {}
function setRowButtonRef(id, el) { if (el) rowButtonEls[id] = el }

function measureRowMenu(id) {
  const btn = rowButtonEls[id]
  if (!btn) return
  const rect = btn.getBoundingClientRect()
  const menuWidth = 180
  const menuHeight = 170
  const maxLeft = window.innerWidth - menuWidth - 10
  const flip = rect.bottom + menuHeight + 12 > window.innerHeight
  openMenuCoords.value = {
    top:  flip ? Math.max(10, rect.top - menuHeight - 6) : rect.bottom + 6,
    left: Math.min(rect.right - menuWidth, Math.max(10, maxLeft)),
  }
}
async function toggleRowMenu(id) {
  if (openMenuId.value === id) { openMenuId.value = null; return }
  openMenuId.value = id
  await nextTick()
  measureRowMenu(id)
}
function onScrollOrResizeRowMenu() { if (openMenuId.value) measureRowMenu(openMenuId.value) }
const rowMenuStyle = computed(() => ({
  top:  openMenuCoords.value.top + 'px',
  left: openMenuCoords.value.left + 'px',
}))

function onDocumentClick(e) {
  if (!e.target.closest?.('.team-row-menu-wrap')) openMenuId.value = null
  if (addRoleRoot.value && !addRoleRoot.value.contains(e.target)) addRoleOpen.value = false
  if (editRoleRoot.value && !editRoleRoot.value.contains(e.target)) editRoleOpen.value = false
}
function onDocumentKeydown(e) {
  if (e.key !== 'Escape') return
  // Close the innermost thing first: dropdowns, then the row menu, then modals.
  if (addRoleOpen.value || editRoleOpen.value) { addRoleOpen.value = false; editRoleOpen.value = false; return }
  if (openMenuId.value) { openMenuId.value = null; return }
  if (showAddModal.value && !addLoading.value) closeAddModal()
  else if (editModal.value && !editLoading.value) closeEditModal()
  else if (confirmModal.value && !confirmLoading.value) closeConfirmModal()
}

/* ══ ADD USER ══ */
const showAddModal = ref(false)
const addForm    = ref({ name: '', email: '', role: 'Cashier' })
const addErrors  = ref({})
const addLoading = ref(false)
const addSuccess = ref(null)

function openAddModal() {
  addForm.value = { name: '', email: '', role: 'Cashier' }
  addErrors.value = {}
  addSuccess.value = null
  addRoleOpen.value = false
  resetCopyState()
  showAddModal.value = true
}
function closeAddModal() {
  showAddModal.value = false
  addSuccess.value = null
  addRoleOpen.value = false
  resetCopyState()
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
function validateAddForm() {
  const errs = {}
  if (!addForm.value.name.trim())  errs.name = 'Enter a full name.'
  if (!addForm.value.email.trim()) errs.email = 'Enter an email address.'
  else if (!EMAIL_RE.test(addForm.value.email.trim())) errs.email = 'That email doesn’t look right.'
  addErrors.value = errs
  return Object.keys(errs).length === 0
}

async function submitAddUser() {
  if (!validateAddForm()) return
  addLoading.value = true
  try {
    const { data } = await api.post('/api/users', {
      name:  addForm.value.name.trim(),
      email: addForm.value.email.trim(),
      role:  addForm.value.role,
    })
    addSuccess.value = { name: addForm.value.name.trim(), tempPassword: data.temp_password }
    await fetchUsers()
  } catch (err) {
    const first = err?.response?.data?.errors ? Object.values(err.response.data.errors).flat()[0] : null
    addErrors.value = { ...addErrors.value, form: first || err?.response?.data?.message || 'Could not create the account. Try again.' }
  } finally {
    addLoading.value = false
  }
}

/* ══ EDIT ROLE ══ */
const editModal   = ref(null)
const editLoading = ref(false)

function openEditModal(u) {
  editModal.value = { user: u, role: u.role }
  editRoleOpen.value = false
  openMenuId.value = null
}
function closeEditModal() { editModal.value = null; editRoleOpen.value = false }
async function submitEditRole() {
  if (!editModal.value) return
  const { user, role } = editModal.value
  editLoading.value = true
  try {
    await api.patch(`/api/users/${user.id}`, { role })
    user.role = role
    editModal.value = null
  } catch (err) {
    console.error('Failed to update role', err)
  } finally {
    editLoading.value = false
  }
}

/* ══ CONFIRM (disable / enable / reset) ══ */
const confirmModal   = ref(null)
const confirmLoading = ref(false)
const confirmError   = ref('')
const resetResult    = ref(null)

function confirmToggleStatus(u) {
  confirmModal.value = { type: u.status === 'active' ? 'disable' : 'enable', user: u }
  confirmError.value = ''
  openMenuId.value = null
}
function confirmResetPassword(u) {
  confirmModal.value = { type: 'reset', user: u }
  confirmError.value = ''
  resetResult.value = null
  resetCopyState()
  openMenuId.value = null
}
function closeConfirmModal() {
  confirmModal.value = null
  confirmError.value = ''
  resetResult.value = null
  resetCopyState()
}

const confirmIcon = computed(() => ({ disable: 'fa-user-slash', enable: 'fa-user-check', reset: 'fa-key' }[confirmModal.value?.type]))
const confirmTitle = computed(() => {
  const u = confirmModal.value?.user
  return {
    disable: `Disable ${u?.name}?`,
    enable:  `Enable ${u?.name}?`,
    reset:   `Reset ${u?.name}’s password?`,
  }[confirmModal.value?.type]
})
const confirmDesc = computed(() => ({
  disable: 'They’ll be signed out and won’t be able to log in until re-enabled. Their past sales and inventory history stays attributed to them.',
  enable:  'They’ll be able to sign in again immediately.',
  reset:   'This generates a new temporary password and clears their current one.',
}[confirmModal.value?.type]))
const confirmActionLabel = computed(() => ({ disable: 'Disable', enable: 'Enable', reset: 'Generate password' }[confirmModal.value?.type]))

async function runConfirmedAction() {
  if (!confirmModal.value) return
  const { type, user } = confirmModal.value
  confirmLoading.value = true
  confirmError.value = ''
  try {
    if (type === 'disable' || type === 'enable') {
      const status = type === 'disable' ? 'disabled' : 'active'
      await api.patch(`/api/users/${user.id}`, { status })
      user.status = status
      confirmModal.value = null
    } else if (type === 'reset') {
      const { data } = await api.post(`/api/users/${user.id}/reset-password`)
      resetResult.value = { tempPassword: data.temp_password }
    }
  } catch (err) {
    console.error('Action failed', err)
    confirmError.value = err?.response?.data?.message || 'That didn’t work. Try again.'
  } finally {
    confirmLoading.value = false
  }
}

/* Warn before leaving the tab with unsaved changes. */
function onBeforeUnload(e) {
  if (!dirty.value) return
  e.preventDefault()
  e.returnValue = ''
}

onMounted(async () => {
  await loadUser()
  // Skip for non-admins: the endpoint would just return 403.
  if (isAdmin.value) fetchUsers()
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
  window.addEventListener('scroll', onScrollOrResizeRowMenu, true)
  window.addEventListener('resize', onScrollOrResizeRowMenu)
  window.addEventListener('beforeunload', onBeforeUnload)
})
onBeforeUnmount(() => {
  clearTimeout(copyTimer)
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('scroll', onScrollOrResizeRowMenu, true)
  window.removeEventListener('resize', onScrollOrResizeRowMenu)
  window.removeEventListener('beforeunload', onBeforeUnload)
})
</script>

<style scoped>
/* ── TOKENS — same set as Home, also available inside the modals
   (they render as siblings of .settings-page, not descendants). ── */
.settings-page, .team-modal-backdrop {
  --s-bg: var(--c-bg); --s-surface: var(--c-surface); --s-raised: var(--c-surface-raised); --s-sunken: var(--c-surface-sunken);
  --s-border: var(--c-border); --s-border-strong: var(--c-border-strong);
  --s-text: var(--c-text-primary); --s-text-2: var(--c-text-secondary); --s-muted: var(--c-text-muted); --s-faint: var(--c-text-faint);
  --s-accent: var(--c-accent); --s-accent-soft: var(--c-accent-soft); --s-accent-border: var(--c-accent-border); --s-ring: var(--c-accent-ring);
  --s-green: #10b981; --s-green-soft: rgba(16,185,129,.12);
  --s-blue: #3b82f6; --s-blue-soft: rgba(59,130,246,.12);
  --s-violet: #6366f1; --s-violet-soft: rgba(99,102,241,.12);
  --s-red: #f43f5e; --s-red-soft: rgba(244,63,94,.12);
  --s-amber: #f59e0b; --s-amber-soft: rgba(245,158,11,.14);
  --s-shadow: 0 1px 2px rgba(16,24,40,.04), 0 10px 28px -14px rgba(16,24,40,.14);
  --s-shadow-hover: 0 2px 4px rgba(16,24,40,.05), 0 18px 40px -16px rgba(16,24,40,.22);
  --s-shine: rgba(255,255,255,.7);
  --s-ease: cubic-bezier(.16, 1, .3, 1);
}
html[data-theme="dark"] .settings-page, html[data-theme="dark"] .team-modal-backdrop {
  --s-green-soft: rgba(16,185,129,.16); --s-blue-soft: rgba(59,130,246,.18); --s-violet-soft: rgba(99,102,241,.2);
  --s-red-soft: rgba(244,63,94,.16); --s-amber-soft: rgba(245,158,11,.16);
  --s-shadow: 0 1px 2px rgba(0,0,0,.3), 0 12px 32px -14px rgba(0,0,0,.55);
  --s-shadow-hover: 0 2px 4px rgba(0,0,0,.35), 0 20px 44px -16px rgba(0,0,0,.7);
  --s-shine: rgba(255,255,255,.07);
}

.settings-page {
  /* SPACE TUNING — identical to Home: reclaims part of the app shell's padding.
     Lower these (or set 0px) if content ever touches the sidebar or top bar. */
  --bleed-x: 30px;
  --bleed-y: 26px;
  --s-gap: 16px;

  min-height: 100%; background: var(--s-bg); padding: 16px 24px 40px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: var(--s-gap);
  -webkit-font-smoothing: antialiased;
}
.settings-page button:focus-visible, .settings-page a:focus-visible, .settings-page input:focus-visible { outline: 2px solid var(--s-accent); outline-offset: 2px; }
.green { color: var(--s-green); }

/* ── header ── */
.page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 26px; font-weight: 800; color: var(--s-text); letter-spacing: -.03em; margin: 0; line-height: 1.15; }
.page-sub { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 4px 0 0; font-size: 13.5px; font-weight: 500; color: var(--s-text-2); }
.status { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; color: var(--s-muted); padding: 2px 10px 2px 8px; background: var(--s-surface); border: 1px solid var(--s-border); border-radius: 999px; }
.status i:not(.live-dot) { font-size: 10px; }
.status--warn { color: var(--s-amber); border-color: rgba(245,158,11,.4); background: var(--s-amber-soft); font-weight: 600; }
.live-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--s-green); animation: pulse 2.2s ease-out infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,.45); } 70%, 100% { box-shadow: 0 0 0 7px rgba(16,185,129,0); } }
.page-header-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* ── buttons ── */
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 38px; padding: 0 16px;
  font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none;
  color: var(--s-text-2); background: var(--s-surface); border: 1px solid var(--s-border-strong); border-radius: 10px;
  box-shadow: 0 1px 2px rgba(16,24,40,.05);
  transition: border-color .15s ease, color .15s ease, transform .15s ease, box-shadow .15s ease, filter .15s ease;
}
.btn:hover:not(:disabled) { border-color: var(--s-accent-border); color: var(--s-text); transform: translateY(-1px); box-shadow: 0 4px 12px -4px rgba(16,24,40,.18); }
.btn:active:not(:disabled) { transform: translateY(0); }
.btn:disabled { opacity: .55; cursor: not-allowed; }
.btn i { font-size: 11px; }
.btn--primary { background: var(--s-accent); border-color: var(--s-accent); color: #fff; box-shadow: 0 1px 2px rgba(16,24,40,.12), inset 0 1px 0 rgba(255,255,255,.18); }
.btn--primary:hover:not(:disabled) { color: #fff; border-color: var(--s-accent); filter: brightness(1.07); }
.btn--danger { background: var(--s-red); border-color: var(--s-red); color: #fff; }
.btn--danger:hover:not(:disabled) { color: #fff; border-color: var(--s-red); filter: brightness(1.07); }
.btn--min { min-width: 130px; }
.btn--block { width: 100%; }

/* ── notices ── */
.notices { display: flex; flex-direction: column; gap: 6px; }
.notice {
  display: flex; align-items: center; gap: 10px; min-height: 38px; padding: 5px 8px 5px 12px;
  border-radius: 10px; font-size: 12.5px; line-height: 1.35;
}
.notice > span, .notice-list { flex: 1; min-width: 0; color: var(--s-text-2); }
.notice-list { margin: 0; padding: 0; list-style: none; }
.notice > i { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.notice--error { background: var(--s-red-soft); border: 1px solid rgba(244,63,94,.28); border-left: 4px solid var(--s-red); }
.notice--error > i { color: var(--s-red); background: rgba(244,63,94,.16); }
.notice--success { background: var(--s-green-soft); border: 1px solid rgba(16,185,129,.3); border-left: 4px solid var(--s-green); }
.notice--success > i { color: var(--s-green); background: rgba(16,185,129,.18); }
.notice-btn { flex-shrink: 0; border: 1px solid var(--s-accent-border); background: transparent; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; color: var(--s-accent); padding: 4px 12px; border-radius: 8px; transition: background-color .15s ease; }
.notice-btn:hover { background: var(--s-accent-soft); }

/* ── cards ── */
.card {
  position: relative; min-width: 0; padding: 20px 22px;
  background: var(--s-surface); border: 1px solid var(--s-border); border-radius: 18px;
  box-shadow: var(--s-shadow);
  transition: box-shadow .3s var(--s-ease), border-color .3s ease;
  animation: rise .7s var(--s-ease) backwards; animation-delay: var(--d, 0ms);
}
.card::before { content: ''; position: absolute; top: 0; left: 24px; right: 24px; height: 1px; pointer-events: none; background: linear-gradient(90deg, transparent, var(--s-shine), transparent); }
.card:hover { box-shadow: var(--s-shadow-hover); border-color: var(--s-border-strong); }
@keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
.card--hero { overflow: hidden; }
.card--hero::after { content: ''; position: absolute; top: -90px; right: -70px; width: 280px; height: 280px; pointer-events: none; background: radial-gradient(circle, color-mix(in srgb, var(--s-accent) 16%, transparent) 0%, transparent 68%); }
.card--hero > * { position: relative; z-index: 1; }

.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
.card-head-l { display: flex; align-items: center; gap: 12px; min-width: 0; }
.card-icon { width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 15px; color: var(--s-accent); background: var(--s-accent-soft); box-shadow: inset 0 0 0 1px var(--s-accent-border); }
.card-icon--blue   { color: var(--s-blue);   background: var(--s-blue-soft);   box-shadow: inset 0 0 0 1px rgba(59,130,246,.22); }
.card-icon--violet { color: var(--s-violet); background: var(--s-violet-soft); box-shadow: inset 0 0 0 1px rgba(99,102,241,.24); }
.card-icon--green  { color: var(--s-green);  background: var(--s-green-soft);  box-shadow: inset 0 0 0 1px rgba(16,185,129,.24); }
.card-title { font-size: 15.5px; font-weight: 700; color: var(--s-text); margin: 0; letter-spacing: -.015em; }
.card-sub { margin: 3px 0 0; font-size: 12.5px; color: var(--s-muted); }

.row-two { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: var(--s-gap); align-items: stretch; }
.row-two--even { grid-template-columns: repeat(2, minmax(0, 1fr)); }

/* ── account summary ── */
.profile { display: flex; align-items: center; gap: 16px; }
.profile-avatar { width: 64px; height: 64px; border-radius: 18px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; letter-spacing: -.02em; }
.profile-text { min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
.profile-name { margin: 0; font-size: 20px; font-weight: 800; color: var(--s-text); letter-spacing: -.025em; overflow-wrap: anywhere; }
.profile-email { margin: 0 0 4px; font-size: 13px; color: var(--s-muted); overflow-wrap: anywhere; }

.mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(118px, 1fr)); gap: 10px; margin: 20px 0 0; }
.ms { padding: 12px 14px; background: var(--s-raised); border: 1px solid var(--s-border); border-radius: 12px; min-width: 0; }
.mini-stats dt { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 500; color: var(--s-muted); white-space: nowrap; }
.mini-stats dt i { font-size: 10px; color: var(--s-faint); }
.mini-stats dd { margin: 5px 0 0; font-size: 16px; font-weight: 700; color: var(--s-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── appearance ── */
.theme-choices { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.theme-opt { display: flex; flex-direction: column; gap: 10px; padding: 10px; font: inherit; cursor: pointer; text-align: left; background: var(--s-raised); border: 1.5px solid var(--s-border); border-radius: 14px; transition: border-color .2s ease, box-shadow .2s ease, transform .2s var(--s-ease); }
.theme-opt:hover { border-color: var(--s-accent-border); transform: translateY(-2px); }
.theme-opt--on { border-color: var(--s-accent); box-shadow: 0 0 0 3px var(--s-ring); }
.theme-prev { display: flex; flex-direction: column; gap: 5px; padding: 10px; border-radius: 9px; height: 62px; }
.theme-prev i { display: block; height: 7px; border-radius: 4px; }
.theme-prev i:nth-child(1) { width: 45%; }
.theme-prev i:nth-child(2) { width: 80%; }
.theme-prev i:nth-child(3) { width: 60%; }
.theme-prev--light { background: #fff; border: 1px solid #e5e7eb; }
.theme-prev--light i { background: #e2e8f0; }
.theme-prev--light i:nth-child(1) { background: #ea580c; }
.theme-prev--dark { background: #1e2130; border: 1px solid #2a2d3e; }
.theme-prev--dark i { background: #3a3f55; }
.theme-prev--dark i:nth-child(1) { background: #fb923c; }
.theme-name { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700; color: var(--s-text-2); }
.theme-name i { font-size: 12px; color: var(--s-faint); }
.theme-opt--on .theme-name, .theme-opt--on .theme-name i { color: var(--s-accent); }

/* ── form ── */
.settings-form { display: flex; flex-direction: column; gap: var(--s-gap); }
.form-stack { display: flex; flex-direction: column; gap: 16px; }
.form-field { display: flex; flex-direction: column; gap: 7px; }
.modal-field { margin-bottom: 14px; text-align: left; }
.form-label { font-size: 12.5px; font-weight: 600; color: var(--s-text-2); }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 13px; color: var(--s-faint); font-size: 12px; pointer-events: none; z-index: 1; transition: color .2s; }
.input-wrap:focus-within .input-icon { color: var(--s-accent); }
.form-input {
  width: 100%; height: 42px; padding: 0 14px 0 36px;
  border: 1.5px solid var(--s-border-strong); border-radius: 10px;
  background: var(--s-sunken); color: var(--s-text);
  font-size: 13.5px; font-family: inherit; outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s;
}
.form-input--plain { padding-left: 14px; }
.form-input::placeholder { color: var(--s-faint); }
.form-input:focus { border-color: var(--s-accent); box-shadow: 0 0 0 3px var(--s-ring); background: var(--s-surface); }
.form-input--pw { padding-right: 42px; }
.form-input--mismatch { border-color: var(--s-red) !important; box-shadow: 0 0 0 3px rgba(244,63,94,.12) !important; }
.pw-toggle { position: absolute; right: 8px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; background: none; border: none; border-radius: 8px; cursor: pointer; color: var(--s-faint); font-size: 13px; transition: color .15s, background-color .15s; }
.pw-toggle:hover { color: var(--s-text-2); background: var(--s-raised); }
.form-hint { font-size: 11.5px; margin: 0; color: var(--s-muted); }
.form-hint--error { color: var(--s-red); }
.form-hint--center { text-align: center; margin-bottom: 10px; }

.pw-strength { display: flex; align-items: center; gap: 8px; }
.pw-strength-bar { flex: 1; height: 5px; border-radius: 5px; background: var(--s-sunken); box-shadow: inset 0 0 0 1px var(--s-border); overflow: hidden; }
.pw-strength-fill { height: 100%; border-radius: 5px; transition: width .3s ease, background-color .3s ease; }
.pw-strength-label { font-size: 11px; font-weight: 700; letter-spacing: .02em; white-space: nowrap; }
.pw-strength--weak   .pw-strength-fill { background: #f43f5e; } .pw-strength--weak   .pw-strength-label { color: #f43f5e; }
.pw-strength--fair   .pw-strength-fill { background: #f59e0b; } .pw-strength--fair   .pw-strength-label { color: #f59e0b; }
.pw-strength--good   .pw-strength-fill { background: #3b82f6; } .pw-strength--good   .pw-strength-label { color: #3b82f6; }
.pw-strength--strong .pw-strength-fill { background: #10b981; } .pw-strength--strong .pw-strength-label { color: #10b981; }

.form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }
.form-actions-note { margin-right: auto; display: inline-flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--s-muted); }
.form-actions-note i { color: var(--s-amber); }

/* ── team ── */
.team-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.team-search { display: flex; align-items: center; gap: 8px; height: 38px; padding: 0 12px; background: var(--s-sunken); border: 1.5px solid var(--s-border-strong); border-radius: 10px; color: var(--s-faint); font-size: 12px; transition: border-color .15s, box-shadow .15s; }
.team-search:focus-within { border-color: var(--s-accent); box-shadow: 0 0 0 3px var(--s-ring); }
.team-search input { width: 150px; border: none; outline: none !important; background: transparent; font: inherit; font-size: 12.5px; color: var(--s-text); }
.team-state { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 40px 20px; color: var(--s-faint); }
.team-state i { font-size: 20px; }
.team-state p { margin: 0; font-size: 13px; font-weight: 500; }
.team-scroll { overflow-x: auto; margin: 0 -22px -8px; padding: 0 12px 8px; }
.team-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.team-table th { text-align: left; padding: 10px 12px; font-size: 11.5px; font-weight: 600; color: var(--s-faint); border-bottom: 1px solid var(--s-border); white-space: nowrap; }
.th-actions { width: 44px; }
.team-table tbody tr { transition: background-color .15s ease; }
.team-table tbody tr:hover { background: var(--s-raised); }
.team-table td { padding: 10px 12px; vertical-align: middle; color: var(--s-text-2); border-bottom: 1px solid var(--s-border); }
.team-table tbody tr:last-child td { border-bottom: none; }
.team-muted { color: var(--s-faint); font-size: 12.5px; white-space: nowrap; }
.team-user-cell { display: flex; align-items: center; gap: 11px; }
.team-user-text { min-width: 0; }
.team-user-name { margin: 0; font-size: 13px; font-weight: 600; color: var(--s-text); line-height: 1.3; }
.team-user-email { margin: 1px 0 0; font-size: 11.5px; color: var(--s-faint); }
.you-tag { margin-left: 8px; font-size: 10px; font-weight: 700; color: var(--s-muted); background: var(--s-sunken); border: 1px solid var(--s-border); padding: 1px 7px; border-radius: 999px; }
.team-avatar { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; }
.team-badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
.role--admin { background: var(--s-accent-soft); color: var(--s-accent); }
.role--cashier { background: rgba(61,127,193,.12); color: #3D7FC1; }
html[data-theme="dark"] .role--cashier { background: rgba(61,127,193,.2); color: #7FB4E8; }
.role--inventory { background: var(--s-green-soft); color: var(--s-green); }
html[data-theme="dark"] .role--inventory { color: #4ADE80; }
.role--default { background: var(--s-sunken); color: var(--s-muted); }
.team-badge--active { background: var(--s-green-soft); color: var(--s-green); }
html[data-theme="dark"] .team-badge--active { color: #4ADE80; }
.team-badge--disabled { background: var(--s-sunken); color: var(--s-faint); }
.team-actions-cell { text-align: right; }
.team-row-menu-wrap { position: relative; display: inline-block; }
.icon-btn-sm { width: 32px; height: 32px; border: 1.5px solid var(--s-border-strong); border-radius: 9px; background: var(--s-surface); color: var(--s-muted); font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color .15s, border-color .15s, color .15s; }
.icon-btn-sm:hover { background: var(--s-accent-soft); border-color: var(--s-accent-border); color: var(--s-accent); }

/* ── modals ── */
.team-modal-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.7); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 16px; }
html[data-theme="dark"] .team-modal-backdrop { background: rgba(0,0,0,.82); }
.team-modal { background: #fff; border: 1px solid #E5E7EB; border-radius: 20px; padding: 30px 28px; width: 400px; max-width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,.35); text-align: center; transition: transform .24s cubic-bezier(.4,0,.2,1), opacity .2s ease; }
.team-modal--edit-role { width: 420px; }
html[data-theme="dark"] .team-modal { background: #1E2130; border-color: #2A2D3E; box-shadow: 0 24px 64px rgba(0,0,0,.6); }
.team-modal-icon { width: 48px; height: 48px; border-radius: 13px; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--s-accent); background: var(--s-accent-soft); border: 1px solid var(--s-accent-border); }
.team-modal-icon--danger { background: var(--s-red-soft); border-color: rgba(244,63,94,.25); color: var(--s-red); }
.team-modal-icon--success { background: var(--s-green-soft); border-color: rgba(16,185,129,.3); color: var(--s-green); }
.team-modal-title { font-size: 16px; font-weight: 700; color: var(--s-text); margin: 0 0 6px; }
.team-modal-desc { font-size: 12.5px; color: var(--s-muted); line-height: 1.6; margin: 0 0 18px; }
.team-modal-actions { display: flex; gap: 10px; justify-content: center; margin-top: 4px; }
.edit-role-subject { display: flex; align-items: center; gap: 10px; background: var(--s-sunken); border: 1px solid var(--s-border); border-radius: 12px; padding: 10px 12px; margin-bottom: 14px; text-align: left; }
.edit-role-subject-text { min-width: 0; }
.edit-role-subject-name { margin: 0; font-size: 13px; font-weight: 700; color: var(--s-text); }
.edit-role-subject-email { margin: 1px 0 0; font-size: 11.5px; color: var(--s-faint); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.temp-password-box { background: var(--s-accent-soft); border: 1px solid var(--s-accent-border); border-radius: 12px; padding: 12px 14px 10px; margin-bottom: 16px; text-align: left; transition: background-color .2s, border-color .2s; }
.temp-password-label { margin: 0 0 6px; font-size: 11.5px; font-weight: 700; color: var(--s-accent); }
.temp-password-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.temp-password-value { font-size: 15px; font-weight: 700; color: var(--s-text); letter-spacing: .02em; font-family: 'Courier New', monospace; user-select: all; overflow-wrap: anywhere; }
.temp-password-box--copied { background: var(--s-green-soft); border-color: rgba(16,185,129,.4); }
.temp-password-box--copied .temp-password-label { color: var(--s-green); }
.copy-btn { display: inline-flex; align-items: center; gap: 6px; height: 32px; padding: 0 12px; border: 1.5px solid var(--s-accent-border); border-radius: 8px; background: var(--s-surface); color: var(--s-accent); font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; flex-shrink: 0; transition: background-color .15s, border-color .15s, color .15s, transform .15s; }
.copy-btn i { font-size: 11px; }
.copy-btn:hover { background: var(--s-accent-soft); }
.copy-btn:active { transform: scale(.96); }
.copy-btn--done, .copy-btn--done:hover { background: var(--s-green); border-color: var(--s-green); color: #fff; animation: copy-pop .28s ease; }
.copy-btn--error, .copy-btn--error:hover { background: var(--s-red); border-color: var(--s-red); color: #fff; }
@keyframes copy-pop { 0% { transform: scale(.92); } 60% { transform: scale(1.06); } 100% { transform: scale(1); } }
.copy-status { min-height: 18px; margin: 8px 0 0; display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; line-height: 1.4; color: var(--s-green); }
.copy-status i { font-size: 11px; flex-shrink: 0; }
.copy-status--error { color: var(--s-red); }
html[data-theme="dark"] .copy-status { color: #4ADE80; }
html[data-theme="dark"] .copy-status--error { color: #F87171; }

/* ── transitions ── */
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.fade-enter-from .team-modal, .fade-leave-to .team-modal { transform: scale(.95) translateY(6px); opacity: 0; }
.dropdown-enter-active { transition: opacity .15s ease, transform .15s ease; }
.dropdown-leave-active { transition: opacity .1s ease, transform .1s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px) scale(.97); }

/* ── responsive ── */
/* Desktop only: pull the page outward into the shell's padding, same as Home */
@media (min-width: 1001px) {
  .settings-page { margin: calc(-1 * var(--bleed-y)) calc(-1 * var(--bleed-x)) 0; }
}
@media (max-width: 1000px) { .row-two, .row-two--even { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 900px) { .settings-page { padding: 12px 14px 32px; --s-gap: 14px; } }
@media (max-width: 600px) {
  .card { padding: 18px 16px; border-radius: 16px; }
  .page-header { flex-direction: column; align-items: stretch; }
  .page-title { font-size: 24px; }
  .page-header-right .btn--primary { display: none; } /* the sticky form actions below cover this on mobile */
  .form-actions { flex-direction: column-reverse; align-items: stretch; }
  .form-actions-note { margin: 0; justify-content: center; }
  .team-tools { width: 100%; }
  .team-search { flex: 1; }
  .team-search input { width: 100%; }
  .team-user-email { display: none; }
  .team-scroll { margin: 0 -16px -8px; }
  .team-modal { padding: 24px 20px; }
  .notice { flex-wrap: wrap; }
}
@media (prefers-reduced-motion: reduce) {
  .settings-page * { transition: none !important; animation: none !important; }
}
</style>

<style>
/* Unscoped: the row menu is teleported to <body>, outside .settings-page,
   so it uses the underlying --c-* tokens directly (same as Layout.vue's popup). */
.team-row-menu { position: fixed; width: 180px; background: var(--c-surface-overlay); border: 1px solid var(--c-border); border-radius: 12px; box-shadow: var(--c-shadow-lg); padding: 6px; z-index: 9999; }
.team-row-menu button { width: 100%; display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 8px; background: none; border: none; cursor: pointer; font-family: inherit; font-size: 12.5px; font-weight: 500; color: var(--c-text-secondary); text-align: left; transition: background-color .15s ease, color .15s ease; }
.team-row-menu button:hover { background: var(--c-surface-raised); color: var(--c-text-primary); }
.team-row-menu-item--danger { color: var(--c-danger) !important; }
.team-row-menu-item--danger:hover { background: var(--c-danger-soft) !important; }
.team-row-menu-divider { height: 1px; background: var(--c-border); margin: 4px 0; }

/* Role dropdown — used by both the Add User and Edit Role modals. */
.role-dd { position: relative; width: 100%; }
.role-dd-trigger { width: 100%; height: 46px; display: flex; align-items: center; gap: 10px; padding: 0 14px; border: 1.5px solid var(--c-border-strong); border-radius: 10px; background: var(--c-surface-sunken); cursor: pointer; font-family: inherit; transition: border-color .18s, box-shadow .18s, background-color .18s; }
.role-dd-trigger:hover { border-color: var(--c-accent-border); }
.role-dd--open .role-dd-trigger, .role-dd-trigger:focus-visible { outline: none; border-color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-ring); background: var(--c-surface); }
.role-dd-icon { width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; }
.role-dd-value { flex: 1; text-align: left; font-size: 13.5px; font-weight: 600; color: var(--c-text-primary); }
.role-dd-chevron { font-size: 11px; color: var(--c-text-faint); transition: transform .18s ease; flex-shrink: 0; }
.role-dd--open .role-dd-chevron { transform: rotate(180deg); color: var(--c-accent); }
.role-dd-menu { position: absolute; left: 0; right: 0; top: calc(100% + 6px); margin: 0; padding: 6px; list-style: none; background: var(--c-surface-overlay); border: 1px solid var(--c-border); border-radius: 12px; box-shadow: var(--c-shadow-lg); z-index: 20; max-height: 260px; overflow-y: auto; }
.role-dd-option { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 8px; cursor: pointer; transition: background-color .15s ease; }
.role-dd-option:hover { background: var(--c-surface-raised); }
.role-dd-option--active { background: var(--c-accent-soft); }
.role-dd-option-text { flex: 1; min-width: 0; text-align: left; }
.role-dd-option-name { display: block; font-size: 13px; font-weight: 600; color: var(--c-text-primary); }
.role-dd-option-desc { display: block; font-size: 11px; color: var(--c-text-faint); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.role-dd-check { font-size: 11px; color: var(--c-accent); flex-shrink: 0; }
</style>