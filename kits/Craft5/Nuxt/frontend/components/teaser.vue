<script setup>
const props = defineProps({
  entry: {
    type: Object,
    required: true
  },
  featured: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <article class="py-6 mb-12">
    <figure v-if="featured">
      <NuxtLink
        :to="`/${entry.uri}`" 
        class="text-red-600 hover:underline focus:underline cursor-pointer block mb-4"
      >
        <img 
          v-if="entry.image && entry.image.length > 0"
          :src="entry.image[0].url" 
          :alt="entry.image[0].alt" 
        />
        <div v-else class="bg-slate-200 aspect-video hover:bg-slate-300"></div>
      </NuxtLink>
    </figure>
    <div class="mb-4">
      <h2 class="font-bold mb-2 text-4xl">
        <NuxtLink 
          :to="`/${entry.uri}`" 
          class="text-red-600 hover:underline focus:underline cursor-pointer"
        >
          {{ entry.title }}
        </NuxtLink>
      </h2>
      <p v-if="entry.pageSubheading">{{ entry.pageSubheading }}</p>
      <p>
        <time class="text-sm" :datetime="entry.postDate">{{ entry.postDate }}</time>
      </p>
    </div>
  </article>
</template>
