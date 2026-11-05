<template>
  <div class="reportes">
    <header class="page-head">
      <div>
        <h1 class="page-title">Reportes por colección</h1>
        <p class="page-sub">
          Bienvenid@, <strong>{{ userName || '—' }}</strong>
          · Reporte completo de los documentos de cada plantilla.
        </p>
      </div>
      <div class="head-actions">
        <button
          type="button"
          class="btn-download"
          :disabled="!puedeDescargar || descargando"
          @click="descargarPDF"
        >
          <span v-if="descargando" class="spinner-border spinner-border-sm me-2" role="status"></span>
          Descargar PDF
        </button>
      </div>
    </header>

    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>

    <section class="kpi-grid" aria-label="Resumen de la colección">
      <article class="kpi kpi-total">
        <span class="kpi-label">Total</span>
        <span class="kpi-value">{{ resumen.total.toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ nombreColeccion || 'Sin colección' }}</span>
      </article>
      <article class="kpi" style="--kpi-color: #AB0A3D">
        <span class="kpi-dot"></span>
        <span class="kpi-label">Aprobados</span>
        <span class="kpi-value">{{ resumen.aprobados.toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ porcentaje(resumen.aprobados) }}</span>
      </article>
      <article class="kpi" style="--kpi-color: #B68400">
        <span class="kpi-dot"></span>
        <span class="kpi-label">Pendientes</span>
        <span class="kpi-value">{{ resumen.pendientes.toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ porcentaje(resumen.pendientes) }}</span>
      </article>
      <article class="kpi" style="--kpi-color: #3D3935">
        <span class="kpi-dot"></span>
        <span class="kpi-label">Con recurso digital</span>
        <span class="kpi-value">{{ resumen.con_recurso.toLocaleString('es-MX') }}</span>
        <span class="kpi-foot">{{ porcentaje(resumen.con_recurso) }}</span>
      </article>
    </section>

    <div class="layout">
      <aside class="filters card-surface">
        <h2 class="panel-title">Colección</h2>

        <label class="field-label" for="plantilla">Plantilla</label>
        <select id="plantilla" v-model="plantillaId" class="select" @change="cargarReporte">
          <option value="">Selecciona una plantilla</option>
          <option v-for="item in plantillas" :key="item.id" :value="item.id">
            {{ item.nombre }}
          </option>
        </select>
        <p class="field-help">Cada plantilla es una colección de documentos.</p>

        <hr class="sep">

        <span class="field-label">Estado</span>
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
              @change="pagina = 1"
            >
            <span class="chip-dot"></span>{{ opt.label }}
          </label>
        </div>

        <hr class="sep">

        <label class="field-label" for="buscar">Buscar en el reporte</label>
        <input
          id="buscar"
          v-model="busqueda"
          type="search"
          class="select"
          placeholder="Título, autor, estado…"
          @input="pagina = 1"
        >
        <p class="field-help">
          El PDF usa los filtros actuales.
          <span v-if="reporte">Mostrando {{ filtrados.length.toLocaleString('es-MX') }} de {{ resumen.total.toLocaleString('es-MX') }}.</span>
        </p>
      </aside>

      <section class="result card-surface">
        <div v-if="cargando" class="empty">
          <div class="skeleton"></div>
          <p class="empty-sub">Armando el reporte…</p>
        </div>

        <div v-else-if="!plantillaId" class="empty">
          <div class="empty-ico">▤</div>
          <p class="empty-title">Elige una colección</p>
          <p class="empty-sub">El reporte lista todos los campos de esa plantilla.</p>
        </div>

        <div v-else-if="!estados.length" class="empty">
          <div class="empty-ico">▤</div>
          <p class="empty-title">Selecciona al menos un estado</p>
          <p class="empty-sub">Aprobados, pendientes, o ambos.</p>
        </div>

        <div v-else-if="!filtrados.length" class="empty">
          <div class="empty-ico">▤</div>
          <p class="empty-title">No hay documentos para esta combinación</p>
          <p class="empty-sub">Cambia el estado o la búsqueda.</p>
        </div>

        <template v-else>
          <div class="table-scroll">
            <table class="reporte">
              <thead>
                <tr>
                  <th>Estado</th>
                  <th v-for="col in columnas" :key="col.key">{{ col.label }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="doc in paginaActual" :key="doc.id + doc.estado">
                  <td>
                    <span class="badge" :class="doc.estado === 'aprobado' ? 'badge-ok' : 'badge-pending'">
                      {{ doc.estado === 'aprobado' ? 'Aprobado' : 'Pendiente' }}
                    </span>
                  </td>
                  <td v-for="col in columnas" :key="col.key" :title="doc.valores[col.key] || ''">
                    <span class="cell">{{ doc.valores[col.key] || '—' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pager">
            <button type="button" class="btn-ghost" :disabled="pagina <= 1" @click="pagina--">Anterior</button>
            <span>Página {{ pagina }} de {{ totalPaginas }}</span>
            <button type="button" class="btn-ghost" :disabled="pagina >= totalPaginas" @click="pagina++">Siguiente</button>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { jsPDF } from 'jspdf'

const POR_PAGINA = 12

export default {
  name: 'Reportes',
  props: {
    userName: { type: String, default: '' },
  },
  data() {
    return {
      opcionesEstado: [
        { value: 'aprobado', label: 'Aprobados', color: '#AB0A3D' },
        { value: 'pendiente', label: 'Pendientes', color: '#B68400' },
      ],
      plantillas: [],
      plantillaId: '',
      estados: ['aprobado', 'pendiente'],
      busqueda: '',
      reporte: null,
      cargando: false,
      descargando: false,
      error: null,
      pagina: 1,
    }
  },
  computed: {
    nombreColeccion() {
      return this.reporte?.nombre || ''
    },
    columnas() {
      return this.reporte?.columnas || []
    },
    resumen() {
      return this.reporte?.resumen || { aprobados: 0, pendientes: 0, total: 0, con_recurso: 0 }
    },
    filtrados() {
      const docs = this.reporte?.documentos || []
      const q = this.busqueda.trim().toLowerCase()
      return docs.filter((doc) => {
        if (!this.estados.includes(doc.estado)) return false
        if (!q) return true
        const estado = doc.estado === 'aprobado' ? 'aprobado' : 'pendiente'
        const texto = [estado, ...Object.values(doc.valores || {})].join(' ').toLowerCase()
        return texto.includes(q)
      })
    },
    totalPaginas() {
      return Math.max(1, Math.ceil(this.filtrados.length / POR_PAGINA))
    },
    paginaActual() {
      const start = (this.pagina - 1) * POR_PAGINA
      return this.filtrados.slice(start, start + POR_PAGINA)
    },
    puedeDescargar() {
      return !!this.reporte && this.estados.length > 0 && this.filtrados.length > 0 && !this.cargando
    },
  },
  watch: {
    totalPaginas(value) {
      if (this.pagina > value) this.pagina = value
    },
  },
  mounted() {
    this.cargarPlantillas()
  },
  methods: {
    porcentaje(valor) {
      const total = this.resumen.total
      if (!total) return '0%'
      return `${Math.round((Number(valor) / total) * 100)}%`
    },
    async cargarPlantillas() {
      try {
        const { data } = await axios.get('/dashboard/plantillas')
        this.plantillas = Array.isArray(data) ? data : []
      } catch (_) {
        this.error = 'No se pudieron cargar las colecciones.'
        this.plantillas = []
      }
    },
    async cargarReporte() {
      this.error = null
      this.reporte = null
      this.pagina = 1
      this.busqueda = ''
      if (!this.plantillaId) return

      this.cargando = true
      try {
        const { data } = await axios.get(`/reportes/datos/${encodeURIComponent(this.plantillaId)}`)
        this.reporte = data
      } catch (e) {
        this.error = e.response?.data?.message || 'No se pudo generar el reporte.'
      } finally {
        this.cargando = false
      }
    },
    nombreArchivo(ext) {
      const base = (this.nombreColeccion || 'coleccion')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\w.-]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '')
        .toLowerCase()
      const fecha = (this.reporte?.generado_en || '').slice(0, 10).replaceAll('/', '-')
      return `reporte-${base || 'coleccion'}${fecha ? '-' + fecha : ''}.${ext}`
    },
    descargarPDF() {
      if (!this.puedeDescargar) return
      this.descargando = true
      this.error = null
      try {
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
        const margin = 8
        const pageW = doc.internal.pageSize.getWidth()
        const pageH = doc.internal.pageSize.getHeight()
        const usable = pageW - margin * 2
        const fontSize = 7
        const lineH = 3.15
        const maxLineas = 3

        const etiquetas = ['Estado', ...this.columnas.map((col) => col.label)]
        const filas = this.filtrados.map((item) => [
          item.estado === 'aprobado' ? 'Aprobado' : 'Pendiente',
          ...this.columnas.map((col) => item.valores[col.key] || ''),
        ])

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(fontSize)
        const anchos = etiquetas.map((etiqueta, indice) => {
          let ancho = doc.getTextWidth(etiqueta) + 3
          filas.slice(0, 30).forEach((fila) => {
            const texto = String(fila[indice] || '').slice(0, 36)
            ancho = Math.max(ancho, doc.getTextWidth(texto) + 3)
          })
          const minimo = indice === 0 ? 20 : 18
          return Math.min(46, Math.max(minimo, ancho))
        })

        const grupos = []
        let cursor = 1
        if (anchos.length <= 1) {
          grupos.push([0])
        }
        while (cursor < anchos.length) {
          const grupo = [0]
          let usado = anchos[0]
          while (cursor < anchos.length && usado + anchos[cursor] <= usable) {
            grupo.push(cursor)
            usado += anchos[cursor]
            cursor += 1
          }
          if (grupo.length === 1) {
            grupo.push(cursor)
            cursor += 1
          }
          grupos.push(grupo)
        }

        const lineasDe = (texto, ancho, negrita = false) => {
          doc.setFont('helvetica', negrita ? 'bold' : 'normal')
          doc.setFontSize(fontSize)
          const contenido = String(texto || '')
          let lineas = doc.splitTextToSize(contenido, Math.max(8, ancho - 2))
          if (!lineas.length) lineas = ['']
          if (lineas.length > maxLineas) {
            lineas = lineas.slice(0, maxLineas)
            lineas[maxLineas - 1] = `${String(lineas[maxLineas - 1]).replace(/\.*$/, '')}...`
          }
          return lineas
        }

        const altoDe = (celdas, anchosGrupo, negrita = false) => {
          const max = Math.max(...celdas.map((texto, i) => lineasDe(texto, anchosGrupo[i], negrita).length))
          return max * lineH + 2
        }

        const dibujarFila = (celdas, anchosGrupo, y, esEncabezado, zebra) => {
          const alto = altoDe(celdas, anchosGrupo, esEncabezado)
          let x = margin
          celdas.forEach((texto, i) => {
            if (esEncabezado) {
              doc.setFillColor(68, 4, 18)
            } else if (zebra) {
              doc.setFillColor(247, 245, 242)
            } else {
              doc.setFillColor(255, 255, 255)
            }
            doc.setDrawColor(228, 224, 216)
            doc.rect(x, y, anchosGrupo[i], alto, 'FD')
            doc.setFont('helvetica', esEncabezado ? 'bold' : 'normal')
            doc.setFontSize(fontSize)
            doc.setTextColor(...(esEncabezado ? [255, 255, 255] : [61, 57, 53]))
            lineasDe(texto, anchosGrupo[i], esEncabezado).forEach((linea, n) => {
              doc.text(linea, x + 1, y + 3.4 + n * lineH)
            })
            x += anchosGrupo[i]
          })
          return alto
        }

        const escalar = (indices) => {
          const crudos = indices.map((i) => anchos[i])
          const suma = crudos.reduce((a, b) => a + b, 0) || 1
          return crudos.map((w) => (w / suma) * usable)
        }

        const encabezado = (indiceGrupo, continuar) => {
          let y = margin
          doc.setFont('helvetica', 'bold')
          doc.setFontSize(13)
          doc.setTextColor(68, 4, 18)
          doc.text(`Reporte de colección — ${this.nombreColeccion}`, margin, y + 4)
          y += 8
          doc.setFont('helvetica', 'normal')
          doc.setFontSize(8)
          doc.setTextColor(61, 57, 53)
          const meta = `Total: ${this.resumen.total}   Aprobados: ${this.resumen.aprobados}   Pendientes: ${this.resumen.pendientes}   Con recurso digital: ${this.resumen.con_recurso}`
          doc.text(meta, margin, y)
          y += 4
          const detalle = `Generado: ${this.reporte.generado_en || '-'}   Usuario: ${this.userName || '-'}   Documentos: ${filas.length}`
          doc.text(detalle, margin, y)
          y += 4
          if (grupos.length > 1) {
            doc.setTextColor(171, 10, 61)
            doc.text(
              continuar
                ? `Tabla ${indiceGrupo + 1} de ${grupos.length}, continuación`
                : `Tabla ${indiceGrupo + 1} de ${grupos.length}`,
              margin,
              y,
            )
            y += 3
          }
          return y + 2
        }

        grupos.forEach((grupo, indiceGrupo) => {
          if (indiceGrupo > 0) doc.addPage()
          const anchosGrupo = escalar(grupo)
          const titulos = grupo.map((i) => etiquetas[i])
          let y = encabezado(indiceGrupo, false)
          y += dibujarFila(titulos, anchosGrupo, y, true, false)

          filas.forEach((fila, indiceFila) => {
            const celdas = grupo.map((i) => fila[i])
            const alto = altoDe(celdas, anchosGrupo)
            if (y + alto > pageH - 10) {
              doc.addPage()
              y = encabezado(indiceGrupo, true)
              y += dibujarFila(titulos, anchosGrupo, y, true, false)
            }
            y += dibujarFila(celdas, anchosGrupo, y, false, indiceFila % 2 === 1)
          })
        })

        const totalPaginasPdf = doc.internal.getNumberOfPages()
        for (let p = 1; p <= totalPaginasPdf; p += 1) {
          doc.setPage(p)
          doc.setFont('helvetica', 'normal')
          doc.setFontSize(8)
          doc.setTextColor(120, 115, 108)
          doc.text(`${p} / ${totalPaginasPdf}`, pageW - margin, pageH - 4, { align: 'right' })
        }

        doc.save(this.nombreArchivo('pdf'))
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
.reportes {
  --surface: #ffffff;
  --ink: #3D3935;
  --ink-soft: #78736a;
  --line: #e4e0d8;
  --brand: #AB0A3D;
  --brand-dark: #440412;
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
}
.page-sub { margin: 0; color: var(--ink-soft); font-size: .9rem; line-height: 1.5; }
.head-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.btn-download {
  background: var(--brand);
  color: #fff;
  border: none;
  padding: .6rem 1.1rem;
  border-radius: .6rem;
  font-weight: 600;
  font-size: .9rem;
  cursor: pointer;
}
.btn-download:hover:not(:disabled) { background: var(--brand-dark); }
.btn-download:disabled { opacity: .45; cursor: not-allowed; }

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
  display: flex;
  flex-direction: column;
  gap: .15rem;
}
.kpi-total {
  background: linear-gradient(135deg, var(--brand-dark), var(--brand));
  border: none;
  color: #fff;
}
.kpi-total .kpi-label, .kpi-total .kpi-foot { color: rgba(255,255,255,.82); }
.kpi-dot {
  width: 10px; height: 10px; border-radius: 3px; background: var(--kpi-color);
  position: absolute; top: 1.15rem; right: 1.1rem;
}
.kpi-label { font-size: .78rem; color: var(--ink-soft); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
.kpi-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
.kpi-foot { font-size: .78rem; color: var(--ink-soft); }

.layout {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 1rem;
  align-items: start;
}
.card-surface {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: .95rem;
}
.filters { padding: 1.25rem; }
.panel-title { font-size: 1rem; font-weight: 700; margin: 0 0 1rem; }
.field-label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .45rem; }
.field-help { font-size: .76rem; color: var(--ink-soft); margin: .55rem 0 0; line-height: 1.4; }
.select {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: .6rem;
  padding: .55rem .7rem;
  font-size: .9rem;
  color: var(--ink);
  background: #fff;
}
.sep { border: none; border-top: 1px solid var(--line); margin: 1.15rem 0; }
.chips { display: flex; flex-wrap: wrap; gap: .5rem; }
.chip {
  --chip-color: var(--brand);
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  border: 1px solid var(--line);
  border-radius: 2rem;
  padding: .4rem .8rem;
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  color: var(--ink-soft);
  position: relative;
}
.chip-input { position: absolute; opacity: 0; width: 0; height: 0; }
.chip-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--chip-color); opacity: .35; }
.chip-on {
  color: var(--chip-color);
  border-color: var(--chip-color);
  background: color-mix(in srgb, var(--chip-color) 10%, #fff);
}
.chip-on .chip-dot { opacity: 1; }

.result { padding: 1rem; min-height: 280px; }
.table-scroll { overflow: auto; max-height: 68vh; }
.reporte {
  width: max-content;
  min-width: 100%;
  border-collapse: collapse;
  font-size: .84rem;
}
.reporte th, .reporte td {
  border-bottom: 1px solid var(--line);
  padding: .55rem .7rem;
  text-align: left;
  vertical-align: top;
  white-space: nowrap;
}
.reporte th {
  position: sticky;
  top: 0;
  background: #f7f5f2;
  font-size: .75rem;
  text-transform: uppercase;
  letter-spacing: .03em;
  color: var(--ink-soft);
}
.cell {
  display: inline-block;
  max-width: 240px;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: bottom;
}
.badge {
  display: inline-block;
  border-radius: 2rem;
  padding: .15rem .55rem;
  font-size: .75rem;
  font-weight: 700;
}
.badge-ok { background: #f7e5eb; color: #AB0A3D; }
.badge-pending { background: #faf1dd; color: #8a6500; }
.pager {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: .75rem;
  padding-top: .9rem;
  color: var(--ink-soft);
  font-size: .85rem;
}
.btn-ghost {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: .5rem;
  padding: .35rem .7rem;
  cursor: pointer;
  color: var(--ink);
}
.btn-ghost:disabled { opacity: .4; cursor: not-allowed; }
.empty { text-align: center; padding: 3rem 1rem; }
.empty-ico { font-size: 1.8rem; color: var(--ink-soft); }
.empty-title { font-weight: 700; margin: .4rem 0 .2rem; }
.empty-sub { color: var(--ink-soft); margin: 0; }
.skeleton {
  height: 8px;
  border-radius: 99px;
  margin: 0 auto 1rem;
  width: 40%;
  background: linear-gradient(90deg, #eee, #f7f5f2, #eee);
  background-size: 200% 100%;
  animation: shine 1.2s infinite;
}
@keyframes shine {
  0% { background-position: 100% 0; }
  100% { background-position: -100% 0; }
}

@media (max-width: 980px) {
  .kpi-grid, .layout { grid-template-columns: 1fr; }
}
</style>
