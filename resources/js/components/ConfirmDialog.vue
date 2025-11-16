<template>
  <teleport to="body">
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="cancel"></div>
        
        <!-- Dialog -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
          <!-- Icon -->
          <div class="flex items-center justify-center w-12 h-12 mx-auto rounded-full mb-4" :class="iconBgClass">
            <svg v-if="type === 'danger'" class="h-6 w-6" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <svg v-else-if="type === 'warning'" class="h-6 w-6" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <svg v-else-if="type === 'info'" class="h-6 w-6" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <svg v-else class="h-6 w-6" :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-lg font-medium text-gray-900 dark:text-white text-center mb-2">
            {{ title }}
          </h3>

          <!-- Message -->
          <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
            {{ message }}
          </p>

          <!-- Actions -->
          <div class="flex space-x-3">
            <button
              @click="cancel"
              class="flex-1 px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
              {{ cancelText }}
            </button>
            <button
              @click="confirm"
              class="flex-1 px-4 py-2 rounded-lg text-white transition-colors"
              :class="confirmButtonClass"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'ConfirmDialog',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Confirmer l\'action'
    },
    message: {
      type: String,
      default: 'Êtes-vous sûr de vouloir continuer ?'
    },
    confirmText: {
      type: String,
      default: 'Confirmer'
    },
    cancelText: {
      type: String,
      default: 'Annuler'
    },
    type: {
      type: String,
      default: 'warning', // 'danger', 'warning', 'info', 'success'
      validator: (value) => ['danger', 'warning', 'info', 'success'].includes(value)
    }
  },
  emits: ['confirm', 'cancel', 'update:show'],
  setup(props, { emit }) {
    const iconBgClass = computed(() => {
      switch (props.type) {
        case 'danger':
          return 'bg-red-100 dark:bg-red-900/20'
        case 'warning':
          return 'bg-orange-100 dark:bg-orange-900/20'
        case 'info':
          return 'bg-blue-100 dark:bg-blue-900/20'
        case 'success':
          return 'bg-green-100 dark:bg-green-900/20'
        default:
          return 'bg-gray-100 dark:bg-gray-700'
      }
    })

    const iconClass = computed(() => {
      switch (props.type) {
        case 'danger':
          return 'text-red-600 dark:text-red-400'
        case 'warning':
          return 'text-orange-600 dark:text-orange-400'
        case 'info':
          return 'text-blue-600 dark:text-blue-400'
        case 'success':
          return 'text-green-600 dark:text-green-400'
        default:
          return 'text-gray-600 dark:text-gray-400'
      }
    })

    const confirmButtonClass = computed(() => {
      switch (props.type) {
        case 'danger':
          return 'bg-red-600 hover:bg-red-700'
        case 'warning':
          return 'bg-orange-600 hover:bg-orange-700'
        case 'info':
          return 'bg-blue-600 hover:bg-blue-700'
        case 'success':
          return 'bg-green-600 hover:bg-green-700'
        default:
          return 'bg-gray-600 hover:bg-gray-700'
      }
    })

    const confirm = () => {
      emit('confirm')
      emit('update:show', false)
    }

    const cancel = () => {
      emit('cancel')
      emit('update:show', false)
    }

    return {
      iconBgClass,
      iconClass,
      confirmButtonClass,
      confirm,
      cancel
    }
  }
}
</script>

