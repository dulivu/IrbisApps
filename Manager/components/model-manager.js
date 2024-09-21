export class ModelManager extends IrbisElement {

    async fetchRecords(ev, force, query) {
        let model = ev.target.getAttribute('data-model');
        let list = ev.target.nextElementSibling;

        if (list.parentElement.hasAttribute('open') && !force) return;
        if (force) list.parentElement.setAttribute('open', '');

        list.replaceChildren(this.createLoader());

        let recordset = new RecordSet(model);
        let records = await recordset.select(query);

        if (!records.length)
            list.replaceChildren(this.createWarning());
        else {
            let items = records.map((x) => this.createItem(x))
            if (records.length == 50)
                items.push(this.createWarning('más ...'));
            list.replaceChildren(items);
        }
    }

    async showActions(ev) {
        ev.preventDefault();
        ev.manager = this;
        let menuid = ev.target.getAttribute('data-contextmenu');
        document.irbisElements[menuid].show(ev);
    }

    createLoader = function () {
        let loader = document.createElement('li');
        let icon = document.createElement('i');
        let text = document.createElement('span');

        loader.classList.add('loading');
        icon.classList.add('fa');
        icon.classList.add('fa-spinner');
        icon.classList.add('fa-spin');
        text.textContent = 'Cargando ...';

        loader.append(icon);
        loader.append(text);

        return loader;
    }

    createWarning = function (str) {
        str = str || 'Sin datos';
        let warning = document.createElement('li');
        let icon = document.createElement('i');
        let text = document.createElement('span');

        warning.classList.add('warning');
        icon.classList.add('fa');
        icon.classList.add('fa-warning');
        text.textContent = str;

        warning.append(icon);
        warning.append(text);

        return warning;
    }

    createItem (data) {
        let item = document.createElement('li');
        let icon = document.createElement('i');
        let text = document.createElement('span');

        item.setAttribute('data-record-id', data[0]);
        icon.classList.add('fa');
        icon.classList.add('fa-file');
        text.textContent = data[1];

        item.append(icon);
        item.append(text);

        item.addEventListener('dblclick', (ev) => {
            let i = ev.target === item ? ev.target : ev.target.parentElement;
            let recordID = i.getAttribute('data-record-id');
            let model = i.parentElement.getAttribute('data-model');
            window.popup({ 'url': `/irbis/model/${model}/update/${recordID}` });
        });

        return item;
    }
}