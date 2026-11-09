import { computed, ref } from 'vue'
import { useRoute } from '#app'

export const usePreview = () => {
  const route = useRoute()
  const previewToken = ref(route.query.token || null)
  const previewTimestamp = ref(Date.now())

  const isPreview = computed(() => {
    return !!route.query['x-craft-live-preview']
  })

  // Update timestamp when preview changes
  const refreshPreview = () => {
    previewTimestamp.value = Date.now()
  }

  return {
    isPreview,
    previewToken,
    previewTimestamp,
    refreshPreview
  }
}