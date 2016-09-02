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

	OCA.WorkflowEngine.Plugins.FileMimeTypePlugin = {
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\FileMimeType',
				'name': t('workflowengine', 'File mime type'),
				'operators': [
					{'operator': 'is', 'name': t('workflowengine', 'is')},
					{'operator': '!is', 'name': t('workflowengine', 'is not')},
					{'operator': 'matches', 'name': t('workflowengine', 'matches')},
					{'operator': '!matches', 'name': t('workflowengine', 'does not match')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\FileMimeType') {
				return;
			}

			var placeholder = t('workflowengine', 'text/plain');
			if (check['operator'] === 'matches' || check['operator'] === '!matches') {
				placeholder = t('workflowengine', '/^text\\/(plain|html)$/i');

				if (this._validateRegex(check['value'])) {
					$(element).removeClass('invalid-input');
				} else {
					$(element).addClass('invalid-input');
				}
			}

			$(element).css('width', '250px')
				.attr('placeholder', placeholder)
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: placeholder}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				});
		},

		_validateRegex: function(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/,
				result = regexRegex.exec(string);
			return result !== null;
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.FileMimeTypePlugin);
