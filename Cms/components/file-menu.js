import { WinMenu } from '@irbis/win-menu.js';

export class FileMenu extends WinMenu {
    validCreateExtensions = ['html', 'js', 'css'];

    get editor () {
        return $id('file-editor').component;
    }

    newFileAction () {
        const extString = this.validCreateExtensions.join(', ');
        const fileName = prompt('Ingrese nombre de archivo').trim();
        const extension = fileName.pathinfo('extension');

        if (fileName) {
            if (this.validCreateExtensions.includes(extension)) {
                const directory = this.searchDirectory(extension);
                if (directory.hasAttribute('open'))
                    directory.component.appendItemRemote(fileName);
            } else {
                alert(`¡Extensión no válida! \n disponibles: ${extString}`);
            }
        }
    }

    searchDirectory (extension) {
        // busca un directorio adecuado para la extensión dada
        const directories = [...$$(`details[record-extensions]`)];
        const directoryOther = directories.pop();
        const directorySome = directories.find((directory) => {
            return directory.component.matchExtension(extension);
        });

        return directorySome || directoryOther;
    }

    uploadFileAction(ev) {
        const input = ev.currentTarget;
        if (!input.files.length) return;

        fetch.upload('/cms/upload', input)
            .then(() => {
                [...input.files].forEach((file) => {
                    const extension = file.name.pathinfo('extension');
                    const directory = this.searchDirectory(extension);
                    if (directory.hasAttribute('open'))
                        directory.appendItem(file.name);
                });
            });
    }

    openRoutesAction () {
        window.popup(`/recordset/routes`);
    }
}