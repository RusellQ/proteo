<template>
  <div class="chart-host">
    <canvas ref="canvas"></canvas>
  </div>
</template>

<script>
import {
  Chart,
  ArcElement,
  BarElement,
  BarController,
  CategoryScale,
  LinearScale,
  PieController,
  PolarAreaController,
  RadialLinearScale,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(
  ArcElement,
  BarElement,
  BarController,
  CategoryScale,
  LinearScale,
  PieController,
  PolarAreaController,
  RadialLinearScale,
  Tooltip,
  Legend,
)

export default {
  name: 'DashboardStatsChart',
  props: {
    type: { type: String, default: 'pie' },
    chartData: { type: Object, required: true },
  },
  data() {
    return { chart: null }
  },
  watch: {
    type() {
      this.render()
    },
    chartData: {
      deep: true,
      handler() {
        this.render()
      },
    },
  },
  mounted() {
    this.render()
  },
  beforeUnmount() {
    this.destroyChart()
  },
  methods: {
    destroyChart() {
      if (this.chart) {
        this.chart.destroy()
        this.chart = null
      }
    },
    render() {
      this.destroyChart()
      const canvas = this.$refs.canvas
      if (!canvas || !this.chartData?.labels?.length) return

      const isBar = this.type === 'bar'
      this.chart = new Chart(canvas, {
        type: this.type,
        data: this.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label(ctx) {
                  const value = ctx.raw ?? 0
                  const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1
                  const pct = Math.round((value / total) * 100)
                  return ` ${ctx.label}: ${value.toLocaleString('es-MX')} (${pct}%)`
                },
              },
            },
          },
          scales: isBar
            ? {
                x: { ticks: { color: '#3D3935' }, grid: { display: false } },
                y: {
                  beginAtZero: true,
                  ticks: { precision: 0, color: '#3D3935' },
                  grid: { color: '#e4e0d8' },
                },
              }
            : undefined,
        },
      })
    },
    toBase64Image() {
      return this.chart?.toBase64Image?.() || null
    },
  },
}
</script>

<style scoped>
.chart-host {
  width: 100%;
  height: 340px;
  position: relative;
}
</style>
