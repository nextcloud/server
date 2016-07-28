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

			var placeholder = '["10:00 Europe\\/Berlin","16:00 Europe\\/Berlin"]'; // FIXME need a time picker JS plugin
			$(element).css('width', '300px')
				.attr('placeholder', placeholder)
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: placeholder}, undefined, {escape: false}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				});
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.RequestTimePlugin);
