export class AuthSign extends IrbisElement {
    changePassword (ev) {
		ev.preventDefault();
        var password = prompt("Ingrese su nueva contraseña");
		if (password) {
			fetch.json("/authorization/password", {
				body: { 'password': password }
			}).then(() => alert('Contraseña actualizada'));
		}
    }
}