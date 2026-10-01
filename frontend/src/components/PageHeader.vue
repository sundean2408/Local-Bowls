<template>
  <div class="lb-pagehead">
    <div class="lb-pagehead__text">
      <p v-if="eyebrow" class="lb-eyebrow">{{ eyebrow }}</p>
      <h1 class="lb-title lb-pagehead__title">
        <slot name="title">{{ title }}</slot>
      </h1>
      <p v-if="subtitle || $slots.subtitle" class="lb-pagehead__sub">
        <slot name="subtitle">{{ subtitle }}</slot>
      </p>
    </div>
    <div v-if="$slots.action" class="lb-pagehead__action">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
})
</script>

<style scoped>
.lb-pagehead {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.lb-pagehead__title {
  font-size: clamp(1.5rem, 4vw, 2rem);
}
.lb-pagehead__sub {
  margin: 0.35rem 0 0;
  color: var(--lb-muted);
  font-size: 0.92rem;
}
.lb-pagehead__action {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}
</style>
