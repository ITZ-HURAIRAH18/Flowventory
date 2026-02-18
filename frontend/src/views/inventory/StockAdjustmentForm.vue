<script setup>
import { ref } from 'vue'
import inventoryService from '@/services/inventoryService'

const form = ref({
  branch_id:'',
  product_id:'',
  quantity:''
})

const submit = async () => {
  try {
    await inventoryService.adjust(form.value)
    alert('Adjusted successfully')
  } catch(e) {
    alert(e.response.data.message)
  }
}
</script>