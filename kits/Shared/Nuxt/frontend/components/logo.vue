<script setup>
const props = defineProps({
  siteName: {
    type: String,
    required: true,
    default: 'Site Name'
  },
  logo: {
    type: Object,
    required: false,
    default: () => ({
      url: '',
      alt: ''
    })
  }
});
</script>

<template>
  <NuxtLink to="/" class="text-red-600 block">
    <template v-if="logo.length > 0">
      <span class="sr-only">{{ siteName }}</span>
      <img 
        :src="logo[0].url" 
        :alt="logo[0].alt" 
        class="w-10 h-10" 
      />
    </template>
    <template v-else>
      {{ siteName }}
    </template>
  </NuxtLink>
</template>
