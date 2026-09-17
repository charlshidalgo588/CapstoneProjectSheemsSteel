<template>
  <Layout title="Settings">
    <div class="settings-page">

      <!-- ── PAGE HEADER ── -->
      <div class="page-header">
        <div class="breadcrumb">
          <RouterLink to="/home" class="breadcrumb-link">
            <i class="fa-solid fa-house"></i> Dashboard
          </RouterLink>
          <i class="fa-solid fa-chevron-right breadcrumb-sep"></i>
          <span class="breadcrumb-current">Settings</span>
        </div>

        <div class="header-row">
          <div>
            <p class="page-eyebrow">Configuration</p>
            <h1 class="page-title">Account Settings</h1>
            <p class="page-sub">Manage your account information and security preferences.</p>
          </div>
          <div class="last-updated" v-if="lastUpdated">
            <i class="fa-regular fa-clock"></i>
            Last updated: {{ lastUpdated }}
          </div>
        </div>
      </div>

      <!-- ── ALERTS ── -->
      <transition name="alert-fade">
        <div v-if="successMessage" class="alert alert--success">
          <span class="alert-icon"><i class="fa-solid fa-circle-check"></i></span>
          <span>{{ successMessage }}</span>
          <button class="alert-close" @click="successMessage = ''">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <transition name="alert-fade">
        <div v-if="errors.length" class="alert alert--error">
          <span class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
          <ul class="alert-list">
            <li v-for="(e, i) in errors" :key="i">{{ e }}</li>
          </ul>
          <button class="alert-close" @click="errors = []">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </transition>

      <!-- ── APPEARANCE ── (moved here from the topbar) -->
      <div class="settings-card">
        <div class="settings-card-header">
          <div class="settings-card-icon settings-card-icon--indigo">
            <i class="fa-solid fa-moon"></i>
          </div>
          <div>
            <h2 class="settings-card-title">Appearance</h2>
            <p class="settings-card-sub">Choose how Sheem Steel looks on this device.</p>
          </div>
        </div>

        <div class="appearance-row">
          <div class="appearance-label">
            <p class="appearance-label-title">Dark Mode</p>
            <p class="appearance-label-sub">{{ darkMode ? 'Currently on' : 'Currently off' }}</p>
          </div>

          <button
            class="theme-pill"
            @click="toggleDark"
            :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
          >
            <span class="pill-track" :class="{ 'pill-track--dark': darkMode }">
              <span class="pill-icon pill-icon--sun" :class="{ 'pill-icon--active': !darkMode }">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="4"/>
                  <line x1="12" y1="2"  x2="12" y2="5"/>
                  <line x1="12" y1="19" x2="12" y2="22"/>
                  <line x1="4.22" y1="4.22"   x2="6.34" y2="6.34"/>
                  <line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                  <line x1="2"  y1="12" x2="5"  y2="12"/>
                  <line x1="19" y1="12" x2="22" y2="12"/>
                  <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/>
                  <line x1="17.66" y1="6.34"  x2="19.78" y2="4.22"/>
                </svg>
              </span>
              <span class="pill-thumb" :class="{ 'pill-thumb--right': darkMode }"></span>
              <span class="pill-icon pill-icon--moon" :class="{ 'pill-icon--active': darkMode }">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
              </span>
            </span>
          </button>
        </div>
      </div>

      <!-- ── FORM ── -->
      <form @submit.prevent="saveChanges" class="settings-form">

        <!-- PERSONAL INFORMATION -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon settings-card-icon--blue">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <h2 class="settings-card-title">Personal Information</h2>
              <p class="settings-card-sub">Update your name and email address.</p>
            </div>
          </div>

          <div class="form-grid">
            <div class="form-field">
              <label class="form-label">Full Name</label>
              <div class="input-wrap">
                <i class="fa-solid fa-user input-icon"></i>
                <input
                  type="text"
                  v-model="form.name"
                  class="form-input"
                  placeholder="Enter your full name"
                />
              </div>
            </div>

            <div class="form-field">
              <label class="form-label">Email Address</label>
              <div class="input-wrap">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input
                  type="email"
                  v-model="form.email"
                  class="form-input"
                  placeholder="Enter your email"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- SECURITY -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon settings-card-icon--orange">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
              <h2 class="settings-card-title">Security</h2>
              <p class="settings-card-sub">Change your password. Leave blank to keep your current one.</p>
            </div>
          </div>

          <div class="form-stack">
            <!-- Current password -->
            <div class="form-field form-field--full">
              <label class="form-label">Current Password</label>
              <div class="input-wrap">
                <i class="fa-solid fa-lock input-icon"></i>
                <input
                  :type="visibility.current ? 'text' : 'password'"
                  v-model="form.current_password"
                  class="form-input form-input--pw"
                  placeholder="Enter your current password"
                />
                <button type="button" class="pw-toggle" @click="toggle('current')">
                  <i :class="visibility.current ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                </button>
              </div>
            </div>

            <!-- New + Confirm -->
            <div class="form-grid">
              <div class="form-field">
                <label class="form-label">New Password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input
                    :type="visibility.new ? 'text' : 'password'"
                    v-model="form.new_password"
                    class="form-input form-input--pw"
                    placeholder="Enter new password"
                  />
                  <button type="button" class="pw-toggle" @click="toggle('new')">
                    <i :class="visibility.new ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <!-- strength bar -->
                <div class="pw-strength" v-if="form.new_password">
                  <div class="pw-strength-bar">
                    <div class="pw-strength-fill" :class="pwStrength.cls" :style="{ width: pwStrength.pct }"></div>
                  </div>
                  <span class="pw-strength-label" :class="pwStrength.cls">{{ pwStrength.label }}</span>
                </div>
              </div>

              <div class="form-field">
                <label class="form-label">Confirm New Password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-key input-icon"></i>
                  <input
                    :type="visibility.confirm ? 'text' : 'password'"
                    v-model="form.new_password_confirmation"
                    class="form-input form-input--pw"
                    :class="{ 'form-input--mismatch': pwMismatch }"
                    placeholder="Confirm new password"
                  />
                  <button type="button" class="pw-toggle" @click="toggle('confirm')">
                    <i :class="visibility.confirm ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                  </button>
                </div>
                <p class="form-hint form-hint--error" v-if="pwMismatch">Passwords do not match.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="form-actions">
          <RouterLink to="/home" class="btn btn--ghost">
            <i class="fa-solid fa-arrow-left"></i> Cancel
          </RouterLink>
          <button type="submit" class="btn btn--primary" :disabled="saving">
            <i class="fa-solid fa-floppy-disk"></i>
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
        </div>

      </form>

      <!-- ── TEAM ── (admin-only account management, lives here instead of
           its own page/nav item so it's never visible to non-admins.
           v-if="isAdmin" is the frontend half of that; UserController's
           authorizeAdmin() is the half that actually matters, since a
           hidden button is not security — this just keeps a Cashier
           from ever seeing a card they can't use in the first place. -->
      <div class="settings-card" v-if="isAdmin">
        <div class="settings-card-header team-card-header">
          <div class="team-card-header-left">
            <div class="settings-card-icon settings-card-icon--teal">
              <i class="fa-solid fa-users"></i>
            </div>
            <div>
              <h2 class="settings-card-title">Team</h2>
              <p class="settings-card-sub">{{ users.length }} account{{ users.length === 1 ? '' : 's' }} with access to this system.</p>
            </div>
          </div>
          <button type="button" class="btn btn--primary btn--sm" @click="openAddModal">
            <i class="fa-solid fa-plus"></i> Add User
          </button>
        </div>

        <div class="team-body">
          <div v-if="teamLoading" class="team-state">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <p>Loading team…</p>
          </div>

          <div v-else-if="users.length === 0" class="team-state">
            <i class="fa-regular fa-user"></i>
            <p>No accounts yet. Add the first one.</p>
          </div>

          <table v-else class="team-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last active</th>
                <th class="th-actions"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in users" :key="u.id">
                <td>
                  <div class="team-user-cell">
                    <span class="team-avatar" :class="roleClass(u.role)">{{ initials(u.name) }}</span>
                    <div class="team-user-text">
                      <p class="team-user-name">{{ u.name }}</p>
                      <p class="team-user-email">{{ u.email }}</p>
                    </div>
                  </div>
                </td>
                <td><span class="team-badge" :class="roleClass(u.role)">{{ u.role }}</span></td>
                <td>
                  <span class="team-badge" :class="u.status === 'active' ? 'team-badge--active' : 'team-badge--disabled'">
                    {{ u.status === 'active' ? 'Active' : 'Disabled' }}
                  </span>
                </td>
                <td class="team-muted">{{ timeAgo(u.last_active_at) }}</td>
                <td class="team-actions-cell">
                  <div class="team-row-menu-wrap">
                    <button type="button" class="icon-btn-sm" :ref="el => setRowButtonRef(u.id, el)" @click.stop="toggleRowMenu(u.id)" aria-label="Actions">
                      <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <Teleport to="body">
                      <transition name="dropdown">
                        <div v-if="openMenuId === u.id" class="team-row-menu" :style="rowMenuStyle" @click.stop>
                        <button type="button" @click="openEditModal(u)">
                          <i class="fa-solid fa-pen"></i> Edit role
                        </button>
                        <button type="button" @click="confirmResetPassword(u)">
                          <i class="fa-solid fa-key"></i> Reset password
                        </button>
                        <div class="team-row-menu-divider"></div>
                        <button v-if="u.status === 'active'" type="button" class="team-row-menu-item--danger" @click="confirmToggleStatus(u)">
                          <i class="fa-solid fa-user-slash"></i> Disable
                        </button>
                        <button v-else type="button" @click="confirmToggleStatus(u)">
                          <i class="fa-solid fa-user-check"></i> Enable
                        </button>
                      </div>
                    </transition>
                    </Teleport>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- ADD USER MODAL -->
    <transition name="fade">
      <div v-if="isAdmin && showAddModal" class="team-modal-backdrop" @click.self="closeAddModal">
        <div class="team-modal">

          <template v-if="!addSuccess">
            <div class="team-modal-icon">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2 class="team-modal-title">Add a team member</h2>
            <p class="team-modal-desc">They'll sign in with these details and set their own password after.</p>

            <form @submit.prevent="submitAddUser" novalidate>
              <div class="form-field" style="margin-bottom: 14px;">
                <label class="form-label">Full Name</label>
                <input v-model="addForm.name" class="form-input" :class="{ 'form-input--mismatch': addErrors.name }" placeholder="Maria Santos" @input="addErrors.name = ''" />
                <p v-if="addErrors.name" class="form-hint form-hint--error">{{ addErrors.name }}</p>
              </div>
              <div class="form-field" style="margin-bottom: 14px;">
                <label class="form-label">Email Address</label>
                <input v-model="addForm.email" type="email" class="form-input" :class="{ 'form-input--mismatch': addErrors.email }" placeholder="maria@sheems.example" @input="addErrors.email = ''" />
                <p v-if="addErrors.email" class="form-hint form-hint--error">{{ addErrors.email }}</p>
              </div>
              <div class="form-field" style="margin-bottom: 18px; text-align: left;">
                <label class="form-label">Role</label>
                <div class="role-dd" :class="{ 'role-dd--open': addRoleOpen }" ref="addRoleRoot">
                  <button
                    type="button"
                    class="role-dd-trigger"
                    aria-haspopup="listbox"
                    :aria-expanded="addRoleOpen"
                    @click.stop="toggleAddRole"
                  >
                    <span class="role-dd-icon" :class="roleClass(addForm.role)">
                      <i class="fa-solid" :class="roleIcon(addForm.role)"></i>
                    </span>
                    <span class="role-dd-value">{{ addForm.role || 'Select a role' }}</span>
                    <i class="fa-solid fa-chevron-down role-dd-chevron"></i>
                  </button>
                  <transition name="dropdown">
                    <ul v-if="addRoleOpen" class="role-dd-menu" role="listbox" @click.stop>
                      <li
                        v-for="r in ROLE_OPTIONS" :key="r"
                        role="option"
                        :aria-selected="addForm.role === r"
                        class="role-dd-option"
                        :class="{ 'role-dd-option--active': addForm.role === r }"
                        @click="selectAddRole(r)"
                      >
                        <span class="role-dd-icon" :class="roleClass(r)">
                          <i class="fa-solid" :class="roleIcon(r)"></i>
                        </span>
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

              <p v-if="addErrors.form" class="form-hint form-hint--error" style="text-align:center; margin-bottom: 10px;">{{ addErrors.form }}</p>

              <div class="team-modal-actions">
                <button type="button" class="btn btn--ghost" :disabled="addLoading" @click="closeAddModal">Cancel</button>
                <button type="submit" class="btn btn--primary" :disabled="addLoading" style="min-width: 140px; justify-content: center;">
                  <i v-if="addLoading" class="fa-solid fa-spinner fa-spin"></i>
                  <span>{{ addLoading ? 'Creating…' : 'Create account' }}</span>
                </button>
              </div>
            </form>
          </template>

          <template v-else>
            <div class="team-modal-icon team-modal-icon--success">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2 class="team-modal-title">{{ addSuccess.name }}'s account is ready</h2>
            <p class="team-modal-desc">Share this password with them directly — it won't be shown again. They'll be asked to set their own on first sign-in.</p>

            <div class="temp-password-box">
              <p class="temp-password-label">Temporary password</p>
              <div class="temp-password-row">
                <code class="temp-password-value">{{ addSuccess.tempPassword }}</code>
                <button type="button" class="icon-btn-sm" @click="copyPassword(addSuccess.tempPassword)" aria-label="Copy password">
                  <i class="fa-solid fa-copy"></i>
                </button>
              </div>
            </div>

            <button type="button" class="btn btn--primary" style="width:100%; justify-content:center;" @click="closeAddModal">Done</button>
          </template>

        </div>
      </div>
    </transition>

    <!-- EDIT ROLE MODAL -->
    <transition name="fade">
      <div v-if="isAdmin && editModal" class="team-modal-backdrop" @click.self="closeEditModal">
        <div class="team-modal team-modal--edit-role">
          <div class="team-modal-icon">
            <i class="fa-solid fa-user-gear"></i>
          </div>
          <h2 class="team-modal-title">Edit role</h2>

          <div class="edit-role-subject">
            <span class="team-avatar" :class="roleClass(editModal.user.role)">{{ initials(editModal.user.name) }}</span>
            <div class="edit-role-subject-text">
              <p class="edit-role-subject-name">{{ editModal.user.name }}</p>
              <p class="edit-role-subject-email">{{ editModal.user.email }}</p>
            </div>
          </div>

          <p class="team-modal-desc">Changes apply the next time {{ editModal.user.name }} loads the app.</p>

          <div class="form-field" style="margin-bottom: 20px; text-align: left;">
            <label class="form-label">Role</label>
            <div class="role-dd" :class="{ 'role-dd--open': editRoleOpen }" ref="editRoleRoot">
              <button
                type="button"
                class="role-dd-trigger"
                aria-haspopup="listbox"
                :aria-expanded="editRoleOpen"
                @click.stop="toggleEditRole"
              >
                <span class="role-dd-icon" :class="roleClass(editModal.role)">
                  <i class="fa-solid" :class="roleIcon(editModal.role)"></i>
                </span>
                <span class="role-dd-value">{{ editModal.role || 'Select a role' }}</span>
                <i class="fa-solid fa-chevron-down role-dd-chevron"></i>
              </button>
              <transition name="dropdown">
                <ul v-if="editRoleOpen" class="role-dd-menu" role="listbox" @click.stop>
                  <li
                    v-for="r in ROLE_OPTIONS" :key="r"
                    role="option"
                    :aria-selected="editModal.role === r"
                    class="role-dd-option"
                    :class="{ 'role-dd-option--active': editModal.role === r }"
                    @click="selectEditRole(r)"
                  >
                    <span class="role-dd-icon" :class="roleClass(r)">
                      <i class="fa-solid" :class="roleIcon(r)"></i>
                    </span>
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
            <button type="button" class="btn btn--ghost" :disabled="editLoading" @click="closeEditModal">Cancel</button>
            <button type="button" class="btn btn--primary" :disabled="editLoading" style="min-width: 120px; justify-content: center;" @click="submitEditRole">
              <i v-if="editLoading" class="fa-solid fa-spinner fa-spin"></i>
              <span>{{ editLoading ? 'Saving…' : 'Save role' }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- CONFIRM MODAL — disable / enable / reset password -->
    <transition name="fade">
      <div v-if="isAdmin && confirmModal" class="team-modal-backdrop" @click.self="closeConfirmModal">
        <div class="team-modal">

          <template v-if="confirmModal.type === 'reset' && resetResult">
            <div class="team-modal-icon team-modal-icon--success">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <h2 class="team-modal-title">Password reset</h2>
            <p class="team-modal-desc">Share this with {{ confirmModal.user.name }} — it won't be shown again.</p>
            <div class="temp-password-box">
              <p class="temp-password-label">New temporary password</p>
              <div class="temp-password-row">
                <code class="temp-password-value">{{ resetResult.tempPassword }}</code>
                <button type="button" class="icon-btn-sm" @click="copyPassword(resetResult.tempPassword)" aria-label="Copy password">
                  <i class="fa-solid fa-copy"></i>
                </button>
              </div>
            </div>
            <button type="button" class="btn btn--primary" style="width:100%; justify-content:center;" @click="closeConfirmModal">Done</button>
          </template>

          <template v-else>
            <div class="team-modal-icon" :class="{ 'team-modal-icon--danger': confirmModal.type === 'disable' }">
              <i class="fa-solid" :class="confirmIcon"></i>
            </div>
            <h2 class="team-modal-title">{{ confirmTitle }}</h2>
            <p class="team-modal-desc">{{ confirmDesc }}</p>
            <div class="team-modal-actions">
              <button type="button" class="btn btn--ghost" :disabled="confirmLoading" @click="closeConfirmModal">Cancel</button>
              <button
                type="button"
                class="btn"
                :class="confirmModal.type === 'disable' ? 'btn--danger' : 'btn--primary'"
                :disabled="confirmLoading"
                style="min-width: 110px; justify-content: center;"
                @click="runConfirmedAction"
              >
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

/* ══════════════════════════════════════════════════════════
   ROLE DROPDOWN — a themed replacement for the native <select>
   used for the Role field. Native selects render their popup
   with the OS/browser's own colors, which is why it showed up
   as a plain white box regardless of dark mode. This is plain
   markup (see the two "role-dd" blocks in the template, one for
   the Add User modal and one for the Edit Role modal) styled
   entirely with our own tokens so it matches the theme, plus a
   little state/helpers below to drive it.
══════════════════════════════════════════════════════════ */
const ROLE_META = {
  Administrator: { icon: 'fa-user-shield', desc: 'Full access, including team & settings' },
  Cashier: { icon: 'fa-cash-register', desc: 'Point of sale and daily transactions' },
  'Inventory Staff': { icon: 'fa-boxes-stacked', desc: 'Manage stock and inventory records' },
}
function roleIcon(role) { return ROLE_META[role]?.icon || 'fa-user' }
function roleDesc(role) { return ROLE_META[role]?.desc || '' }

const addRoleOpen  = ref(false)
const editRoleOpen = ref(false)
const addRoleRoot  = ref(null)
const editRoleRoot = ref(null)

function toggleAddRole() {
  addRoleOpen.value = !addRoleOpen.value
  editRoleOpen.value = false
}
function selectAddRole(r) {
  addForm.value.role = r
  addRoleOpen.value = false
}
function toggleEditRole() {
  editRoleOpen.value = !editRoleOpen.value
  addRoleOpen.value = false
}
function selectEditRole(r) {
  if (editModal.value) editModal.value.role = r
  editRoleOpen.value = false
}

const lastUpdated    = ref('')
const successMessage = ref('')
const errors         = ref([])
const saving         = ref(false)

// Who's actually looking at this page — drives whether the Team card
// (below) renders at all. A Cashier should never see it, not just be
// blocked from using it once they do.
const currentUser = ref(null)
const isAdmin     = computed(() => currentUser.value?.role === 'Administrator')

const form = ref({
  name:                     '',
  email:                    '',
  current_password:         '',
  new_password:             '',
  new_password_confirmation:'',
})

const visibility = ref({ current: false, new: false, confirm: false })
const toggle = f => { visibility.value[f] = !visibility.value[f] }

/* password strength */
const pwStrength = computed(() => {
  const pw = form.value.new_password
  if (!pw) return { pct:'0%', cls:'', label:'' }
  let score = 0
  if (pw.length >= 8)              score++
  if (pw.length >= 12)             score++
  if (/[A-Z]/.test(pw))           score++
  if (/[0-9]/.test(pw))           score++
  if (/[^A-Za-z0-9]/.test(pw))   score++
  if (score <= 1) return { pct:'25%',  cls:'pw-strength--weak',   label:'Weak' }
  if (score <= 2) return { pct:'50%',  cls:'pw-strength--fair',   label:'Fair' }
  if (score <= 3) return { pct:'75%',  cls:'pw-strength--good',   label:'Good' }
  return              { pct:'100%', cls:'pw-strength--strong', label:'Strong' }
})

const pwMismatch = computed(() =>
  form.value.new_password_confirmation &&
  form.value.new_password !== form.value.new_password_confirmation
)

async function loadUser() {
  try {
    const res = await api.get('/api/user')
    form.value.name  = res.data.name
    form.value.email = res.data.email
    // Powers the Team card's visibility below — if your /api/user
    // response doesn't include `role`, check that it isn't excluded by
    // a $hidden/$visible array on the User model; it's a plain column
    // now (see the migration), not something that needs to be hidden.
    currentUser.value = res.data
    lastUpdated.value = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' })
  } catch (e) { console.error(e) }
}

async function saveChanges() {
  if (pwMismatch.value) return
  successMessage.value = ''
  errors.value         = []
  saving.value         = true
  try {
    await api.put('/api/user', { name: form.value.name, email: form.value.email })
    if (form.value.new_password) {
      await api.put('/api/user/password', {
        current_password:          form.value.current_password,
        new_password:              form.value.new_password,
        new_password_confirmation: form.value.new_password_confirmation,
      })
    }
    successMessage.value = 'Your changes have been saved successfully.'
    form.value.current_password          = ''
    form.value.new_password              = ''
    form.value.new_password_confirmation = ''
    lastUpdated.value = new Date().toLocaleDateString('en-US', { month:'short', day:'2-digit', year:'numeric' })
  } catch (err) {
    errors.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat()
      : ['Something went wrong. Please try again.']
  } finally {
    saving.value = false
  }
}

/* ══════════════════════════════════════════════════════════
   TEAM — admin-only account management. See UserController's
   authorizeAdmin() for the server-side gate this UI depends on;
   a non-admin loading this page would just get a failed request
   here (no separate frontend role check exists yet).
══════════════════════════════════════════════════════════ */

const ROLE_OPTIONS = ['Administrator', 'Cashier', 'Inventory Staff']

const users      = ref([])
const teamLoading = ref(true)

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
  return (name || '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(w => w[0].toUpperCase())
    .join('')
}

function roleClass(role) {
  const key = (role || '').toLowerCase().replace(/\s+/g, '-')
  if (key === 'administrator')    return 'role--admin'
  if (key === 'cashier')          return 'role--cashier'
  if (key === 'inventory-staff')  return 'role--inventory'
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

function copyPassword(pw) {
  navigator.clipboard?.writeText(pw).catch(() => {})
}

const openMenuId = ref(null)
// The menu is teleported to <body> (see template) so .settings-card's
// overflow: hidden can't clip it — that was the actual bug, not menu
// direction. Coordinates are measured fresh each time it opens/scrolls,
// same pattern as the account popup in Layout.vue.
const openMenuCoords = ref({ top: 0, left: 0 })
const rowButtonEls = {}
function setRowButtonRef(id, el) {
  if (el) rowButtonEls[id] = el
}

function measureRowMenu(id) {
  const btn = rowButtonEls[id]
  if (!btn) return
  const rect = btn.getBoundingClientRect()
  const menuWidth = 180
  const maxLeft = window.innerWidth - menuWidth - 10
  openMenuCoords.value = {
    top:  rect.bottom + 6,
    left: Math.min(rect.right - menuWidth, Math.max(10, maxLeft)),
  }
}

async function toggleRowMenu(id) {
  if (openMenuId.value === id) {
    openMenuId.value = null
    return
  }
  openMenuId.value = id
  await nextTick()
  measureRowMenu(id)
}

function onScrollOrResizeRowMenu() {
  if (openMenuId.value) measureRowMenu(openMenuId.value)
}

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
  if (e.key === 'Escape') {
    addRoleOpen.value = false
    editRoleOpen.value = false
  }
}

const showAddModal = ref(false)
const addForm    = ref({ name: '', email: '', role: 'Cashier' })
const addErrors  = ref({})
const addLoading = ref(false)
const addSuccess = ref(null)

function openAddModal() {
  addForm.value    = { name: '', email: '', role: 'Cashier' }
  addErrors.value  = {}
  addSuccess.value = null
  addRoleOpen.value = false
  showAddModal.value = true
}
function closeAddModal() {
  showAddModal.value = false
  addSuccess.value   = null
  addRoleOpen.value  = false
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
function validateAddForm() {
  const errs = {}
  if (!addForm.value.name.trim())  errs.name  = 'Enter a full name.'
  if (!addForm.value.email.trim()) errs.email = 'Enter an email address.'
  else if (!EMAIL_RE.test(addForm.value.email.trim())) errs.email = 'That email doesn\u2019t look right.'
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
    addErrors.value = { ...addErrors.value, form: err?.response?.data?.message || 'Could not create the account. Try again.' }
  } finally {
    addLoading.value = false
  }
}

const editModal   = ref(null)
const editLoading = ref(false)

function openEditModal(u) {
  editModal.value = { user: u, role: u.role }
  editRoleOpen.value = false
  openMenuId.value = null
}
function closeEditModal() {
  editModal.value = null
  editRoleOpen.value = false
}
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

const confirmModal   = ref(null)
const confirmLoading = ref(false)
const resetResult    = ref(null)

function confirmToggleStatus(u) {
  confirmModal.value = { type: u.status === 'active' ? 'disable' : 'enable', user: u }
  openMenuId.value = null
}
function confirmResetPassword(u) {
  confirmModal.value = { type: 'reset', user: u }
  resetResult.value  = null
  openMenuId.value = null
}
function closeConfirmModal() {
  confirmModal.value = null
  resetResult.value  = null
}

const confirmIcon = computed(() => ({
  disable: 'fa-user-slash',
  enable:  'fa-user-check',
  reset:   'fa-key',
}[confirmModal.value?.type]))

const confirmTitle = computed(() => {
  const u = confirmModal.value?.user
  return {
    disable: `Disable ${u?.name}?`,
    enable:  `Enable ${u?.name}?`,
    reset:   `Reset ${u?.name}'s password?`,
  }[confirmModal.value?.type]
})

const confirmDesc = computed(() => ({
  disable: 'They\u2019ll be signed out and won\u2019t be able to log in until re-enabled. Their past sales and inventory history stays attributed to them.',
  enable:  'They\u2019ll be able to sign in again immediately.',
  reset:   'This generates a new temporary password and clears their current one.',
}[confirmModal.value?.type]))

const confirmActionLabel = computed(() => ({
  disable: 'Disable',
  enable:  'Enable',
  reset:   'Generate password',
}[confirmModal.value?.type]))

async function runConfirmedAction() {
  if (!confirmModal.value) return
  const { type, user } = confirmModal.value
  confirmLoading.value = true
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
  } finally {
    confirmLoading.value = false
  }
}

onMounted(async () => {
  await loadUser()
  // Skip this entirely for non-admins — previously this fired for
  // every user regardless of role, guaranteeing a 403 from
  // UserController@authorizeAdmin for anyone who wasn't an admin.
  if (isAdmin.value) {
    fetchUsers()
  }
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onDocumentKeydown)
  window.addEventListener('scroll', onScrollOrResizeRowMenu, true)
  window.addEventListener('resize', onScrollOrResizeRowMenu)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('scroll', onScrollOrResizeRowMenu, true)
  window.removeEventListener('resize', onScrollOrResizeRowMenu)
})
</script>

