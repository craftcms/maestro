<script setup>
const props = defineProps({
  image: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <nuxt-img
    :src="image.url"
    :alt="image.alt"
    sizes="sm:100vw md:50vw lg:400px"
    loading="lazy"
  />
</template>