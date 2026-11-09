<script setup>
const props = defineProps({
  globalData: {
    type: Object,
    default: () => ({
      address: []
    })
  }
})

const address = computed(() => props.globalData?.address?.[0] || null)
</script>


<template>
  <footer class="bg-slate-50 py-6 px-2 text-sm">
    <address 
      v-if="address"
      class="container mx-auto not-italic"
    >
      <p class="font-bold">{{ address.title }}</p>
      <p>
        <template v-if="address.addressLine1">
          {{ address.addressLine1 }}<br>
        </template>
        <template v-if="address.addressLine2">
          {{ address.addressLine2 }}<br>
        </template>
        <template v-if="address.addressLine3">
          {{ address.addressLine3 }}<br>
        </template>
        {{ address.locality }} {{ address.postalCode }}<br>
        {{ address.countryCode }}
      </p>
    </address>
    <div v-else class="container mx-auto">
      <tip>Add an address by visiting Entries &rarr; Globals in the control panel!</tip>
    </div>
  </footer>
</template>
