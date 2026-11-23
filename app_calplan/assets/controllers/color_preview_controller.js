import { Controller } from '@hotwired/stimulus';

/*
 * Controla el desplegable visual de colores (sin texto visible) y sincroniza
 * el <select> real de Symfony Forms junto con la muestra del botón principal.
 */
export default class extends Controller {
    static targets = ['select', 'muestra', 'opcion'];

    connect() {
        this.sincronizar();
    }

    elegir(event) {
        const color = event.currentTarget.dataset.colorValue;
        if (!color) {
            return;
        }

        this.selectTarget.value = color;
        this.sincronizar();
    }

    sincronizar() {
        const actual = (this.selectTarget.value || '#ffffff').toLowerCase();
        this.muestraTarget.style.backgroundColor = actual;

        this.opcionTargets.forEach((boton) => {
            const seleccionado = (boton.dataset.colorValue || '').toLowerCase() === actual;
            boton.classList.toggle('color-opcion-activa', seleccionado);
            boton.setAttribute('aria-pressed', seleccionado ? 'true' : 'false');
        });
    }
}
