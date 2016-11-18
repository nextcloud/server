/* global Backbone, Handlebars, OC, _ */

(function (OC, Handlebars, $, _) {
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

	var View = OC.Backbone.View.extend({
		_template: undefined,
		template: function (data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},
		_loading: undefined,
		_enabled: undefined,
		_total: undefined,
		_used: undefined,
		_codes: undefined,
		events: {
			'click #generate-backup-codes': '_onGenerateBackupCodes',
			'click #print-backup-codes': '_onPrintBackupCodes',
		},
		initialize: function () {
			this._load();
		},
		render: function () {
			this.$el.html(this.template({
				enabled: this._enabled,
				total: this._total,
				used: this._used,
				codes: this._codes,
				download: this._getDownloadDataHref()
			}));
		},
		_getDownloadDataHref: function () {
			if (!this._codes) {
				return '';
			}
			return 'data:text/plain,' + encodeURIComponent(_.reduce(this._codes, function (prev, code) {
				return prev + code + "\r\n";
			}, ''));
		},
		_load: function () {
			this._loading = true;

			var url = OC.generateUrl('/apps/twofactor_backupcodes/settings/state');
			var loading = $.ajax(url, {
				method: 'GET',
			});

			$.when(loading).done(function (data) {
				this._enabled = data.enabled;
				this._total = data.total;
				this._used = data.used;
			}.bind(this));
			$.when(loading).always(function () {
				this._loading = false;
				this.render();
			}.bind(this));
		},
		_onGenerateBackupCodes: function () {
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
			}).done(function (data) {
				this._enabled = data.state.enabled;
				this._total = data.state.total;
				this._used = data.state.used;
				this._codes = data.codes;
				this.render();
			}.bind(this)).fail(function () {
				OC.Notification.showTemporary(t('twofactor_backupcodes', 'An error occurred while generating your backup codes'));
				$('#generate-backup-codes').removeClass('icon-loading-small');
			});
		},
		_onPrintBackupCodes: function () {
			var url = this._getDownloadDataHref();
			window.open(url, t('twofactor_backupcodes', 'Nextcloud backup codes'));
			window.print();
			window.close();
		}
	});

	OC.Settings.TwoFactorBackupCodes.View = View;

})(OC, Handlebars, $, _);
