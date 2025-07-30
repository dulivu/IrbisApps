class RecordSet extends Array {
    constructor (name) {
        super();
        this.name = name;
        this.current = null;
        this.length = 0;
    }

    byId (id) {
        return this.find(rec => parseInt(rec.id) === parseInt(id)) || null;
    }

    ids () {
        return this.map(rec => rec.id);
    }

    async selectById (id) {
        let url = `/record/${this.name}/${id}`;
        let record = await fetch.json(url);

        record = new Record(record, this);
        this.push(record);
        return record;
    }

    async select (where, limit) {
        if (parseInt(where) && !isNaN(where))
            return await this.selectById(parseInt(where));
        limit = limit || '0-50';

        let url = `/record/${this.name}/select/${limit}`;
        let query = where ? '?' + JSON.toQueryString(where) : '';

        let records = await fetch.json(url + query);
        records = records.map((record) => new Record(record, this));
        this.push(...records);
        return records;
    }

    async insert (inserts) {
        let url = `/record/${this.name}/insert`;
        let params = { method: 'POST', body: inserts };
        let records = await fetch.json(url, params);
        records = records.map((record) => new Record(record, this));
        this.push(...records);
        return records;
    }

    async update (updates, ids) {
        updates = updates || {};
        let _ids = ids || this.ids();

        let url = `/record/${this.name}/update/${_ids.join(',')}`;
        let params = { method: 'PUT', body: updates };
        let records = await fetch.json(url, params);
    }
}


class Record {
    constructor (values, recordset) {
        return new Proxy(values, this.handler(recordset));
    }

    handler (recordset) {
        return {
            target: null,
            recordset: recordset,

            async update (updates) {
                let target = this.target;
                let updated = await this.recordset.update(updates, [target.id]);
            },

            get (target, prop) {
                this.target = target;
                if (typeof this[prop] === 'function')
                    return this[prop].bind(this);
                return target[prop] || null;
            },
        
            set (target, prop, value) {
                let update = {};
                update[prop] = value;
                this.recordset
                    .update(update, [target.id])
                    .then(() => {
                        target[prop] = value;
                    });
                target[prop] = value;
                return true;
            }
        }
    }
}
