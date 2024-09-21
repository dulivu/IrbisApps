window.popup = function (options) {
    let [width, height, url, name] = [640, 480, '', '__blank'];
    if (options instanceof HTMLElement) {
        width = options.getAttribute('data-popup-width') || width;
        height = options.getAttribute('data-popup-height') || height;
        url = options.getAttribute('data-popup-url');
        name = options.getAttribute('data-popup-name') || name;
    } else {
        width = options.width || width;
        height = options.height || height;
        url = options.url;
        name = options.name || name;
    }

    let left = (screen.width - width) / 2;
    let top = (screen.height - height) / 2;
    let params = 'width=' + width + ', height=' + height;
    params += ', top=' + top + ', left=' + left;
    params += ', directories=no';
    params += ', location=no';
    params += ', menubar=no';
    params += ', resizable=no';
    params += ', scrollbars=no';
    params += ', status=no';
    params += ', toolbar=no';
    newwin = window.open(url, name, params);
    if (window.focus) { newwin.focus() }
}

String.toCamelCase = function (str) {
    return str.replace(/[-_](.)/g, function (match, letter) {
        return letter.toUpperCase();
    });
}

JSON.encode = function (json, replacer, space) {
    return JSON.stringify(json, replacer, space);
}

JSON.decode = function (json_string, reviver) {
    return JSON.parse(json_string, reviver);
}

JSON.toQueryString = function (json) {
    return Object.keys(json)
        .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(json[key]))
        .join('&');
}

HTMLElement.prototype.clearChildren = function () {
    while (this.firstChild) {
        this.firstChild.remove();
    }
    return this;
}

HTMLElement.prototype.replaceChildren = function (newChildren) {
    if (newChildren instanceof HTMLCollection)
        newChildren = [...newChildren];
    if (newChildren instanceof HTMLElement)
        newChildren = [newChildren];
    
    this.clearChildren();
    newChildren.forEach((child) => this.append(child));
    return this;
}

class RecordSet {
    constructor (name, fields) {
        this.fields = fields || ['id', 'name'];
        this.name = name;
        this.records = [];
    }

    async select (where, order, limit) {
        let url = '/irbis/model/' + this.name + '/select';
        let query = where ? '?' + JSON.toQueryString(where) : '';
        let body = { 
            fields: this.fields,
            limit: limit || '0-50',
            order: order || []
        };
        let params = {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.encode(body)
        }

        let response = await fetch(url + query, params);

        if (response.status == 401) 
            console.log('Unauthorized');

        return this.records = await response.json();
    }
}

class IrbisElement {
    events_list = [
        // mouse events
        'event-click',
        'event-dblclick',
        'event-contextmenu',
        'event-mousedown',
        'event-mouseup',
        'event-mouseover',
        'event-mouseout',
        'event-mousewheel',
        // touch events
        'event-touchstart',
        'event-touchend',
        'event-touchmove',
        'event-touchcancel',
        // keyboard events
        'event-keydown',
        'event-keyup',
        'event-keypress',
        // form events
        'event-focus',
        'event-blur',
        'event-change',
        'event-submit',
        // window events
        'event-scroll'
    ];

    constructor (element) {
        this.element = element;
        element.component = this;
        let self = this;
        this.events_list.forEach(function (event) {
            let elements = [...self.element.querySelectorAll('['+event+']')];
            if (self.element.hasAttribute(event)) elements.push(self.element);
            elements.forEach(function (el) {
                let fn = el.getAttribute(event)
                el.addEventListener(event.split('-')[1], async function (ev) {
                    await self[fn](ev);
                });
            });
        });
    }
}
