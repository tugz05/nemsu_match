<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <p class="text-2xl font-bold" :class="textColor">{{ value ?? 0 }}</p>
    <p class="text-sm text-gray-500 mt-1">{{ label }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: String,
  value: [Number, String],
  color: { type: String, default: 'purple' },
})

const textColor = computed(() => ({
  purple: 'text-purple-700',
  blue:   'text-blue-700',
  indigo: 'text-indigo-700',
  teal:   'text-teal-700',
  pink:   'text-pink-700',
  rose:   'text-rose-700',
  orange: 'text-orange-700',
  green:  'text-green-700',
}[props.color] ?? 'text-purple-700'))
</script>