export class ListItem extends IrbisElement {
    actionCall (ev, actionName) {
        ev.preventDefault();
        let record_name = this.element.getAttribute('record-name');
        let record_id = this.element.getAttribute('record-id');
        let record_url = `/record/${record_name}/${record_id}/${actionName}`
        fetch.json(record_url, { method: 'PUT' }).then((response) => {
            window.location.reload();
        });
    }
}