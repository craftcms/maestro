<script setup>
import { usePreview } from '@/composables/usePreview'
import { useGraphQL } from '@/composables/useGraphQL'
import { HOME_QUERY } from '@/queries/home.mjs'
import { useAsyncData } from '#app'

const { isPreview, previewToken, previewTimestamp } = usePreview()
const graphql = useGraphQL()

// Disable SSR for preview mode
if (isPreview.value) {
  definePageMeta({ ssr: false })
}

// Fetch data function
const { data, refresh } = await useAsyncData(
  'home',
  async () => {
    try {
      const result = await graphql.query(HOME_QUERY, {}, {
        previewToken: previewToken.value
      })
      return result.entry
    } catch (error) {
      console.error('Failed to fetch home data:', error)
      throw createError({ 
        statusCode: 404,
        message: 'Homepage not found'
      })
    }
  },
  {
    watch: [previewToken]
  }
)

watch([isPreview, previewToken], () => {
  if (isPreview.value && previewToken.value) {
    refresh()
  }
})
</script>

<template>
  <div :key="previewTimestamp">
    <figure v-if="data.image && data.image.length > 0">
      <img :src="data.image[0].url" :alt="data.image[0].alt" />
    </figure>
    <header class="container mx-auto pt-12 pb-6 px-2 text-2xl">
      <h1 class="font-bold text-4xl sm:text-6xl lg:text-9xl">
        {{ data.title }}
      </h1>
      <p v-if="data.pageSubheading" class="mt-4">
        {{ data.pageSubheading }}
      </p>
    </header>
    <section class="page__content">
      <div class="container mx-auto py-12 px-2 text-balance">
        <div
          v-if="data.pageContent"
          v-html="data.pageContent"
        ></div>
        <tip v-else>Add content to the homepage by visiting Entries &rarr; Default Pages in the control panel!</tip>
      </div>
    </section>
  </div>
</template>
