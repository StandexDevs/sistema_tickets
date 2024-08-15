let modal;

function mostrarModal(id_modal, callback) {
    
    modal = new bootstrap.Modal(id_modal, {});
    modal.show();

    console.log(document.getElementById(id_modal))
		if (callback) {
			callback();
		}
}
