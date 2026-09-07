<template>
  <div class="form-container">
    <div class="caratula-background">
      <h1>Crear documento</h1>

      <div class="selector-expediente caratula-form mb-4">
        <h5 class="selector-titulo">Plantilla del acervo</h5>
        <p class="selector-ayuda">
          Seleccione la plantilla con la que se capturará el documento. Los campos se cargarán automáticamente.
        </p>

        <div class="form-group">
          <label for="plantillaSelector">Plantilla</label>
          <select
            id="plantillaSelector"
            class="form-control"
            v-model="selectedPlantilla"
            @change="onPlantillaSelected"
          >
            <option value="" disabled selected>Selecciona una plantilla</option>
            <option
              v-for="plantilla in filteredPlantillas"
              :key="plantilla.nombre"
              :value="plantilla.nombre"
            >
              {{ plantilla.title || plantilla.nombre }}
            </option>
          </select>
        </div>
      </div>

      <div v-if="camposPlantilla.length > 0" class="caratula-form caratula-form--captura">
        <section class="form-block form-block--capture">
          <div class="section-head">
            <div>
              <span class="section-badge">Captura manual</span>
              <h2 class="section-title">Campos de la plantilla</h2>
              <p class="section-desc">
                Complete la información del documento según la plantilla seleccionada.
              </p>
            </div>
          </div>

          <form ref="form" @submit.prevent="onSubmit" enctype="multipart/form-data">
            <div class="field-grid">
              <div
                v-for="campo in camposPlantilla"
                :key="campo.name"
                class="form-group"
                :class="{ 'field-span-2': campo.type === 'file' || campo.type === 'string' }"
              >
                <div v-if="campo.name !== '_id'">
                  <label :for="campo.name">
                    {{ campo.alias || campo.name }}
                    <span v-if="campo.required" class="req-mark">*</span>
                  </label>

                  <input
                    v-if="campo.type === 'file'"
                    type="file"
                    class="form-control"
                    :id="campo.name"
                    :name="campo.name"
                    @change="onFileChange($event, campo.name)"
                    multiple
                  >
                  <input
                    v-else-if="campo.type === 'number'"
                    type="number"
                    class="form-control"
                    :id="campo.name"
                    v-model="documentData[campo.name]"
                    :required="campo.required"
                  >
                  <input
                    v-else-if="campo.type === 'date'"
                    type="date"
                    class="form-control"
                    :id="campo.name"
                    v-model="documentData[campo.name]"
                    :required="campo.required"
                  >
                  <input
                    v-else
                    type="text"
                    class="form-control"
                    :id="campo.name"
                    v-model="documentData[campo.name]"
                    :required="campo.required"
                  >

                  <div v-if="campo.type === 'file' && files[campo.name]" class="file-list">
                    <h5 class="file-list-title">Archivos seleccionados</h5>
                    <div
                      class="file-thumbnail"
                      v-for="(file, index) in files[campo.name]"
                      :key="index"
                    >
                      <img v-if="isImageFile(file)" :src="getThumbnailUrl(file)" alt="Miniatura">
                      <span v-else class="file-name">{{ file.name }}</span>
                      <button
                        type="button"
                        class="btn btn-link delete-button"
                        @click="removeFile(campo.name, index)"
                        aria-label="Quitar archivo"
                      >
                        <i class="fa fa-times-circle" aria-hidden="true"></i>
                      </button>
                    </div>
                  </div>

                  <small v-if="campo.required" class="campo-obligatorio-leyenda">* Obligatorio</small>
                  <small v-else class="campo-opcional-leyenda">Opcional</small>
                </div>
              </div>
            </div>

            <div class="button-group">
              <button type="submit" class="btn-primary">Crear documento</button>
            </div>
          </form>
        </section>
      </div>

      <div v-else-if="selectedPlantilla" class="caratula-form empty-card">
        <p class="empty-fields">Esta plantilla no tiene campos para capturar.</p>
      </div>

      <div v-else class="caratula-form empty-card">
        <p class="empty-fields">Seleccione una plantilla para comenzar la captura del documento.</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Swal from 'sweetalert2';

