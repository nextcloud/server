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

/**
 * @namespace
 * @since 9.2.0
 */
OCP.AppConfig = {
	/**
	 * @param {string} method
	 * @param {string} endpoint
	 * @param {Object} [options]
	 * @param {Object} [options.data]
	 * @param {function} [options.success]
	 * @param {function} [options.error]
	 * @internal
	 */
	_call: function(method, endpoint, options) {

		$.ajax({
			type: method.toUpperCase(),
			url: OC.linkToOCS('apps/provisioning_api/api/v1', 2) + 'config/apps' + endpoint,
			data: options.data || {},
			success: options.success,
			error: options.error
		})
	},

	/**
	 * @param {Object} [options]
	 * @param {function} [options.success]
	 * @since 9.2.0
	 */
	getApps: function(options) {
		this._call('get', '', options);
	},

	/**
	 * @param {string} app
	 * @param {Object} [options]
	 * @param {function} [options.success]
	 * @param {function} [options.error]
	 * @since 9.2.0
	 */
	getKeys: function(app, options) {
		this._call('get', '/' + app, options);
	},

	/**
	 * @param {string} app
	 * @param {string} key
	 * @param {string|function} defaultValue
	 * @param {Object} [options]
	 * @param {function} [options.success]
	 * @param {function} [options.error]
	 * @since 9.2.0
	 */
	getValue: function(app, key, defaultValue, options) {
		options = options || {};
		options['data'] = {
			defaultValue: defaultValue
		};

		this._call('get', '/' + app + '/' + key, options);
	},

	/**
	 * @param {string} app
	 * @param {string} key
	 * @param {string} value
	 * @param {Object} [options]
	 * @param {function} [options.success]
	 * @param {function} [options.error]
	 * @since 9.2.0
	 */
	setValue: function(app, key, value, options) {
		options = options || {};
		options['data'] = {
			value: value
		};

		this._call('post', '/' + app + '/' + key, options);
	},

	/**
	 * @param {string} app
	 * @param {string} key
	 * @param {Object} [options]
	 * @param {function} [options.success]
	 * @param {function} [options.error]
	 * @since 9.2.0
	 */
	deleteKey: function(app, key, options) {
		this._call('delete', '/' + app + '/' + key, options);
	}
};
