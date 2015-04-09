/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};
OCA.LDAP = {};
OCA.LDAP.Wizard = {};

(function(){

	/**
	 * @classdesc minimalistic controller that basically makes the view render
	 *
	 * @constructor
	 */
	var WizardController = function() {};

	WizardController.prototype = {
		/**
		 * initializes the instance. Always call it after creating the instance.
		 */
		init: function() {
			this.view = false;
			this.configModel = false;
		},

		/**
		 * sets the model instance
		 *
		 * @param {OCA.LDAP.Wizard.ConfigModel} [model]
		 */
		setModel: function(model) {
			this.configModel = model;
		},

		/**
		 * sets the view instance
		 *
		 * @param {OCA.LDAP.Wizard.WizardView} [view]
		 */
		setView: function(view) {
			this.view = view;
		},

		/**
		 * makes the view render i.e. ready to be used
		 */
		run: function() {
			this.view.render();
		}
	};

	OCA.LDAP.Wizard.Controller = WizardController;
})();
