<script setup>
import { useGraphQL } from '@/composables/useGraphQL'
import { usePaginatedData } from '@/composables/usePaginatedData'
import { GUESTBOOK_POSTS_QUERY } from '@/queries/guestbookPosts.mjs'

const props = defineProps({
  previewToken: {
    type: String,
    default: null
  }
})

const fetchPosts = async (page, perPage) => {
  try {
    const result = await useGraphQL().query(GUESTBOOK_POSTS_QUERY, {
      limit: perPage,
      offset: (page - 1) * perPage
    }, {
      previewToken: props.previewToken
    })
    
    return {
      posts: result?.guestbookPostsEntries || [],
      total: result?.entryCount || 0
    }
  } catch (err) {
    console.error('GraphQL Error:', err)
    throw err
  }
}

const {
  currentPage,
  data,
  totalPages,
  error,
  loading,
  updateCurrentPage,
  refresh
} = usePaginatedData(fetchPosts)

defineExpose({
  refresh
})
</script>

<template>
  <div v-if="loading" class="py-4">
    Loading...
  </div>
  
  <div v-else-if="error" class="py-4 text-red-600">
    {{ error.message }}
  </div>
  
  <div v-else>
    <div v-if="data?.posts?.length > 0">
      <ol class="mb-2 divide-y divide-slate-300">
        <li v-for="post in data.posts" :key="post.id">
          <article class="text-xl py-6">
            <div v-html="post.textBlock"></div>
            <p class="text-sm mt-1">
              <time :datetime="post.postDate">{{ post.postDate }}</time>
            </p>
          </article>
        </li>
      </ol>
      <Pagination
        v-if="totalPages > 1"
        :currentPage="currentPage"
        :totalPages="totalPages"
        @update:currentPage="updateCurrentPage"
      />
    </div>
    <p v-else class="text-2xl">No entries yet. Create one using the form.</p>
  </div>
</template>