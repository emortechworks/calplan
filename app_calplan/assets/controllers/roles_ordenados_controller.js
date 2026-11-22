import { Controller } from '@hotwired/stimulus';

/*
 * Gestiona la asignación y el orden de prioridad (#1 = mayor peso) de los roles
 * de un miembro del personal, manteniendo sincronizado el campo oculto del formulario.
 */
export default class extends Controller {
    static targets = ['input', 'asignados', 'disponibles', 'vacio'];

    connect() {
        this.sincronizar();
    }

    agregar(event) {
        const item = event.currentTarget.closest('[data-rol-id]');
        if (!item) {
            return;
        }

        this.asignadosTarget.appendChild(item);
        this.sincronizar();
    }

    quitar(event) {
        const item = event.currentTarget.closest('[data-rol-id]');
        if (!item) {
            return;
        }

        this.disponiblesTarget.appendChild(item);
        this.ordenarDisponibles();
        this.sincronizar();
    }

    subir(event) {
        const item = event.currentTarget.closest('[data-rol-id]');
        if (!item || !item.previousElementSibling) {
            return;
        }

        this.asignadosTarget.insertBefore(item, item.previousElementSibling);
        this.sincronizar();
    }

    bajar(event) {
        const item = event.currentTarget.closest('[data-rol-id]');
        if (!item || !item.nextElementSibling) {
            return;
        }

        this.asignadosTarget.insertBefore(item.nextElementSibling, item);
        this.sincronizar();
    }

    ordenarDisponibles() {
        const items = Array.from(this.disponiblesTarget.querySelectorAll('[data-rol-id]'));
        items.sort((a, b) => (a.dataset.rolNombre || '').localeCompare(b.dataset.rolNombre || '', 'es'));
        items.forEach((item) => this.disponiblesTarget.appendChild(item));
    }

    sincronizar() {
        const asignados = Array.from(this.asignadosTarget.querySelectorAll('[data-rol-id]'));
        const disponibles = Array.from(this.disponiblesTarget.querySelectorAll('[data-rol-id]'));

        const ids = [];
        asignados.forEach((item, index) => {
            ids.push(item.dataset.rolId);

            const badge = item.querySelector('[data-role-badge]');
            if (badge) {
                badge.classList.remove('d-none');
                badge.textContent = index === 0 ? '#1 Principal' : `#${index + 1}`;
                badge.classList.toggle('text-bg-dark', index === 0);
                badge.classList.toggle('text-bg-secondary', index > 0);
            }

            item.querySelector('[data-acciones-asignado]')?.classList.remove('d-none');
            item.querySelector('[data-acciones-disponible]')?.classList.add('d-none');

            const btnSubir = item.querySelector('[data-btn-subir]');
            const btnBajar = item.querySelector('[data-btn-bajar]');
            if (btnSubir) {
                btnSubir.disabled = index === 0;
            }
            if (btnBajar) {
                btnBajar.disabled = index === asignados.length - 1;
            }
        });

        disponibles.forEach((item) => {
            item.querySelector('[data-role-badge]')?.classList.add('d-none');
            item.querySelector('[data-acciones-asignado]')?.classList.add('d-none');
            item.querySelector('[data-acciones-disponible]')?.classList.remove('d-none');
        });

        if (this.hasVacioTarget) {
            this.vacioTarget.classList.toggle('d-none', asignados.length > 0);
        }

        this.inputTarget.value = ids.join(',');
    }
}