<style scoped>
/* ── TOKEN BRIDGE ── */
/* Same --s-* bridge as .settings-page below, but scoped to the modal
   backdrop instead. The three modals render as siblings of
   .settings-page (see the template), not descendants of it, so without
   this the --s-* variables buttons/icons/inputs inside the modals rely
   on are simply undefined there — e.g. .btn--primary's background
   silently fails and falls through to its plain white text color,
   which is invisible in light mode. This makes the same tokens
   available inside the modals too. */
.team-modal-backdrop {
  --s-bg:             var(--c-bg);
  --s-surface:        var(--c-surface);
  --s-surface-raised: var(--c-surface-raised);
  --s-surface-sunken: var(--c-surface-sunken);
  --s-border:         var(--c-border);
  --s-border-strong:  var(--c-border-strong);
  --s-text-primary:   var(--c-text-primary);
  --s-text-secondary: var(--c-text-secondary);
  --s-text-muted:     var(--c-text-muted);
  --s-text-faint:     var(--c-text-faint);
  --s-accent:         var(--c-accent);
  --s-accent-soft:    var(--c-accent-soft);
  --s-accent-border:  var(--c-accent-border);
  --s-ring:           var(--c-accent-ring);
  --s-shadow:         var(--c-shadow-sm);
  --s-shadow-md:      var(--c-shadow-md);
  --s-green:          #10b981;
  --s-red:            #f43f5e;
  --s-blue:           #3b82f6;
}

