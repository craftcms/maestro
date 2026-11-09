<script setup>
import { useRoute } from '#app'
import { useGraphQL } from '@/composables/useGraphQL'
import { usePreview } from '@/composables/usePreview'
import { BLOG_POSTS_QUERY } from '@/queries/blogPosts.mjs'
import { watch, computed } from 'vue'
import { useHead } from '#imports'

const route = useRoute()
const graphql = useGraphQL()
const { isPreview, previewToken, previewTimestamp } = usePreview()
const hero = computed(() => currentPost.value?.image && currentPost.value?.image.length > 0)

// Disable SSR for preview mode
if (isPreview.value) {
  definePageMeta({ ssr: false })
}

const { data, error, refresh } = await useAsyncData(
  `post-${route.params.slug}`,
  async () => {
    try {
      const result = await graphql.query(BLOG_POSTS_QUERY, {
        slug: route.params.slug
      }, {
        previewToken: previewToken.value
      })
      
      if (!result?.blogPostsEntries?.length) {
        throw createError({ 
          statusCode: 404, 
          message: 'Post not found' 
        })
      }
      
      return result
    } catch (err) {
      console.error('Error fetching post:', err)
      throw createError({ 
        statusCode: 404,
        message: 'Post not found'
      })
    }
  },
  {
    watch: [previewToken]
  }
)

// Watch for preview changes
watch([isPreview, previewToken], () => {
  if (isPreview.value && previewToken.value) {
    refresh()
  }
})

const currentPost = computed(() => data.value?.blogPostsEntries?.[0] || null)
const hasPost = computed(() => !!currentPost.value)
const hasNextPrev = computed(() => hasPost.value && (currentPost.value?.prev || currentPost.value?.next))

// Set the page title
useHead(() => ({
  title: currentPost.value?.title || ''
}))
</script>

<template>
  <div :key="previewTimestamp">
    <div v-if="pending" class="container mx-auto py-12 px-2">
      Loading...
    </div>
    
    <div v-else-if="error" class="container mx-auto py-12 px-2 text-red-600">
      {{ error.message }}
    </div>
    
    <template v-else-if="hasPost">
      <header class="container mx-auto pt-12 pb-6 px-2 text-2xl relative" :class="hero ? 'aspect-video' : ''">
        
        <figure v-if="hero" class="absolute top-0 left-0 w-full h-full">
          <img :src="currentPost.image[0].url" :alt="currentPost.image[0].alt" />
        </figure>

        <div class=" z-10" :class="hero ? 'text-white bg-black/80 p-4 sm:bottom-0 relative sm:ml-4 sm:max-w-screen-lg sm:absolute sm:rounded' : ''">
          <h1 class="font-bold text-4xl sm:text-6xl lg:text-9xl">{{ currentPost.title }}</h1>
          <p v-if="currentPost.pageSubheading" class="mt-4">{{ currentPost.pageSubheading }}</p>
          <div class="text-xs mt-4">
            <p>
              <span v-if="currentPost.category?.length" class="font-bold">
                {{ currentPost.category[0].title }}
              </span>
            <template v-if="currentPost.category?.length && currentPost.postDate">
              |
            </template>
            <time v-if="currentPost.postDate" :datetime="currentPost.postDate">
              {{ currentPost.postDate }}
              </time>
              <span v-if="currentPost.authorName" class="block">
                By {{ currentPost.authorName }}
              </span>
            </p>
          </div>
        </div>
      
      </header>

      <section class="page__content">
        <div 
          class="container mx-auto py-12 px-2 text-balance prose" 
          v-html="currentPost.pageContent"
        ></div>
      </section>
    </template>

    <div v-else class="container mx-auto py-12 px-2">
      Post not found
    </div>

    <section v-if="hasNextPrev"class="bg-slate-100">
        <div class="container mx-auto divide-y divide-slate-300 py-12 px-2">
          <h2 class="font-bold text-4xl mb-2">Other articles</h2>
          <div class="sm:grid sm:grid-cols-2 sm:gap-6">
            <Teaser 
              v-if="currentPost?.prev"
            :key="currentPost.prev.id"
            :entry="currentPost.prev"
          />
          <Teaser
            v-if="currentPost?.next"
            :key="currentPost.next.id"
              :entry="currentPost.next"
            />   
          </div>
        </div>
      </section>
  </div>
</template>