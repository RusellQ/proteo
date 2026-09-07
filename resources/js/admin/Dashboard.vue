<template>
  <div class="dashboard-stats">
    <header class="page-head">
      <div>
        <h1 class="page-title">Estadísticas de documentos</h1>
        <p class="page-sub">
          Bienvenid@, <strong>{{ userName }}</strong>
          · Documentos <strong>aprobados</strong> y <strong>pendientes</strong> por plantilla.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-download"
        :disabled="!puedeDescargar || descargando"
        @click="descargarPDF"
      >
        <span v-if="descargando" class="spinner-border spinner-border-sm me-2" role="status"></span>
        <span v-else class="ico">↓</span>
        Descargar PDF
      </button>
    </header>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <section class="kpi-grid" aria-label="Resumen">
      <article class="kpi kpi-total">
        <span class="kpi-label">Total de documentos</span>
        <span class="kpi-value">{{ total.toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ dimensionLabel || 'Sin dimensión' }}</span>
      </article>
      <article
        v-for="opt in opcionesEstado"
        :key="opt.value"
        class="kpi"
        :class="{ 'kpi-off': !estados.includes(opt.value) }"
        :style="{ '--kpi-color': opt.color }"
      >
        <span class="kpi-dot"></span>
        <span class="kpi-label">{{ opt.label }}</span>
        <span class="kpi-value">{{ (kpiPorEstado[opt.value] ?? 0).toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ porcentajeKpi(kpiPorEstado[opt.value]) }}</span>
      </article>
    </section>

    <div class="layout">
      <aside class="filters card-surface">
        <h2 class="panel-title">Filtros</h2>

        <div class="field">
          <span class="field-label">Estado del documento</span>
          <div class="chips">
            <label
              v-for="opt in opcionesEstado"
              :key="opt.value"
              class="chip"
              :class="{ 'chip-on': estados.includes(opt.value) }"
              :style="{ '--chip-color': opt.color }"
            >
              <input
                class="chip-input"
                type="checkbox"
                :value="opt.value"
                v-model="estados"
                @change="onEstadosChange"
              >
              <span class="chip-dot"></span>{{ opt.label }}
            </label>
          </div>
          <p class="field-help">
            Ambos estados → la gráfica se parte por estado; uno solo → se parte por plantilla.
          </p>
        </div>

        <hr class="sep">

        <div class="steps">
          <div class="step">
            <span class="step-num">1</span>
            <div class="step-body">
              <div class="step-top">
                <span class="step-name">Plantillas</span>
                <button
                  v-if="idsPlantillas.length"
                  class="clear-badge"
                  type="button"
                  @click="limpiarPlantillas"
                  aria-label="Limpiar plantillas"
                >{{ idsPlantillas.length }} ×</button>
              </div>
              <button type="button" class="picker" @click="abrirModal">
                <span class="picker-text">{{ resumenPlantillas }}</span>
                <span class="picker-caret">▾</span>
              </button>
              <p class="step-help">Vacío = todas las plantillas del acervo.</p>
            </div>
          </div>
        </div>
      </aside>

      <section class="result card-surface">
        <div class="result-head">
          <div class="result-meta">
            <span class="result-scope scope-global">Alcance global</span>
            <span v-if="filtroResumen" class="result-filtro">{{ filtroResumen }}</span>
          </div>
          <div class="segmented" role="group" aria-label="Tipo de gráfica">
            <button
              v-for="t in tiposGrafica"
              :key="t.value"
              type="button"
              class="seg-btn"
              :class="{ 'seg-on': tipoGrafica === t.value }"
              @click="tipoGrafica = t.value"
            >
              <span class="seg-ico">{{ t.ico }}</span>{{ t.label }}
            </button>
          </div>
        </div>

        <div v-if="cargando" class="chart-wrap">
          <div class="skeleton-chart"></div>
        </div>

        <div v-else-if="!tieneDatos" class="empty">
          <div class="empty-ico">▤</div>
          <p class="empty-title">No hay datos para esta combinación</p>
          <p class="empty-sub">Ajuste el estado o las plantillas seleccionadas.</p>
        </div>

        <div v-else class="chart-and-legend">
          <div class="chart-wrap">
            <DashboardStatsChart
              :key="tipoGrafica + '-' + chartKey"
              ref="chartCmp"
              :type="tipoGrafica"
              :chart-data="chartData"
            />
          </div>

          <div class="legend">
            <div class="legend-head">
              <span>Categoría</span>
              <span class="legend-num">Cantidad</span>
              <span class="legend-num">%</span>
            </div>
            <ul class="legend-list">
              <li v-for="(lab, i) in labels" :key="i" class="legend-row">
                <span class="legend-name">
                  <span class="legend-swatch" :style="{ background: colorFor(lab, i) }"></span>
                  {{ lab }}
                </span>
                <span class="legend-num">{{ (data[i] ?? 0).toLocaleString('es-MX') }}</span>
                <span class="legend-num legend-pct">{{ porcentaje(data[i]) }}</span>
              </li>
            </ul>
            <div class="legend-total">
              <span>Total</span>
              <span class="legend-num">{{ total.toLocaleString('es-MX') }}</span>
              <span class="legend-num">100%</span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div v-if="modalAbierto" class="modal-backdrop-custom" @click.self="cerrarModal">
      <div class="modal-panel card-surface" role="dialog" aria-modal="true">
        <div class="modal-head">
          <strong>Seleccionar plantillas</strong>
          <button type="button" class="btn-close-x" aria-label="Cerrar" @click="cerrarModal">×</button>
        </div>

        <div class="modal-search">
          <span class="search-ico">⌕</span>
          <input
            v-model="modalBusqueda"
            type="search"
            class="search-input"
            placeholder="Buscar por nombre…"
          >
        </div>

        <div class="modal-body-scroll">
          <p v-if="!modalOpcionesFiltradas.length" class="empty-mini">
            {{ plantillas.length ? 'Sin coincidencias para la búsqueda.' : 'No hay plantillas disponibles.' }}
          </p>
          <label
            v-for="item in modalOpcionesFiltradas"
            :key="item.id"
            class="opt-row"
            :class="{ 'opt-on': modalDraft.includes(String(item.id)) }"
          >
            <input
              class="opt-check"
              type="checkbox"
              :value="String(item.id)"
              v-model="modalDraft"
            >
            <span class="opt-name">{{ item.nombre }}</span>
          </label>
        </div>

        <div class="modal-foot">
          <div class="foot-left">
            <button type="button" class="btn-ghost" @click="modalSeleccionarTodo">Marcar todos</button>
            <button type="button" class="btn-ghost" @click="modalDraft = []">Limpiar</button>
            <span class="foot-count">{{ modalDraft.length }} seleccionado(s)</span>
          </div>
          <div class="foot-right">
            <button type="button" class="btn-ghost" @click="cerrarModal">Cancelar</button>
            <button type="button" class="btn-apply" @click="aplicarModal">Aplicar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { jsPDF } from 'jspdf'
