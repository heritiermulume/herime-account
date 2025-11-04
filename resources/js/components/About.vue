<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">À propos</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Votre portail d'accès à l'écosystème Herime
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Herime Account Role -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Qu'est-ce qu'un Compte Herime ?
        </h3>
        
        <div class="space-y-4">
          <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
            Votre <strong>Compte Herime</strong> est votre clé d'accès unique à l'ensemble de l'écosystème Herime. 
            Il s'agit de votre identité numérique centralisée qui vous permet de naviguer facilement entre 
            tous les services et plateformes de l'écosystème Herime.
          </p>
          
          <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-md font-semibold text-blue-900 dark:text-blue-100 mb-2">
              🌐 Accès à l'écosystème complet
            </h4>
            <p class="text-blue-800 dark:text-blue-200 text-sm">
              Avec un seul compte, accédez à tous les services Herime : plateformes de formation, 
              outils de développement, communautés, ressources, et bien plus encore.
            </p>
          </div>
          
          <div class="space-y-3">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Avantages de votre Compte Herime :</h4>
            <ul class="space-y-2">
              <li class="flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Authentification unique (SSO) pour tous les services Herime</span>
              </li>
              <li class="flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Synchronisation de vos données et préférences</span>
              </li>
              <li class="flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Gestion centralisée de votre profil et de vos paramètres</span>
              </li>
              <li class="flex items-start">
                <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700 dark:text-gray-300">Sécurité avancée avec authentification à deux facteurs</span>
              </li>
            </ul>
          </div>
          
          <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200 text-sm">
              <strong>En savoir plus sur Herime :</strong> 
              <a 
                href="https://www.herime.com" 
                target="_blank" 
                rel="noopener noreferrer"
                class="underline hover:no-underline font-medium"
                style="color: #ffcc33;"
              >
                www.herime.com
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>


    <!-- Team -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Équipe de développement
        </h3>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
              <div class="h-12 w-12 rounded-full flex items-center justify-center" style="background-color: #ffcc33;">
                <span class="text-white font-medium text-lg">HM</span>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">Heritier Mulume</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">Développeur Full-Stack</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Features -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Fonctionnalités principales
        </h3>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div class="space-y-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Sécurité</h4>
            <ul class="space-y-2">
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Authentification sécurisée
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Gestion des sessions
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Authentification à deux facteurs
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Chiffrement des données
              </li>
            </ul>
          </div>
          
          <div class="space-y-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Interface utilisateur</h4>
            <ul class="space-y-2">
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Mode sombre/clair
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Interface responsive
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Navigation intuitive
              </li>
              <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Notifications personnalisables
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact & Support -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-6">
          Contact et support
        </h3>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Support technique</h4>
            <div class="space-y-2">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">E-mail:</span> 
                <a href="mailto:contact@herime.com" class="hover:opacity-80" style="color: #ffcc33;">
                  contact@herime.com
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Téléphone:</span> 
                <a href="tel:+243824449218" class="hover:opacity-80" style="color: #ffcc33;">
                  +243 824 449 218
                </a>
              </p>
            </div>
          </div>
          
          <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Réseaux sociaux</h4>
            <div class="space-y-2">
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">LinkedIn:</span> 
                <a href="https://www.linkedin.com/company/herime1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  @herime1
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Instagram:</span> 
                <a href="https://www.instagram.com/herime_1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  @herime_1
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Facebook:</span> 
                <a href="https://www.facebook.com/herime1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  @herime1
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">TikTok:</span> 
                <a href="https://www.tiktok.com/@herime_1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  @herime_1
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">YouTube:</span> 
                <a href="https://www.youtube.com/@herime_1" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  @herime_1
                </a>
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">WhatsApp:</span> 
                <a href="https://whatsapp.com/channel/0029VaU6teH3mFYCdZPjoT0h" target="_blank" rel="noopener noreferrer" class="hover:opacity-80" style="color: #ffcc33;">
                  Chaîne officielle
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Copyright -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6">
      <div class="text-center">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          © {{ currentYear }} HERIME. Tous droits réservés.
        </p>
        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
          Développé par 
          <a 
            href="https://facebook.com/heritier.mulume.001" 
            target="_blank" 
            rel="noopener noreferrer"
            class="hover:opacity-80 font-medium"
            style="color: #ffcc33;"
          >
            Héritier Mulume
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'About',
  setup() {
    const currentYear = computed(() => new Date().getFullYear())

    const formatDate = (date) => {
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    return {
      currentYear,
      formatDate
    }
  }
}
</script>