.settings-page {
  --s-bg:             var(--c-bg);
  --s-surface:        var(--c-surface);
  --s-surface-raised: var(--c-surface-raised);
  --s-surface-sunken: var(--c-surface-sunken);
  --s-border:         var(--c-border);
  --s-border-strong:  var(--c-border-strong);
  --s-text-primary:   var(--c-text-primary);
  --s-text-secondary: var(--c-text-secondary);
  --s-text-muted:     var(--c-text-muted);
  --s-text-faint:     var(--c-text-faint);
  --s-accent:         var(--c-accent);
  --s-accent-soft:    var(--c-accent-soft);
  --s-accent-border:  var(--c-accent-border);
  --s-ring:           var(--c-accent-ring);
  --s-shadow:         var(--c-shadow-sm);
  --s-shadow-md:      var(--c-shadow-md);
  --s-green:          #10b981;
  --s-red:            #f43f5e;
  --s-blue:           #3b82f6;
  --radius: 16px;

  min-height: 100%;
  background: var(--s-bg);
  padding: 28px 28px 64px;
  font-family: 'Inter', system-ui, sans-serif;
  display: flex; flex-direction: column; gap: 24px;
  max-width: 860px;
  margin: 0 auto;
  transition: background-color .22s ease;
}

/* ── PAGE HEADER ── */
.breadcrumb {
  display: flex; align-items: center; gap: 8px;
  font-size: 12.5px; margin-bottom: 14px;
}
.breadcrumb-link {
  color: var(--s-text-muted); text-decoration: none; font-weight: 500;
  display: flex; align-items: center; gap: 5px;
  transition: color .15s;
}
.breadcrumb-link:hover { color: var(--s-accent); }
.breadcrumb-sep   { font-size: 9px; color: var(--s-text-faint); }
.breadcrumb-current { color: var(--s-text-secondary); font-weight: 600; }

