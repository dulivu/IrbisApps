export class WinMenuUser extends IrbisElement {
    get userId () {
        return this.element.getAttribute('record-id');
    }

    openUserPreferencesAction (ev) {
        window.popup(`/record/users/${this.userId}`);
    }

    actionUserLogout (ev) {
        ev.preventDefault();
        if (confirm("¿Desea cerrar sesión?")) {
            window.location.href='/logout';
        }
    }
}