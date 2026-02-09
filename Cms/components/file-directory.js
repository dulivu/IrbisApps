export class FileList extends IrbisElement {

    get isOpened () {
        return this.element.hasAttribute('open');
    }

    get editor () {
        return $id('file-editor').component;
    }

    constructor (element) {
        super(element);
        if (this.isOpened)
            this.fetchItems();
    }

    fetchItems() {
        const recordId = this.element.getAttribute('record-id');
        this.appendItem('cargando ...', 'fa-spinner fa-spin');
        fetch.json(`/record/directories/${recordId}/fileList`)
            .then((data) => {
                this.clearItems();
                data.forEach(item => {
                    this.appendItem(item);
                });
            });
    }

    appendItem(item, icon) {
        icon = icon || this.element.getAttribute('record-icon') || 'fa-file-o';
        const color = this.element.getAttribute('record-color') || 'black';
        const html = `<i class="fa fa-fw ${icon}" style="color:${color};"></i> ${item}`;
        this.element
            .querySelector('ul')
            .append(Element.create('li', {
                innerHTML: html
            }));
    }

    appendItemRemote(item, icon) {
        const recordId = this.element.getAttribute('record-id');
        const filePath = this.element.getAttribute('record-name');
        fetch.json(`/record/directories/${recordId}/filePush`, {
            body: {
                filename: item,
            }
        }).then(() => {
            this.appendItem(item, icon);
            this.editor.openFile(item, filePath);
        });
    }

    matchExtension (extension) {
        const recordExtensions = this.element
            .getAttribute('record-extensions')
            .split(',')
            .map(ext => ext.trim());
        return recordExtensions.includes(extension);
    }

    openToggleAction () {
        if (!this.isOpened) {
            this.fetchItems();
        } else {
            this.clearItems();
        }
    }

    clearItems () {
        this.element
            .querySelector('ul')
            .clearChildren();
    }

    openFileAction (ev) {
        if (ev.target.tagName == 'LI') {
            const filePath = this.element.getAttribute('record-name');
            const fileName = ev.target.textContent.trim();
            if (fileName != 'cargando ...') {
                this.editor.openFile(fileName, filePath);
            };
        }
    }

    openConfigAction (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        const recordId = this.element.getAttribute('record-id');
        window.popup(`/record/directories/${recordId}`);
    }

    saveItemRemote (fileName, content) {
        const recordId = this.element.getAttribute('record-id');
        return fetch.json(`/record/directories/${recordId}/filePut`, {
            body: {
                filename: fileName,
                filecontent: content
            }
        }).then(() => { alert('Archivo guardado'); });
    }

    removeItem (fileName) {
        const items = this.element.querySelectorAll('ul > li');
        items.forEach((item) => {
            if (item.textContent.trim() == fileName) {
                item.remove();
            }
        });
    }

    removeItemRemote (fileName) {
        const recordId = this.element.getAttribute('record-id');
        return fetch.json(`/record/directories/${recordId}/fileUnlink`, {
            body: {
                filename: fileName
            }
        }).then(() => { this.removeItem(fileName); });
    }
}