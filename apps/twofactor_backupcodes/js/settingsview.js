/* global Backbone, Handlebars, OC, _ */

(function(OC, Handlebars, $, _) {
	'use strict';

	OC.Settings = OC.Settings || {};
	OC.Settings.TwoFactorBackupCodes = OC.Settings.TwoFactorBackupCodes || {};

	var TEMPLATE = '<div>'
			+ '{{#unless enabled}}'
			+ '<button id="generate-backup-codes">' + t('twofactor_backupcodes', 'Generate backup codes') + '</button>'
			+ '{{else}}'
			+ '<p>'
			+ '{{#unless codes}}'
			+ t('twofactor_backupcodes', 'Backup codes have been generated. {{used}} of {{total}} codes have been used.')
			+ '{{else}}'
			+ t('twofactor_backupcodes', 'These are your backup codes. Please save and/or print them as you will not be able to read the codes again later')
			+ '<ul>'
			+ '{{#each codes}}'
			+ '<li class="backup-code">{{this}}</li>'
			+ '{{/each}}'
			+ '</ul>'
			+ '<a href="{{download}}" class="button"  download="Nextcloud-backup-codes.txt">' + t('twofactor_backupcodes', 'Save backup codes') + '</a>'
			+ '<button id="print-backup-codes" class="button">' + t('twofactor_backupcodes', 'Print backup codes') + '</button>'
			+ '{{/unless}}'
			+ '</p>'
			+ '<p>'
			+ '<button id="generate-backup-codes">' + t('twofactor_backupcodes', 'Regenerate backup codes') + '</button>'
			+ '</p>'
			+ '<p>'
			+ t('twofactor_backupcodes', 'If you regenerate backup codes, you automatically invalidate old codes.')
			+ '</p>'
			+ '{{/unless}}'
			+ '</div';

	/**
	 * @class OC.Settings.TwoFactorBackupCodes.View
	 */
	var View = OC.Backbone.View.extend({

		/**
		 * @type {undefined|Function}
		 */
		_template: undefined,

		/**
		 * @param {Object} data
		 * @returns {string}
		 */
		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},

		/**
		 * @type {boolean}
		 */
		_loading: undefined,

		/**
		 * @type {boolean}
		 */
		_enabled: undefined,

		/**
		 * @type {Number}
		 */
		_total: undefined,

		/**
		 * @type {Number}
		 */
		_used: undefined,

		/**
		 * @type {Array}
		 */
		_codes: undefined,

		events: {
			'click #generate-backup-codes': '_onGenerateBackupCodes',
			'click #print-backup-codes': '_onPrintBackupCodes'
		},

		/**
		 * @returns {undefined}
		 */
		initialize: function() {
			this._load();
		},

		/**
		 * @returns {self}
		 */
		render: function() {
			this.$el.html(this.template({
				enabled: this._enabled,
				total: this._total,
				used: this._used,
				codes: this._codes,
				download: this._getDownloadData()
			}));

			return this;
		},

		/**
		 * @private
		 * @returns {String}
		 */
		_getDownloadData: function() {
			if (!this._codes) {
				return '';
			}
			return 'data:text/plain,' + encodeURIComponent(_.reduce(this._codes, function(prev, code) {
				return prev + code + '\r\n';
			}, ''));
		},

		/**
		 * @private
		 * @returns {String}
		 */
		_getPrintData: function() {
			if (!this._codes) {
				return '';
			}
			return _.reduce(this._codes, function(prev, code) {
				return prev + code + "<br>";
			}, '');
		},

		/**
		 * Load codes from the server
		 *
		 * @returns {undefined}
		 */
		_load: function() {
			this._loading = true;

			var url = OC.generateUrl('/apps/twofactor_backupcodes/settings/state');
			var loading = $.ajax(url, {
				method: 'GET'
			});

			$.when(loading).done(function(data) {
				this._enabled = data.enabled;
				this._total = data.total;
				this._used = data.used;
			}.bind(this));
			$.when(loading).always(function() {
				this._loading = false;
				this.render();
			}.bind(this));
		},

		/**
		 * Event handler to generate the codes
		 *
		 * @returns {undefined}
		 */
		_onGenerateBackupCodes: function() {
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this._onGenerateBackupCodes, this));
				return;
			}

			// Hide old codes
			this._enabled = false;
			this.render();
			$('#generate-backup-codes').addClass('icon-loading-small');
			var url = OC.generateUrl('/apps/twofactor_backupcodes/settings/create');
			$.ajax(url, {
				method: 'POST'
			}).done(function(data) {
				this._enabled = data.state.enabled;
				this._total = data.state.total;
				this._used = data.state.used;
				this._codes = data.codes;
				this.render();
			}.bind(this)).fail(function() {
				OC.Notification.showTemporary(t('twofactor_backupcodes', 'An error occurred while generating your backup codes'));
				$('#generate-backup-codes').removeClass('icon-loading-small');
			});
		},

		/**
		 * Event handler to print the codes
		 *
		 * @returns {undefined}
		 */
		_onPrintBackupCodes: function() {
			var data = this._getPrintData();
			var newTab = window.open('', t('twofactor_backupcodes', 'Nextcloud backup codes'));
			newTab.document.write('<h1>' + t('twofactor_backupcodes', 'Nextcloud backup codes') + '</h1>');
			newTab.document.write(data);
			newTab.print();
			newTab.close();
		}

	});

	OC.Settings.TwoFactorBackupCodes.View = View;

})(OC, Handlebars, $, _);
