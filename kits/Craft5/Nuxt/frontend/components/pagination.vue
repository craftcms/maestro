<script setup>
// Define props
const props = defineProps({
  currentPage: {
    type: Number,
    required: true
  },
  totalPages: {
    type: Number,
    required: true
  }
});

// Define emits
const emit = defineEmits(['update:currentPage']);

// Function to go to the next page
const nextPage = () => {
  if (props.currentPage < props.totalPages) {
    emit('update:currentPage', props.currentPage + 1);
  }
};

// Function to go to the previous page
const prevPage = () => {
  if (props.currentPage > 1) {
    emit('update:currentPage', props.currentPage - 1);
  }
};
</script>

<template>
  <nav class="pt-6 text-sm" role="navigation" aria-label="Entry pagination">
    <ul class="flex justify-between">
      <li>
        <button @click="prevPage" v-if="currentPage !== 1" aria-label="Previous Page" class="text-red-600 cursor-pointer font-bold hover:underline focus:underline">
          ← Previous page ({{ currentPage - 1 }} of {{ totalPages }})
        </button>
      </li>
      <li>
        <button @click="nextPage" v-if="currentPage !== totalPages" aria-label="Next Page" class="text-red-600 cursor-pointer font-bold hover:underline focus:underline">
          Next page ({{ currentPage }} of {{ totalPages }}) →
        </button>
      </li>
    </ul>
  </nav>
</template>
