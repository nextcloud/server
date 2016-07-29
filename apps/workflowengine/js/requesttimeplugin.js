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

			var startTime = '16:00',
				endTime = '18:00',
				timezone = 'Europe/London',
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
				.addClass('start')
				.val(startTime)
				.insertBefore($element);
			$('<input>')
				.attr('type', 'text')
				.attr('placeholder', t('workflowengine', 'End'))
				.addClass('end')
				.val(endTime)
				.insertBefore($element);

			var timezoneSelect = $('<select>')
				.addClass('timezone');
			_.each(this.timezones, function(timezoneName){
				var timezoneElement = $('<option>').val(timezoneName).html(timezoneName);

				if (timezoneName === timezone) {
					timezoneElement.attr('selected', 'selected');
				}

				timezoneSelect.append(timezoneElement);
			});
			timezoneSelect.insertBefore($element);

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
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.RequestTimePlugin);
