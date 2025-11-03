<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      :duration="toast.duration"
      @close="removeToast(toast.id)"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import Toast from './Toast.vue'

export default {
  name: 'ToastContainer',
  components: {
    Toast
  },
  setup() {
    const toasts = ref([])
    let nextId = 1

    const addToast = (toast) => {
      const id = nextId++
      toasts.value.push({
        id,
        type: toast.type || 'info',
        title: toast.title,
        message: toast.message || '',
        duration: toast.duration || 5000
      })
    }

    const removeToast = (id) => {
      const index = toasts.value.findIndex(toast => toast.id === id)
      if (index > -1) {
        toasts.value.splice(index, 1)
      }
    }

    const success = (title, message = '') => {
      addToast({ type: 'success', title, message })
    }

    const error = (title, message = '') => {
      addToast({ type: 'error', title, message })
    }

    const info = (title, message = '') => {
      addToast({ type: 'info', title, message })
    }

    const warning = (title, message = '') => {
      addToast({ type: 'warning', title, message })
    }

    return {
      toasts,
      addToast,
      removeToast,
      success,
      error,
      info,
      warning
    }
  }
}
</script>
