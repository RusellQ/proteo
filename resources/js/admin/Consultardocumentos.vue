<template>
  <div class="documentos-page">
    <header class="page-head">
      <div>
        <h1 class="page-title">Consultar documentos</h1>
        <p class="page-sub">Documentos aprobados del acervo, filtrados por colección.</p>
      </div>
      <div class="head-controls">
        <div class="picker-wrap">
          <label for="coleccionSelector" class="field-label">Colección</label>
          <select
            id="coleccionSelector"
            class="picker-select"
            v-model="selectedColeccion"
            @change="onColeccionSelected"
          >
            <option :value="null" disabled>Selecciona una colección</option>
            <option v-for="coleccion in coleccionesFiltradas" :key="coleccion" :value="coleccion">
              {{ coleccion }}
            </option>
          </select>
        </div>
        <div v-if="documentos.length" class="search-box">
          <span class="search-ico">⌕</span>
          <form @submit.prevent="buscar">
            <input
              v-model="palabraClave"
              type="search"
              placeholder="Buscar"
              class="search-input"
              aria-label="Buscar"
            >
          </form>
        </div>
      </div>
    </header>

    <template v-if="selectedColeccion && documentos.length">
      <p class="results-count">
        {{ documentos.length }} documento{{ documentos.length === 1 ? '' : 's' }} en
        <strong>{{ selectedColeccion }}</strong>
      </p>

      <div class="folders-grid">
        <details
          v-for="documento in paginatedDocumentos"
          :key="documento._id.$oid"
          class="folder"
        >
          <summary class="folder-cover">
            <span class="folder-tab" aria-hidden="true"></span>
            <div class="folder-cover-inner">
              <div class="folder-cover-top">
                <span class="exp-badge" :class="claseMedio(documento)">
                  <span class="exp-badge-dot"></span>
                  {{ etiquetaMedio(documento) }}
                </span>
                <span class="folder-chevron" aria-hidden="true"></span>
              </div>

              <div class="folder-cover-main">
                <span class="folder-icon" aria-hidden="true">
                  <template v-if="tieneRecurso(documento)">
                    <a
                      href="#"
                      class="media-thumb-link"
                      @click.prevent.stop="openFancybox(documento, documento['Recurso Digital'])"
                    >
                      <img
                        v-if="isImage(documento['Recurso Digital'][0])"
                        src="assets/8344917.png"
                        class="media-thumb"
                        alt="Imagen"
                      >
                      <img
                        v-else-if="isPDF(documento['Recurso Digital'][0])"
                        src="assets/icon-pdf.png"
                        class="media-thumb"
                        alt="Pdf"
                      >
                      <img
                        v-else-if="isAudio(documento['Recurso Digital'][0])"
                        src="assets/icon-audio.png"
                        class="media-thumb"
                        alt="Audio"
                      >
                      <img
                        v-else-if="isVideo(documento['Recurso Digital'][0])"
                        src="assets/icon-video.png"
                        class="media-thumb"
                        alt="Video"
                      >
                      <svg
                        v-else
                        viewBox="0 0 24 24"
                        width="26"
                        height="26"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                      </svg>
                    </a>
                  </template>
                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    width="26"
                    height="26"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                  </svg>
                </span>
                <div class="folder-heading">
                  <h3 class="exp-title">{{ tituloDocumento(documento) }}</h3>
                  <p class="exp-path">{{ selectedColeccion }}</p>
                </div>
              </div>

              <div class="folder-cover-foot">
                <span
                  v-for="chip in chipsPortada(documento)"
                  :key="chip.name"
                  class="folder-chip"
                  :title="chip.name + ': ' + chip.value"
                >
                  {{ chip.name }}: {{ chip.value }}
                </span>
                <span class="folder-open-label">
                  <span class="lbl-closed">Abrir documento</span>
                  <span class="lbl-open">Cerrar documento</span>
                </span>
              </div>
            </div>
          </summary>

          <div class="folder-content">
            <div class="campos-head">
              <div class="exp-section-label campos-title">Datos del documento</div>
              <span class="campos-count">
                {{ camposDetalle.length }} campo{{ camposDetalle.length === 1 ? '' : 's' }}
              </span>
            </div>

            <div class="campos-grid">
              <article
                v-for="campo in camposDetalle"
                :key="campo"
                class="campo-card"
              >
                <div class="campo-card-top">
                  <span class="campo-name">{{ formatFieldName(campo) }}</span>
                </div>
                <span class="campo-value">{{ documento[campo] ?? '—' }}</span>
              </article>
            </div>

            <div v-if="tieneRecurso(documento)" class="recurso-block">
              <div class="exp-section-label">Recurso digital</div>
              <button
                type="button"
                class="btn-ghost"
                @click.stop="openFancybox(documento, documento['Recurso Digital'])"
              >
                Ver recurso ({{ documento['Recurso Digital'].length }})
              </button>
            </div>

            <div class="exp-actions">
              <button class="btn-apply" @click.stop="editarDocumento(documento)">
                Editar documento
              </button>
              <button
                class="btn-ghost btn-danger-ghost"
                @click.stop="eliminarDocumento(documento._id.$oid)"
              >
                Eliminar documento
              </button>
            </div>
          </div>
        </details>
      </div>

      <nav v-if="totalPages > 1" aria-label="Paginación" class="pager">
        <button
          class="btn-ghost"
          :disabled="currentPage === 1"
          @click="changePage(currentPage - 1)"
        >
          ‹ Anterior
        </button>
        <button
          v-for="(page, index) in visiblePages"
          :key="page === '...' ? 'ellipsis-' + index : page"
          class="btn-ghost pager-num"
          :class="{ 'pager-on': currentPage === page, 'pager-ellipsis': page === '...' }"
          :disabled="page === '...'"
          @click="page !== '...' && changePage(page)"
        >
          {{ page }}
        </button>
        <button
          class="btn-ghost"
          :disabled="currentPage === totalPages"
          @click="changePage(currentPage + 1)"
        >
          Siguiente ›
        </button>
      </nav>
    </template>

    <div v-else-if="selectedColeccion && !documentos.length" class="empty">
      <div class="empty-ico">▤</div>
      <p class="empty-title">No hay documentos en esta colección</p>
      <p class="empty-sub">Seleccione otra colección o cree documentos desde el módulo correspondiente.</p>
    </div>

    <div v-else class="empty">
      <div class="empty-ico">▤</div>
      <p class="empty-title">Seleccione una colección</p>
      <p class="empty-sub">Elija una colección arriba para consultar sus documentos.</p>
    </div>

    <!-- Modal de Edición -->
    <div class="modal exp-modal" tabindex="-1" v-if="isModalOpen" style="display: block;">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar documento</h5>
            <button type="button" class="btn-close-x" aria-label="Cerrar" @click="closeModal">×</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitEdit">
              <div class="field-grid">
                <div
                  v-for="campo in camposDetalle"
                  :key="campo"
                  class="field"
                  :class="{ 'field-wide': campo !== 'Fecha' }"
                >
                  <label :for="campo" class="field-label">{{ formatFieldName(campo) }}</label>
                  <input
                    v-if="campo === 'Fecha'"
                    type="date"
                    class="form-control"
                    v-model="documentoEdit[campo]"
                    :id="campo"
                  >
                  <input
                    v-else
                    type="text"
                    class="form-control"
                    v-model="documentoEdit[campo]"
                    :id="campo"
                  >
                </div>
              </div>

              <div class="recurso-edit">
                <label class="field-label">Recurso digital</label>
                <input
                  type="file"
                  class="form-control"
                  @change="handleFileUpload"
                  multiple
                  accept="image/*,video/*,audio/*,.pdf"
                >

                <div
                  v-if="documentoEdit['Recurso Digital'] && documentoEdit['Recurso Digital'].length"
                  class="adjuntos"
                >
                  <h6 class="adjuntos-title">Archivos adjuntos</h6>
                  <div class="adjuntos-grid">
                    <div
                      class="file-preview"
                      v-for="(file, index) in documentoEdit['Recurso Digital']"
                      :key="index"
                    >
                      <template v-if="isImage(file)">
                        <img :src="getFileUrl(file)" alt="Imagen" class="preview-media">
                      </template>
                      <template v-else-if="isVideo(file)">
                        <video controls class="preview-media">
                          <source :src="getFileUrl(file)" type="video/mp4">
                          Tu navegador no soporta videos.
                        </video>
                      </template>
                      <template v-else-if="isAudio(file)">
                        <audio controls class="preview-audio">
                          <source :src="getFileUrl(file)" type="audio/mp3">
                          Tu navegador no soporta audio.
                        </audio>
                      </template>
                      <template v-else-if="isPDF(file)">
                        <a :href="getFileUrl(file)" target="_blank" class="btn-ghost btn-sm-preview">Ver PDF</a>
                      </template>
                      <button type="button" class="btn-remove" @click="removeFile(index)" aria-label="Quitar archivo">
                        ×
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button type="button" class="btn-ghost" @click="closeModal">Cancelar</button>
                <button type="submit" class="btn-apply">Guardar cambios</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div v-if="isModalOpen" class="modal-backdrop-custom" @click="closeModal"></div>
  </div>
