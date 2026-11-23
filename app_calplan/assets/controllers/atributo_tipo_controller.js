import { Controller } from '@hotwired/stimulus';

/*
 * Muestra u oculta el campo de opciones según si el atributo es de tipo lista.
 */
export default class extends Controller {
    static targets = ['tipo', 'opcionesWrapper'];

    connect() {
        this.cambiar();
    }

    cambiar() {
        const esLista = this.tipoTarget.value === 'lista';
        this.opcionesWrapperTarget.classList.toggle('d-none', !esLista);
    }
}
