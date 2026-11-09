<script setup>
  import { useFlashes } from '~/composables/useFlashes'

  const { flashes } = useFlashes()
  const props = defineProps({
    flashes: {
      type: Array,
      required: false
    }
  })
</script>

<template>
  <div 
    v-if="flashes.length > 0" 
    role="alert"
    class="absolute top-0 left-0 right-0 z-50"
  >
    <p 
      v-for="flash in flashes" 
      :key="flash.id" 
      :class="[
        'container mx-auto p-2 text-md text-center text-slate-50',
        {
          'bg-red-600': flash.level === 'error',
          'bg-emerald-600': flash.level === 'success',
          'bg-yellow-500': flash.level === 'warning',
          'bg-blue-600': flash.level === 'info'
        }
      ]"
    >
      {{ flash.message }}
    </p>
  </div>
</template>
