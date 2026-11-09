<script setup>
const props = defineProps({
  text: {
    type: String,
  }
})
</script>

<template>
    <p class="p-4 border-2 border-slate-300 border-dashed rounded-sm bg-slate-100 text-center">
        <slot></slot>
    </p>
</template>
