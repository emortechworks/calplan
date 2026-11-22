import { Controller } from '@hotwired/stimulus';

/*
 * Muestra/oculta el panel de roles de cada tienda marcada y gestiona la prioridad
 * (#1 = mayor peso) de los roles dentro de cada tienda, serializando el mapa
 * { tiendaId: [rolId, ...] } en el campo oculto del formulario.
 */
export default class extends Controller {
    static targets = ['input', 'panel', 'sinTiendas'];

    connect() {
        this.actualizarTiendas();
    }

    actualizarTiendas() {
        const seleccionadas = new Set(
            Array.from(this.element.querySelectorAll('input[data-tienda-checkbox]:checked'))
                .map((checkbox) => checkbox.value),
        );

        let visibles = 0;
        this.panelTargets.forEach((panel) => {
            const activo = seleccionadas.has(panel.dataset.tiendaPanelId);
            panel.classList.toggle('d-none', !activo);
            if (activo) {
                visibles += 1;
            }
        });

        if (this.hasSinTiendasTarget) {
            this.sinTiendasTarget.classList.toggle('d-none', visibles > 0);
        }

        this.sincronizar();
    }

    agregar(event) {
        const panel = event.currentTarget.closest('[data-tienda-panel-id]');
        const item = event.currentTarget.closest('[data-rol-id]');
        const asignados = panel?.querySelector('[data-lista-asignados]');
        if (!panel || !item || !asignados) {
            return;
        }

        asignados.appendChild(item);
        this.sincronizar();
    }

    quitar(event) {
        const panel = event.currentTarget.closest('[data-tienda-panel-id]');
        const item = event.currentTarget.closest('[data-rol-id]');
        const disponibles = panel?.querySelector('[data-lista-disponibles]');
        if (!panel || !item || !disponibles) {
            return;
        }

        disponibles.appendChild(item);
        this.ordenarDisponibles(disponibles);
        this.sincronizar();
    }

    subir(event) {
        const panel = event.currentTarget.closest('[data-tienda-panel-id]');
        const item = event.currentTarget.closest('[data-rol-id]');
        const asignados = panel?.querySelector('[data-lista-asignados]');
        if (!asignados || !item || !item.previousElementSibling) {
            return;
        }

        asignados.insertBefore(item, item.previousElementSibling);
        this.sincronizar();
    }

    bajar(event) {
        const panel = event.currentTarget.closest('[data-tienda-panel-id]');
        const item = event.currentTarget.closest('[data-rol-id]');
        const asignados = panel?.querySelector('[data-lista-asignados]');
        if (!asignados || !item || !item.nextElementSibling) {
            return;
        }

        asignados.insertBefore(item.nextElementSibling, item);
        this.sincronizar();
    }

    ordenarDisponibles(contenedor) {
        const items = Array.from(contenedor.querySelectorAll('[data-rol-id]'));
        items.sort((a, b) => (a.dataset.rolNombre || '').localeCompare(b.dataset.rolNombre || '', 'es'));
        items.forEach((item) => contenedor.appendChild(item));
    }

    sincronizar() {
        const mapa = {};

        this.panelTargets.forEach((panel) => {
            const tiendaId = panel.dataset.tiendaPanelId;
            const visible = !panel.classList.contains('d-none');
            const asignadosCont = panel.querySelector('[data-lista-asignados]');
            const disponiblesCont = panel.querySelector('[data-lista-disponibles]');
            const vacio = panel.querySelector('[data-lista-vacio]');

            const asignados = asignadosCont ? Array.from(asignadosCont.querySelectorAll('[data-rol-id]')) : [];
            const disponibles = disponiblesCont ? Array.from(disponiblesCont.querySelectorAll('[data-rol-id]')) : [];

            const ids = [];
            asignados.forEach((item, index) => {
                ids.push(Number(item.dataset.rolId));

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

            if (vacio) {
                vacio.classList.toggle('d-none', asignados.length > 0);
            }

            if (visible && ids.length > 0) {
                mapa[tiendaId] = ids;
            }
        });

        this.inputTarget.value = JSON.stringify(mapa);
    }
}