import DashboardStatsChart from '../components/DashboardStatsChart.vue'

const ESTADO_COLORS = {
  aprobados: '#AB0A3D',
  pendientes: '#B68400',
}

const PALETTE = [
  '#AB0A3D', '#B68400', '#440412', '#3D3935', '#8F8A80',
  '#D9A94A', '#7A1F33', '#6B675F', '#D4667E', '#B0ABA1',
]

function appendArray(params, key, values) {
  values.forEach((v) => params.append(`${key}[]`, String(v)))
}

export default {
  name: 'Dashboard',
  components: { DashboardStatsChart },
  props: {
    userName: { type: String, default: '' },
  },
  data() {
    return {
      opcionesEstado: [
        { value: 'aprobados', label: 'Aprobados', color: ESTADO_COLORS.aprobados },
        { value: 'pendientes', label: 'Pendientes', color: ESTADO_COLORS.pendientes },
      ],
      tiposGrafica: [
        { value: 'pie', label: 'Pastel', ico: '◕' },
        { value: 'bar', label: 'Barras', ico: '▮' },
        { value: 'polarArea', label: 'Polar', ico: '◎' },
      ],
      estados: ['aprobados', 'pendientes'],
      idsPlantillas: [],
      plantillas: [],
      tipoGrafica: 'pie',
      labels: [],
      data: [],
      total: 0,
      kpi: { aprobados: 0, pendientes: 0 },
      dimensionLabel: '',
      filtroResumen: '',
      cargando: false,
      descargando: false,
      error: null,
      chartKey: 0,
      modalAbierto: false,
      modalDraft: [],
      modalBusqueda: '',
    }
  },
  computed: {
    modalOpcionesFiltradas() {
      const q = this.modalBusqueda.trim().toLowerCase()
      if (!q) return this.plantillas
      return this.plantillas.filter((i) =>
        String(i.nombre || '').toLowerCase().includes(q) ||
        String(i.codigo || '').toLowerCase().includes(q)
      )
    },
    resumenPlantillas() {
      if (!this.idsPlantillas.length) return 'Todas las plantillas'
      if (this.idsPlantillas.length === 1) {
        const item = this.plantillas.find((c) => String(c.id) === String(this.idsPlantillas[0]))
        return item?.nombre || '1 seleccionada'
      }
      return `${this.idsPlantillas.length} seleccionadas`
    },
    tieneDatos() {
      return this.total > 0 && this.labels.length > 0
    },
    puedeDescargar() {
      return this.tieneDatos && !this.cargando && this.estados.length > 0
    },
    kpiPorEstado() {
      return {
        aprobados: this.kpi.aprobados ?? 0,
        pendientes: this.kpi.pendientes ?? 0,
      }
    },
    chartData() {
      const colors = this.labels.map((lab, i) => this.colorFor(lab, i))
      return {
        labels: this.labels,
        datasets: [{
          label: this.dimensionLabel || 'Cantidad',
          data: this.data,
          backgroundColor: colors,
          borderColor: this.tipoGrafica === 'bar' ? colors : '#ffffff',
          borderWidth: this.tipoGrafica === 'bar' ? 0 : 2,
        }],
      }
    },
  },
  async mounted() {
    await this.cargarPlantillas()
    await this.cargarEstadisticas()
  },
  methods: {
    colorFor(label, index) {
      const opt = this.opcionesEstado.find((o) =>
        String(label).toLowerCase().includes(o.label.toLowerCase())
      )
      return opt ? opt.color : PALETTE[index % PALETTE.length]
    },
    porcentaje(valor) {
      if (!this.total || !valor) return '0%'
      return `${Math.round((valor / this.total) * 100)}%`
    },
    porcentajeKpi(valor) {
      const base = (this.kpi.aprobados || 0) + (this.kpi.pendientes || 0)
      if (!base || !valor) return '0%'
      return `${Math.round((valor / base) * 100)}%`
    },
    limpiarPlantillas() {
      this.idsPlantillas = []
      this.cargarEstadisticas()
    },
    abrirModal() {
      this.modalAbierto = true
      this.modalBusqueda = ''
      this.modalDraft = [...this.idsPlantillas]
    },
    cerrarModal() {
      this.modalAbierto = false
      this.modalDraft = []
      this.modalBusqueda = ''
    },
    modalSeleccionarTodo() {
      this.modalDraft = this.modalOpcionesFiltradas.map((i) => String(i.id))
    },
    async aplicarModal() {
      this.idsPlantillas = [...this.modalDraft]
      this.cerrarModal()
      await this.cargarEstadisticas()
    },
    buildQueryParams() {
      const params = new URLSearchParams()
      appendArray(params, 'estados', this.estados)
      if (this.idsPlantillas.length) appendArray(params, 'plantillas', this.idsPlantillas)
      return params
    },
    async onEstadosChange() {
      if (!this.estados.length) {
        this.error = 'Seleccione al menos un estado.'
        this.labels = []
        this.data = []
        this.total = 0
        this.kpi = { aprobados: 0, pendientes: 0 }
        return
      }
      await this.cargarEstadisticas()
    },
    async cargarPlantillas() {
      try {
        const { data } = await axios.get('/dashboard/plantillas')
        this.plantillas = Array.isArray(data) ? data : []
      } catch (_) {
        this.plantillas = []
      }
    },
    async cargarEstadisticas() {
      if (!this.estados.length) {
        this.error = 'Seleccione al menos un estado.'
        return
      }

      this.cargando = true
      this.error = null
      try {
        const { data } = await axios.get(`/dashboard/estadisticas?${this.buildQueryParams().toString()}`)
        this.labels = data.labels || []
        this.data = data.data || []
        this.total = data.total || 0
        this.dimensionLabel = data.dimension_label || ''
        this.filtroResumen = data.filtro_resumen || ''
        this.kpi = data.kpi || { aprobados: 0, pendientes: 0 }
        this.chartKey += 1
      } catch (e) {
        this.labels = []
        this.data = []
        this.total = 0
        this.kpi = { aprobados: 0, pendientes: 0 }
        const errors = e.response?.data?.errors
        if (errors) {
          this.error = Object.values(errors).flat().join(' ')
        } else {
          this.error = e.response?.data?.message
            || 'No se pudieron cargar las estadísticas.'
        }
      } finally {
        this.cargando = false
      }
    },
    async descargarPDF() {
      if (!this.puedeDescargar) return

      this.descargando = true
      this.error = null
      try {
        await this.$nextTick()
        await new Promise((resolve) => requestAnimationFrame(() => setTimeout(resolve, 150)))
        const chartImage = this.$refs.chartCmp?.toBase64Image?.()
        if (!chartImage) {
          this.error = 'No se pudo capturar la gráfica para el PDF. Intente de nuevo.'
          return
        }

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
        const margin = 16
        let y = margin

        doc.setFontSize(16)
        doc.setTextColor(68, 4, 18)
        doc.text('Estadísticas de documentos — Proteo', margin, y)
        y += 8

        doc.setFontSize(10)
        doc.setTextColor(61, 57, 53)
        doc.text(`Usuario: ${this.userName || '—'}`, margin, y)
        y += 6
        doc.text(`Filtro: ${this.filtroResumen || '—'}`, margin, y)
        y += 6
        doc.text(`Total: ${this.total.toLocaleString('es-MX')}`, margin, y)
        y += 6
        doc.text(
          `Aprobados: ${(this.kpi.aprobados ?? 0).toLocaleString('es-MX')} · Pendientes: ${(this.kpi.pendientes ?? 0).toLocaleString('es-MX')}`,
          margin,
          y,
        )
        y += 10

        const imgW = 178
        const imgH = 95
        doc.addImage(chartImage, 'PNG', margin, y, imgW, imgH)
        y += imgH + 10

        doc.setFontSize(11)
        doc.text('Detalle', margin, y)
        y += 7
        doc.setFontSize(9)
        this.labels.forEach((lab, i) => {
          if (y > 280) {
            doc.addPage()
            y = margin
          }
          const line = `${lab}: ${(this.data[i] ?? 0).toLocaleString('es-MX')} (${this.porcentaje(this.data[i])})`
          doc.text(line, margin, y)
          y += 5
        })

        doc.save('estadisticas-documentos.pdf')
      } catch (_) {
        this.error = 'No se pudo generar el PDF.'
      } finally {
        this.descargando = false
      }
    },
  },
}
</script>