.header-row {
  display: flex; align-items: flex-start;
  justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.page-eyebrow {
  font-size: 11px; font-weight: 700; letter-spacing: .12em;
  color: var(--s-accent); text-transform: uppercase; margin: 0 0 5px;
}
.page-title {
  font-size: 26px; font-weight: 800;
  color: var(--s-text-primary);
  letter-spacing: -.03em; margin: 0 0 4px;
  transition: color .22s;
}
.page-sub {
  font-size: 13px; color: var(--s-text-muted); margin: 0;
}
.last-updated {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--s-text-faint);
  background: var(--s-surface-raised);
  border: 1px solid var(--s-border);
  border-radius: 8px; padding: 6px 12px;
  white-space: nowrap;
  transition: background .22s, border-color .22s;
}

/* ── ALERTS ── */
.alert {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px; border-radius: 12px;
  font-size: 13.5px; font-weight: 500;
  border: 1px solid transparent;
  position: relative;
}
.alert--success {
  background: rgba(16,185,129,.10);
  border-color: rgba(16,185,129,.25);
  color: var(--s-green);
}
.alert--error {
  background: rgba(244,63,94,.09);
  border-color: rgba(244,63,94,.22);
  color: var(--s-red);
}
html[data-theme="dark"] .alert--success {
  background: rgba(16,185,129,.12);
  border-color: rgba(16,185,129,.28);
  color: #4ADE80;
}
html[data-theme="dark"] .alert--error {
  background: rgba(244,63,94,.13);
  border-color: rgba(244,63,94,.30);
  color: #F87171;
}
.alert-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }
.alert-list { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 3px; flex: 1; }
.alert-close {
  background: none; border: none; cursor: pointer;
  color: inherit; opacity: .6; margin-left: auto; flex-shrink: 0;
  font-size: 13px; padding: 2px; line-height: 1;
  transition: opacity .15s;
}
.alert-close:hover { opacity: 1; }

