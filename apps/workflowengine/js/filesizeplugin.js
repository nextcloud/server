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

	OCA.WorkflowEngine.Plugins.FileSizePlugin = {
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\FileSize',
				'name': t('workflowengine', 'File size (upload)'),
				'operators': [
					{'operator': 'less', 'name': t('workflowengine', 'less')},
					{'operator': '!greater', 'name': t('workflowengine', 'less or equals')},
					{'operator': '!less', 'name': t('workflowengine', 'greater or equals')},
					{'operator': 'greater', 'name': t('workflowengine', 'greater')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\FileSize') {
				return;
			}

			var placeholder = '12 MB'; // Do not translate!!!
			$(element).css('width', '250px')
				.attr('placeholder', placeholder)
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: placeholder}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				});
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.FileSizePlugin);
