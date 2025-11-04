<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sécurité</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Gérez vos identifiants, l'authentification à deux facteurs et les appareils connectés
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Password Section -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Mot de passe
        </h3>
        
        <form @submit.prevent="updatePassword" class="space-y-6">
          <!-- Current Password -->
          <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Mot de passe actuel
            </label>
            <input
              id="current_password"
              v-model="passwordForm.current_password"
              type="password"
              required
              placeholder="Entrez votre mot de passe actuel"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- New Password -->
          <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nouveau mot de passe
            </label>
            <input
              id="new_password"
              v-model="passwordForm.new_password"
              type="password"
              required
              minlength="8"
              placeholder="Entrez votre nouveau mot de passe"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Le mot de passe doit contenir au moins 8 caractères
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Confirmer le nouveau mot de passe
            </label>
            <input
              id="confirm_password"
              v-model="passwordForm.confirm_password"
              type="password"
              required
              placeholder="Confirmez votre nouveau mot de passe"
              class="mt-1 block w-full h-10 px-3 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm sm:text-sm"
              style="focus:ring-color: #003366; focus:border-color: #003366;"
            />
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="passwordLoading"
              class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
              style="background-color: #003366; focus:ring-color: #003366;"
              @mouseenter="$event.target.style.backgroundColor = '#ffcc33'"
              @mouseleave="$event.target.style.backgroundColor = '#003366'"
            >
              <div v-if="passwordLoading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              {{ passwordLoading ? 'Mise à jour...' : 'Mettre à jour le mot de passe' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Authentification à deux facteurs
        </h3>
        
        <div class="space-y-4">
          <!-- 2FA Status -->
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                Authentification à deux facteurs
              </h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Ajoutez une couche de sécurité supplémentaire à votre compte
              </p>
            </div>
            <div class="flex items-center space-x-3">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                twoFactorEnabled ? 'text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
              ]"
              :style="twoFactorEnabled ? 'background-color: #ffcc33;' : ''"
              >
                {{ twoFactorEnabled ? 'Activé' : 'Désactivé' }}
              </span>
              <button
                @click="toggleTwoFactor"
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2',
                  twoFactorEnabled ? 'bg-herime-600' : 'bg-gray-200 dark:bg-gray-600'
                ]"
                :style="twoFactorEnabled ? 'background-color: #003366;' : ''"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    twoFactorEnabled ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>
          </div>

          <!-- 2FA Setup (if disabled) -->
          <div v-if="!twoFactorEnabled" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                  Authentification à deux facteurs recommandée
                </h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                  <p>Activez l'authentification à deux facteurs pour renforcer la sécurité de votre compte.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Connected Devices -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Appareils connectés
        </h3>
        
        <div class="space-y-4">
          <div v-for="device in paginatedDevices" :key="device.id" class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ device.device_name }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ device.platform }} • {{ device.browser }} • {{ device.ip_address }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">
                  Dernière activité: {{ formatDate(device.last_activity) }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span v-if="device.is_current" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style="background-color: #ffcc33;">
                Appareil actuel
              </span>
              <button
                v-if="!device.is_current"
                @click="openRevokeDevice(device)"
                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
              >
                Révoquer
              </button>
            </div>
          </div>
        </div>
        
        <!-- Pagination for devices -->
        <div v-if="Math.ceil(devices.length / 15) > 1" class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
          <Pagination :page="devicesPage" :perPage="15" :total="devices.length" @update:page="devicesPage = $event" />
        </div>
      </div>
    </div>

    <!-- Login History -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Historique de connexion
        </h3>
        
        <div class="flow-root">
          <ul class="-mb-8">
            <li v-for="(login, index) in paginatedLoginHistory" :key="login.id">
              <div class="relative pb-8">
                <div v-if="index !== paginatedLoginHistory.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                <div class="relative flex space-x-3">
                  <div>
                    <span :class="[
                      'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800',
                      login.success ? 'bg-green-500' : 'bg-red-500'
                    ]">
                      <svg v-if="login.success" class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </div>
                  <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ login.device_name }} • {{ login.platform }} • {{ login.browser }}
                      </p>
                      <p class="text-sm text-gray-900 dark:text-white">
                        {{ login.ip_address }}
                      </p>
                    </div>
                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                      <time :datetime="login.created_at">
                        {{ formatDate(login.created_at) }}
                      </time>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
        
        <!-- Pagination for login history -->
        <div v-if="Math.ceil(loginHistory.length / 15) > 1" class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
          <Pagination :page="loginHistoryPage" :perPage="15" :total="loginHistory.length" @update:page="loginHistoryPage = $event" />
        </div>
      </div>
    </div>

    <!-- Revoke Device Modal -->
    <teleport to="body">
      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showRevokeDevice" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRevokeDevice = false"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Confirmer la révocation</h3>
              <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showRevokeDevice = false" aria-label="Fermer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-300" viewBox="0 0 24 24" fill="currentColor"><path d="M12 9v4m0 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-700 dark:text-gray-200">Voulez-vous vraiment révoquer cet appareil ?</p>
                <div v-if="revokeDeviceTarget" class="mt-2 text-sm text-gray-600 dark:text-gray-300 space-y-1">
                  <div><span class="text-gray-500">Appareil:</span> {{ revokeDeviceTarget.device_name }}</div>
                  <div><span class="text-gray-500">Plateforme:</span> {{ revokeDeviceTarget.platform }} • {{ revokeDeviceTarget.browser }}</div>
                  <div><span class="text-gray-500">IP:</span> {{ revokeDeviceTarget.ip_address }}</div>
                  <div><span class="text-gray-500">Dernière activité:</span> {{ formatDate(revokeDeviceTarget.last_activity) }}</div>
                </div>
                <p v-if="revokeDeviceError" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ revokeDeviceError }}</p>
              </div>
            </div>
            <div class="mt-6 flex justify-end space-x-2">
              <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="showRevokeDevice = false">Annuler</button>
              <button :disabled="revokeDeviceLoading" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50" @click="confirmRevokeDevice">
                <span v-if="revokeDeviceLoading">Révocation...</span>
                <span v-else>Révoquer</span>
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- 2FA Setup Modal -->
    <teleport to="body">
      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show2FASetup" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="fixed inset-0 bg-black bg-opacity-50" @click="show2FASetup = false"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Configurer l'authentification à deux facteurs</h3>
              <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="show2FASetup = false" aria-label="Fermer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="space-y-4">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Scannez ce QR code avec votre application d'authentification (Google Authenticator, Authy, etc.)
              </p>
              <div class="flex justify-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg" v-html="twoFactorQrCode"></div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Code de vérification (6 chiffres)
                </label>
                <input
                  v-model="twoFactorCode"
                  type="text"
                  maxlength="6"
                  placeholder="000000"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  @input="twoFactorError = ''"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mot de passe actuel
                </label>
                <input
                  v-model="twoFactorPassword"
                  type="password"
                  placeholder="Votre mot de passe"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  @input="twoFactorError = ''"
                />
              </div>
              <p v-if="twoFactorError" class="text-sm text-red-600 dark:text-red-400">{{ twoFactorError }}</p>
              <div class="flex justify-end space-x-2 pt-2">
                <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="show2FASetup = false">Annuler</button>
                <button :disabled="twoFactorLoading" class="px-4 py-2 rounded text-white hover:opacity-90 disabled:opacity-50" style="background-color: #003366;" @click="confirm2FA">
                  <span v-if="twoFactorLoading">Activation...</span>
                  <span v-else>Activer</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- 2FA Disable Modal -->
    <teleport to="body">
      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="show2FADisable" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="fixed inset-0 bg-black bg-opacity-50" @click="show2FADisable = false"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Désactiver l'authentification à deux facteurs</h3>
              <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="show2FADisable = false" aria-label="Fermer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="space-y-4">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Pour désactiver l'authentification à deux facteurs, veuillez entrer votre mot de passe.
              </p>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mot de passe actuel
                </label>
                <input
                  v-model="twoFactorPassword"
                  type="password"
                  placeholder="Votre mot de passe"
                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  @input="twoFactorError = ''"
                />
              </div>
              <p v-if="twoFactorError" class="text-sm text-red-600 dark:text-red-400">{{ twoFactorError }}</p>
              <div class="flex justify-end space-x-2 pt-2">
                <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600" @click="show2FADisable = false">Annuler</button>
                <button :disabled="twoFactorLoading" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50" @click="disable2FA">
                  <span v-if="twoFactorLoading">Désactivation...</span>
                  <span v-else>Désactiver</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Recovery Codes Modal -->
    <teleport to="body">
      <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showRecoveryCodes" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="fixed inset-0 bg-black bg-opacity-50" @click="showRecoveryCodes = false"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Codes de récupération</h3>
              <button class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700" @click="showRecoveryCodes = false" aria-label="Fermer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
              </button>
            </div>
            <div class="space-y-4">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Conservez ces codes de récupération dans un endroit sûr. Vous pouvez les utiliser pour accéder à votre compte si vous perdez votre appareil d'authentification.
              </p>
              <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                  <div v-for="(code, index) in recoveryCodes" :key="index" class="text-gray-900 dark:text-gray-100">
                    {{ code }}
                  </div>
                </div>
              </div>
              <div class="flex justify-end">
                <button class="px-4 py-2 rounded text-white hover:opacity-90" style="background-color: #003366;" @click="showRecoveryCodes = false">
                  J'ai sauvegardé les codes
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script>
import { ref, reactive, onMounted, inject, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Pagination from './Pagination.vue'

export default {
  name: 'Security',
  components: { Pagination },
  setup() {
    const authStore = useAuthStore()
    const notify = inject('notify')
    const passwordLoading = ref(false)
    const twoFactorEnabled = ref(false)
    const devices = ref([])
    const loginHistory = ref([])
    const devicesPage = ref(1)
    const loginHistoryPage = ref(1)
    
    // Revoke device modal state
    const showRevokeDevice = ref(false)
    const revokeDeviceTarget = ref(null)
    const revokeDeviceLoading = ref(false)
    const revokeDeviceError = ref('')
    
    // Paginated computed properties
    const paginatedDevices = computed(() => {
      const start = (devicesPage.value - 1) * 15
      return devices.value.slice(start, start + 15)
    })
    
    const paginatedLoginHistory = computed(() => {
      const start = (loginHistoryPage.value - 1) * 15
      return loginHistory.value.slice(start, start + 15)
    })

    const passwordForm = reactive({
      current_password: '',
      new_password: '',
      confirm_password: ''
    })

    const updatePassword = async () => {
      if (passwordForm.new_password !== passwordForm.confirm_password) {
        notify.error('Erreur', 'Les mots de passe ne correspondent pas')
        return
      }

      passwordLoading.value = true
      try {
        await axios.put('/user/password', {
          current_password: passwordForm.current_password,
          password: passwordForm.new_password,
          password_confirmation: passwordForm.confirm_password
        })
        
        notify.success('Succès', 'Mot de passe mis à jour avec succès!')
        
        // Reset form
        Object.assign(passwordForm, {
          current_password: '',
          new_password: '',
          confirm_password: ''
        })
      } catch (error) {
        console.error('Error updating password:', error)
        if (error.response?.data?.message) {
          notify.error('Erreur', error.response.data.message)
        } else {
          notify.error('Erreur', 'Erreur lors de la mise à jour du mot de passe')
        }
      } finally {
        passwordLoading.value = false
      }
    }

    // 2FA state
    const show2FASetup = ref(false)
    const show2FADisable = ref(false)
    const twoFactorQrCode = ref('')
    const twoFactorCode = ref('')
    const twoFactorPassword = ref('')
    const twoFactorLoading = ref(false)
    const twoFactorError = ref('')
    const recoveryCodes = ref([])
    const showRecoveryCodes = ref(false)

    const load2FAStatus = async () => {
      try {
        const response = await axios.get('/user/two-factor/status')
        if (response.data.success) {
          twoFactorEnabled.value = response.data.data.enabled
        }
      } catch (error) {
        console.error('Error loading 2FA status:', error)
      }
    }

    const toggleTwoFactor = async () => {
      if (twoFactorEnabled.value) {
        // Désactiver
        show2FADisable.value = true
        twoFactorPassword.value = ''
        twoFactorError.value = ''
      } else {
        // Activer - générer QR code
        try {
          twoFactorLoading.value = true
          twoFactorError.value = ''
          const response = await axios.post('/user/two-factor/generate')
          if (response.data.success) {
            twoFactorQrCode.value = response.data.data.qr_code_svg
            show2FASetup.value = true
            twoFactorCode.value = ''
            twoFactorPassword.value = ''
          }
        } catch (error) {
          console.error('Error generating 2FA QR code:', error)
          if (error.response?.data?.message) {
            notify.error('Erreur', error.response.data.message)
          } else {
            notify.error('Erreur', 'Erreur lors de la génération du QR code')
          }
        } finally {
          twoFactorLoading.value = false
        }
      }
    }

    const confirm2FA = async () => {
      if (!twoFactorCode.value || twoFactorCode.value.length !== 6) {
        twoFactorError.value = 'Veuillez entrer un code de 6 chiffres'
        return
      }
      if (!twoFactorPassword.value) {
        twoFactorError.value = 'Veuillez entrer votre mot de passe'
        return
      }

      twoFactorLoading.value = true
      twoFactorError.value = ''
      try {
        const response = await axios.post('/user/two-factor/confirm', {
          code: twoFactorCode.value,
          password: twoFactorPassword.value
        })
        if (response.data.success) {
          recoveryCodes.value = response.data.data.recovery_codes || []
          showRecoveryCodes.value = true
          show2FASetup.value = false
          twoFactorEnabled.value = true
          notify.success('Succès', 'Authentification à deux facteurs activée avec succès!')
        }
      } catch (error) {
        console.error('Error confirming 2FA:', error)
        if (error.response?.data?.message) {
          twoFactorError.value = error.response.data.message
        } else {
          twoFactorError.value = 'Erreur lors de la confirmation'
        }
      } finally {
        twoFactorLoading.value = false
      }
    }

    const disable2FA = async () => {
      if (!twoFactorPassword.value) {
        twoFactorError.value = 'Veuillez entrer votre mot de passe'
        return
      }

      twoFactorLoading.value = true
      twoFactorError.value = ''
      try {
        const response = await axios.post('/user/two-factor/disable', {
          password: twoFactorPassword.value
        })
        if (response.data.success) {
          twoFactorEnabled.value = false
          show2FADisable.value = false
          twoFactorPassword.value = ''
          notify.success('Succès', 'Authentification à deux facteurs désactivée avec succès!')
        }
      } catch (error) {
        console.error('Error disabling 2FA:', error)
        if (error.response?.data?.message) {
          twoFactorError.value = error.response.data.message
        } else {
          twoFactorError.value = 'Erreur lors de la désactivation'
        }
      } finally {
        twoFactorLoading.value = false
      }
    }

    const openRevokeDevice = (device) => {
      revokeDeviceTarget.value = device
      revokeDeviceError.value = ''
      showRevokeDevice.value = true
    }

    const confirmRevokeDevice = async () => {
      if (!revokeDeviceTarget.value) return
      revokeDeviceLoading.value = true
      revokeDeviceError.value = ''
      try {
        await axios.delete(`/sso/sessions/${revokeDeviceTarget.value.id}`)
        devices.value = devices.value.filter(device => device.id !== revokeDeviceTarget.value.id)
        showRevokeDevice.value = false
        revokeDeviceTarget.value = null
        notify.success('Succès', 'Appareil révoqué avec succès!')
      } catch (error) {
        console.error('Error revoking device:', error)
        if (error.response?.data?.message) {
          revokeDeviceError.value = error.response.data.message
        } else {
          revokeDeviceError.value = 'Erreur lors de la révocation de l\'appareil'
        }
      } finally {
        revokeDeviceLoading.value = false
      }
    }

    const formatDate = (date) => {
      if (!date) return 'Jamais'
      return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const loadDevices = async () => {
      try {
        const response = await axios.get('/sso/sessions')
        if (response.data.success) {
          devices.value = response.data.data.sessions
        }
      } catch (error) {
        console.error('Error loading devices:', error)
        notify.error('Erreur', 'Erreur lors du chargement des appareils')
      }
    }

    const loadLoginHistory = async () => {
      try {
        console.log('🔄 Loading login history from API...')
        // Use the same sessions data for login history since we don't have a separate endpoint
        const response = await axios.get('/sso/sessions')
        console.log('✅ Login history API response:', response.status, response.data)
        
        if (response.data && response.data.success && response.data.data && Array.isArray(response.data.data.sessions)) {
          loginHistory.value = response.data.data.sessions.map(session => {
            // S'assurer que toutes les propriétés nécessaires existent avec des valeurs par défaut
            return {
              id: session.id || null,
              device_name: session.device_name || 'Unknown Device',
              platform: session.platform || 'Unknown',
              browser: session.browser || 'Unknown',
              ip_address: session.ip_address || 'Unknown',
              success: true, // All sessions are successful by definition
              created_at: session.created_at || session.last_activity || null,
              last_activity: session.last_activity || session.created_at || null
            }
          }).filter(session => session.id !== null) // Filtrer les sessions invalides
          console.log('📊 Login history loaded:', loginHistory.value.length, 'entries')
        } else {
          console.warn('⚠️ Login history response not successful or missing data:', response.data)
          loginHistory.value = []
          // Ne pas afficher d'erreur si c'est juste qu'il n'y a pas de sessions
          if (response.data && response.data.success && (!response.data.data || !response.data.data.sessions || response.data.data.sessions.length === 0)) {
            console.log('   No sessions available (normal for new users)')
          } else {
            notify.warning('Avertissement', 'Impossible de charger l\'historique de connexion')
          }
        }
      } catch (error) {
        console.error('❌ Error loading login history:', error)
        console.error('   Status:', error.response?.status)
        console.error('   Message:', error.response?.data?.message || error.message)
        console.error('   Data:', error.response?.data)
        console.error('   Error details:', error)
        
        // Ne pas afficher l'erreur si c'est juste qu'il n'y a pas de sessions ou si l'utilisateur n'est pas autorisé
        if (error.response?.status === 404 || error.response?.status === 401) {
          console.warn('   No sessions found or unauthorized')
          loginHistory.value = []
        } else if (error.response?.status === 500) {
          console.error('   Server error - check logs')
          notify.error('Erreur serveur', 'Erreur lors du chargement de l\'historique de connexion. Veuillez réessayer.')
          loginHistory.value = []
        } else {
          notify.error('Erreur', 'Erreur lors du chargement de l\'historique de connexion')
          loginHistory.value = []
        }
      }
    }

    onMounted(() => {
      loadDevices()
      loadLoginHistory()
      load2FAStatus()
    })

    return {
      passwordForm,
      passwordLoading,
      twoFactorEnabled,
      devices,
      loginHistory,
      devicesPage,
      loginHistoryPage,
      paginatedDevices,
      paginatedLoginHistory,
      updatePassword,
      toggleTwoFactor,
      show2FASetup,
      show2FADisable,
      twoFactorQrCode,
      twoFactorCode,
      twoFactorPassword,
      twoFactorLoading,
      twoFactorError,
      recoveryCodes,
      showRecoveryCodes,
      confirm2FA,
      disable2FA,
      openRevokeDevice,
      confirmRevokeDevice,
      showRevokeDevice,
      revokeDeviceTarget,
      revokeDeviceLoading,
      revokeDeviceError,
      formatDate
    }
  }
}
</script>