</template>

<script>
import { onMounted, ref, computed } from 'vue';
import { Fancybox } from "@fancyapps/ui";
import '@fancyapps/ui/dist/fancybox/fancybox.css';
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
  setup() {
    const colecciones = ref([]);
    const excludedCollections = ref([
      'failed_jobs', 'navbar_colors', 'password_reset_tokens', 'migrations',
      'personal_access_tokens', 'plantillas_predeterminadas', 'noticias_collection',
      'users', 'comentarios', 'carrousel_images'
    ]);
    const coleccionesFiltradas = computed(() => {
      return colecciones.value.filter(coleccion => !excludedCollections.value.includes(coleccion));
    });
    const selectedColeccion = ref(null);
    const documentos = ref([]);
    const camposDocumento = ref([]);
    const plantillaName = ref(null);
    const palabraClave = ref('');
    const currentPage = ref(1);
    const itemsPerPage = ref(6);
    const isModalOpen = ref(false);
    const uploadedFiles = ref([]); // Archivos seleccionados
    const documentoEdit = ref({}); // Datos del documento a editar
    const nuevoArchivo = ref(null); // Archivo nuevo
    const camposPlantilla = ref([]); // Inicialización correcta
const getFileUrl = (file) => {
  const baseUrl = `${window.location.protocol}//${window.location.host}`;
  return `${baseUrl}/storage/${file}`;
};

    const isImage = (file) => file.match(/\.(jpeg|jpg|png|gif)$/i);
    const isVideo = (file) => file.match(/\.(mp4|mov|avi)$/i);
    const isAudio = (file) => file.match(/\.(mp3|wav|ogg)$/i);
    const isPDF = (file) => file.match(/\.pdf$/i);

    
    // Obtener colecciones
    const getColecciones = async () => {
      try {
        const response = await axios.get('/documentos/plantillas');
        colecciones.value = response.data.filter((coleccion) => !coleccion.endsWith('_pendientes'));
      } catch (error) {
        console.error('Error obteniendo colecciones', error);
      }
    };

    
    const onColeccionSelected = async () => {
      if (selectedColeccion.value) {
        plantillaName.value = selectedColeccion.value;
        console.log('Nombre de la plantilla seleccionada:', plantillaName.value);

        try {
          const response = await axios.get(`/documentos/${selectedColeccion.value}`);
          documentos.value = response.data;
          currentPage.value = 1;

          if (documentos.value.length > 0) {
            const documentoConMasCampos = documentos.value.reduce((prev, current) =>
              (Object.keys(current).length > Object.keys(prev).length) ? current : prev
            );
            camposDocumento.value = Object.keys(documentoConMasCampos).filter(campo => campo !== '_id');
          } else {
            camposDocumento.value = [];
          }

        } catch (error) {
          console.error('Error obteniendo documentos o plantilla', error);
          documentos.value = [];
          camposDocumento.value = [];
        }
      } else {
        plantillaName.value = null;
        documentos.value = [];
        camposDocumento.value = [];
      }
    };




    const formatFieldName = (fieldName) => {
      const fieldMap = {
        created_at: 'Fecha de creación',
        updated_at: 'Última actualización'
      };
      return fieldMap[fieldName] || fieldName.replace(/_/g, ' ').replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase());
    };

    const editarDocumento = (documento) => {
      console.log("Editando documento:", documento);
      documentoEdit.value = { ...documento }; // Clonar el documento
      uploadedFiles.value = [];
      isModalOpen.value = true;
    };

    const closeModal = () => {
      isModalOpen.value = false;
      uploadedFiles.value = [];
    };

    // Eliminar documento
    const eliminarDocumento = async (documentoId) => {
      try {
        const result = await Swal.fire({
          title: '¿Estás seguro?',
          text: '¿Quieres eliminar este documento?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Sí, eliminarlo'
        });

        if (result.isConfirmed) {
          await axios.delete(`/documentos/${plantillaName.value}/${documentoId}`);
          documentos.value = documentos.value.filter(doc => doc._id.$oid !== documentoId);
          if (currentPage.value > totalPages.value) {
            currentPage.value = Math.max(totalPages.value, 1);
          }
          Swal.fire('Eliminado', 'El documento se ha eliminado exitosamente.', 'success');
        }
      } catch (error) {
        console.error('Error al eliminar el documento', error);
        Swal.fire('Error', 'Hubo un problema al eliminar el documento. Por favor, inténtalo de nuevo.', 'error');
      }
    };

  
