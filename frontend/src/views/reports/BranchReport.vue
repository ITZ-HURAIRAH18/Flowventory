<template>
  <div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Branch Reporting Dashboard</h2>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-4 shadow rounded">
        <h3 class="text-gray-500">Today Sales</h3>
        <p class="text-2xl font-bold text-green-600">
          ${{ report.today_sales }}
        </p>
      </div>

      <div class="bg-white p-4 shadow rounded">
        <h3 class="text-gray-500">Monthly Sales</h3>
        <p class="text-2xl font-bold text-blue-600">
          ${{ report.monthly_sales }}
        </p>
      </div>

      <div class="bg-white p-4 shadow rounded">
        <h3 class="text-gray-500">Total Orders</h3>
        <p class="text-2xl font-bold text-purple-600">
          {{ report.total_orders }}
        </p>
      </div>
    </div>

    <!-- Chart -->
    <div class="bg-white p-6 shadow rounded mb-8">
      <canvas ref="salesChart"></canvas>
    </div>

    <!-- Top Products -->
    <div class="bg-white p-6 shadow rounded mb-8">
      <h3 class="text-xl font-semibold mb-4">Top 5 Products</h3>
      <ul>
        <li v-for="product in report.top_products" :key="product.name"
            class="flex justify-between border-b py-2">
          <span>{{ product.name }}</span>
          <span class="font-bold">{{ product.total_sold }}</span>
        </li>
      </ul>
    </div>

    <!-- Low Stock -->
    <div class="bg-white p-6 shadow rounded">
      <h3 class="text-xl font-semibold mb-4 text-red-600">
        Low Stock Items
      </h3>
      <div v-for="item in report.low_stock" :key="item.id"
           class="flex justify-between border-b py-2">
        <span>{{ item.product.name }}</span>
        <span class="text-red-600 font-bold">
          {{ item.quantity }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Chart from 'chart.js/auto'

export default {
  props: ['branchId'],

  data() {
    return {
      report: {}
    }
  },

  mounted() {
    this.fetchReport()
  },

  methods: {
    async fetchReport() {
      const token = localStorage.getItem('token')

      const res = await axios.get(
        `http://127.0.0.1:8000/api/branches/${this.branchId}/report`,
        {
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      )

      this.report = res.data
      this.renderChart()
    },

    renderChart() {
      const ctx = this.$refs.salesChart

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Today Sales', 'Monthly Sales'],
          datasets: [
            {
              label: 'Sales',
              data: [
                this.report.today_sales,
                this.report.monthly_sales
              ]
            }
          ]
        }
      })
    }
  }
}
</script>