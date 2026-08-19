<template>
  <div class="container mt-4">
    <h1 class="text-center mb-4">Documentos Pendientes</h1>

    <!-- Buscador -->
    <div class="mb-4 d-flex justify-content-center">
      <input
        v-model="searchTerm"
        type="text"
        placeholder="Buscar documentos..."
        class="form-control w-50"
        @input="currentPage = 1"
      />
    </div>

    <!-- SELECT PARA FILTRAR POR COLECCIÓN -->
    <div class="mb-4 d-flex justify-content-center">
      <select class="form-control w-50" v-model="selectedPlantilla" @change="currentPage = 1">
        <option value="">Mostrar todas las colecciones</option>
        <option v-for="col in plantillas" :key="col" :value="col">
          {{ cleanCollectionName(col) }}
        </option>
      </select>
    </div>

    <!-- Mensaje cuando no hay documentos -->
    <p v-if="documentosFiltrados.length === 0" class="text-center">No hay documentos pendientes.</p>

    <!-- Lista de documentos filtrados y paginados -->
    <div v-for="(doc, index) in documentosPaginados" :key="index" class="documento mb-3 p-3 border rounded shadow-sm">
      <h5 class="text-primary">Fondo al que pertenece: {{ cleanCollectionName(doc._coleccion) }}</h5>

      <div v-for="field in Object.keys(doc).filter(field => !isHiddenField(field))" :key="field" class="mb-2">
        <p>
          <strong>{{ formatFieldName(field) }}:</strong>
          <span v-if="field === 'Recurso Digital' && Array.isArray(doc[field])">
            <a
              v-for="(image, imgIndex) in doc[field]"
              :key="imgIndex"
              :href="getImageUrl(image)"
              :data-fancybox="'gallery-' + index"
            >
              <i class="fas fa-external-link-alt"></i>
            </a>
          </span>
          <span v-else>{{ doc[field] }}</span>
        </p>
      </div>

      <div class="d-flex justify-content-start flex-wrap gap-2">
        <button @click="editarDocumento(doc)" class="btn btn-primary">
          Editar
        </button>
        <button @click="aprobarDocumento(doc._coleccion, getDocumentId(doc))" class="btn btn-success">
          Aprobar
        </button>
        <button @click="eliminarDocumento(doc._coleccion, getDocumentId(doc))" class="btn btn-danger">
          Denegar
        </button>
      </div>
    </div>

    <!-- Paginador -->
    <div v-if="totalPages > 1" class="d-flex justify-content-center mt-4">
      <nav>
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="currentPage--" :disabled="currentPage === 1">Anterior</button>
          </li>
          <li
            class="page-item"
            v-for="page in totalPages"
            :key="page"
            :class="{ active: currentPage === page }"
          >
            <button class="page-link" @click="currentPage = page">{{ page }}</button>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <button class="page-link" @click="currentPage++" :disabled="currentPage === totalPages">Siguiente</button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Modal de edición -->
    <div class="modal" tabindex="-1" v-if="isModalOpen" style="display: block;">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar documento pendiente</h5>
            <button type="button" class="btn-close" @click="closeModal" :disabled="guardando"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted">
              Fondo: <strong>{{ cleanCollectionName(documentoEdit._coleccion) }}</strong>
            </p>
            <form @submit.prevent="guardarDocumento()">
              <div v-for="campo in camposEditables" :key="campo" class="mb-3">
                <label :for="'edit-' + campo" class="form-label">{{ formatFieldName(campo) }}</label>
                <input
                  v-if="esCampoFecha(campo)"
                  type="date"
                  class="form-control"
                  v-model="documentoEdit[campo]"
                  :id="'edit-' + campo"
                >
                <textarea
                  v-else
                  class="form-control"
                  v-model="documentoEdit[campo]"
                  :id="'edit-' + campo"
                  rows="2"
                ></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">Recurso Digital:</label>
                <input
                  type="file"
                  class="form-control"
                  @change="handleFileUpload"
                  multiple
                  accept="image/*,video/*,audio/*,.pdf"
                >
              </div>

              <div v-if="uploadedFiles.length" class="mb-3">
                <h6>Archivos nuevos por agregar:</h6>
                <ul class="mb-0">
                  <li v-for="(file, index) in uploadedFiles" :key="'new-' + index">
                    {{ file.name }}
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1" @click="removeUploadedFile(index)">
                      Quitar
                    </button>
                  </li>
                </ul>
              </div>

              <div v-if="documentoEdit['Recurso Digital'] && documentoEdit['Recurso Digital'].length" class="mb-3">
                <h6>Archivos adjuntos:</h6>
                <div class="d-flex flex-wrap gap-2">
                  <div
                    class="file-preview"
                    v-for="(file, index) in documentoEdit['Recurso Digital']"
                    :key="index"
                    style="position: relative;"
                  >
                    <template v-if="isImage(file)">
                      <img :src="getFileUrl(file)" alt="Imagen" class="img-thumbnail" style="width: 100px;">
                    </template>
                    <template v-else-if="isVideo(file)">
                      <video controls style="width: 100px;">
                        <source :src="getFileUrl(file)" type="video/mp4">
                      </video>
                    </template>
                    <template v-else-if="isAudio(file)">
                      <audio controls style="width: 140px;">
                        <source :src="getFileUrl(file)" type="audio/mp3">
                      </audio>
                    </template>
                    <template v-else-if="isPDF(file)">
                      <a :href="getFileUrl(file)" target="_blank" class="btn btn-sm btn-secondary">Ver PDF</a>
                    </template>
                    <template v-else>
                      <a :href="getFileUrl(file)" target="_blank" class="btn btn-sm btn-outline-secondary">Ver archivo</a>
                    </template>
                    <button type="button" class="btn-remove" @click="removeFile(index)">✖</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" @click="closeModal" :disabled="guardando">
              Cancelar
            </button>
            <button type="button" class="btn btn-primary" @click="guardarDocumento()" :disabled="guardando">
              {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
            </button>
            <button type="button" class="btn btn-success" @click="guardarYAprobar" :disabled="guardando">
              Guardar y aprobar
            </button>
            <button type="button" class="btn btn-danger" @click="denegarDesdeModal" :disabled="guardando">
              Denegar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { Fancybox } from "@fancyapps/ui";
import '@fancyapps/ui/dist/fancybox/fancybox.css';
import Swal from 'sweetalert2';

export default {
  data() {
    return {
      pendientes: [],
      searchTerm: "",
      selectedPlantilla: "",
      plantillas: [],
      currentPage: 1,
      itemsPerPage: 3,
      isModalOpen: false,
      documentoEdit: {},
      uploadedFiles: [],
      guardando: false,
    };
  },

  mounted() {
    this.obtenerPendientes();
  },

  computed: {
    documentosFiltrados() {
      const docsConContenido = this.pendientes.filter(p => p.documents.length > 0);

      let todosDocumentos = docsConContenido.flatMap(p =>
        p.documents.map(doc => ({
          ...doc,
          _coleccion: p.collection,
        }))
      );

      // FILTRO POR COLECCIÓN
      if (this.selectedPlantilla) {
        todosDocumentos = todosDocumentos.filter(
          d => d._coleccion === this.selectedPlantilla
        );
      }

      // FILTRO POR BÚSQUEDA
      if (!this.searchTerm) return todosDocumentos;

      const searchLower = this.searchTerm.toLowerCase();

      return todosDocumentos.filter(doc =>
        Object.values(doc).some(val => {
          if (typeof val === 'string') {
            return val.toLowerCase().includes(searchLower);
          }
          if (Array.isArray(val)) {
            return val.join(' ').toLowerCase().includes(searchLower);
          }
          return false;
        })
      );
    },

    documentosPaginados() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.documentosFiltrados.slice(start, end);
    },

    totalPages() {
      return Math.ceil(this.documentosFiltrados.length / this.itemsPerPage);
    },

    camposEditables() {
      return Object.keys(this.documentoEdit).filter(
        field => !this.isHiddenField(field) && field !== 'Recurso Digital'
      );
    },
  },

  methods: {
    getDocumentId(doc) {
      if (!doc || !doc._id) return null;
      return doc._id.$oid || doc._id['$oid'] || doc._id;
    },

    async obtenerPendientes() {
      try {
        const response = await axios.get('/documentos-pendientes');
        this.pendientes = response.data;

        // Crear lista de colecciones
        this.plantillas = [...new Set(this.pendientes.map(p => p.collection))];

        this.$nextTick(() => {
          Fancybox.bind("[data-fancybox]");
        });
      } catch (error) {
        console.error('Error al obtener los documentos pendientes:', error);
      }
    },

    editarDocumento(doc) {
      this.documentoEdit = { ...doc };
      if (Array.isArray(this.documentoEdit['Recurso Digital'])) {
        this.documentoEdit['Recurso Digital'] = [...this.documentoEdit['Recurso Digital']];
      }
      this.uploadedFiles = [];
      this.isModalOpen = true;
    },

    closeModal() {
      this.isModalOpen = false;
      this.documentoEdit = {};
      this.uploadedFiles = [];
      this.guardando = false;
    },

    esCampoFecha(campo) {
      return campo.toLowerCase().includes('fecha');
    },

    handleFileUpload(event) {
      const files = event.target.files;
      if (files.length) {
        this.uploadedFiles.push(...files);
      }
      event.target.value = '';
    },

    removeUploadedFile(index) {
      this.uploadedFiles.splice(index, 1);
    },

    removeFile(index) {
      this.documentoEdit['Recurso Digital'].splice(index, 1);
    },

    isImage(file) {
      return typeof file === 'string' && /\.(jpeg|jpg|png|gif|tiff)$/i.test(file);
    },

    isVideo(file) {
      return typeof file === 'string' && /\.(mp4|mov|avi)$/i.test(file);
    },

    isAudio(file) {
      return typeof file === 'string' && /\.(mp3|wav|ogg)$/i.test(file);
    },

    isPDF(file) {
      return typeof file === 'string' && /\.pdf$/i.test(file);
    },

    getFileUrl(file) {
      const baseUrl = `${window.location.protocol}//${window.location.host}`;
      return `${baseUrl}/storage/${file}`;
    },

    async guardarDocumento(mostrarAlerta = true) {
      const collection = this.documentoEdit._coleccion;
      const docId = this.getDocumentId(this.documentoEdit);

      if (!collection || !docId) {
        await Swal.fire('Error', 'No se pudo identificar el documento.', 'error');
        return false;
      }

      try {
        this.guardando = true;
        const formData = new FormData();

        Object.keys(this.documentoEdit).forEach((key) => {
          if (['Recurso Digital', '_id', '_coleccion', 'created_at', 'updated_at'].includes(key)) return;
          const value = this.documentoEdit[key];
          if (value == null || typeof value === 'object') return;
          formData.append(`document_data[${key}]`, value);
        });

        if (Array.isArray(this.documentoEdit['Recurso Digital'])) {
          this.documentoEdit['Recurso Digital'].forEach((existingFile) => {
            if (typeof existingFile === 'string') {
              formData.append('existing_files[]', existingFile);
            }
          });
        }

        this.uploadedFiles.forEach((file) => {
          formData.append('files[]', file);
        });

        await axios.post(`/documentos/${collection}/${docId}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });

        this.uploadedFiles = [];
        await this.obtenerPendientes();

        const actualizado = this.documentosFiltrados.find(
          d => this.getDocumentId(d) === docId && d._coleccion === collection
        );
        if (actualizado) {
          this.documentoEdit = { ...actualizado };
          if (Array.isArray(this.documentoEdit['Recurso Digital'])) {
            this.documentoEdit['Recurso Digital'] = [...this.documentoEdit['Recurso Digital']];
          }
        }

        if (mostrarAlerta) {
          await Swal.fire('Éxito', 'Documento actualizado correctamente.', 'success');
        }

        return true;
      } catch (error) {
        console.error('Error al actualizar el documento:', error);
        await Swal.fire('Error', 'Hubo un problema al actualizar el documento.', 'error');
        return false;
      } finally {
        this.guardando = false;
      }
    },

    async guardarYAprobar() {
      const collection = this.documentoEdit._coleccion;
      const docId = this.getDocumentId(this.documentoEdit);

      const confirmacion = await Swal.fire({
        title: '¿Guardar y aprobar?',
        text: 'Se guardarán los cambios y el documento pasará a la colección definitiva.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, guardar y aprobar',
        cancelButtonText: 'Cancelar',
      });

      if (!confirmacion.isConfirmed) return;

      const guardado = await this.guardarDocumento(false);
      if (!guardado) return;

      await this.aprobarDocumento(collection, docId, false);
      this.closeModal();
    },

    async denegarDesdeModal() {
      const collection = this.documentoEdit._coleccion;
      const docId = this.getDocumentId(this.documentoEdit);
      const denegado = await this.eliminarDocumento(collection, docId);
      if (denegado) {
        this.closeModal();
      }
    },

    async aprobarDocumento(collection, docId, confirmar = true) {
      try {
        if (confirmar) {
          const result = await Swal.fire({
            title: '¿Aprobar este documento?',
            text: 'El documento se moverá a la colección definitiva.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, aprobar',
            cancelButtonText: 'Cancelar',
          });

          if (!result.isConfirmed) return;
        }

        const response = await axios.post(`/aprobar-documentos/${collection}/${docId}`);
        if (response.data.message) {
          this.obtenerPendientes();
          await Swal.fire('Aprobado', 'Documento aprobado con éxito.', 'success');
        }
      } catch (error) {
        console.error('Error al aprobar el documento:', error);
        await Swal.fire('Error', 'No se pudo aprobar el documento.', 'error');
      }
    },

    async eliminarDocumento(collection, docId) {
      try {
        const result = await Swal.fire({
          title: '¿Denegar este documento?',
          text: 'El documento pendiente se eliminará y no se podrá revertir.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Sí, denegar',
          cancelButtonText: 'Cancelar',
        });

        if (!result.isConfirmed) return false;

        const response = await axios.delete(`/denegar-documentos/${collection}/${docId}`);
        if (response.data.success) {
          this.obtenerPendientes();
          await Swal.fire('Denegado', 'Documento eliminado con éxito.', 'success');
          return true;
        }
        return false;
      } catch (error) {
        console.error('Error al eliminar el documento:', error);
        await Swal.fire('Error', 'No se pudo denegar el documento.', 'error');
        return false;
      }
    },

    formatFieldName(field) {
      return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
    },

    isHiddenField(field) {
      return ['_id', 'created_at', 'updated_at', '_coleccion'].includes(field);
    },

    cleanCollectionName(collection) {
      return collection ? collection.replace(/_pendientes$/, '') : '';
    },

    getImageUrl(image) {
      const baseUrl = `${window.location.protocol}//${window.location.hostname}:${window.location.port}/storage`;
      return `${baseUrl}/${image.split('/').map(encodeURIComponent).join('/')}`;
    },
  },
};
</script>


<style scoped>
.documento {
  background-color: #f9f9f9;
}

.pagination {
  flex-wrap: wrap !important;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1050;
}

.file-preview {
  margin-right: 8px;
  margin-bottom: 8px;
}

.btn-remove {
  position: absolute;
  top: 2px;
  right: 2px;
  background-color: rgba(255, 255, 255, 0.85);
  border: none;
  border-radius: 50%;
  font-size: 14px;
  color: #dc3545;
  padding: 2px 6px;
  cursor: pointer;
}
</style>
