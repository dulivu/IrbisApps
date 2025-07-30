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
    tabsLabels = null;

    constructor (element) {
        super(element);
        this.tabsElement = this.element.parentElement;
        this.tabsLabels = this.tabsElement.querySelector('menu');
        this.tabsLabels.addEventListener('click', (ev) => {
            if (ev.target.tagName.toLowerCase() == 'button')
                this.tabChange(ev.target);
        });
    }

    /*** ACTIONS ***/

    openFileAction (ev) {
        let anchor = ev.currentTarget
        let fileName = anchor.textContent.trim();
        this.openFile(fileName);
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

    saveEditorAction (ev) {
        let tabInfo = this.tabInfo(ev.currentTarget.parentElement.parentElement);
        let editor = tabInfo['content'].querySelector('textarea').CodeMirror;
        let fileContent = editor.getValue();
        fetch.put('/cms/file?name='+tabInfo['name'], {
            body: fileContent
        }).then(() => alert('Archivo actualizado'));

        // si una ruta existe esta se guarda
        let routeInput = tabInfo['content'].querySelector('input.file-route');
        if (routeInput) {
            if (routeInput.vale && !routeInput.value.startsWith('/'))
                routeInput.value = '/'+routeInput.value;
            fetch.post('/cms?routefor='+tabInfo['name'], {
                body: { fileRoute: routeInput.value }
            });
        }
    }

    closeEditorAction (ev) {
        let tabInfo = this.tabInfo(ev.currentTarget.parentElement.parentElement);
        if (this.validCreateExtensions.includes(tabInfo['name'].getExtension()))
            this.detachCodeEditor(tabInfo['content']);
        this.tabRemove(tabInfo['name']);
        return tabInfo['name'];
    }

    deleteEditorAction (ev) {
        if (!confirm('¿Está seguro de eliminar este archivo?')) return;
        let fileName = this.closeEditorAction(ev);
        this.detachFileName(fileName);
    }

    /*** TAB ACTIONS ***/

    tabChange (tabLabel) {
        let tabActive = this.tabsLabels.querySelector('[aria-selected=true]');
        if (tabLabel === tabActive) return;
        let tabIndex = Array.from(this.tabsLabels.children).indexOf(tabLabel);
        let contentActive = this.tabsElement.children[tabIndex+1];
        let contentLabel = this.tabsElement.querySelector('article:not([hidden])');

        tabLabel.setAttribute('aria-selected', true);
        tabActive.removeAttribute('aria-selected');

        contentLabel.setAttribute('hidden', true);
        contentActive.removeAttribute('hidden');
    }

    tabExists (tabLabel) {
        let labels = [...this.tabsLabels.children];
        let exists = false;
        labels.some((label) => {
            if (label.textContent.trim() == tabLabel) {
                this.tabChange(label);
                return true;
            }
        });
        return exists;
    }

    tabAdd (index, tabText) {
        let tabIndex = 'tab-'+index;
        let tabLabel = Element.create('button', {
            attributes: {
                'role': 'tab', 
                'aria-controls': tabIndex
            },
            textContent: tabText
        });
        let tabContent = Element.create('article', {
            attributes: {
                'role': 'tabpanel', 
                'hidden': true,
                'id': tabIndex
            },
            innerHTML: '<p>cargando ...</p>'
        });

        this.tabsLabels.append(tabLabel);
        this.tabsElement.append(tabContent);
        return [tabLabel, tabContent];
    }

    tabRemove (tabLabel) {
        let self = this;
        let labels = [...this.tabsLabels.children];
        labels.some(function (label) {
            if (label.textContent.trim() == tabLabel) {
                let index = Array.from(self.tabsLabels.children).indexOf(label);
                let content = Array.from(self.tabsElement.children)[index+1];
                if (label.getAttribute('aria-selected') === 'true')
                    self.tabChange(labels[0]);

                content.remove();
                label.remove();
                return true;
            }
        });
    }

    tabInfo (element) {
        if (element.tagName.toLowerCase() == 'button') {
            let tabIndex = Array.from(this.tabsLabels.children).indexOf(element) + 1;
            return {
                name: element.textContent.trim(),
                label: element,
                content: this.tabsElement.children[tabIndex]
            }
        } else if (element.tagName.toLowerCase() == 'article') {
            let tabIndex = Array.from(this.tabsElement.children).indexOf(element) - 1;
            let tabLabel = this.tabsLabels.children[tabIndex];
            return {
                name: tabLabel.textContent.trim(),
                label: tabLabel,
                content: element
            }
        }
        return {};
    }

    /*** INTERNALS ***/

    attachFileName (fileName) {
        let self = this;
        let blocks = [...this.element.querySelectorAll('fieldset > ul')];
        blocks.some(function (block) {
            let faIcon = block.getAttribute('data-file-icon');
            let validExtensions = block.getAttribute('data-valid-extensions').split(' ');
			if (validExtensions.includes(fileName.getExtension()) || validExtensions[0] == "") {
                block.append(Element.create('li', {
                    children: [
                        Element.create('a', {
                            events: {click: (ev) => self.openFileAction(ev)},
                            children: [
                                Element.create('i', {classList: ['fa-li', 'fa', faIcon]}),
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

    openFile (fileName) {
        if (this.tabExists(fileName)) return;

        // obtener el último indice de los tabs
        let last = [...this.tabsLabels.children].pop();
        let index = this.tabsLabels.children.length;
        let [label, content] = this.tabAdd(index, fileName);

        this.tabChange(label);

        // si es un archivo editable por codigo
        if (this.validCreateExtensions.includes(fileName.getExtension())) {
            try { 
                this.attachCodeEditor(content, fileName);
            } catch (e) {
                alert('No se pudo cargar el archivo'); 
                throw e;
            }
        }

        // si es una imagen
        else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileName.getExtension())) {
            this.attachImage(content, fileName);
        }

        // otros archivos
        else {
            this.attachOthers(content, fileName);
        }
    }

    attachOthers (container, fileName) {
        let buttonsBar = Element.create('div', {
            attributes: {role: 'buttonbar'},
            children: [
                Element.create('button', {
                    innerHTML: '<i class="fa fa-close" title="Cerrar"></i>',
                    events: { click: (ev) => this.closeEditorAction(ev) }
                }),
                Element.create('button', {
                    innerHTML: '<i class="fa fa-trash" title="Eliminar"></i>',
                    events: { click: (ev) => this.deleteEditorAction(ev) }
                })
            ]
        });

        container.replaceChildren([
            buttonsBar,
            Element.create('p', {
                textContent: 'No se puede editar este archivo'
            })
        ]);
    }

    attachImage (container, fileName) {
        let buttonsBar = Element.create('div', {
            attributes: {role: 'buttonbar'},
            children: [
                Element.create('button', {
                    innerHTML: '<i class="fa fa-close" title="Cerrar"></i>',
                    events: { click: (ev) => this.closeEditorAction(ev) }
                }),
                Element.create('button', {
                    innerHTML: '<i class="fa fa-trash" title="Eliminar"></i>',
                    events: { click: (ev) => this.deleteEditorAction(ev) }
                })
            ]
        });

        container.replaceChildren([
            buttonsBar,
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

            let buttonsBar = Element.create('div', {
                attributes: {role: 'buttonbar'},
                children: [
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
                ]
            });

            // se agregan los comandos
            container.replaceChildren(buttonsBar);

            // si es html, se agrega el enrutador
            if (fileName.getExtension() == 'html') {
                buttonsBar.append(
                    Element.create('input', {
                        classList: ['file-route'],
                        attributes: {
                            type: 'text', 
                            placeholder: '/ruta/web',
                            title: 'Ingrese la ruta de la página web',
                            value: await fetch.get('/cms?routefor='+fileName)
                        }
                    })
                );
                buttonsBar.append(Element.create('button', {
                    innerHTML: '<i class="fa fa-eye" title="Vista previa"></i>',
                    events: {
                        click: () => window.open('/cms/preview/'+fileName, '_blank')
                    }
                }));
            }

            // se agrega el editor de codigo
            let editorElement = Element.create('textarea', {textContent: fileContent});
            let editorContainer = Element.create('div', {
                classList: ['code-editor'],
                styles: {'font-size': '12px'},
                children: [editorElement]
            });
            
            container.append(editorContainer);
            editorElement.CodeMirror = CodeMirror.fromTextArea(editorElement, {
                mode: this.modesExtensions[fileName.getExtension()],
                tabSize: 4, 
                lineNumbers: true,
                //theme: 'monokai',
            });
            editorElement.CodeMirror.setSize(null, 'calc(100vh - 18em)');
        } catch (e) {
            alert('No se pudo cargar el archivo');
            throw e;
        }
    }

    detachCodeEditor (container) {
        let editor = container.querySelector('textarea');
        editor.CodeMirror.toTextArea();
    }
}