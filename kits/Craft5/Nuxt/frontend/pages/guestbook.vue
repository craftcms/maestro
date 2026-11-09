<script setup>
import { useGraphQL } from '@/composables/useGraphQL'
import { usePreview } from '@/composables/usePreview'
import { GUESTBOOK_QUERY } from '@/queries/guestbook.mjs'
import { ref } from 'vue'
import { useHead } from '#imports'
import PostList from '@/components/postList.vue'
import PostForm from '@/components/postForm.vue'

// Composables
const graphql = useGraphQL()
const { isPreview, previewToken } = usePreview()

// Disable SSR for preview mode
if (isPreview.value) {
  definePageMeta({ ssr: false })
}

// Data fetching
const { 
  data: content, 
  error, 
  pending: loading,
  refresh: refreshContent
} = await useAsyncData(
  'guestbook',
  async () => {
    try {
      const result = await graphql.query(GUESTBOOK_QUERY, {}, {
        previewToken: previewToken.value
      })
      
      return result?.guestbookEntries?.[0] || {}
    } catch (err) {
      throw createError({ 
        statusCode: 404,
        message: `Failed to fetch guestbook data: ${err.message}`
      })
    }
  },
  {
    watch: [previewToken]
  }
)

// Post list refresh handling
const postListRef = ref(null)
const handleNewPost = async () => {
  if (postListRef.value) {
    await postListRef.value.refresh()
  }
}

watch([isPreview, previewToken], () => {
  if (isPreview.value && previewToken.value) {
    refreshContent()
  }
})

// Page title
useHead(() => ({
  title: content.value?.title || 'Guestbook'
}))
</script>

<template>
  <div>
    <!-- Loading state -->
    <div v-if="loading" class="container mx-auto py-12 px-2">
      Loading...
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="container mx-auto py-12 px-2 text-red-600">
      {{ error.message }}
    </div>
    
    <!-- Content -->
    <template v-else>
      <header class="container mx-auto pt-12 pb-6 px-2">
        <h1 class="font-bold text-4xl sm:text-6xl lg:text-9xl">
          {{ content.title }}
        </h1>
        <p 
          v-if="content.pageSubheading" 
          class="mt-4 text-2xl"
        >
          {{ content.pageSubheading }}
        </p>
      </header>

      <section 
        v-if="content.pageContent"
        class="page__content"
      >
        <div 
          class="container mx-auto py-12 px-2 text-balance" 
          v-html="content.pageContent"
        />
      </section>

      <div class="container mx-auto px-2 sm:grid gap-6 grid-cols-2">
        <!-- Posts list -->
        <section class="mb-12">
          <PostList 
            ref="postListRef"
            :preview-token="previewToken"
          />
        </section>

        <!-- Post form -->
        <section>
          <div class="bg-slate-200 p-6 mb-9 rounded">
            <h2 class="font-bold text-3xl mb-4">Post an entry</h2>
            <PostForm 
              @post-submitted="handleNewPost" 
              :author-id="content.authorId" 
            />
          </div>
        </section>
      </div>
    </template>
  </div>
</template>