export default {
    data() {
        return {
            plantillas: [], // Listado de plantillas (modelo)
            selectedPlantilla: null, // Plantilla seleccionada
            camposPlantilla: [], // Campos de la plantilla seleccionada
            documentData: {}, // Datos del formulario
            files: {}, // Archivos por nombre de campo
        };
    },
    computed: {
        filteredPlantillas() {
            // Excluir plantillas innecesarias (si es necesario)
            const excludedPlantillas = ['CarrouselImage', 'Comentario', 'NavbarColor', 'Noticia', 'User', 'PlantillaPredeterminada', 'plantillas_predeterminadas', 'users', 'comentarios', 'carrousel_images', 'visitas'];
            return this.plantillas.filter(plantilla => !excludedPlantillas.includes(plantilla.nombre));
        }
    },
    methods: {
        async fetchPlantillas() {
            try {
                // Aquí se hace una llamada a la API que te devuelve las plantillas y sus estructuras
                const response = await axios.get('/plantillas');
                this.plantillas = response.data;
                console.log(this.plantillas);

            } catch (error) {
                console.error('Error obteniendo plantillas', error);
            }
        },
        async onPlantillaSelected() {
            if (this.selectedPlantilla) {
                try {
                    // Realiza una solicitud al backend para obtener los campos
                    const response = await axios.get(`/plantillas/${this.selectedPlantilla}/fields`);
                    if (response.data) {
                        this.camposPlantilla = response.data;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron obtener los campos de la plantilla seleccionada.',
                        });
                    }
                } catch (error) {
                    console.error('Error al obtener los campos de la plantilla:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al obtener los campos de la plantilla.',
                    });
                }
            }
        },
        onFileChange(event, fieldName) {
            const fileList = event.target.files;
            if (fileList.length > 0) {
                this.files = {
                    ...this.files,
                    [fieldName]: Array.from(fileList)
                };
            }
        },
        isImageFile(file) {
            return file.type.startsWith('image/');
        },
        getThumbnailUrl(file) {
            if (this.isImageFile(file)) {
                return URL.createObjectURL(file);
            }
            return null;
        },
        removeFile(fieldName, index) {
            this.files[fieldName].splice(index, 1);
            if (this.files[fieldName].length === 0) {
                const { [fieldName]: omit, ...rest } = this.files;
                this.files = rest;
            } else {
                this.files = {
                    ...this.files,
                    [fieldName]: this.files[fieldName]
                };
            }
        },
        async onSubmit() {
            // Validación de campos requeridos
            const camposRequeridosVacios = this.camposPlantilla
                .filter(campo => campo.required)
                .some(campo => {
                    if (campo.type === 'file') {
                        return campo.required && (!this.files[campo.name] || this.files[campo.name].length === 0);
                    } else {
                        return campo.required && !this.documentData[campo.name];
                    }
                });

            if (camposRequeridosVacios) {
                Swal.fire({
                    icon: 'error',
                    title: 'Campos Obligatorios',
                    text: 'Por favor, completa todos los campos obligatorios.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Mostrar datos y archivos antes de enviar
            console.log('Datos del documento antes de crear:', this.documentData);
            console.log('Archivos adjuntos:', this.files);

            const formData = new FormData();

            // Añadir los datos del documento al FormData
            for (const campo of this.camposPlantilla) {
                if (campo.type !== 'file') {
                    formData.append(`document_data[${campo.name}]`, this.documentData[campo.name] || '');
                }
            }

            // Agregar archivos al FormData
            for (const campo of this.camposPlantilla) {
                if (campo.type === 'file') {
                    if (this.files[campo.name] && this.files[campo.name].length > 0) {
                        this.files[campo.name].forEach(file => {
                            formData.append(`files[]`, file);
                        });
                    }
                }
            }

            try {
                const response = await axios.post(`/documentos/${this.selectedPlantilla}`, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                Swal.fire({
                    title: 'Éxito',
                    text: 'El documento se ha creado exitosamente.',
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                });

                // Limpiar el formulario y conservar la plantilla seleccionada
                this.documentData = {};
                this.files = {};
                this.$refs.form.reset(); // Resetea los campos de archivo
            } catch (error) {
                console.error('Error al crear el documento', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un problema al crear el documento. Por favor, inténtalo de nuevo.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        }
    },
    mounted() {
        this.fetchPlantillas();
    }
};

</script>

<style scoped>
.form-container {
  --brand: #AB0A3D;
  --brand-dark: #440412;
  --brand-soft: #f7e5eb;
  --gold: #B68400;
  --gold-soft: #faf1dd;
  --bg: #f7f5f2;
  --surface: #ffffff;
  --surface-soft: #faf6f4;
  --line: #e4e0d8;
  --ink: #3D3935;
  --ink-soft: #78736a;
  --ring: rgba(171, 10, 61, .18);
  --stone: #B0ABA1;

  position: relative;
  width: 100%;
  min-height: 100vh;
  background: transparent;
  font-family: 'Montserrat', sans-serif;
  color: var(--ink);
}

.caratula-background {
  max-width: 1040px;
  margin: 0 auto;
  padding: 48px 24px 24px;
  position: relative;
}

h1 {
  color: var(--brand-dark);
  text-align: center;
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -.02em;
  margin-bottom: 8px;
  text-wrap: balance;
}
h1::after {
  content: "";
  display: block;
  width: 72px;
  height: 4px;
  margin: 14px auto 32px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--brand), var(--gold));
}

.caratula-form {
  background: var(--surface);
  padding: 32px;
  border: 1px solid var(--line);
  border-radius: 16px;
  max-width: 100%;
  margin: 0 auto 24px;
  box-shadow: 0 12px 30px rgba(68, 4, 18, .07);
}

.caratula-form--captura {
  border-top: 4px solid var(--brand);
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.selector-expediente {
  position: relative;
  border: 1px solid var(--line);
  border-top: 4px solid var(--brand);
  background: var(--surface);
  overflow: hidden;
}

.selector-titulo {
  font-weight: 700;
  font-size: 1.15rem;
  color: var(--brand-dark);
  margin-bottom: .5rem;
}

.selector-ayuda {
  font-size: .9rem;
  color: var(--ink-soft);
  margin-bottom: 1rem;
  line-height: 1.55;
}

.form-block {
  padding: 0;
  border: none;
  background: transparent;
}

.section-head {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.1rem;
}
.section-badge {
  display: inline-block;
  font-size: .72rem;
  font-weight: 700;
  color: #7a5600;
  background: var(--gold-soft);
  border-radius: 1rem;
  padding: .2rem .65rem;
  margin-bottom: .45rem;
}
.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--brand-dark);
  margin: 0 0 .35rem;
}
.section-desc {
  font-size: .86rem;
  color: var(--ink-soft);
  margin: 0;
  line-height: 1.5;
  max-width: 36rem;
}

label {
  display: block;
  font-size: .82rem;
  font-weight: 600;
  color: var(--brand-dark);
  margin-bottom: 2px;
  letter-spacing: .01em;
}
.req-mark {
  color: var(--brand);
  margin-left: .15rem;
}

.form-group {
  width: 100%;
  margin-bottom: 0;
}

.field-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem 1.1rem;
}

