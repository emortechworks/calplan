import { Controller } from '@hotwired/stimulus';

/*
 * Sincroniza la muestra visual del color seleccionado junto al desplegable.
 */
export default class extends Controller {
    static targets = ['select', 'muestra'];

    connect() {
        this.actualizar();
    }

    actualizar() {
        const color = this.selectTarget.value || '#ffffff';
        this.muestraTarget.style.backgroundColor = color;
    }
}
