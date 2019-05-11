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

	OCA.WorkflowEngine.Plugins.RequestRemoteAddressPlugin = {
		getCheck: function() {
			return {
				'class': 'OCA\\WorkflowEngine\\Check\\RequestRemoteAddress',
				'name': t('workflowengine', 'Request remote address'),
				'operators': [
					{'operator': 'matchesIPv4', 'name': t('workflowengine', 'matches IPv4')},
					{'operator': '!matchesIPv4', 'name': t('workflowengine', 'does not match IPv4')},
					{'operator': 'matchesIPv6', 'name': t('workflowengine', 'matches IPv6')},
					{'operator': '!matchesIPv6', 'name': t('workflowengine', 'does not match IPv6')}
				]
			};
		},
		render: function(element, check) {
			if (check['class'] !== 'OCA\\WorkflowEngine\\Check\\RequestRemoteAddress') {
				return;
			}

			var placeholder = '127.0.0.1/32'; // Do not translate!!!
			if (check['operator'] === 'matchesIPv6' || check['operator'] === '!matchesIPv6') {
				placeholder = '::1/128'; // Do not translate!!!
				if (this._validateIPv6(check['value'])) {
					$(element).removeClass('invalid-input');
				} else {
					$(element).addClass('invalid-input');
				}
			} else {
				if (this._validateIPv4(check['value'])) {
					$(element).removeClass('invalid-input');
				} else {
					$(element).addClass('invalid-input');
				}
			}

			$(element).css('width', '300px')
				.attr('placeholder', placeholder)
				.attr('title', t('workflowengine', 'Example: {placeholder}', {placeholder: placeholder}))
				.addClass('has-tooltip')
				.tooltip({
					placement: 'bottom'
				});
		},

		_validateIPv4: function(string) {
			var regexRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(3[0-2]|[1-2][0-9]|[1-9])$/,
				result = regexRegex.exec(string);
			return result !== null;
		},

		_validateIPv6: function(string) {
			var regexRegex = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))\/(1([01][0-9]|2[0-8])|[1-9][0-9]|[0-9])$/,
				result = regexRegex.exec(string);
			return result !== null;
		}
	};
})();

OC.Plugins.register('OCA.WorkflowEngine.CheckPlugins', OCA.WorkflowEngine.Plugins.RequestRemoteAddressPlugin);
