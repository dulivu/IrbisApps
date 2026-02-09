export class RecordRow extends IrbisElement {
    get recordModel () {
        return this.element.getAttribute('record-model');
    }

    get recordId () {
        return this.element.getAttribute('record-id');
    }

    deleteAction (ev) {
        ev.preventDefault();
        
        if (confirm('¿Está seguro de eliminar este registro?')) {
            fetch.json(`/record/${this.recordModel}/${this.recordId}/delete`)
                .then(() => {
                    alert('Registro eliminado');
                    window.location.reload();
                });
        }
    }
}