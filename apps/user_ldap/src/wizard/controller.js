/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {}
OCA.LDAP = {}
OCA.LDAP.Wizard = {};

(function() {

	/**
	 * @classdesc minimalistic controller that basically makes the view render
	 *
	 * @constructor
	 */
	const WizardController = function() {}

	WizardController.prototype = {
		/**
		 * initializes the instance. Always call it after creating the instance.
		 */
		init() {
			this.view = false
			this.configModel = false
		},

		/**
		 * sets the model instance
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} [model]
		 */
		setModel(model) {
			this.configModel = model
		},

		/**
		 * sets the view instance
		 *
		 * @param {OCA.LDAP.Wizard.WizardView} [view]
		 */
		setView(view) {
			this.view = view
		},

		/**
		 * makes the view render i.e. ready to be used
		 */
		run() {
			this.view.render()
		},
	}

	OCA.LDAP.Wizard.Controller = WizardController
})()