<style scoped>
.dashboard-stats {
  --bg: #f7f5f2;
  --surface: #ffffff;
  --ink: #3D3935;
  --ink-soft: #78736a;
  --line: #e4e0d8;
  --brand: #AB0A3D;
  --brand-dark: #440412;
  --brand-soft: #f7e5eb;
  --gold: #B68400;
  --gold-soft: #faf1dd;
  --stone: #B0ABA1;
  max-width: 1240px;
  margin: 0 auto;
  padding: 1.75rem 1rem 3rem;
  color: var(--ink);
}

.page-head {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 1.5rem;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 .25rem;
  letter-spacing: -.01em;
}
.page-sub { margin: 0; color: var(--ink-soft); font-size: .9rem; line-height: 1.5; }
.btn-download {
  display: inline-flex; align-items: center; gap: .1rem;
  background: var(--brand); color: #fff; border: none;
  padding: .6rem 1.1rem; border-radius: .6rem; font-weight: 600; font-size: .9rem;
  cursor: pointer; transition: background .15s, opacity .15s;
}
.btn-download .ico { font-size: 1rem; margin-right: .4rem; }
.btn-download:hover:not(:disabled) { background: var(--brand-dark); }
.btn-download:disabled { opacity: .45; cursor: not-allowed; }

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.kpi {
  --kpi-color: var(--brand);
  position: relative;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: .85rem;
  padding: 1rem 1.1rem;
  display: flex; flex-direction: column; gap: .15rem;
  box-shadow: 0 1px 2px rgba(20, 30, 60, .04);
}
.kpi-total {
  background: linear-gradient(135deg, var(--brand-dark), var(--brand));
  border: none; color: #fff;
}
.kpi-total .kpi-label, .kpi-total .kpi-foot { color: rgba(255,255,255,.82); }
.kpi-dot {
  width: 10px; height: 10px; border-radius: 3px; background: var(--kpi-color);
  position: absolute; top: 1.15rem; right: 1.1rem;
}
.kpi-label { font-size: .78rem; color: var(--ink-soft); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.kpi-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
.kpi-foot { font-size: .78rem; color: var(--ink-soft); }
.kpi-off { opacity: .5; }

.layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 1rem;
  align-items: start;
}
.card-surface {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: .95rem;
  box-shadow: 0 1px 2px rgba(20, 30, 60, .04);
}
.filters { padding: 1.25rem; }
.panel-title { font-size: 1rem; font-weight: 700; margin: 0 0 1rem; }
.field-label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .55rem; }
.field-help { font-size: .76rem; color: var(--ink-soft); margin: .55rem 0 0; line-height: 1.4; }

.chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.chip {
  --chip-color: var(--brand);
  display: inline-flex; align-items: center; gap: .45rem;
  border: 1px solid var(--line); border-radius: 2rem;
  padding: .4rem .8rem; font-size: .82rem; font-weight: 600;
  cursor: pointer; user-select: none; color: var(--ink-soft);
  transition: all .15s;
  position: relative;
}
.chip-input { position: absolute; opacity: 0; width: 0; height: 0; }
.chip-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--chip-color); opacity: .35; }
.chip-on {
  color: var(--chip-color); border-color: var(--chip-color);
  background: color-mix(in srgb, var(--chip-color) 10%, #fff);
}
.chip-on .chip-dot { opacity: 1; }

.sep { border: none; border-top: 1px solid var(--line); margin: 1.25rem 0; }

.steps { display: flex; flex-direction: column; gap: .9rem; }
.step { display: flex; gap: .7rem; }
.step-num {
  flex: none;
  width: 26px; height: 26px; border-radius: 50%;
  background: var(--brand-soft); color: var(--brand);
  display: grid; place-items: center; font-size: .8rem; font-weight: 700;
}
.step-body { flex: 1; min-width: 0; }
.step-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem; gap: .5rem; }
.step-name { font-size: .85rem; font-weight: 600; }
.step-help { font-size: .74rem; color: var(--ink-soft); margin: .35rem 0 0; line-height: 1.4; }
.clear-badge {
  flex: none;
  border: none; background: var(--brand-soft); color: var(--brand);
  font-size: .72rem; font-weight: 700; border-radius: 1rem;
  padding: .1rem .5rem; cursor: pointer;
}
.picker {
  width: 100%; text-align: left;
  border: 1px solid var(--line); background: #fbfcfe; border-radius: .6rem;
  padding: .55rem .75rem; font-size: .85rem; color: var(--ink);
  display: flex; align-items: center; justify-content: space-between; gap: .5rem;
  cursor: pointer; transition: border-color .15s;
}
.picker:hover { border-color: var(--brand); }
.picker-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.picker-caret { color: var(--ink-soft); font-size: .7rem; }