.alert-fade-enter-active, .alert-fade-leave-active { transition: opacity .22s ease, transform .22s ease; }
.alert-fade-enter-from, .alert-fade-leave-to { opacity: 0; transform: translateY(-6px); }

/* ── SETTINGS FORM ── */
.settings-form { display: flex; flex-direction: column; gap: 20px; }

/* ── SETTINGS CARD ── */
.settings-card {
  background: var(--s-surface);
  border: 1px solid var(--s-border);
  border-radius: var(--radius);
  box-shadow: var(--s-shadow);
  overflow: hidden;
  transition: background-color .22s, border-color .22s;
}

.settings-card-header {
  display: flex; align-items: flex-start; gap: 14px;
  padding: 22px 24px 18px;
  border-bottom: 1px solid var(--s-border);
  transition: border-color .22s;
}
.settings-card-icon {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
}
.settings-card-icon--blue   { background: rgba(59,130,246,.12); color: var(--s-blue); }
.settings-card-icon--orange { background: var(--s-accent-soft); color: var(--s-accent); }
.settings-card-icon--indigo { background: rgba(129,140,248,.14); color: #818CF8; }
.settings-card-icon--teal   { background: rgba(16,185,129,.12); color: var(--s-green); }
html[data-theme="dark"] .settings-card-icon--teal { background: rgba(16,185,129,.18); }

.settings-card-title {
  font-size: 15px; font-weight: 700; color: var(--s-text-primary);
  margin: 0 0 3px; transition: color .22s;
}
.settings-card-sub {
  font-size: 12.5px; color: var(--s-text-muted); margin: 0;
}

/* ── APPEARANCE ── */
.appearance-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px;
}
.appearance-label-title { font-size: 13.5px; font-weight: 600; color: var(--s-text-primary); }
.appearance-label-sub   { font-size: 12px; color: var(--s-text-faint); margin-top: 2px; }

