/**
 * Por falta de un componente TABS este mismo componente
 * gestiona la logica de los TABS de los archivos.
 * por medio de su propiedad tabsElement
 * 
 * gestiona los archivos existentes en el cms visualización
 * permite abrir y editar archivos html, css y js
 */
export class FileManager extends IrbisElement {
    validCreateExtensions = ['html', 'js', 'css'];
    modesExtensions = {
        html: {name: 'twig', base: 'text/html'},
        css: 'css',
        js: 'javascript'
    }
    tabsElement = null;

    constructor (element) {
        super(element);
        this.tabsElement = this.element.parentElement.parentElement;
    }

    openFileAction (ev) {
        let anchor = ev.currentTarget
        let fileName = anchor.textContent.trim();
        this.openFile(fileName);
    }

    attachFileName (fileName) {
        let self = this;
        let blocks = [...this.element.children].filter((el) => el.tagName.toLowerCase() == 'ul');
        blocks.some(function (block) {
            let faIcon = block.getAttribute('data-file-icon');
            let validExtensions = block.getAttribute('data-valid-extensions').split(' ');
			if (validExtensions.includes(fileName.getExtension()) || validExtensions[0] == "") {
                block.append(Element.create('li', {
                    children: [
                        Element.create('a', {
                            events: {click: (ev) => self.openFileAction(ev)},
                            children: [
                                Element.create('i', {classList: ['fa', faIcon]}),
                                Element.create('span', {textContent: fileName})
                            ]
                        })
                    ]
                }));

                return true;
			}
		});
    }

    /**
     * WARNING: este método envia una petición de
     * eliminación del archivo al servidor
     */
    detachFileName (fileName) {
        let files = [...this.element.querySelectorAll('li')];
        files.some(function (file) {
            if (file.textContent.trim() == fileName) {
                fetch.delete('/cms/file?name='+fileName)
                    .then(() => { file.remove(); });
                return true;
            }
        });
    }

    newFileAction (ev) {
        let extString = this.validCreateExtensions.join(', ');
        var fileName = prompt(`Ingrese nombre de archivo (puede usar extensiones: ${extString})`);
        if (fileName) {
            fileName = fileName.trim();
            if (this.validCreateExtensions.includes(fileName.getExtension())) {
                this.openFile(fileName);
                this.attachFileName(fileName);
            } else {
                alert(`Extensión de archivo no válida (puede usar: ${extString})`);
            }
        }
    }

    uploadFileAction(ev) {
        let input = ev.currentTarget;
        if (input.files.length) {
            fetch.uploadFileInput('/cms/file', input)
                .then(() => {
                    [...input.files].forEach((file) => {
                        this.attachFileName(file.name);
                    });
                });
        }
    }

    /*** TAB ACTIONS ***/

    tabExists (fileName) {
        let labels = [...this.tabsElement.children]
            .filter((el) => el.tagName.toLowerCase() == 'label');
        let exists = false;
        labels.some((label) => {
            if (label.textContent.trim() == fileName) {
                let input = label.previousElementSibling;
                return exists = input.checked = true;
            }
        });
        return exists;
    }

