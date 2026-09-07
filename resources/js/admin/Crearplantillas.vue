<template>
  <div class="form-container">
    <div class="caratula-background">
      <h1>Crear plantilla</h1>

      <div class="selector-expediente caratula-form mb-4">
        <h5 class="selector-titulo">Datos de la plantilla</h5>
        <p class="selector-ayuda">
          Defina el nombre de la colección y los campos que tendrán los documentos de este acervo.
        </p>

        <div class="form-group">
          <label for="plantillaName">Nombre de la plantilla</label>
          <input
            id="plantillaName"
            v-model="plantillaName"
            class="form-control"
            placeholder="Ej. Acervo Fotográfico"
          >
        </div>
      </div>

      <div class="caratula-form caratula-form--captura">
        <section class="form-block form-block--capture">
          <div class="section-head">
            <div>
              <span class="section-badge">Captura manual</span>
              <h2 class="section-title">Campos de la plantilla</h2>
              <p class="section-desc">
                Agregue los campos que formarán la estructura del documento. Puede marcarlos como obligatorios.
              </p>
            </div>
            <button type="button" class="btn-ghost" @click="agregarCampo">
              + Agregar campo
            </button>
          </div>

          <div v-if="!camposPlantilla.length" class="empty-fields">
            No hay campos todavía. Agregue al menos uno para continuar.
          </div>

          <div
            v-for="(campo, index) in camposPlantilla"
            :key="index"
            class="campo"
          >
            <button
              type="button"
              class="campo-boton-quitar"
              @click="quitarCampo(index)"
              aria-label="Quitar campo"
            >
              <i class="fas fa-minus-circle"></i>
            </button>

            <div class="field-grid">
              <div class="form-group">
                <label :for="'campo-nombre-' + index">Nombre del campo</label>
                <input
                  :id="'campo-nombre-' + index"
                  v-model="campo.name"
                  class="form-control"
                  placeholder="Ej. Título"
                >
              </div>

              <div class="form-group">
                <label :for="'campo-tipo-' + index">Tipo del campo</label>
                <select :id="'campo-tipo-' + index" v-model="campo.type" class="form-control">
                  <option value="string">String</option>
                  <option value="number">Number</option>
                  <option value="file">File</option>
                  <option value="date">Date</option>
                </select>
              </div>

              <div class="form-group field-span-2">
                <label class="check-line">
                  <input type="checkbox" v-model="campo.required">
                  Campo obligatorio
                </label>
              </div>
            </div>
          </div>
        </section>

        <div class="button-group">
          <button type="button" class="btn-ghost" @click="agregarCampo">
            Agregar campo
          </button>
          <button type="button" class="btn-primary" @click="crearPlantilla">
            Crear plantilla
          </button>
        </div>

        <div v-if="mensaje" class="alert alert-success">{{ mensaje }}</div>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      plantillaName: '',
      camposPlantilla: [
        { name: '', type: 'string', required: false }
      ],
      mensaje: '',
      error: ''
    };
  },
  methods: {
    agregarCampo() {
      this.camposPlantilla.push({ name: '', type: 'string', required: false });
    },
    quitarCampo(index) {
      this.camposPlantilla.splice(index, 1);
    },
    async crearPlantilla() {
      try {
        const response = await axios.post('/plantillas', {
          plantilla_name: this.plantillaName,
          fields: this.camposPlantilla
        });
        this.mensaje = response.data.message;
        this.error = '';
        // Limpiar el formulario
        this.plantillaName = '';
        this.camposPlantilla = [{ name: '', type: 'string', required: false }];
      } catch (error) {
        if (error.response) {
          this.error = error.response.data.error || 'Error al crear la plantilla.';
        } else {
          this.error = 'Error de red o servidor.';
        }
        this.mensaje = '';
      }
    }
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
  padding: 1.1rem 1.15rem 1.2rem;
  border: 1px solid var(--line);
  border-radius: 14px;
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

.check-line {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  margin-top: .35rem;
  font-size: .88rem;
  font-weight: 600;
  color: var(--ink);
  cursor: pointer;
}
.check-line input {
  width: 16px;
  height: 16px;
  accent-color: var(--brand);
}

.campo {
  border: 1px solid var(--line);
  background: var(--bg);
  padding: 1.1rem 1.15rem 1.15rem;
  border-radius: 12px;
  position: relative;
  margin-bottom: .85rem;
}

.campo-boton-quitar {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 18px;
  padding: 4px 8px;
  background-color: #ffffff;
  color: var(--brand);
  border: 1px solid var(--line);
  border-radius: .5rem;
  cursor: pointer;
  line-height: 1;
}
.campo-boton-quitar i { color: var(--brand); }
.campo-boton-quitar:hover {
  background-color: var(--brand-soft);
  border-color: var(--brand);
}

.empty-fields {
  font-size: .88rem;
  color: var(--ink-soft);
  background: var(--bg);
  border: 1px dashed var(--line);
  border-radius: .7rem;
  padding: 1rem;
  margin-bottom: .5rem;
}

.button-group {
  display: flex;
  flex-wrap: wrap;
  gap: .65rem;
}

.btn-ghost {
  border: 1px solid var(--line);
  background: #fff;
  color: var(--ink);
  font-size: .88rem;
  font-weight: 600;
  padding: .7rem 1.1rem;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  transition: background .15s, border-color .15s;
}
.btn-ghost:hover {
  background: #f5f7fb;
  border-color: var(--brand);
}

.btn-primary {
  margin-top: 0;
  padding: .7rem 1.25rem;
  border-radius: 10px;
  border: none;
  font-weight: 700;
  font-size: .92rem;
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

.alert {
  border-radius: 10px;
  padding: .85rem 1rem;
  font-size: .9rem;
  margin: 0;
}
.alert-success {
  background: #eef7f0;
  color: #1f5b2f;
  border: 1px solid #cfe6d5;
}
.alert-danger {
  background: var(--brand-soft);
  color: var(--brand-dark);
  border: 1px solid color-mix(in srgb, var(--brand) 25%, #fff);
}

.mb-4 { margin-bottom: 1.5rem; }

@media (max-width: 640px) {
  .caratula-background { padding: 32px 14px 16px; }
  .caratula-form { padding: 22px; }
  h1 { font-size: 1.6rem; }
  .section-head { align-items: stretch; }
}
</style>