.theme-pill {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  outline: none;
  flex-shrink: 0;
}
.theme-pill:focus-visible .pill-track {
  box-shadow: 0 0 0 3px var(--s-ring), 0 0 0 1px var(--s-accent);
}

.pill-track {
  position: relative;
  display: flex;
  align-items: center;
  width: 64px;
  height: 30px;
  border-radius: 999px;
  background: var(--s-surface-sunken);
  border: 1.5px solid var(--s-border-strong);
  padding: 0 4px;
  transition: background-color 0.26s ease, border-color 0.26s ease, box-shadow 0.18s ease;
  gap: 0;
  justify-content: space-between;
}
.pill-track--dark {
  background: #1E2130;
  border-color: var(--s-accent-border);
  box-shadow: 0 0 10px rgba(251,146,60,0.15);
}
.pill-track:hover {
  border-color: var(--s-accent-border);
  box-shadow: 0 0 0 3px var(--s-ring);
}

.pill-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  z-index: 1;
  transition: color 0.22s ease, opacity 0.22s ease;
  flex-shrink: 0;
}
.pill-icon--sun  { color: var(--s-text-faint); }
.pill-icon--moon { color: var(--s-text-faint); }
.pill-icon--active.pill-icon--sun  { color: #F59E0B; }
.pill-icon--active.pill-icon--moon { color: #818CF8; }

.pill-thumb {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #FFFFFF;
  box-shadow: 0 1px 4px rgba(0,0,0,0.20), 0 2px 8px rgba(0,0,0,0.12);
  transition: transform 0.26s cubic-bezier(.4,0,.2,1), background-color 0.26s ease;
  z-index: 2;
}
.pill-thumb--right {
  transform: translateX(34px);
  background: var(--s-accent);
  box-shadow: 0 1px 4px rgba(0,0,0,0.30), 0 0 8px rgba(251,146,60,0.40);
}

/* FORM FIELDS inside card */
.form-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 22px 24px; }
.form-stack { display: flex; flex-direction: column; gap: 0; }
.form-stack .form-field--full { padding: 22px 24px 0; }
.form-stack .form-grid { padding-top: 20px; padding-bottom: 22px; }

.form-field { display: flex; flex-direction: column; gap: 7px; }
.form-field--full { width: 100%; }

.form-label {
  font-size: 12.5px; font-weight: 600;
  color: var(--s-text-secondary);
  letter-spacing: .01em;
  transition: color .22s;
}

/* INPUT WRAP */
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
  position: absolute; left: 13px;
  color: var(--s-text-faint); font-size: 12px;
  pointer-events: none; transition: color .22s;
  z-index: 1;
}

.form-input {
  width: 100%; height: 42px;
  padding: 0 14px 0 36px;
  border: 1.5px solid var(--s-border-strong);
  border-radius: 10px;
  background: var(--s-surface-sunken);
  color: var(--s-text-primary);
  font-size: 13.5px; font-family: inherit;
  outline: none;
  transition: border-color .18s, box-shadow .18s, background-color .22s, color .22s;
}
.form-input::placeholder { color: var(--s-text-faint); }
.form-input:focus {
  border-color: var(--s-accent);
  box-shadow: 0 0 0 3px var(--s-ring);
  background: var(--s-surface);
}
.form-input:focus + .input-icon,
.input-wrap:focus-within .input-icon { color: var(--s-accent); }

/* password field has extra right padding for toggle */
.form-input--pw { padding-right: 42px; }

/* mismatch state (also reused for team-modal form errors) */
.form-input--mismatch {
  border-color: var(--s-red) !important;
  box-shadow: 0 0 0 3px rgba(244,63,94,.12) !important;
}

/* pw toggle button */
.pw-toggle {
  position: absolute; right: 12px;
  background: none; border: none; cursor: pointer;
  color: var(--s-text-faint); font-size: 13px; padding: 4px;
  transition: color .15s; line-height: 1;
  display: flex; align-items: center;
}
.pw-toggle:hover { color: var(--s-text-muted); }

/* HINTS */
.form-hint { font-size: 11.5px; margin: 2px 0 0; }
.form-hint--error { color: var(--s-red); }

/* PASSWORD STRENGTH */
.pw-strength {
  display: flex; align-items: center; gap: 8px; margin-top: 6px;
}
.pw-strength-bar {
  flex: 1; height: 4px; border-radius: 2px;
  background: var(--s-border-strong);
  overflow: hidden;
}
.pw-strength-fill {
  height: 100%; border-radius: 2px;
  transition: width .3s ease, background-color .3s ease;
}
.pw-strength-label {
  font-size: 11px; font-weight: 700; letter-spacing: .04em;
  text-transform: uppercase; white-space: nowrap;
}

