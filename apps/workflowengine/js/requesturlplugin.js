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

	OCA.WorkflowEngine.Plugins.RequestURLPlugin = {
		predefinedValues: ['webdav'],
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\RequestURL',
				'name': t('workflowengine', 'Request URL'),
				'operators': [
					{'operator': 'is', 'name': t('workflowengine', 'is')},
					{'operator': '!is', 'name': t('workflowengine', 'is not')},
					{'operator': 'matches', 'name': t('workflowengine', 'matches')},
					{'operator': '!matches', 'name': t('workflowengine', 'does not match')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\RequestURL') {
				return;
			}

			var placeholder = t('workflowengine', 'https://localhost/index.php');

			if (check['operator'] === 'matches' || check['operator'] === '!matches') {
				placeholder = t('workflowengine', '/^https\\:\\/\\/localhost\\/index\\.php$/i');
			}

			$(element).css('width', '250px')
				.attr('placeholder', placeholder)
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: placeholder}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				});

			if (check['operator'] === 'matches' || check['operator'] === '!matches') {
				if (this._validateRegex(check['value'])) {
					$(element).removeClass('invalid-input');
				} else {
					$(element).addClass('invalid-input');
				}
			} else {
				var self = this,
					data = [
					{
						text: t('workflowengine', 'Predefined URLs'),
						children: [
							{id: 'webdav', text: t('workflowengine', 'Files WebDAV')}
						]
					}
				];
				if (this.predefinedValues.indexOf(check['value']) === -1) {
					data.unshift({
						id: check['value'],
						text: check['value']
					})
				}


				$(element).select2({
					data: data,
					createSearchChoice: function(term) {
						if (self.predefinedValues.indexOf(check['value']) === -1) {
							return {
								id: term,
								text: term
							};
						}
					},
					id: function(element) {
						return element.id;
					},
					formatResult: function (tag) {
						return tag.text;
					},
					formatSelection: function (tag) {
						return tag.text;
					},
					escapeMarkup: function(m) {
						return m;
					}
				})
			}
		},

		_validateRegex: function(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/,
				result = regexRegex.exec(string);
			return result !== null;
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.RequestURLPlugin);
