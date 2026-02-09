$id = function (id) {
    return document.getElementById(id);
}

$ = function (selector) {
    return document.querySelector(selector);
}

$$ = function (selector) {
    return document.querySelectorAll(selector);
}

window.popup = function (url, name, options) {
    options = options || {};
    name = name || '__blank';
    const width = options.width || 640;
    const height = options.height || 480;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;
    const params = `width=${width}, height=${height}, left=${left}, top=${top}`;
    const defaults = ', directories=no, location=no, menubar=no, resizable=no, scrollbars=no, status=no, toolbar=no';
    const ref = window.open(url, name, params + defaults);
    if (window.focus) { ref.focus() }
    return ref;
}

window.submit = function (ev, adds) {
    ev.preventDefault();
    adds = adds || {};
    const form = ev.currentTarget.closest('form');
    Object.keys(adds).forEach((key) => {
        form.append(Element.create('input', {
            attributes: {
                type: 'hidden',
                name: key,
                value: adds[key]
            }
        }));
    });
    form.submit();
}

String.toCamelCase = function (str) {
    return str.replace(/[-_](.)/g, function (match, letter) {
        return letter.toUpperCase();
    });
}

String.prototype.pathinfo = function (infoType) {
    let fullPath = this.trim();
    let dirname = fullPath.substring(0, fullPath.lastIndexOf('/')+1);
    let basename = fullPath.split('/').pop();
    let extension = basename.split('.').pop();
    let filename = basename.substring(0, basename.lastIndexOf('.'));
    let info = {
        dirname: dirname,
        basename: basename,
        extension: extension,
        filename: filename
    }
    if (infoType) {
        return info[infoType];
    } else {
        return info;
    }
}

// TODO: para retirar
String.prototype.getExtension = function () {
	return this.substring(this.lastIndexOf('.')+1);
};

// TODO: para retirar
String.prototype.getBaseName = function () {
	return this.trim().substring(this.lastIndexOf('/')+1);
};

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

Element.create = function (tag, options) {
    let element = document.createElement(tag);
    if (options instanceof Object) {
        if (options.classList) {
            if (typeof options.classList == 'string') {
                element.classList.add(options.classList);
            } else {
                options.classList.forEach((className) => element.classList.add(className));
            }
        }
        if (options.textContent) {
            element.textContent = options.textContent;
        }
        if (options.innerHTML) {
            element.innerHTML = options.innerHTML;
        }
        if (options.children) {
            options.children.forEach((child) => element.append(child));
        }
        if (options.events) {
            Object.keys(options.events).forEach((event) => {
                element.addEventListener(event, options.events[event]);
            });
        }
        if (options.attributes) {
            Object.keys(options.attributes).forEach((attr) => {
                let value = options.attributes[attr];
                if (typeof value == 'boolean') {
                    if (value) element.setAttribute(attr, '');
                } else {
                    element.setAttribute(attr, value);
                }
            });
        }
        if (options.checked) {
            element.checked = options.checked;
        }
        if (options.styles) {
            Object.keys(options.styles).forEach((style) => {
                element.style[style] = options.styles[style];
            });
        }
    }
    return element;
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

HTMLElement.prototype.appendChildren = function (children) {
    if (children instanceof HTMLCollection)
        children = [...children];
    if (children instanceof HTMLElement)
        children = [children];

    children.forEach((child) => this.append(child));
    return this;
}

class JsonRequestError extends Error {
    constructor(jsonResponse) {        
        super(jsonResponse.error.message+", ");
        this.name = jsonResponse.error.class;
        this.cause = "error on server side";
        this.code = jsonResponse.error.code;
        this.fileName = jsonResponse.error.file;
        this.lineNumber = jsonResponse.error.line;
        this.stack = jsonResponse.error.trace;
    }
}

class RequestError extends Error {
    constructor(message) {
        let parser = new DOMParser();
        let doc = parser.parseFromString(message, 'text/html');

        super(doc.querySelector('.error-message').textContent+", ");
        this.name = doc.querySelector('.error-class').textContent;
        this.cause = "error on server side";
        this.code = doc.querySelector('.error-code').textContent;
        this.fileName = doc.querySelector('.error-file').textContent;
        this.lineNumber = doc.querySelector('.error-line').textContent;
        let trace = doc.querySelector('.error-trace');
        this.stack = [...trace.children].map((el) => el.textContent);
    }
}

fetch.json = async function (url, options = {}) {
    options.headers = options.headers || {};
    options.headers['Content-Type'] = 'application/json';

    if (options.body) {
        options.method = options.method || 'POST';
        options.body = JSON.encode(options.body || {});
    }

    const response = await fetch(url, options);
    const contentType = response.headers.get('Content-Type') || '';
    const contentLength = response.headers.get('Content-Length');

    if (
        response.status === 204 ||
        contentLength === '0' ||
        !contentLength
    ) {
        return null;
    }

    if (!contentType.includes('application/json')) {
        throw new Error('Response is not declared as application/json');
    }

    const json = await response.json();

    if (!response.ok) {
        throw new JsonRequestError(json);
    }

    return json;
};

fetch.get = function (url, options) {
    return fetch(url, options)
        .then(function(response) {
            if (response.ok) return response.text();
            return response.text().then(function (text) {
                throw new RequestError(text);
            });
        });
}

fetch.post = function (url, options) {
    options = options || {};
    options.method = 'POST';
    if (options.body instanceof Object) {
        let formData = new FormData();
        Object.keys(options.body).forEach((key) => {
            formData.append(key, options.body[key]);
        });
        options.body = formData
    }
    return fetch(url, options)
        .then(function(response) {
            if (response.ok) return response.text();
            return response.text().then(function (text) {
                throw new RequestError(text);
            });
        });
}

fetch.put = function (url, options) {
    options = options || {};
    options.method = 'PUT';
    if (options.body instanceof Object) {
        let formData = new FormData();
        Object.keys(options.body).forEach((key) => {
            formData.append(key, options.body[key]);
        });
        options.body = formData
    }
    return fetch(url, options)
        .then(function(response) {
            if (response.ok) return response.text();
            return response.text().then(function (text) {
                throw new RequestError(text);
            });
        });
}

fetch.delete = function (url, options) {
    options = options || {};
    options.method = 'DELETE';
    return fetch(url, options)
        .then(function(response) {
            if (response.ok) return response.text();
            return response.text().then(function (text) {
                throw new RequestError(text);
            });
        });
}

fetch.upload = function (url, input) {
    if (!input instanceof HTMLInputElement) {
        throw new Error('Invalid input element');
    }
    if (!input.files.length) {
        throw new Error('No files selected');
    }

    const formData = new FormData();
    [...input.files].forEach(
        (file) => formData.append(input.name, file)
    );

    const options = {
        method: 'POST',
        body: formData
    };
    
    return fetch(url, options)
        .then(function(response) {
            if (response.ok) return response.text();
            return response.text().then(function (text) {
                throw new RequestError(text);
            });
        });
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
        const self = this;
        this.events_list.forEach(function (event) {
            const elements = [...self.element.querySelectorAll('['+event+']')];
            if (self.element.hasAttribute(event)) elements.push(self.element);
            elements.forEach(function (el) {
                const fn = el.getAttribute(event);
                el.addEventListener(event.split('-')[1], async function (ev) {
                    await self[fn](ev);
                });
            });
        });
    }
}
