/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function() {

	OCA.WorkflowEngine = OCA.WorkflowEngine || {};
	OCA.WorkflowEngine.Plugins = OCA.WorkflowEngine.Plugins || {};

	OCA.WorkflowEngine.Plugins.RequestTimePlugin = {
		timezones: [
			"Europe/Berlin",
			"Europe/London"
		],
		_$element: null,
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\RequestTime',
				'name': t('workflowengine', 'Request time'),
				'operators': [
					{'operator': 'in', 'name': t('workflowengine', 'between')},
					{'operator': '!in', 'name': t('workflowengine', 'not between')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\RequestTime') {
				return;
			}

			var startTime = '09:00',
				endTime = '18:00',
				timezone = jstz.determine().name(),
				$element = $(element);

			if (_.isString(check['value']) && check['value'] !== '') {
				var value = JSON.parse(check['value']),
					splittedStart = value[0].split(' ', 2),
					splittedEnd = value[1].split(' ', 2);

				startTime = splittedStart[0];
				endTime = splittedEnd[0];
				timezone = splittedStart[1];
			}

			var valueJSON = JSON.stringify([startTime + ' ' + timezone, endTime + ' ' + timezone]);
			if (check['value'] !== valueJSON) {
				check['value'] = valueJSON;
				$element.val(valueJSON);
			}

			$element.css('display', 'none');

			$('<input>')
				.attr('type', 'text')
				.attr('placeholder', t('workflowengine', 'Start'))
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: '16:00'}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				})
				.addClass('start')
				.val(startTime)
				.insertBefore($element);
			$('<input>')
				.attr('type', 'text')
				.attr('placeholder', t('workflowengine', 'End'))
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: '16:00'}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				})
				.addClass('end')
				.val(endTime)
				.insertBefore($element);

			var timezoneInput = $('<input>')
				.attr('type', 'hidden')
				.css('width', '250px')
				.insertBefore($element)
				.val(timezone);

			timezoneInput.select2({
				allowClear: false,
				multiple: false,
				placeholder: t('workflowengine', 'Select timezone…'),
				ajax: {
					url: OC.generateUrl('apps/workflowengine/timezones'),
					dataType: 'json',
					quietMillis: 100,
					data: function (term) {
						if (term === '') {
							// Default search in the same continent...
							term = jstz.determine().name().split('/');
							term = term[0];
						}
						return {
							search: term
						};
					},
					results: function (response) {
						var results = [];
						$.each(response, function(timezone) {
							results.push({ id: timezone });
						});

						return {
							results: results,
							more: false
						};
					}
				},
				initSelection: function (element, callback) {
					callback(element.val());
				},
				formatResult: function (element) {
					return '<span>' + element.id + '</span>';
				},
				formatSelection: function (element) {
					if (!_.isUndefined(element.id)) {
						element = element.id;
					}
					return '<span>' + element + '</span>';
				}
			});

			// Has to be added after select2 for `event.target.classList`
			timezoneInput.addClass('timezone');

			$element.parent()
				.on('change', '.start', _.bind(this.update, this))
				.on('change', '.end', _.bind(this.update, this))
				.on('change', '.timezone', _.bind(this.update, this));

			this._$element = $element;
		},
		update: function(event) {
			var value = event.target.value,
				key = null;

			for (var i = 0; i < event.target.classList.length; i++) {
				key = event.target.classList[i];
			}

			if (key === null) {
				console.warn('update triggered but element doesn\'t have any class');
				return;
			}

			var data = JSON.parse(this._$element.val()),
				startTime = moment(data[0].split(' ', 2)[0], 'H:m Z'),
				endTime = moment(data[1].split(' ', 2)[0], 'H:m Z'),
				timezone = data[0].split(' ', 2)[1];

			if (key === 'start' || key === 'end') {
				var parsedDate = moment(value, ['H:m', 'h:m a'], true).format('HH:mm');

				if (parsedDate === 'Invalid date') {
					return;
				}

				var indexValue = 0;
				if (key === 'end') {
					indexValue = 1;
				}
				data[indexValue] = parsedDate + ' ' + timezone;
			}

			if (key === 'timezone') {
				data[0] = startTime.format('HH:mm') + ' ' + value;
				data[1] = endTime.format('HH:mm') + ' ' + value;
			}

			this._$element.val(JSON.stringify(data));
			this._$element.trigger('change');
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.RequestTimePlugin);
