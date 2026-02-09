export class WinMenu extends IrbisElement {
    enableItems () {
        const listClass = Array.from(arguments);
        listClass.forEach((itemClass) => {
            this.element
                .querySelector(`.${itemClass}`)
                .removeAttribute('aria-disabled');
        });
    }

    disableItems () {
        const listClass = Array.from(arguments);
        listClass.forEach((itemClass) => {
            this.element
                .querySelector(`.${itemClass}`)
                .setAttribute('aria-disabled', 'true');
        });
    }
}