    openFile (fileName) {
        if (this.tabExists(fileName)) return;

        // obtener el último indice de los tabs
        let inputs = [...this.tabsElement.children]
            .filter((el) => el.tagName.toLowerCase() == 'input');
        let index = parseInt(inputs.pop().getAttribute('id').split('-')[1]) + 1;
        let tabContent = this.tabAdd(index, fileName);

        // si es un archivo editable por codigo
        if (this.validCreateExtensions.includes(fileName.getExtension())) {
            try { 
                this.attachCodeEditor(tabContent, fileName);
            } catch (e) {
                alert('No se pudo cargar el archivo'); 
                throw e;
            }
        }

        // si es una imagen
        else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileName.getExtension())) {
            this.attachImage(tabContent, fileName);
        }

        // otros archivos
        else {
            this.attachOthers(tabContent, fileName);
        }
    }

    tabAdd (index, tabLabel) {
        let inputName = 'tab-'+index;
        let input = Element.create('input', {
            checked: true,
            attributes: {
                name: 'tabs',
                type: 'radio',
                id: inputName
            }
        });
        let label = Element.create('label', {
            classList: ['tab-label'],
            attributes: {for: inputName},
            textContent: tabLabel
        });
        let content = Element.create('div', {
            classList: ['tab-content'],
            innerHTML: '<p>cargando ...</p>'
        });

        this.tabsElement.append(input, label, content);
        return content;
    }

    tabRemove (tabLabel) {
        let self = this;
        let labels = [...this.tabsElement.children]
            .filter((el) => el.tagName.toLowerCase() == 'label');
        labels.some(function (label) {
            if (label.textContent.trim() == tabLabel) {
                let input = label.previousElementSibling;
                let content = label.nextElementSibling;
                if (input.checked)
                    self.tabsElement.children[0].checked = true;

                input.remove();
                content.remove();
                label.remove();
                return true;
            }
        });
    }

    /*** EDITOR ACTIONS ***/

    attachOthers (container, fileName) {
        container.replaceChildren([
            Element.create('button', {
                innerHTML: '<i class="fa fa-close" title="Cerrar"></i>',
                events: { click: (ev) => this.closeEditorAction(ev) }
            }),
            Element.create('button', {
                innerHTML: '<i class="fa fa-trash" title="Eliminar"></i>',
                events: { click: (ev) => this.deleteEditorAction(ev) }
            }),
            Element.create('p', {
                textContent: 'No se puede editar este archivo'
            })
        ]);
    }

    attachImage (container, fileName) {
        container.replaceChildren([
            Element.create('button', {
                innerHTML: '<i class="fa fa-close" title="Cerrar"></i>',
                events: { click: (ev) => this.closeEditorAction(ev) }
            }),
            Element.create('button', {
                innerHTML: '<i class="fa fa-trash" title="Eliminar"></i>',
                events: { click: (ev) => this.deleteEditorAction(ev) }
            }),
            Element.create('br'),
            Element.create('img', {
                attributes: {
                    src: 'IrbisApps/Cms/content/images/'+fileName, 
                    alt: fileName
                },
                styles: {
                    maxWidth: '100%'
                }
            })
        ]);
    }

    async attachCodeEditor (container, fileName) {
        try {
            let fileContent = await fetch.get('/cms/file?name='+fileName, {redirect: 'error'});

            // se agregan los comandos
            container.replaceChildren([
                Element.create('button', {
                    innerHTML: '<i class="fa fa-save" title="Guardar"></i>',
                    events: { click: (ev) => this.saveEditorAction(ev) }
                }),
                Element.create('button', {
                    innerHTML: '<i class="fa fa-close" title="Cerrar"></i>',
                    events: { click: (ev) => this.closeEditorAction(ev) }
                }),
                Element.create('button', {
                    innerHTML: '<i class="fa fa-trash" title="Eliminar"></i>',
                    events: { click: (ev) => this.deleteEditorAction(ev) }
                })
            ]);

            // si es html, se agrega el enrutador
            if (fileName.getExtension() == 'html') {
                container.append(
                    Element.create('input', {
                        classList: ['file-route'],
                        attributes: {
                            type: 'text', 
                            placeholder: '/ruta/web',
                            value: await fetch.get('/cms?routefor='+fileName)
                        }
                    })
                );
                container.append(Element.create('a', {
                    classList: ['button'],
                    innerHTML: '<i class="fa fa-eye" title="Vista previa"></i>',
                    attributes: {
                        href: '/cms/preview/'+fileName,
                        target: '_blank'
                    }
                }));
            }

            // se agrega el editor de codigo
            let editorElement = Element.create('textarea', {textContent: fileContent});
            container.append(Element.create('br'), editorElement);
            editorElement.CodeMirror = CodeMirror.fromTextArea(editorElement, {
                mode: this.modesExtensions[fileName.getExtension()],
                tabSize: 4, 
                lineNumbers: true,
                theme: 'monokai',
            });
            editorElement.CodeMirror.setSize(null, 'calc(100vh - 13.5em)');
        } catch (e) {
            alert('No se pudo cargar el archivo');
            throw e;
        }
    }

    detachCodeEditor (container) {
        let editor = container.querySelector('textarea');
        editor.CodeMirror.toTextArea();
    }

    saveEditorAction (ev) {
        let tabContent = ev.currentTarget.parentElement;
        let editor = tabContent.querySelector('textarea').CodeMirror;
        let fileName = tabContent.previousElementSibling.textContent.trim();
        let fileContent = editor.getValue();
        fetch.put('/cms/file?name='+fileName, {
            body: fileContent
        }).then(() => alert('Archivo actualizado'));

        // si una ruta existe esta se guarda
        let routeInput = tabContent.querySelector('input.file-route');
        if (routeInput) {
            if (routeInput.vale && !routeInput.value.startsWith('/'))
                routeInput.value = '/'+routeInput.value;
            fetch.post('/cms?routefor='+fileName, {
                body: { fileRoute: routeInput.value }
            });
        }
    }

    closeEditorAction (ev) {
        let tabContent = ev.currentTarget.parentElement;
        let fileName = tabContent.previousElementSibling.textContent.trim();
        if (this.validCreateExtensions.includes(fileName.getExtension()))
            this.detachCodeEditor(tabContent);
        this.tabRemove(fileName);
        return fileName;
    }

    deleteEditorAction (ev) {
        if (!confirm('¿Está seguro de eliminar este archivo?')) return;
        let fileName = this.closeEditorAction(ev);
        this.detachFileName(fileName);
    }
}