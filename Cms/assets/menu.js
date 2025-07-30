(function () {
    MenuBar.prototype.newFileAction = function (ev) {
        document.irbisElements['file-manager'].newFileAction(ev);
    }

    MenuBar.prototype.uploadFileAction = function (ev) {
        document.irbisElements['file-manager'].uploadFileAction(ev);
    }
})();