@media (min-width: 720px) {
  .field-grid {
    grid-template-columns: 1fr 1fr;
  }
  .field-span-2 {
    grid-column: 1 / -1;
  }
}

.form-control {
  width: 100%;
  height: auto;
  min-height: 44px;
  padding: 10px 13px;
  margin-top: 6px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--surface);
  color: var(--ink);
  font-size: .95rem;
  line-height: 1.4;
  box-sizing: border-box;
  font-family: inherit;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
}
.form-control::placeholder { color: #b7a7aa; }
.form-control:hover { border-color: #d9c4c8; }
.form-control:focus {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 4px var(--ring);
  background: var(--surface);
}
select.form-control {
  cursor: pointer;
  display: block;
  padding: 10px 36px 10px 13px;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: var(--surface);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23440412' d='M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 12px 8px;
}

.campo-obligatorio-leyenda {
  display: inline-block;
  margin-top: .35rem;
  font-size: .74rem;
  font-weight: 600;
  color: var(--brand);
}
.campo-opcional-leyenda {
  display: inline-block;
  margin-top: .35rem;
  font-size: .74rem;
  font-weight: 600;
  color: var(--ink-soft);
}

.file-list {
  margin-top: .75rem;
  padding: .75rem;
  background: var(--bg);
  border: 1px solid var(--line);
  border-radius: .7rem;
}
.file-list-title {
  font-size: .82rem;
  font-weight: 700;
  color: var(--brand-dark);
  margin: 0 0 .55rem;
}
.file-thumbnail {
  display: flex;
  align-items: center;
  gap: .55rem;
  padding: .4rem 0;
  border-top: 1px dashed var(--line);
}
.file-thumbnail:first-of-type { border-top: none; }
.file-thumbnail img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: .4rem;
  border: 1px solid var(--line);
}
.file-name {
  flex: 1;
  font-size: .84rem;
  font-weight: 600;
  color: var(--ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.delete-button {
  color: var(--brand);
  cursor: pointer;
  border: none;
  background: transparent;
  padding: .2rem;
  font-size: 1.1rem;
}
.delete-button:hover { color: var(--brand-dark); }

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: .65rem;
  margin-top: 1.25rem;
}

.btn-primary {
  margin-top: 0;
  padding: .75rem 1.35rem;
  border-radius: 10px;
  border: none;
  font-weight: 700;
  font-size: .95rem;
  cursor: pointer;
  font-family: inherit;
  background: linear-gradient(135deg, var(--brand), var(--brand-dark));
  color: #fff;
  box-shadow: 0 8px 18px rgba(171, 10, 61, .25);
  transition: transform .12s ease, box-shadow .15s ease, filter .15s ease;
}
.btn-primary:hover {
  transform: translateY(-1px);
  filter: brightness(1.05);
  box-shadow: 0 12px 24px rgba(171, 10, 61, .32);
}

.empty-card {
  border-top: 4px solid var(--stone);
}
.empty-fields {
  font-size: .9rem;
  color: var(--ink-soft);
  background: var(--bg);
  border: 1px dashed var(--line);
  border-radius: .7rem;
  padding: 1rem;
  margin: 0;
  text-align: center;
}

.mb-4 { margin-bottom: 1.5rem; }

@media (max-width: 640px) {
  .caratula-background { padding: 32px 14px 16px; }
  .caratula-form { padding: 22px; }
  h1 { font-size: 1.6rem; }
}
</style>
