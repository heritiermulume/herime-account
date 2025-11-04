<template>
  <nav class="flex items-center justify-between" aria-label="Pagination">
    <div class="flex-1 flex items-center justify-between sm:hidden">
      <button
        :disabled="currentPage <= 1"
        @click="$emit('update:page', Math.max(1, currentPage - 1))"
        class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 disabled:opacity-50"
      >
        Précédent
      </button>
      <button
        :disabled="currentPage >= totalPages"
        @click="$emit('update:page', Math.min(totalPages, currentPage + 1))"
        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 disabled:opacity-50"
      >
        Suivant
      </button>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
      <p class="text-sm text-gray-700 dark:text-gray-300">
        Page <span class="font-medium">{{ currentPage }}</span> sur <span class="font-medium">{{ totalPages }}</span>
      </p>
      <div>
        <ul class="inline-flex -space-x-px rounded-md shadow-sm" role="list">
          <li>
            <button
              :disabled="currentPage <= 1"
              @click="$emit('update:page', Math.max(1, currentPage - 1))"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              aria-label="Précédent"
            >
              ‹
            </button>
          </li>
          <li v-for="page in pages" :key="page">
            <button
              @click="$emit('update:page', page)"
              :class="[
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                page === currentPage
                  ? 'z-10 border-herime-600 text-white' 
                  : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50',
              ]"
              :style="page === currentPage ? 'background-color: #003366;' : ''"
            >
              {{ page }}
            </button>
          </li>
          <li>
            <button
              :disabled="currentPage >= totalPages"
              @click="$emit('update:page', Math.min(totalPages, currentPage + 1))"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
              aria-label="Suivant"
            >
              ›
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'Pagination',
  props: {
    page: { type: Number, required: true },
    perPage: { type: Number, default: 15 },
    total: { type: Number, required: true },
    maxButtons: { type: Number, default: 5 },
  },
  emits: ['update:page'],
  setup(props) {
    const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
    const currentPage = computed(() => Math.min(props.page, totalPages.value))

    const pages = computed(() => {
      const half = Math.floor(props.maxButtons / 2)
      let start = Math.max(1, currentPage.value - half)
      let end = Math.min(totalPages.value, start + props.maxButtons - 1)
      start = Math.max(1, end - props.maxButtons + 1)
      return Array.from({ length: end - start + 1 }, (_, i) => start + i)
    })

    return { totalPages, currentPage, pages }
  }
}
</script>


