
/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {
	let initializing = false
	const superPattern = /xyz/.test(function() { xyz }) ? /\b_super\b/ : /.*/

	/**
	 * @classdesc a base class that allows inheritance
	 *
	 * @abstrcact
	 * @constructor
	 */
	const WizardObject = function() {}
	WizardObject.subClass = function(properties) {
		const _super = this.prototype

		initializing = true
		const proto = new this()
		initializing = false

		for (const name in properties) {
			proto[name]
				= typeof properties[name] === 'function'
				&& typeof _super[name] === 'function'
				&& superPattern.test(properties[name])
					? (function(name, fn) {
						return function() {
							const tmp = this._super
							this._super = _super[name]
							const ret = fn.apply(this, arguments)
							this._super = tmp
							return ret
						}
					})(name, properties[name])
					: properties[name]
		}

		function Class() {
			if (!initializing && this.init) {
				this.init.apply(this, arguments)
			}
		}

		Class.prototype = proto
		Class.constructor = Class
		Class.subClass = arguments.callee
		return Class
	}

	WizardObject.constructor = WizardObject

	OCA.LDAP.Wizard.WizardObject = WizardObject
})()