const openFancybox = (documento) => {
  const baseUrl = `${window.location.protocol}//${window.location.host}`;

  const items = documento['Recurso Digital'].map((image) => ({
    src: `${baseUrl}/storage/${image}`
  }));

  Fancybox.show(items);
};



    const totalPages = computed(() => Math.ceil(documentos.value.length / itemsPerPage.value));

    const paginatedDocumentos = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value;
      return documentos.value.slice(start, start + itemsPerPage.value);
    });

    const visiblePages = computed(() => {
      const total = totalPages.value;
      const current = currentPage.value;
      const delta = 2;

      if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }

      const pages = [];
      const rangeStart = Math.max(2, current - delta);
      const rangeEnd = Math.min(total - 1, current + delta);

      pages.push(1);

      if (rangeStart > 2) {
        pages.push('...');
      }

      for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
      }

      if (rangeEnd < total - 1) {
        pages.push('...');
      }

      pages.push(total);

      return pages;
    });

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
      }
    };

    const removeImage = (index) => {
      documentoEdit.value['Recurso Digital'].splice(index, 1);
    };

    const handleFileUpload = (event) => {
      const files = event.target.files;
      if (files.length) {
        for (const file of files) {
          uploadedFiles.value.push(file);
        }
      }
    };


    const removeFile = (index) => {
      documentoEdit.value['Recurso Digital'].splice(index, 1);
    };

    // Mover la función submitEdit a los métodos


    const submitEdit = async () => {
      try {
        const formData = new FormData();

        Object.keys(documentoEdit.value).forEach((key) => {
          if (['_id', 'Recurso Digital', 'created_at', 'updated_at'].includes(key)) return;
          const value = documentoEdit.value[key];
          if (value == null || typeof value === 'object') return;
          formData.append(`document_data[${key}]`, value);
        });

        // 🔹 Agregar archivos ya existentes en 'Recurso Digital'
        if (documentoEdit.value['Recurso Digital']) {
          documentoEdit.value['Recurso Digital'].forEach((existingFile) => {
            formData.append('existing_files[]', existingFile);
          });
        }

        // 🔹 Agregar archivos nuevos si existen
        uploadedFiles.value.forEach((file) => {
          formData.append('files[]', file); // Usa 'files[]' para que coincida con el backend
        });


        await axios.post(`/documentos/${plantillaName.value}/${documentoEdit.value._id.$oid}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        Swal.fire('Éxito', 'Documento actualizado correctamente', 'success');
        uploadedFiles.value = [];
        closeModal();
        onColeccionSelected(); // Recargar la lista de documentos
      } catch (error) {
        console.error('Error actualizando documento', error);
        Swal.fire('Error', 'Hubo un problema al actualizar el documento.', 'error');
      }
    };

    // Helpers solo de presentación (no alteran APIs ni flujo)
    const camposDetalle = computed(() =>
      camposDocumento.value.filter((c) => c !== 'Recurso Digital')
    );

    const tieneRecurso = (documento) =>
      Array.isArray(documento?.['Recurso Digital']) && documento['Recurso Digital'].length > 0;

    const tituloDocumento = (documento) => {
      const preferidos = [
        'Título', 'Título de expediente', 'Título del expediente',
        'Expediente', 'Nombre', 'Nomenclatura', 'Descripción',
      ];
      for (const p of preferidos) {
        if (documento?.[p] != null && documento[p] !== '') return documento[p];
      }
      const first = camposDetalle.value.find(
        (c) => documento?.[c] != null && documento[c] !== '' && typeof documento[c] !== 'object'
      );
      return first ? documento[first] : 'Documento';
    };

    const chipsPortada = (documento) => {
      const preferidos = ['Fondo', 'Sección', 'Serie', 'Año', 'Número de expediente', 'Municipio_o_Estado'];
      const chips = [];
      for (const name of preferidos) {
        if (documento?.[name] != null && documento[name] !== '') {
          chips.push({ name, value: documento[name] });
        }
        if (chips.length >= 2) break;
      }
      if (!chips.length) {
        for (const name of camposDetalle.value) {
          if (['created_at', 'updated_at'].includes(name)) continue;
          if (documento?.[name] != null && documento[name] !== '' && typeof documento[name] !== 'object') {
            chips.push({ name, value: documento[name] });
          }
          if (chips.length >= 2) break;
        }
      }
      return chips;
    };

    const etiquetaMedio = (documento) => {
      if (!tieneRecurso(documento)) return 'Sin recurso';
      const f = documento['Recurso Digital'][0];
      if (isImage(f)) return 'Imagen';
      if (isPDF(f)) return 'PDF';
      if (isAudio(f)) return 'Audio';
      if (isVideo(f)) return 'Video';
      return 'Archivo';
    };

    const claseMedio = (documento) => {
      if (!tieneRecurso(documento)) return 'badge-none';
      const f = documento['Recurso Digital'][0];
      if (isImage(f)) return 'badge-image';
      if (isPDF(f)) return 'badge-pdf';
      if (isAudio(f)) return 'badge-audio';
      if (isVideo(f)) return 'badge-video';
      return 'badge-file';
    };

    

    onMounted(() => {
  getColecciones();
  Fancybox.bind("[data-fancybox]", {
        // Opciones de FancyBox aquí si es necesario
      });
  
  
});
    return {
      colecciones,
      coleccionesFiltradas,
      selectedColeccion,
      documentos,
      camposDocumento,
      plantillaName,
      excludedCollections,
      palabraClave,
      currentPage,
      itemsPerPage,
      isModalOpen,
      documentoEdit,
      getColecciones,
      onColeccionSelected,
      formatFieldName,
      editarDocumento,
      closeModal,
      submitEdit,
      eliminarDocumento,
      openFancybox,
      totalPages,
      visiblePages,
      paginatedDocumentos,
      changePage,
      removeImage,
      handleFileUpload,
      nuevoArchivo, getFileUrl, isImage, isVideo, isAudio, isPDF, removeFile,
      camposDetalle,
      tieneRecurso,
      tituloDocumento,
      chipsPortada,
      etiquetaMedio,
      claseMedio,
    };
  }
};
</script>

<style scoped>
.documentos-page {
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

.head-controls {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem;
  align-items: flex-end;
}
.picker-wrap { min-width: 220px; }
.field-label {
  display: block;
  font-size: .78rem;
  font-weight: 600;
  color: var(--ink-soft);
  margin-bottom: .3rem;
}
.picker-select {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: .6rem;
  background: var(--surface);
  padding: .55rem .75rem;
  font-size: .88rem;
  color: var(--ink);
  font-family: inherit;
}
.picker-select:focus {
  outline: none;
  border-color: var(--brand);
}

.search-box {
  position: relative;
  min-width: 220px;
}
.search-ico {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ink-soft);
  z-index: 1;
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
  overflow: hidden;
}
.media-thumb-link {
  display: grid;
  place-items: center;
  width: 100%;
  height: 100%;
}
.media-thumb {
  width: 28px;
  height: 28px;
  object-fit: contain;
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
  max-width: 46%;
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
@media (min-width: 720px) {
  .folder[open] { grid-column: 1 / -1; }
}

.exp-badge {
  --badge-color: var(--brand);
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
.badge-pdf { --badge-color: #AB0A3D; }
.badge-image { --badge-color: #B68400; }
.badge-audio { --badge-color: #6B675F; }
.badge-video { --badge-color: #440412; }
.badge-file { --badge-color: #3D3935; }
.badge-none { --badge-color: #B0ABA1; }

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
  margin: 0 0 .5rem;
}

.campos-head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: .5rem;
  margin-bottom: .85rem;
  border-top: 1px solid var(--line);
  padding-top: .75rem;
}
.campos-title { margin: 0; }
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
  gap: .35rem;
  min-width: 0;
}
.campo-name {
  font-size: .72rem;
  font-weight: 700;
  color: var(--ink-soft);
  text-transform: uppercase;
  letter-spacing: .02em;
}
.campo-value {
  font-size: .88rem;
  font-weight: 600;
  color: var(--ink);
  overflow-wrap: break-word;
}

.recurso-block {
  margin-top: 1rem;
  padding-top: .75rem;
  border-top: 1px dashed var(--line);
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
  flex-wrap: wrap;
  gap: .5rem;
}
.pager-num { min-width: 2.25rem; padding: .45rem .55rem; }
.pager-on {
  background: var(--brand) !important;
  color: #fff !important;
  border-color: var(--brand) !important;
}
.pager-ellipsis {
  border: none !important;
  background: transparent !important;
  pointer-events: none;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  z-index: 1040;
  background: rgba(15, 22, 40, .5);
  backdrop-filter: blur(2px);
}
.exp-modal {
  position: fixed;
  inset: 0;
  z-index: 1055;
  background: transparent;
  display: flex !important;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.exp-modal .modal-dialog {
  margin: 0;
  width: min(720px, 100%);
  max-height: min(88vh, 760px);
}
.exp-modal .modal-content {
  border: 1px solid var(--line);
  border-radius: .95rem;
  box-shadow: 0 12px 32px rgba(20, 10, 15, .18);
  color: var(--ink);
  max-height: min(88vh, 760px);
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
.exp-modal .modal-body { padding: 1.25rem; }
.exp-modal .modal-footer {
  border-top: 1px solid var(--line);
  padding: .9rem 0 0;
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: .5rem;
}

.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .9rem 1rem;
}
.field-wide { grid-column: 1 / -1; }
.exp-modal .form-control {
  border: 1px solid var(--line);
  border-radius: .55rem;
  font-size: .88rem;
  padding: .5rem .7rem;
  font-family: inherit;
  width: 100%;
}
.exp-modal .form-control:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--brand) 18%, transparent);
}

.recurso-edit {
  margin-top: 1rem;
  padding-top: .9rem;
  border-top: 1px solid var(--line);
}
.adjuntos { margin-top: .85rem; }
.adjuntos-title {
  font-size: .82rem;
  font-weight: 700;
  margin: 0 0 .55rem;
}
.adjuntos-grid {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem;
}
.file-preview {
  position: relative;
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: .65rem;
  padding: .55rem;
  min-width: 110px;
}
.preview-media {
  width: 100px;
  height: auto;
  display: block;
  border-radius: .35rem;
}
.preview-audio { width: 160px; }
.btn-sm-preview {
  display: inline-block;
  padding: .35rem .65rem;
  font-size: .75rem;
}
.btn-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, .9);
  color: var(--brand);
  font-size: 1rem;
  line-height: 1;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
}

@media (max-width: 640px) {
  .page-head { align-items: stretch; }
  .head-controls { width: 100%; }
  .picker-wrap, .search-box { min-width: 0; width: 100%; }
  .folders-grid { grid-template-columns: 1fr; }
  .field-grid { grid-template-columns: 1fr; }
  .field-wide { grid-column: auto; }
  .campos-grid { grid-template-columns: 1fr; }
  .folder-chip { max-width: 100%; }
}
</style>
