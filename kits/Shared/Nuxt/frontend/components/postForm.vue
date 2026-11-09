<script setup>
import { computed, ref } from 'vue'
import { useGraphQL } from '~/composables/useGraphQL'
import { CREATE_POST_MUTATION } from '~/queries/post.mjs'
import { useFlashes } from '~/composables/useFlashes'

const config = useRuntimeConfig()
const graphql = useGraphQL()
const { addFlash } = useFlashes()
const message = ref('')
const loading = ref(false)

const emit = defineEmits(['post-submitted'])

const generateTitle = (text) => {
  const words = text.split(' ').slice(0, 3).join(' ').trim()
  return `Post: ${words}${words ? '...' : ''}`
}

const props = defineProps({
  authorId: {
    type: Number,
    required: true
  }
})

const title = computed(() => generateTitle(message.value))

const submitPost = async () => {
  if (!message.value.trim()) {
    console.error('Message is required')
    return
  }
  loading.value = true
  try {
    console.log('Submitting with:', {
      title: title.value,
      message: message.value,
      authorId: props.authorId
    })

    const result = await graphql.query(CREATE_POST_MUTATION, {
      title: title.value,
      message: message.value,
      authorId: props.authorId.toString()
    }, {
      private: true
    })

    console.log('Mutation result:', result)

    if (!result?.save_guestbookPosts_text_Entry) {
      throw new Error('No data returned from the mutation')
    }

    addFlash('Message posted successfully', 'success')
    message.value = ''
    emit('post-submitted')
  } catch (err) {
    addFlash(`Error posting message: ${err.message}`, 'error')
    console.error('Error creating post:', err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form method="post" @submit.prevent="submitPost">
    <div class="mb-6 mt-4">
      <label for="message" class="font-bold">Message</label>
      <textarea 
        name="message" 
        class="w-full px-6 py-4" 
        required 
        id="message" 
        v-model="message"
      ></textarea>
    </div>
    <input 
      type="submit" 
      class="rounded font-bold bg-red-600 text-slate-50 px-6 py-4" 
      value="Post entry" 
      :disabled="loading"
    >
  </form>
</template>