.pw-strength--weak   .pw-strength-fill  { background: #f43f5e; }
.pw-strength--fair   .pw-strength-fill  { background: #f59e0b; }
.pw-strength--good   .pw-strength-fill  { background: #3b82f6; }
.pw-strength--strong .pw-strength-fill  { background: #10b981; }

.pw-strength--weak   { color: #f43f5e; }
.pw-strength--fair   { color: #f59e0b; }
.pw-strength--good   { color: #3b82f6; }
.pw-strength--strong { color: #10b981; }

/* ── FORM ACTIONS ── */
.form-actions {
  display: flex; align-items: center; justify-content: flex-end;
  gap: 10px; padding-top: 4px;
}

.btn {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 13.5px; font-weight: 600; font-family: inherit;
  border-radius: 10px; padding: 10px 22px; cursor: pointer;
  border: 1.5px solid transparent;
  text-decoration: none;
  transition: background-color .18s, border-color .18s, color .18s, box-shadow .18s, opacity .18s, transform .15s;
}
.btn:active { transform: scale(0.98); }
.btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.btn--sm { padding: 7px 14px; font-size: 12.5px; }

.btn--ghost {
  background: var(--s-surface);
  border-color: var(--s-border-strong);
  color: var(--s-text-secondary);
}
.btn--ghost:hover {
  background: var(--s-surface-raised);
  border-color: var(--s-accent-border);
  color: var(--s-text-primary);
}

.btn--primary {
  background: linear-gradient(135deg, var(--s-accent) 0%, var(--c-accent-deep) 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: var(--c-shadow-accent);
}
.btn--primary:hover {
  filter: brightness(1.08);
  box-shadow: var(--c-shadow-accent-h);
  transform: translateY(-1px);
}
.btn--primary:disabled {
  opacity: .6; cursor: not-allowed;
  filter: none; transform: none; box-shadow: none;
}

.btn--danger {
  background: linear-gradient(135deg, var(--s-red) 0%, #b91c3c 100%);
  border-color: transparent;
  color: #fff;
}
.btn--danger:hover { opacity: .9; transform: translateY(-1px); }

/* ══════════════════════════════════════════════════════════
   TEAM
══════════════════════════════════════════════════════════ */
.team-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.team-card-header-left { display: flex; align-items: flex-start; gap: 14px; }

.team-body { padding: 4px 4px 8px; }

.team-state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; padding: 44px 20px;
  color: var(--s-text-faint);
}
.team-state i { font-size: 20px; }
.team-state p { font-size: 13px; font-weight: 500; }

.team-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.team-table thead tr { border-bottom: 1px solid var(--s-border); }
.team-table th {
  text-align: left; padding: 10px 20px;
  font-size: 10.5px; font-weight: 700; color: var(--s-text-faint);
  text-transform: uppercase; letter-spacing: 0.06em;
}
.th-actions { width: 44px; }
.team-table tbody tr { border-top: 1px solid var(--s-border); transition: background-color 0.15s ease; }
.team-table tbody tr:hover { background: var(--s-surface-raised); }
.team-table td { padding: 10px 20px; vertical-align: middle; color: var(--s-text-secondary); }
.team-muted { color: var(--s-text-faint); font-size: 12.5px; }

.team-user-cell { display: flex; align-items: center; gap: 11px; }
.team-user-text { min-width: 0; }
.team-user-name  { font-size: 13px; font-weight: 600; color: var(--s-text-primary); line-height: 1.3; }
.team-user-email { font-size: 11.5px; color: var(--s-text-faint); margin-top: 1px; }

.team-avatar {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
}

.team-badge {
  display: inline-flex; align-items: center;
  font-size: 10.5px; font-weight: 700;
  padding: 3px 10px; border-radius: 999px;
  letter-spacing: 0.01em;
}
.role--admin      { background: var(--s-accent-soft); color: var(--s-accent); }
.role--cashier     { background: rgba(61,127,193,0.12); color: #3D7FC1; }
html[data-theme="dark"] .role--cashier { background: rgba(61,127,193,0.20); color: #7FB4E8; }
.role--inventory   { background: rgba(16,185,129,0.12); color: var(--s-green); }
html[data-theme="dark"] .role--inventory { background: rgba(16,185,129,0.18); color: #4ADE80; }
.role--default     { background: var(--s-surface-sunken); color: var(--s-text-muted); }

.team-badge--active   { background: rgba(16,185,129,0.12); color: var(--s-green); }
html[data-theme="dark"] .team-badge--active { background: rgba(16,185,129,0.18); color: #4ADE80; }
.team-badge--disabled { background: var(--s-surface-sunken); color: var(--s-text-faint); }

.team-actions-cell { text-align: right; }
.team-row-menu-wrap { position: relative; display: inline-block; }
.icon-btn-sm {
  width: 30px; height: 30px;
  border: 1.5px solid var(--s-border-strong);
  border-radius: 8px;
  background: var(--s-surface);
  color: var(--s-text-muted);
  font-size: 12px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.icon-btn-sm:hover { background: var(--s-accent-soft); border-color: var(--s-accent-border); color: var(--s-accent); }

/* ── TEAM MODALS — solid, opaque overlay; color adapts to theme rather
   than a translucent blurred wash, so it never blends into a dark-mode
   page. Modal panel gets an explicit solid background per theme instead
   of var(--s-surface), matching the pattern used on the other pages. */
.team-modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(15, 23, 42, .70); /* light mode: solid dark slate overlay */
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
html[data-theme="dark"] .team-modal-backdrop {
  background: rgba(0, 0, 0, .82); /* dark mode: solid near-black overlay */
}
.team-modal {
  background: #FFFFFF;
  border-radius: 20px;
  padding: 30px 28px;
  width: 380px; max-width: 100%;
  box-shadow: 0 24px 64px rgba(0,0,0,.35);
  text-align: center;
  border: 1px solid #E5E7EB;
  transition: background-color .22s, border-color .22s, transform .24s cubic-bezier(.4,0,.2,1), opacity .2s ease;
}
.team-modal--edit-role { width: 400px; }
html[data-theme="dark"] .team-modal {
  background: #1E2130;
  border-color: #2A2D3E;
  box-shadow: 0 24px 64px rgba(0,0,0,.6);
}
.team-modal-icon {
  width: 48px; height: 48px;
  background: var(--s-accent-soft);
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px;
  border: 1px solid var(--s-accent-border);
  color: var(--s-accent); font-size: 18px;
}
.team-modal-icon--danger  { background: rgba(244,63,94,.10); border-color: rgba(244,63,94,.25); color: var(--s-red); }
.team-modal-icon--success { background: rgba(16,185,129,0.12); border-color: rgba(16,185,129,0.3); color: var(--s-green); }
html[data-theme="dark"] .team-modal-icon--success { background: rgba(16,185,129,0.18); }

.team-modal-title { font-size: 16px; font-weight: 700; color: var(--s-text-primary); margin-bottom: 6px; }
.team-modal-desc  { font-size: 12.5px; color: var(--s-text-muted); line-height: 1.6; margin-bottom: 18px; }
.team-modal-actions { display: flex; gap: 10px; justify-content: center; margin-top: 4px; }

/* subject strip on the Edit role modal — shows who is being edited so
   the role change never feels ambiguous mid-list */
.edit-role-subject {
  display: flex; align-items: center; gap: 10px;
  background: var(--s-surface-sunken);
  border: 1px solid var(--s-border);
  border-radius: 12px;
  padding: 10px 12px;
  margin-bottom: 14px;
  text-align: left;
}
.edit-role-subject-text { min-width: 0; }
.edit-role-subject-name { font-size: 13px; font-weight: 700; color: var(--s-text-primary); line-height: 1.3; }
.edit-role-subject-email { font-size: 11.5px; color: var(--s-text-faint); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.temp-password-box {
  background: var(--s-accent-soft);
  border: 1px solid var(--s-accent-border);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 16px;
  text-align: left;
}
.temp-password-label { font-size: 10.5px; font-weight: 700; color: var(--s-accent); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
.temp-password-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.temp-password-value { font-size: 15px; font-weight: 700; color: var(--s-text-primary); letter-spacing: 0.02em; font-family: 'Courier New', monospace; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.fade-enter-from .team-modal, .fade-leave-to .team-modal { transform: scale(.95) translateY(6px); opacity: 0; }
.dropdown-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.dropdown-leave-active { transition: opacity 0.1s ease, transform 0.1s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px) scale(0.97); }

/* ── RESPONSIVE ── */
@media (max-width: 640px) {
  .settings-page { padding: 16px 16px 48px; }
  .form-grid { grid-template-columns: 1fr; }
  .form-stack .form-grid { grid-template-columns: 1fr; }
  .header-row { flex-direction: column; }
  .form-actions { flex-direction: column-reverse; align-items: stretch; }
  .btn { justify-content: center; }
  .appearance-row { flex-wrap: wrap; gap: 12px; }
  .team-card-header { flex-direction: column; align-items: stretch; }
  .team-table { font-size: 12px; }
  .team-table th, .team-table td { padding: 8px 12px; }
  .team-user-email { display: none; }
  .team-modal { padding: 24px 20px; }
}
</style>

<style>
/* Unscoped — the row-actions menu is teleported to <body> (see
   UsersView... er, SettingsView's Team table above) to escape
   .settings-card's overflow: hidden, which is what was clipping it
   before. Being teleported means it sits outside .settings-page's DOM
   subtree, so the local --s-* token aliases (defined only on
   .settings-page) aren't in scope here — this uses the underlying
   --c-* tokens directly instead, same pattern as Layout.vue's own
   teleported account popup. */
.team-row-menu {
  position: fixed;
  width: 180px;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 12px;
  box-shadow: var(--c-shadow-lg);
  padding: 6px;
  z-index: 9999;
}
.team-row-menu button {
  width: 100%; display: flex; align-items: center; gap: 9px;
  padding: 8px 10px; border-radius: 8px;
  background: none; border: none; cursor: pointer;
  font-family: inherit; font-size: 12.5px; font-weight: 500;
  color: var(--c-text-secondary); text-align: left;
  transition: background-color 0.15s ease, color 0.15s ease;
}
.team-row-menu button:hover { background: var(--c-surface-raised); color: var(--c-text-primary); }
.team-row-menu-item--danger { color: var(--c-danger) !important; }
.team-row-menu-item--danger:hover { background: var(--c-danger-soft) !important; }
.team-row-menu-divider { height: 1px; background: var(--c-border); margin: 4px 0; }

/* ══════════════════════════════════════════════════════════
   ROLE DROPDOWN — used by both the Add User and Edit Role
   modals (see the two "role-dd" blocks in the template). Kept
   in this unscoped block since the Add User modal's markup is
   inside a nested <form>, and this makes the rules easy to
   share between both spots. Fully theme-aware: every color
   reads from --c-*, which already flips for
   html[data-theme="dark"] app-wide, so this looks right in
   both light and dark mode with no extra overrides needed here.
══════════════════════════════════════════════════════════ */
.role-dd { position: relative; width: 100%; }

.role-dd-trigger {
  width: 100%; height: 46px;
  display: flex; align-items: center; gap: 10px;
  padding: 0 14px;
  border: 1.5px solid var(--c-border-strong);
  border-radius: 10px;
  background: var(--c-surface-sunken);
  cursor: pointer;
  font-family: inherit;
  transition: border-color .18s, box-shadow .18s, background-color .18s;
}
.role-dd-trigger:hover { border-color: var(--c-accent-border); }
.role-dd--open .role-dd-trigger,
.role-dd-trigger:focus-visible {
  outline: none;
  border-color: var(--c-accent);
  box-shadow: 0 0 0 3px var(--c-accent-ring);
  background: var(--c-surface);
}

.role-dd-icon {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px;
}

.role-dd-value {
  flex: 1; text-align: left;
  font-size: 13.5px; font-weight: 600;
  color: var(--c-text-primary);
}

.role-dd-chevron {
  font-size: 11px; color: var(--c-text-faint);
  transition: transform .18s ease;
  flex-shrink: 0;
}
.role-dd--open .role-dd-chevron { transform: rotate(180deg); color: var(--c-accent); }

.role-dd-menu {
  position: absolute; left: 0; right: 0; top: calc(100% + 6px);
  margin: 0; padding: 6px;
  list-style: none;
  background: var(--c-surface-overlay);
  border: 1px solid var(--c-border);
  border-radius: 12px;
  box-shadow: var(--c-shadow-lg);
  z-index: 20;
  max-height: 260px;
  overflow-y: auto;
}

.role-dd-option {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color .15s ease;
}
.role-dd-option:hover { background: var(--c-surface-raised); }
.role-dd-option--active { background: var(--c-accent-soft); }

.role-dd-option-text { flex: 1; min-width: 0; text-align: left; }
.role-dd-option-name {
  display: block;
  font-size: 13px; font-weight: 600;
  color: var(--c-text-primary);
}
.role-dd-option-desc {
  display: block;
  font-size: 11px; color: var(--c-text-faint);
  margin-top: 1px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.role-dd-check {
  font-size: 11px; color: var(--c-accent); flex-shrink: 0;
}
</style>