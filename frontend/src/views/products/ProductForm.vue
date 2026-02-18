<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import productService from '@/services/productService'

const route = useRoute()
const router = useRouter()

const product = ref({
  name: '',
  sku: '',
  cost_price: '',
  sale_price: '',
  tax_percentage: '',
  status: 'active'
})

const isEdit = route.params.id

onMounted(async () => {
  if (isEdit) {
    const res = await productService.get(route.params.id)
    product.value = res.data
  }
})

const submit = async () => {
  if (isEdit) {
    await productService.update(route.params.id, product.value)
  } else {
    await productService.create(product.value)
  }

  router.push('/products')
}
</script>

<template>
  <div>
    <h2>{{ isEdit ? 'Edit Product' : 'Create Product' }}</h2>

    <input v-model="product.name" placeholder="Name" />
    <input v-model="product.sku" placeholder="SKU" />
    <input v-model="product.cost_price" placeholder="Cost Price" />
    <input v-model="product.sale_price" placeholder="Sale Price" />
    <input v-model="product.tax_percentage" placeholder="Tax %" />

    <select v-model="product.status">
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>

    <button @click="submit">
      Save
    </button>
  </div>
</template>