export class FieldPassword extends IrbisElement {
    get input () {
        return this.element.querySelector('input');
    }

    changeValueAction (ev) {
        if (this.input.value.length > 0) {
            const _name = this.input.getAttribute('alt-name');
            this.input.setAttribute('name', _name);
        } else {
            this.input.removeAttribute('name');
        }
    }
}