<template>
  <div class="plantillas-page">
    <header class="page-head">
      <div>
        <h1 class="page-title">Consultar plantillas</h1>
        <p class="page-sub">Plantillas del acervo disponibles para editar o eliminar.</p>
      </div>
      <div class="search-box">
        <span class="search-ico">⌕</span>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar plantilla..."
          class="search-input"
        >
      </div>
    </header>

    <p class="results-count">
      {{ plantillasVisibles.length }} plantilla{{ plantillasVisibles.length === 1 ? '' : 's' }} encontrada{{ plantillasVisibles.length === 1 ? '' : 's' }}
    </p>

    <div v-if="paginatedPlantillas.length" class="folders-grid">
      <details
        v-for="plantilla in paginatedPlantillas"
        :key="nombreDe(plantilla)"
        class="folder"
      >
        <summary class="folder-cover">
          <span class="folder-tab" aria-hidden="true"></span>
          <div class="folder-cover-inner">
            <div class="folder-cover-top">
              <span class="exp-badge">
                <span class="exp-badge-dot"></span>Plantilla
              </span>
              <span class="folder-chevron" aria-hidden="true"></span>
            </div>
            <div class="folder-cover-main">
              <span class="folder-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                </svg>
              </span>
              <div class="folder-heading">
                <h3 class="exp-title">{{ nombreDe(plantilla) }}</h3>
                <p class="exp-path">
                  {{ cantidadCampos(plantilla) }} campo{{ cantidadCampos(plantilla) === 1 ? '' : 's' }} definidos
                </p>
              </div>
            </div>
            <div class="folder-cover-foot">
              <span class="folder-chip">Colección: {{ nombreDe(plantilla) }}</span>
              <span class="folder-open-label">
                <span class="lbl-closed">Abrir plantilla</span>
                <span class="lbl-open">Cerrar plantilla</span>
              </span>
            </div>
          </div>
        </summary>

        <div class="folder-content">
          <div class="folder-content-grid">
            <div class="folder-detail-block">
              <div class="campos-head">
                <div class="exp-section-label campos-title">Campos de la plantilla</div>
                <span class="campos-count">{{ cantidadCampos(plantilla) }} campo{{ cantidadCampos(plantilla) === 1 ? '' : 's' }}</span>
              </div>

              <div v-if="listaCampos(plantilla).length" class="campos-grid">
                <article
                  v-for="campo in listaCampos(plantilla)"
                  :key="campo.name"
                  class="campo-card"
                  :class="{ 'campo-required': campo.required }"
                >
                  <div class="campo-card-top">
                    <span class="campo-name">{{ campo.name }}</span>
                    <span v-if="campo.required" class="campo-req-badge">Obligatorio</span>
                  </div>
                  <span class="campo-type" :class="'tipo-' + (campo.type || 'string')">
                    {{ etiquetaTipo(campo.type) }}
                  </span>
                </article>
              </div>
              <p v-else class="empty-fields">Sin campos cargados en el listado.</p>
            </div>
          </div>

          <div class="exp-actions">
            <button class="btn-apply" @click.stop="openEditModal(nombreDe(plantilla))">
              Editar plantilla
            </button>
            <button class="btn-ghost btn-danger-ghost" @click.stop="eliminarPlantilla(plantilla)">
              Eliminar plantilla
            </button>
          </div>
        </div>
      </details>
    </div>

    <div v-else class="empty">
      <div class="empty-ico">▤</div>
      <p class="empty-title">No se encontraron plantillas</p>
      <p class="empty-sub">Ajuste el término de búsqueda e intente de nuevo.</p>
    </div>

    <nav v-if="totalPaginas > 1" aria-label="Paginación" class="pager">
      <button class="btn-ghost" :disabled="paginaActual === 1" @click="paginaActual--">
        ‹ Anterior
      </button>
      <span class="pager-info">Página {{ paginaActual }} de {{ totalPaginas }}</span>
      <button class="btn-ghost" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
        Siguiente ›
      </button>
    </nav>

    <!-- Modal para editar plantilla -->
    <div
      v-if="mostrarModalEdit"
      class="modal fade show exp-modal"
      id="editModal"
      tabindex="-1"
      aria-labelledby="editModalLabel"
      aria-hidden="true"
      style="display: block;"
    >
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Editar plantilla: {{ nombrePlantilla }}</h5>
            <button type="button" class="btn-close-x" aria-label="Cerrar" @click="closeEditModal">×</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitEditForm">
              <div v-for="(campo, index) in camposPlantilla" :key="index" class="field-card">
                <div class="field-grid">
                  <div class="field">
                    <label class="field-label" :for="'campoNombre_' + index">Nombre del campo</label>
                    <input
                      v-model="camposPlantilla[index].name"
                      :id="'campoNombre_' + index"
                      :name="'campoNombre_' + index"
                      class="form-control"
                    >
                  </div>
                  <div class="field">
                    <label class="field-label" :for="'campoTipo_' + index">Tipo del campo</label>
                    <select
                      v-model="camposPlantilla[index].type"
                      :id="'campoTipo_' + index"
                      :name="'campoTipo_' + index"
                      class="form-select"
                    >
                      <option value="string">String</option>
                      <option value="number">Number</option>
                      <option value="file">File</option>
                      <option value="date">Date</option>
                    </select>
                  </div>
                  <div class="field field-wide field-check-row">
                    <label class="check-line">
                      <input
                        type="checkbox"
                        :id="'campoRequerido_' + index"
                        v-model="camposPlantilla[index].required"
                        :name="'campoRequerido_' + index"
                      >
                      Campo obligatorio
                    </label>
                    <button type="button" class="btn-ghost btn-danger-ghost" @click="eliminarCampo(index)">
                      Eliminar campo
                    </button>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn-ghost" @click="agregarCampo()">Agregar campo</button>
                <button type="button" class="btn-ghost" @click="closeEditModal">Cancelar</button>
                <button type="submit" class="btn-apply">Actualizar plantilla</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div v-if="mostrarModalEdit" class="modal-backdrop-custom" @click="closeEditModal"></div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      plantillas: [], // Lista de plantillas obtenidas del servidor
      nombrePlantilla: '', // Nombre de la plantilla seleccionada para edición
      camposPlantilla: [], // Campos de la plantilla seleccionada para edición
      mostrarModalEdit: false, // Control de visibilidad del modal de edición
      nuevoCampo: {  // Nuevo campo que se agregará dinámicamente
        name: '',
        type: '',
        alias: '',
        required: false
      },
      busqueda: '',
      paginaActual: 1,
      porPagina: 6,
    };
  },
  computed: {
    plantillasFiltradas() {
      // Excluir plantillas específicas
      const excluidas = [
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'personal_access_tokens',
        'plantillas_predeterminadas',
        'users',
        'carrousel_images',
        'navbar_colors',
        'comentarios', 'noticias_collection'
      ];
      return this.plantillas.filter(plantilla => !excluidas.includes(plantilla));
    },
    plantillasVisibles() {
      const texto = this.busqueda.trim().toLowerCase();
      if (!texto) return this.plantillasFiltradas;
      return this.plantillasFiltradas.filter((plantilla) =>
        String(this.nombreDe(plantilla)).toLowerCase().includes(texto)
      );
    },
    totalPaginas() {
      return Math.ceil(this.plantillasVisibles.length / this.porPagina) || 0;
    },
    paginatedPlantillas() {
      const start = (this.paginaActual - 1) * this.porPagina;
      return this.plantillasVisibles.slice(start, start + this.porPagina);
    },
  },
  watch: {
    busqueda() {
      this.paginaActual = 1;
    },
  },
  methods: {
    nombreDe(plantilla) {
      return plantilla && plantilla.nombre ? plantilla.nombre : String(plantilla);
    },
    cantidadCampos(plantilla) {
      return this.listaCampos(plantilla).length;
    },
    listaCampos(plantilla) {
      const tipos = plantilla?.campos?.tipos;
      if (!tipos || typeof tipos !== 'object') return [];
      const requeridos = Array.isArray(plantilla?.campos?.requeridos)
        ? plantilla.campos.requeridos
        : [];
      return Object.keys(tipos).map((name) => ({
        name,
        type: tipos[name],
        required: requeridos.includes(name),
      }));
    },
    etiquetaTipo(tipo) {
      const map = {
        string: 'Texto',
        number: 'Número',
        file: 'Archivo',
        date: 'Fecha',
      };
      return map[tipo] || (tipo || 'Texto');
    },
    async fetchPlantillas() {
      try {
        const response = await axios.get('/plantillasEdit');
        this.plantillas = response.data;
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al obtener las plantillas.',
        });
      }
    },
    async fetchCamposPlantilla(plantillaName) {
      try {
        const response = await axios.get(`/plantillas/${plantillaName}/fields`);
        this.nombrePlantilla = plantillaName;
        this.camposPlantilla = response.data;
        this.mostrarModalEdit = true; // Mostrar modal de edición
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Error al obtener los campos de la plantilla.',
        });
      }
    },
    async submitEditForm() {
      const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Quieres actualizar la plantilla?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar'
      });

      if (result.isConfirmed) {
        try {
          const updatedFields = {
            fields: this.camposPlantilla  // Asegurarse de que los campos están dentro de un objeto "fields"
          };
          const response = await axios.put(`/plantillas/${this.nombrePlantilla}`, updatedFields);
          Swal.fire({
            icon: 'success',
            title: 'Actualizado',
            text: 'La plantilla ha sido actualizada exitosamente.',
          });
          this.closeEditModal(); // Cerrar modal después de la actualización
        } catch (error) {
          console.error('Error al actualizar la plantilla:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema al actualizar la plantilla.',
          });
        }
      }
    },
    eliminarCampo(index) {
      this.camposPlantilla.splice(index, 1);
    },
    agregarCampo() {
      this.camposPlantilla.push({ ...this.nuevoCampo });
      this.nuevoCampo = {
        name: '',
        type: '',
        alias: '',
        required: false
      };
    },
    openEditModal(plantilla) {
      this.fetchCamposPlantilla(String(plantilla));
    },
    closeEditModal() {
      this.mostrarModalEdit = false;
    },
    async eliminarPlantilla(plantilla) {
      const plantillaName = plantilla.nombre ? plantilla.nombre : String(plantilla); // Usa plantilla.nombre si está disponible
      console.log('Nombre de la plantilla' , plantillaName);

      const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Estás seguro de que deseas eliminar la plantilla ${plantillaName}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar',
      });

      if (result.isConfirmed) {
        try {
          const response = await axios.delete(`/plantillas/${plantillaName}`);
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: response.data.message,
          });
          this.fetchPlantillas(); // Refrescar la lista de plantillas
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Error al eliminar la plantilla: ${plantillaName}`,
          });
        }
      }
    },
  },
  created() {
    this.fetchPlantillas();
  }
};
</script>

<style scoped>
.plantillas-page {
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
  font-family: 'Montserrat', sans-serif;
  background: transparent;
  min-height: 100vh;
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
  margin-bottom: .5rem;
}
.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 .25rem;
  letter-spacing: -.01em;
}
.page-sub { margin: 0; color: var(--ink-soft); font-size: .9rem; }

.search-box {
  position: relative;
  min-width: 260px;
}
.search-ico {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-soft);
}
.search-input {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: .6rem;
  background: var(--surface);
  padding: .55rem .75rem .55rem 2.1rem;
  font-size: .88rem;
  color: var(--ink);
  font-family: inherit;
}
.search-input:focus {
  outline: none;
  border-color: var(--brand);
}

.results-count {
  font-size: .8rem;
  color: var(--ink-soft);
  margin: 0 0 1.25rem;
}

.folders-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem 1.25rem;
  align-items: stretch;
}

.folder {
  --folder-accent: var(--brand);
  position: relative;
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.folder-tab {
  position: absolute;
  top: -13px;
  left: 18px;
  width: 108px;
  height: 16px;
  background: var(--folder-accent);
  border-radius: .5rem .5rem 0 0;
  z-index: 0;
}
.folder-tab::after {
  content: "";
  position: absolute;
  right: -12px;
  bottom: 0;
  width: 14px;
  height: 100%;
  background: var(--folder-accent);
  transform: skewX(24deg);
  transform-origin: bottom left;
  border-radius: 0 .4rem 0 0;
}

.folder-cover {
  position: relative;
  z-index: 1;
  list-style: none;
  cursor: pointer;
  background: var(--surface);
  border: 1px solid var(--line);
  border-top: 3px solid var(--folder-accent);
  border-radius: .2rem .95rem .95rem .95rem;
  box-shadow: 0 6px 16px rgba(68, 4, 18, .06);
  transition: box-shadow .18s ease, transform .18s ease;
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 100%;
}
.folder-cover::-webkit-details-marker { display: none; }
.folder-cover:hover {
  box-shadow: 0 12px 26px rgba(68, 4, 18, .12);
  transform: translateY(-3px);
}
.folder[open] .folder-cover {
  border-radius: .2rem .95rem 0 0;
  box-shadow: 0 8px 18px rgba(68, 4, 18, .1);
  flex: none;
  min-height: 0;
}

.folder-cover-inner {
  padding: 1rem 1.15rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: .75rem;
  flex: 1;
  min-height: 11.5rem;
}
.folder-cover-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: none;
}

.folder-chevron {
  width: 11px;
  height: 11px;
  border-right: 2px solid var(--ink-soft);
  border-bottom: 2px solid var(--ink-soft);
  transform: rotate(45deg);
  transition: transform .2s ease;
}
.folder[open] .folder-chevron {
  transform: rotate(-135deg);
  border-color: var(--folder-accent);
}

.folder-cover-main {
  display: flex;
  align-items: flex-start;
  gap: .7rem;
  flex: 1 1 auto;
  min-height: 0;
}
.folder-icon {
  flex: none;
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: .6rem;
  color: var(--folder-accent);
  background: color-mix(in srgb, var(--folder-accent) 12%, #fff);
}
.folder-heading { min-width: 0; flex: 1; }

.folder-cover-foot {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .4rem;
  border-top: 1px dashed var(--line);
  padding-top: .7rem;
  margin-top: auto;
  flex: none;
}
.folder-chip {
  font-size: .7rem;
  font-weight: 600;
  color: var(--ink-soft);
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: .5rem;
  padding: .2rem .5rem;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.folder-open-label {
  margin-left: auto;
  font-size: .74rem;
  font-weight: 700;
  color: var(--folder-accent);
}
.folder-open-label .lbl-open { display: none; }
.folder[open] .folder-open-label .lbl-closed { display: none; }
.folder[open] .folder-open-label .lbl-open { display: inline; }

.folder-content {
  position: relative;
  z-index: 1;
  background: var(--surface);
  border: 1px solid var(--line);
  border-top: none;
  border-radius: 0 0 .95rem .95rem;
  padding: 1.1rem 1.15rem 1.25rem;
  box-shadow: 0 12px 26px rgba(68, 4, 18, .1);
  animation: folder-reveal .22s ease;
}
@keyframes folder-reveal {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.folder-content-grid {
  display: grid;
  gap: .5rem 1.5rem;
}
@media (min-width: 720px) {
  .folder[open] { grid-column: 1 / -1; }
}

.exp-badge {
  --badge-color: var(--brand);
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-size: .72rem;
  font-weight: 700;
  color: var(--badge-color);
  background: color-mix(in srgb, var(--badge-color) 12%, #fff);
  border-radius: 1rem;
  padding: .25rem .65rem;
}
.exp-badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--badge-color);
}

.exp-title {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0 0 .25rem;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
  min-height: calc(1.05rem * 1.3 * 2);
}
.exp-path {
  font-size: .78rem;
  color: var(--ink-soft);
  margin: 0;
  line-height: 1.4;
}

.exp-section-label {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .03em;
  color: var(--ink-soft);
  border-top: 1px solid var(--line);
  padding-top: .75rem;
  margin: .25rem 0 .5rem;
}

.campos-head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: .5rem;
  margin-bottom: .85rem;
}
.campos-title {
  margin: 0;
  border-top: none;
  padding-top: 0;
}
.campos-count {
  font-size: .74rem;
  font-weight: 700;
  color: var(--brand);
  background: var(--brand-soft);
  border-radius: 1rem;
  padding: .2rem .65rem;
}

.campos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: .65rem;
}
.campo-card {
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: .7rem;
  padding: .7rem .8rem;
  display: flex;
  flex-direction: column;
  gap: .55rem;
  min-width: 0;
  transition: border-color .15s, box-shadow .15s;
}
.campo-card:hover {
  border-color: color-mix(in srgb, var(--brand) 35%, var(--line));
  box-shadow: 0 4px 12px rgba(68, 4, 18, .06);
}
.campo-required {
  border-color: color-mix(in srgb, var(--brand) 28%, var(--line));
  background: linear-gradient(180deg, #fff 0%, var(--brand-soft) 160%);
}
.campo-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: .45rem;
}
.campo-name {
  font-size: .88rem;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.3;
  overflow-wrap: break-word;
}
.campo-req-badge {
  flex: none;
  font-size: .65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .02em;
  color: var(--brand);
  background: #fff;
  border: 1px solid color-mix(in srgb, var(--brand) 30%, #fff);
  border-radius: 1rem;
  padding: .15rem .45rem;
}
.campo-type {
  align-self: flex-start;
  font-size: .72rem;
  font-weight: 700;
  border-radius: .45rem;
  padding: .22rem .55rem;
  letter-spacing: .01em;
}
.tipo-string {
  color: #3D3935;
  background: #ece8e1;
}
.tipo-number {
  color: #7a5600;
  background: var(--gold-soft);
}
.tipo-file {
  color: var(--brand-dark);
  background: var(--brand-soft);
}
.tipo-date {
  color: #4a4640;
  background: #e8e4dc;
}

.empty-fields {
  font-size: .84rem;
  color: var(--ink-soft);
  margin: .25rem 0 0;
}

.exp-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-top: 1.1rem;
}
.btn-apply {
  border: none;
  background: var(--brand);
  color: #fff;
  font-size: .82rem;
  font-weight: 600;
  padding: .5rem 1rem;
  border-radius: .55rem;
  cursor: pointer;
  transition: background .15s;
  font-family: inherit;
}
.btn-apply:hover { background: var(--brand-dark); }
.btn-ghost {
  border: 1px solid var(--line);
  background: #fff;
  color: var(--ink);
  font-size: .82rem;
  font-weight: 600;
  padding: .5rem 1rem;
  border-radius: .55rem;
  cursor: pointer;
  transition: background .15s, border-color .15s;
  font-family: inherit;
}
.btn-ghost:hover:not(:disabled) { background: #f5f7fb; border-color: var(--brand); }
.btn-ghost:disabled { opacity: .45; cursor: not-allowed; }
.btn-danger-ghost {
  color: var(--brand);
  border-color: color-mix(in srgb, var(--brand) 35%, var(--line));
}
.btn-danger-ghost:hover:not(:disabled) {
  background: var(--brand-soft);
  border-color: var(--brand);
}

.empty { text-align: center; padding: 3.5rem 1rem; }
.empty-ico { font-size: 2.5rem; color: var(--stone); margin-bottom: .5rem; }
.empty-title { font-weight: 600; margin: 0 0 .25rem; }
.empty-sub { color: var(--ink-soft); font-size: .85rem; margin: 0; }

.pager {
  margin-top: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
}
.pager-info { font-size: .82rem; color: var(--ink-soft); }

.field-card {
  border: 1px solid var(--line);
  border-radius: .7rem;
  padding: .9rem 1rem;
  margin-bottom: .85rem;
  background: var(--bg);
}
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .9rem 1rem;
}
.field-wide { grid-column: 1 / -1; }
.field-label {
  display: block;
  font-size: .78rem;
  font-weight: 600;
  color: var(--ink-soft);
  margin-bottom: .3rem;
}
.field-check-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
}
.check-line {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  font-size: .85rem;
  font-weight: 600;
  color: var(--ink);
  cursor: pointer;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  z-index: 1040;
  background: rgba(15, 22, 40, .5);
  backdrop-filter: blur(2px);
}
.exp-modal {
  z-index: 1055;
  background: transparent;
}
.exp-modal .modal-content {
  border: 1px solid var(--line);
  border-radius: .95rem;
  box-shadow: 0 12px 32px rgba(20, 10, 15, .18);
  color: var(--ink);
}
.exp-modal .modal-header {
  border-bottom: 1px solid var(--line);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.exp-modal .modal-title {
  font-weight: 700;
  color: var(--ink);
  margin: 0;
}
.btn-close-x {
  border: none;
  background: none;
  font-size: 1.5rem;
  line-height: 1;
  color: var(--ink-soft);
  cursor: pointer;
}
.exp-modal .modal-body {
  padding: 1.25rem;
}
.exp-modal .modal-footer {
  border-top: 1px solid var(--line);
  padding: .9rem 1.25rem;
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: .5rem;
}
.exp-modal .form-control,
.exp-modal .form-select {
  border: 1px solid var(--line);
  border-radius: .55rem;
  font-size: .88rem;
  padding: .5rem .7rem;
  font-family: inherit;
  width: 100%;
}
.exp-modal .form-control:focus,
.exp-modal .form-select:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--brand) 18%, transparent);
}

@media (max-width: 640px) {
  .page-head { align-items: stretch; }
  .search-box { min-width: 0; }
  .folders-grid { grid-template-columns: 1fr; }
  .field-grid { grid-template-columns: 1fr; }
  .field-wide { grid-column: auto; }
  .campos-grid { grid-template-columns: 1fr; }
}
</style>
