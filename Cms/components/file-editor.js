import { WinTabs } from '@irbis/win-tabs.js';

export class FileTabs extends WinTabs {
    validEditExtensions = ['html', 'js', 'css'];
    validImageExtensions = ['jpg', 'jpeg', 'png', 'gif','svg','webp'];
    editorModes = {
        html: {name: 'twig', base: 'text/html'},
        js: 'javascript',
        css: 'css',
    }

    get selected() {
        return super.selected;
    }

    set selected(tab) {
        const label = tab.textContent.trim();
        this.disableMenuItems('file-route', 'file-save', 'file-close', 'file-delete');
        if (label.pathinfo('extension') == 'html') {
            this.enableMenuItems('file-route', 'file-save', 'file-close', 'file-delete');
        } else if (this.validEditExtensions.includes(label.pathinfo('extension'))) {
            this.enableMenuItems('file-save', 'file-close', 'file-delete');
        } else if (label != 'Inicio') {
            this.enableMenuItems('file-close', 'file-delete');
        }
        super.selected = tab;
    }

    openFile(fileName, filePath) {
        // si el tab existe lo muestra, sino lo crea
        let exists = Array.from(this.tabs).filter((tab) => tab.textContent.trim() == fileName);

        if (exists.length) {
            this.selected = exists[0];
        } else {
            const added = this.append(fileName, 'cargando ...');
            const ext = fileName.pathinfo('extension');

            added.tab.setAttribute('record-name', filePath);
            this.selected = added.tab;

            // si es un archivo editable por codigo
            if (this.validEditExtensions.includes(ext)) {
                try { 
                    this.openEditor(added.panel, fileName, filePath);
                } catch (e) {
                    alert('No se pudo cargar el archivo'); 
                    throw e;
                }
            }

            // si es una imagen
            else if (this.validImageExtensions.includes(ext)) {
                this.openImage(added.panel, fileName, filePath);
            }

            // otros archivos
            else {
                this.openOther(added.panel, fileName, filePath);
            }
        }
    }

    disableMenuItems() {
        const listIds = Array.from(arguments);
        listIds.forEach((itemId) => {
            $id(itemId).setAttribute('aria-disabled', 'true');
        });
    }

    enableMenuItems() {
        const listIds = Array.from(arguments);
        listIds.forEach((itemId) => {
            $id(itemId).removeAttribute('aria-disabled');
        });
    }

    async openEditor(container, fileName, filePath) {
        try {
            let fileContent = await fetch.get(`/IrbisApps/Cms/${filePath}/${fileName}`, {redirect: 'error'});

            // se agrega el editor de codigo
            let editorElement = Element.create('textarea', {textContent: fileContent});
            let editorContainer = Element.create('div', {
                classList: ['code-editor'],
                styles: {'font-size': '12px'},
                children: [editorElement]
            });

            // se agregan comandos
            let editorMenu

            
            container.style.padding = '0';
            container.replaceChildren(editorContainer);
            editorElement.CodeMirror = CodeMirror.fromTextArea(editorElement, {
                mode: this.editorModes[fileName.pathinfo('extension')],
                tabSize: 4, 
                lineNumbers: true,
                lineWrapping: true,
                //theme: 'monokai',
            });
            editorElement.CodeMirror.setSize(false, 'calc(100vh - 53px)');
        } catch (e) {
            alert('No se pudo cargar el archivo');
            throw e;
        }
    }

    closeEditor(container) {
        let editor = container.querySelector('textarea');
        if (editor && editor.CodeMirror)
            editor.CodeMirror.toTextArea();
    }

    openImage(container, fileName, filePath) {
        container.replaceChildren([
            Element.create('img', {
                attributes: {
                    src: '/IrbisApps/Cms/'+filePath+'/'+fileName, 
                    alt: fileName
                },
                styles: {
                    maxWidth: '100%'
                }
            })
        ]);
    }

    openOther(container, fileName, filePath) {
        container.replaceChildren([
            Element.create('p', {
                textContent: `No se puede editar este archivo: ${fileName}.`
            })
        ]);
    }

    // -= acciones de archivo =-

    fileSaveAction () {
        const selected = this.selected;
        const recordName = selected.tab.getAttribute('record-name');
        const fileContent = selected.panel.querySelector('textarea').CodeMirror.getValue();
        const directory = $(`details[record-name="${recordName}"]`).component;

        directory.saveItemRemote(selected.tab.textContent.trim(), fileContent);
    }

    fileCloseAction () {
        const selected = this.selected;
        this.closeEditor(selected.panel);
        this.remove(selected.tab);
    }

    fileDeleteAction (ev) {
        const selected = this.selected;
        const fileName = selected.tab.textContent.trim();
        const confirm = window.confirm(`¿Está seguro de eliminar ${fileName}?`);

        if (confirm) {
            const recordName = selected.tab.getAttribute('record-name');
            const directory = $(`details[record-name="${recordName}"]`).component;

            this.closeEditor(selected.panel);
            this.remove(selected.tab);

            directory.removeItemRemote(fileName);
        }
    }

    createRouteAction () {
        const selected = this.selected;
        const fileName = selected.tab.textContent.trim();
        const routeName = prompt(`¿Crear una nueva ruta para ${fileName}?`).trim();
        if (routeName) {
            fetch.json('/recordset/routes/insert', {
                body: {
                    name: routeName,
                    file: fileName
                }
            }).then(() => { alert('Ruta establecida, puede ir al menú rutas para gestionarlas'); });
        }
    }
}