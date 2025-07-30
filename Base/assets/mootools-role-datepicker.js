Roles.register('calendar', {
	properties: {
		head: { get: function () { return this.retrieve('table').getElement('thead'); }},
		body: { get: function () { return this.retrieve('table').getElement('tbody'); }},
		internal: {
			get: function () { return this.retrieve('internal', new Date()); },
			set: function (val) { this.store('internal', val || new Date()); }
		},
		mode: {
			get: function () { return this.get('data-mode') || this.modes[0]; },
			set: function (v) { 
				var i = this.modes.indexOf(v);
				if (i === -1) return;
				this.set('data-mode', v); 
				this[('render-' + v).camelCase()]();
			}
		},
		modes: { 
			get: function () { 
				var modes = this.get('data-modes') || 'days,months';
				modes = modes.split(',').map(function (i) {return i.trim();});
				return Object.keys(this.availableModes).filter(function (m) { return modes.indexOf(m) !== -1; });
			},
			set: function (v) { 
				v = typeOf(v) == 'array' ? v.join(',') : v;
				this.set('data-modes', v);
				this.render();
			}
		},
		availableModes: {
			get: function () { return {
				time: { interval: 'day', step: 1 },
				days: { interval: 'month', step: 1 },
				months: { interval: 'year', step: 1 },
				years: { interval: 'year', step: 9 }
			}}
		},
		format: { 
			get: function () { return this.get('data-format') || '%x'; },
			set: function (v) { return this.set('data-format', v); }
		},
		showWeeks: { 
			get: function () { return this.get('data-show-weeks') !== null; },
			set: function (v) { if (v) this.set('data-show-weeks', '1'); else this.erase('data-show-weeks'); },
		}
	},

	initialize: function (self) {
		this.store('table', Element.create('table', {
			'onClick:relay(tr.is-pickable>td)': function () { self.render(this); },
			'onWheel:relay(tr.is-display-time>th)': function (e) {
				var max = this.hasClass('is-hours') ? 23 : 59;
				if (e.event.deltaY < 0)
					this.set('text', (this.get('text').toInt() < max ? this.get('text').toInt()+1: 0).toString().pad(2, '0','left'));
				else this.set('text', (this.get('text').toInt() > 0 ? this.get('text').toInt()-1: max).toString().pad(2, '0','left'));
			}
		}, [ 
			['thead', [
				['tr', [
					['th', [['a', {text: '<<', onClick: function () { self.render('decrement'); }}]]],
					['th.is-title', {text: 'datepicker', onClick: function () { self.render('up'); }}],
					['th', [['a', {text: '>>', onClick: function () { self.render('increment'); }}]]]
				]]
			]], 
			['tbody']
		]).inject(this));

		this.mode = this.get('data-mode') || this.modes[0];
	},

	render: function (p) {
		if (p == 'increment' || p == 'decrement') {
			var mode = this.availableModes[this.mode];
			this.internal[p](mode.interval, mode.step);
			this.mode = this.mode;
		} 
		else if (p == 'up') {
			var i = this.modes.indexOf(this.mode);
			i = i !== -1 && i < this.modes.length-1 ? i+1 : 0;
			this.mode = this.modes[i];
		} 
		else if (typeOf(p) == 'element' && p.get('tag') == 'td') {
			var i = this.modes.indexOf(this.mode);
			if (i !== 0) {
				i = i !== -1 && i < this.modes.length ? i-1 : 0;
				this.internal = this.cloneInternal(p.get('text'));
				this.mode = this.modes[i];
			} else {
				var date = this.cloneInternal(p.get('text'));
				this.fireEvent('pick', [date.format(this.format), date]);
			}
		} 
		else {
			if (p) this.mode = p;
			else this.mode = this.mode;
		}
		return this;
	},

	renderDays: function () {
		this.body.erase('class').addClass('is-days-mode').empty();
		this.head.getElements('th')[1]
			.set('text', Locale.get('Date.months')[this.internal.get('month')]+' '+this.internal.get('year'))
			.set('colspan', this.showWeeks ? '6' : '5');

		var heads = new Element('tr').inject(this.body);
		if (this.showWeeks)
			heads.grab(new Element('th.is-week-name', {text: Locale.get('Date.week_abbr')}));

		var days = Locale.get('Date.days_abbr').clone();
		var idays = [0,1,2,3,4,5,6];
		if (Locale.get('Date.firstDayOfWeek')) { days.push(days.shift()); idays.push(idays.shift()); }
		days.each(function (day) { heads.grab(new Element('th.is-day-name', {text: day.substring(0, 2)})); });

		var d = this.internal.clone().set('Date', 1);
		var lastdayofmonth = d.get('lastdayofmonth');
		var fpos = idays.indexOf(d.get('day'));
		var lpos = idays.indexOf(d.set('Date', lastdayofmonth).get('day'));
		var t = 6 + fpos + lastdayofmonth - lpos;
		var p = d.decrement('month', 1).get('lastdayofmonth');
		var x = 0, tr;

		d.set('Date', p).decrement('day', fpos - 1);
		for (var i = 0; i < t; i++) {
			var td = 'td.is-out';
			if (d.get('month') == this.internal.get('month')) {
				 td = 'td'; if (d.diff(new Date(), 'day') == 0) td = 'td.is-now';
			}

			if (x == 0) {
				tr = new Element('tr.is-pickable').inject(this.body)
				if (this.showWeeks)
					tr.grab(new Element('th.is-week', {text: d.get('week')}).store('date', d.clone()));
			}

			new Element(td, { text: d.get('Date') }).store('date', d.clone()).inject(tr);
			x++; d.increment();
			if (x == 7) x = 0;
		}
	},

	renderMonths: function () {
		this.head.getElements('th')[1]
			.set('text', this.internal.get('year'))
			.erase('colspan');

		this.body.erase('class').addClass('is-months-mode').empty();
		var d = this.internal.clone().set('month', 0);
		(4).times(function (i) {
			(3).times(function (y) {
				this.grab(new Element(
					d.get('month') - new Date().get('month') == 0 && d.get('year') == new Date().get('year') ? 'td.is-now' : 'td',
					{text: Locale.get('Date.months')[(i*3)+y]})
				);
				d.increment('month', 1);
			}, new Element('tr.is-pickable').inject(this.body));
		}, this);
	},

	renderYears: function () {
		this.body.erase('class').addClass('is-years-mode').empty();
		this.head.getElements('th')[1]
			.set('text', (this.internal.get('year')-4)+' - '+(this.internal.get('year')+4))
			.erase('colspan');

		var d = this.internal.clone().decrement('year', 4);
		(3).times(function () {
			(3).times(function () {
				this.grab(new Element(d.diff(new Date(), 'year') == 0 ? 'td.is-now' : 'td', {text: d.get('year')}));
				d.increment('year', 1);
			}, new Element('tr.is-pickable').inject(this.body));
		}, this);
	},

	renderTime: function () {
		var date = this.internal.format('%A %d/%m/%y');
		this.head.getElements('th')[1].set('text', date).erase('colspan');

		if (this.body.hasClass('is-time-mode')) return;

		var h = new Element('th.is-hours', { text: new Date().get('Hours').toString().pad(2, '0','left') });
		var m = new Element('th.is-minutes', { text: new Date().get('Minutes').toString().pad(2, '0','left') });
		var s = new Element('th.is-seconds', { text: new Date().get('Seconds').toString().pad(2, '0','left') });

		this.body.erase('class').addClass('is-time-mode').empty();

		Element.create('tr', [
			['td', {text: '˄', onClick: function () { h.set('text', (h.get('text').toInt() < 23 ? h.get('text').toInt()+1: 0).toString().pad(2, '0','left')); }}],
			['td', {text: '˄', onClick: function () { m.set('text', (m.get('text').toInt() < 59 ? m.get('text').toInt()+1: 0).toString().pad(2, '0','left')); }}],
			['td', {text: '˄', onClick: function () { s.set('text', (s.get('text').toInt() < 59 ? s.get('text').toInt()+1: 0).toString().pad(2, '0','left')); }}],
		]).inject(this.body);

		Element.create('tr.is-display-time', [h, m, s]).inject(this.body);

		Element.create('tr', [
			['td', {text: '˅', onClick: function () { h.set('text', (h.get('text').toInt() > 0 ? h.get('text').toInt()-1: 23).toString().pad(2, '0','left')); }}],
			['td', {text: '˅', onClick: function () { m.set('text', (m.get('text').toInt() > 0 ? m.get('text').toInt()-1: 59).toString().pad(2, '0','left')); }}],
			['td', {text: '˅', onClick: function () { s.set('text', (s.get('text').toInt() > 0 ? s.get('text').toInt()-1: 59).toString().pad(2, '0','left')); }}],
		]).inject(this.body);

		Element.create('tr.is-pickable', [
			['td[colspan=3]', {
				text: 'Ok'
			}]
		]).inject(this.body);
	},

	cloneInternal: function (factor) {
		switch (this.mode) {
			case 'time': 
				var t = this.body.getElements('tr')[1].getElements('th');
				var i = this.internal.clone()
					.set('Hours', t[0].get('text').toInt())
					.set('Minutes', t[1].get('text').toInt())
					.set('Seconds', t[2].get('text').toInt());
				return i;
			case 'days':
				return this.internal.clone().set('Date', factor);
			case 'months':
				var months = Locale.get('Date.months');
				return this.internal.clone().set('Month', months.indexOf(factor));
			case 'years':
				return this.internal.clone().set('Year', factor);
			default:
				return this.internal.clone();
		}
	}
});