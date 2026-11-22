import { Controller } from '@hotwired/stimulus';

/*
 * Muestra un overlay centrado con spinner únicamente en navegaciones reales (`turbo:visit`)
 * y envíos de formularios (`turbo:submit-start`), evitando dispararse con el prefetch al pasar
 * el cursor de Turbo 8. También muestra un spinner inline en el botón pulsado para evitar doble envío.
 */
export default class extends Controller {
    static targets = ['overlay'];
    static values = { delay: { type: Number, default: 120 } };

    connect() {
        this.temporizador = null;
        this.botonActivo = null;
        this.ocultarOverlay();

        this.alNavegar = this.programarOverlay.bind(this);
        this.alTerminarCarga = this.restaurarTodo.bind(this);
        this.alIniciarEnvio = this.activarBoton.bind(this);
        this.alTerminarEnvio = this.finalizarEnvio.bind(this);

        document.addEventListener('turbo:visit', this.alNavegar);
        document.addEventListener('turbo:render', this.alTerminarCarga);
        document.addEventListener('turbo:load', this.alTerminarCarga);
        document.addEventListener('turbo:before-cache', this.alTerminarCarga);
        document.addEventListener('turbo:fetch-request-error', this.alTerminarCarga);
        document.addEventListener('turbo:submit-start', this.alIniciarEnvio);
        document.addEventListener('turbo:submit-end', this.alTerminarEnvio);
        window.addEventListener('pageshow', this.alTerminarCarga);
    }

    disconnect() {
        this.restaurarTodo();

        document.removeEventListener('turbo:visit', this.alNavegar);
        document.removeEventListener('turbo:render', this.alTerminarCarga);
        document.removeEventListener('turbo:load', this.alTerminarCarga);
        document.removeEventListener('turbo:before-cache', this.alTerminarCarga);
        document.removeEventListener('turbo:fetch-request-error', this.alTerminarCarga);
        document.removeEventListener('turbo:submit-start', this.alIniciarEnvio);
        document.removeEventListener('turbo:submit-end', this.alTerminarEnvio);
        window.removeEventListener('pageshow', this.alTerminarCarga);
    }

    programarOverlay() {
        if (this.temporizador !== null) {
            return;
        }

        this.temporizador = window.setTimeout(() => {
            if (this.hasOverlayTarget) {
                this.overlayTarget.classList.remove('d-none');
            }
        }, this.delayValue);
    }

    ocultarOverlay() {
        if (this.temporizador !== null) {
            window.clearTimeout(this.temporizador);
            this.temporizador = null;
        }

        if (this.hasOverlayTarget) {
            this.overlayTarget.classList.add('d-none');
        }
    }

    activarBoton(event) {
        this.programarOverlay();

        const boton = event.detail?.formSubmission?.submitter;
        if (!(boton instanceof HTMLButtonElement) || boton.disabled) {
            return;
        }

        this.restaurarBoton();
        this.botonActivo = boton;
        boton.disabled = true;

        const spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-1';
        spinner.setAttribute('aria-hidden', 'true');
        spinner.dataset.cargaSpinner = 'true';
        boton.prepend(spinner);
    }

    finalizarEnvio(event) {
        this.restaurarBoton();

        // Si el POST devolvió redirección, mantiene el overlay hasta turbo:render/turbo:load.
        const redirigido = Boolean(event.detail?.fetchResponse?.redirected);
        if (!redirigido) {
            this.ocultarOverlay();
        }
    }

    restaurarBoton() {
        if (!this.botonActivo) {
            return;
        }

        this.botonActivo.querySelector('[data-carga-spinner]')?.remove();
        this.botonActivo.disabled = false;
        this.botonActivo = null;
    }

    restaurarTodo() {
        this.ocultarOverlay();
        this.restaurarBoton();
    }
}
