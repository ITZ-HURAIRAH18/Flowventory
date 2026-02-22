<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  type: { type: String, default: 'text' },
  placeholder: String,
  error: String,
  icon: String,
  id: String,
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
    
    <div class="input-wrapper">
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="base-input"
        :placeholder="placeholder"
        v-bind="$attrs"
      />
      <div v-if="$slots.suffix" class="input-suffix">
        <slot name="suffix"></slot>
      </div>
    </div>
    
    <Transition name="fade-slide">
      <p v-if="error" class="base-err-msg">{{ error }}</p>
    </Transition>
  </div>
</template>

<style scoped>
@import "@/styles/ui/BaseInput.css";
</style>
