export class modelField extends IrbisElement {
    timer = null;

    constructor (element) {
        super(element);
        this.input = this.element.previousElementSibling;
        this.button = this.element.nextElementSibling;
        this.list = this.button.nextElementSibling;

        this.button.addEventListener('mousedown', this.searchText.bind(this));
    }

    show (with_info) {
        with_info = with_info || false;
        if (with_info) {
            let li = document.createElement('li');
            li.textContent = 'buscando...';
            li.setAttribute('role', 'option');
            li.addEventListener('click', () => { return false });
            li.style.color = '#ccc';
            this.list.replaceChildren(li);
        }
        this.list.style.display = 'block';
    }

    hidde () {
        this.list.clearChildren();
        this.list.style.display = 'none';
    }

    selectNext () {
        if (this.list.style.display == 'block') {
            let selected = this.list.querySelector('.selected');
            if (selected) {
                selected.classList.remove('selected');
                selected = selected.nextElementSibling || this.list.firstChild;
            } else {
                selected = this.list.firstChild;
            }
            selected.classList.add('selected');
        }
    }

    selectPrev () {
        if (this.list.style.display == 'block') {
            let selected = this.list.querySelector('.selected');
            if (selected) {
                selected.classList.remove('selected');
                selected = selected.previousElementSibling || this.list.lastChild;
            } else {
                selected = this.list.lastChild;
            }
            selected.classList.add('selected');
        }
    }

    async selectItem (ev) {
        if (this.timer) clearTimeout(this.timer);
        if (this.list.style.display == 'block') {
            let selected = ev.type == 'click' ? ev.target : this.list.querySelector('.selected');
            if (!selected) selected = this.list.firstChild;
            this.time = setTimeout(() => {
                if (selected) {
                    this.element.value = selected.textContent;
                    this.input.value = selected.getAttribute('data-record-id');
                }
                this.hidde();
            }, 250);
        }
    }

    async searchText (ev) {
        this.input.value = "";

        if (this.timer) clearTimeout(this.timer);
        if (ev.key == 'Escape') return this.hidde();
        if (ev.key == 'ArrowDown') return this.selectNext();
        if (ev.key == 'ArrowUp') return this.selectPrev();
        if (ev.key == 'Enter') return this.selectItem(ev);

        let search = ev.target === this.element ? this.element.value : this.lastSearch;
        if (ev.target === this.button) {
            ev.preventDefault();
            this.element.focus();
            if (this.list.style.display == 'block')
                return this.hidde();
        }

        if (search) {
            this.show(true);
            let target = this.element.getAttribute('data-target-model');
            let recordset = new RecordSet(target);
            this.timer = setTimeout(async () => {
                let records = await recordset.select({'name:like': '%'+search+'%'})
                this.list.replaceChildren(records.map((x) => this.createItem(x)));
                this.lastSearch = search;
            }, 500);
        } else { this.hidde(); }
    }

    createItem (item) {
        let li = document.createElement('li');
        li.textContent = item[1];
        li.setAttribute('data-record-id', item[0]);
        li.setAttribute('role', 'option');
        li.addEventListener('click', this.selectItem.bind(this));
        return li;
    }
}