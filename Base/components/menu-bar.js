// Clase no modular, la usa directamente el layout APP
// como un elemento indispensable y extensible
class MenuBar extends IrbisElement {
    actionUserChangePassword (ev) {
		ev.preventDefault();
        var password = prompt("Ingrese su nueva contraseña");
		if (password) {
			fetch.post("/irbis/user-change-password", {
				body: { 'password': password }
			}).then(() => alert('Contraseña actualizada'));
		}
    }

	actionUserLogout (ev) {
        ev.preventDefault();
        if (confirm("¿Está seguro de que desea cerrar sesión?")) {
            window.location.href='/irbis/logout';
        }
	}

    actionUpdateApps (ev) {
		ev.preventDefault();
        if (confirm("Confirme para actualizar la lista de aplicaciones")) {
			fetch.get('/irbis/update-list-apps').then((r) => {
				alert(r);
				window.location.reload();
			}).catch(error => {
				console.error('Error al actualizar la lista de aplicaciones:', error);
                alert('Error al actualizar la lista aplicaciones');
            });
        }
	}

	doAction (ev, action) {
		ev.preventDefault();
		let component = irbis.component();
		if (component) component[action](ev);
		else console.warn("No component found: ", action);
	}
}