.result { padding: 1.25rem; }
.result-head {
  display: flex; flex-wrap: wrap; gap: .75rem;
  justify-content: space-between; align-items: center; margin-bottom: 1rem;
}
.result-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
.result-scope { font-size: .74rem; font-weight: 700; padding: .2rem .6rem; border-radius: 1rem; }
.scope-global { background: var(--gold-soft); color: var(--gold); }
.result-filtro { font-size: .8rem; color: var(--ink-soft); }

.segmented { display: inline-flex; background: #ece8e1; border-radius: .6rem; padding: .2rem; gap: .15rem; }
.seg-btn {
  border: none; background: transparent; color: var(--ink-soft);
  font-size: .82rem; font-weight: 600; padding: .35rem .75rem; border-radius: .45rem;
  cursor: pointer; display: inline-flex; align-items: center; gap: .35rem;
}
.seg-ico { font-size: .9rem; }
.seg-on { background: #fff; color: var(--brand); box-shadow: 0 1px 2px rgba(20,30,60,.1); }

.chart-and-legend {
  display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; align-items: center;
}
.chart-wrap { min-height: 340px; display: flex; align-items: center; justify-content: center; }
.skeleton-chart {
  width: 260px; height: 260px; border-radius: 50%;
  background: linear-gradient(100deg, #ece8e1 30%, #f7f5f2 50%, #ece8e1 70%);
  background-size: 200% 100%; animation: shimmer 1.3s infinite;
}
@keyframes shimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }

.legend { border: 1px solid var(--line); border-radius: .7rem; overflow: hidden; }
.legend-head, .legend-row, .legend-total {
  display: grid; grid-template-columns: 1fr auto auto; gap: .75rem; align-items: center;
  padding: .55rem .85rem;
}
.legend-head { background: #f7f9fc; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; color: var(--ink-soft); font-weight: 700; }
.legend-list { list-style: none; margin: 0; padding: 0; max-height: 260px; overflow-y: auto; }
.legend-row { font-size: .84rem; border-top: 1px solid var(--line); }
.legend-name { display: flex; align-items: center; gap: .5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.legend-swatch { width: 11px; height: 11px; border-radius: 3px; flex: none; }
.legend-num { text-align: right; font-variant-numeric: tabular-nums; min-width: 44px; }
.legend-pct { color: var(--ink-soft); }
.legend-total { border-top: 2px solid var(--line); font-weight: 700; background: #f7f9fc; }

.empty { text-align: center; padding: 3rem 1rem; }
.empty-ico { font-size: 2.5rem; color: var(--stone); margin-bottom: .5rem; }
.empty-title { font-weight: 600; margin: 0 0 .25rem; }
.empty-sub { color: var(--ink-soft); font-size: .85rem; margin: 0; }
.empty-mini { color: var(--ink-soft); text-align: center; padding: 1.5rem 0; margin: 0; }

.modal-backdrop-custom {
  position: fixed; inset: 0; z-index: 1055;
  background: rgba(15, 22, 40, .5); backdrop-filter: blur(2px);
  display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.modal-panel { width: min(540px, 100%); max-height: min(82vh, 680px); display: flex; flex-direction: column; }
.modal-head { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--line); }
.btn-close-x { border: none; background: none; font-size: 1.5rem; line-height: 1; color: var(--ink-soft); cursor: pointer; }
.modal-search { position: relative; padding: .85rem 1.25rem; border-bottom: 1px solid var(--line); }
.search-ico { position: absolute; left: 1.7rem; top: 50%; transform: translateY(-50%); color: var(--ink-soft); }
.search-input {
  width: 100%; border: 1px solid var(--line); border-radius: .55rem;
  padding: .5rem .75rem .5rem 2rem; font-size: .88rem;
}
.search-input:focus { outline: none; border-color: var(--brand); }
.modal-body-scroll { overflow-y: auto; padding: .5rem .75rem; flex: 1; }
.opt-row {
  display: flex; align-items: center; gap: .65rem;
  padding: .55rem .6rem; border-radius: .5rem; cursor: pointer; font-size: .88rem;
}
.opt-row:hover { background: #f5f7fb; }
.opt-on { background: var(--brand-soft); }
.opt-check { width: 16px; height: 16px; accent-color: var(--brand); }
.opt-name { display: flex; align-items: baseline; gap: .4rem; }
.modal-foot { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: space-between; align-items: center; padding: .85rem 1.25rem; border-top: 1px solid var(--line); }
.foot-left, .foot-right { display: flex; align-items: center; gap: .5rem; }
.foot-count { font-size: .78rem; color: var(--ink-soft); }
.btn-ghost {
  border: 1px solid var(--line); background: #fff; color: var(--ink);
  font-size: .82rem; font-weight: 600; padding: .45rem .8rem; border-radius: .5rem; cursor: pointer;
}
.btn-ghost:hover { background: #f5f7fb; }
.btn-apply {
  border: none; background: var(--brand); color: #fff;
  font-size: .82rem; font-weight: 600; padding: .45rem 1rem; border-radius: .5rem; cursor: pointer;
}
.btn-apply:hover { background: var(--brand-dark); }

@media (max-width: 992px) {
  .kpi-grid { grid-template-columns: 1fr; }
  .layout { grid-template-columns: 1fr; }
  .chart-and-legend { grid-template-columns: 1fr; }
}
@media (max-width: 520px) {
  .kpi-grid { grid-template-columns: 1fr; }
}
</style>
