<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const form = ref({
  name: '',
  sku: '',
  cost_price: '',
  sale_price: '',
  tax_percentage: '',
  status: true
})

const errors = ref({})

const isEdit = route.params.id

const fetchProduct = async () => {
  const res = await api.get(`/products/${route.params.id}`)
  form.value = res.data
}

onMounted(() => {
  if (isEdit) fetchProduct()
})

const submit = async () => {
  try {
    if (isEdit) {
      await api.put(`/products/${route.params.id}`, form.value)
    } else {
      await api.post('/products', form.value)
    }
    router.push('/products')
  } catch (err) {
    errors.value = err.response.data.errors
  }
}
</script>

<template>
  <div>
    <h2>{{ isEdit ? 'Edit' : 'Create' }} Product</h2>

    <div>
      <input v-model="form.name" placeholder="Name" />
      <span>{{ errors.name?.[0] }}</span>
    </div>

    <div>
      <input v-model="form.sku" placeholder="SKU" />
      <span>{{ errors.sku?.[0] }}</span>
    </div>

    <div>
      <input v-model="form.cost_price" type="number" placeholder="Cost Price" />
    </div>

    <div>
      <input v-model="form.sale_price" type="number" placeholder="Sale Price" />
    </div>

    <div>
      <input v-model="form.tax_percentage" type="number" placeholder="Tax %" />
    </div>

    <div>
      <select v-model="form.status">
        <option :value="true">Active</option>
        <option :value="false">Inactive</option>
      </select>
    </div>

    <button @click="submit">Save</button>
  </div>
</template>