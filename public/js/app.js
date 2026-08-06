document.addEventListener("DOMContentLoaded", function () {
    // 1. CALCULO DINÁMICO DE EDAD EN ADMISIÓN
    const fechaNacInput = document.getElementById("fecha_nacimiento");
    const edadInput     = document.getElementById("edad_calculada");

    if (fechaNacInput && edadInput) {
        fechaNacInput.addEventListener("change", function () {
            if (this.value) {
                const nac = new Date(this.value);
                const hoy = new Date();
                let edad = hoy.getFullYear() - nac.getFullYear();
                if (hoy.getMonth() - nac.getMonth() < 0 || (hoy.getMonth() === nac.getMonth() && hoy.getDate() < nac.getDate())) {
                    edad--;
                }
                edadInput.value = edad >= 0 ? edad + " años" : "Fecha Incorrecta";
            }
        });
    }

    // 2. BUSCADOR EN VIVO DE PACIENTES (AJAX)
    const buscador = document.getElementById("buscar_paciente_input");
    const resultados = document.getElementById("tabla_resultados_pacientes");

    if (buscador && resultados) {
        buscador.addEventListener("keyup", function () {
            let query = this.value.trim();
            if (query.length >= 2) {
                fetch(`index.php?action=buscar_paciente_ajax&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        resultados.innerHTML = "";
                        if(data.length === 0) {
                            resultados.innerHTML = `<tr><td class='text-center text-muted'>No hay coincidencias en VitaSalud.</td></tr>`;
                            return;
                        }
                        data.forEach(p => {
                            let celdaAccion = "";
                            if(p.cita_hoy_id) {
                                // Si el sistema detecta que tiene cita hoy pero no está en la cola, permite meterlo de inmediato como 'Citado'
                                celdaAccion = `<form action="index.php?action=procesar_cola" method="POST">
                                                <input type="hidden" name="sub_accion" value="mover_a_cola">
                                                <input type="hidden" name="cita_id" value="${p.cita_hoy_id}">
                                                <button type="submit" class="btn btn-xs btn-info text-white py-0 px-2 fw-bold" style="font-size:11px;">Mover a Cola (Citado)</button>
                                               </form>`;
                            } else {
                                // Paciente regular sin cita, pasa por orden de llegada
                                celdaAccion = `<button class="btn btn-xs btn-warning py-0 px-2 fw-bold text-dark" style="font-size:11px;" onclick="abrirModalCola(${p.id})">+ Ingresar a Cola</button>`;
                            }

                            resultados.innerHTML += `
                                <tr>
                                    <td><b>${p.nombres} ${p.apellidos}</b><br><small class="text-muted">${p.expediente}</small></td>
                                    <td>${p.dui ? p.dui : 'Sin DUI'}</td>
                                    <td class="text-end">${celdaAccion}</td>
                                </tr>`;
                        });
                    });
            }
        });
    }
});

function abrirModalCola(id) {
    document.getElementById('modal_paciente_id').value = id;
    var myModal = new bootstrap.Modal(document.getElementById('modalEnviarCola'));
    myModal.show();
}