<script setup>
import { useGraphQL } from '~/composables/useGraphQL'
import { PAGE_QUERY } from '~/queries/pages.mjs'
import { usePreview } from '@/composables/usePreview'
import { useHead } from '#imports'

const route = useRoute()
const graphql = useGraphQL()
const { isPreview, previewToken, previewTimestamp } = usePreview()

// Disable SSR for preview mode
if (isPreview.value) {
  definePageMeta({ ssr: false })
}

// Get the current slug from the route
const slug = computed(() => {
  const slugParam = route.params.slug
  if (!slugParam) return ''
  return Array.isArray(slugParam) ? slugParam.join('/') : slugParam
})

// Fetch the page data
const { data: pageData, refresh } = await useAsyncData(
  `page-${slug.value}`,
  async () => {
    try {
      const result = await graphql.query(PAGE_QUERY, {
        uri: slug.value
      }, {
        previewToken: previewToken.value
      })
      
      if (!result?.entry) {
        throw createError({ 
          statusCode: 404, 
          message: 'Page not found' 
        })
      }
      
      return result.entry
    } catch (err) {
      console.error('Error fetching page:', err)
      throw createError({ 
        statusCode: 404,
        message: 'Page not found'
      })
    }
  },
  {
    watch: [slug, previewToken] // Watch both slug and preview token
  }
)

watch([isPreview, previewToken], () => {
  if (isPreview.value && previewToken.value) {
    refresh()
  }
})

// Set the page title
useHead(() => ({
  title: pageData.value?.title || ''
}))
</script>

<template>
  <div v-if="pageData" :key="previewTimestamp">
    <figure v-if="pageData.image && pageData.image.length > 0">
      <img :src="pageData.image[0].url" :alt="pageData.image[0].alt" />
    </figure>
    <header class="container mx-auto pt-12 pb-6 px-2 text-2xl">
      <ul v-if="pageData.ancestors.length" class="mb-2 text-base text-slate-400">
        <li v-for="ancestor in pageData.ancestors" v-bind:key="ancestor.id">
          <NuxtLink :to="`/${ancestor.uri}`">{{ ancestor.title }}</NuxtLink>
        </li>
      </ul>
      <h1 class="font-bold text-4xl sm:text-6xl lg:text-9xl">
        {{ pageData.title }}
      </h1> 
      <p v-if="pageData.pageSubheading" class="mt-4">
        {{ pageData.pageSubheading }}
      </p>
    </header>
    <section class="page__content">
      <div
        v-if="pageData.pageContent"
        class="container mx-auto py-12 px-2 text-balance" 
        v-html="pageData.pageContent"
      />
      <tip v-else>This page has no content, but you can add some in the control panel!</tip>
    </section>
    <footer v-if="pageData.children.length" class="page__extra">
      <div class="container mx-auto py-12 px-2 text-balance">
        <h3 class="font-bold text-3xl mb-4">Children</h3>
        <ul>
          <li v-for="child in pageData.children" v-bind:key="child.id">
            <span class="text-slate-400 mr-2" aria-hidden="true">&rarr;</span>
            <NuxtLink :to="`/${child.uri}`" class="text-red-600 hover:underline">{{ child.title }}</NuxtLink>
          </li>
        </ul>
      </div>
    </footer>
  </div>
</template>
