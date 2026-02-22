<script setup>
defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] },
  label: String,
  error: String,
  icon: String,
  id: String,
  placeholder: { type: String, default: 'Select an option' },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  required: Boolean
})
defineEmits(['update:modelValue'])
</script>

<template>
  <div class="base-field" :class="{ 'has-error': error }">
    <label v-if="label" :for="id" class="base-label">
      <span v-if="icon" class="material-symbols-outlined label-icon">{{ icon }}</span>
      {{ label }}
      <span v-if="required" class="required-mark">*</span>
    </label>
    
    <div class="select-wrapper">
      <select
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="base-select"
        v-bind="$attrs"
      >
        <option value="" disabled selected>{{ placeholder }}</option>
        <option
          v-for="opt in options"
          :key="opt[optionValue] || opt"
          :value="opt[optionValue] !== undefined ? opt[optionValue] : opt"
        >
          {{ opt[optionLabel] || opt }}
        </option>
      </select>
      <span class="material-symbols-outlined select-arrow">expand_more</span>
    </div>
    
    <Transition name="fade-slide">
      <p v-if="error" class="base-err-msg">{{ error }}</p>
    </Transition>
  </div>
</template>

<style scoped>
@import "@/styles/ui/BaseSelect.css";
</style>
