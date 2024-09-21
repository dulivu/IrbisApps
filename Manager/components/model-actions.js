export class ModelActions extends IrbisElement {
    constructor (element) {
        super(element);
        document.addEventListener('click', (ev) => {
            if (this.element.style.display == 'block' && !this.element.contains(ev.target))
                this.hidde();
        });
    }

    show (ev) {
        this.event = ev
        this.element.style.left = ev.clientX + 'px';
        this.element.style.top = ev.clientY + 'px';
        this.element.style.display = 'block';
    }

    hidde () {
        this.element.style.display = 'none';
        this.event = null;
    }

    async actionNew (ev) {
        let model = this.event.target.getAttribute('data-model');
        window.popup({ 'url': `/irbis/model/${model}/insert` });
        this.hidde();
    }

    async actionSearch () {
        let model_name = this.event.target.textContent;
        let search = prompt(`Buscar '${model_name}' que contengan`);
        if (search)
            this.event.manager.fetchRecords(this.event, true, {'name:like': '%'+search+'%'});
        this.hidde();
    }

    async actionRefresh () {
        this.event.manager.fetchRecords(this.event, true);
        this.hidde();
    }
}