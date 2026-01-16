<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-gray-200 bg-gray-50/50 shadow-sm transition-all duration-200 focus:border-blue-500 focus:bg-white focus:ring-blue-500 hover:bg-white"
        v-model="model"
        ref="input"
    />
</template>
