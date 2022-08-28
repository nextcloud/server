/* global OC */

/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 *               2013, Morris Jobke <morris.jobke@gmail.com>
 *               2016, Christoph Wurst <christoph@owncloud.com>
 *               2017, Arthur Schiwon <blizzz@arthur-schiwon.de>
 *               2017, Thomas Citharel <tcit@tcit.fr>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Settings = OC.Settings || {};

/**
 * The callback will be fired as soon as enter is pressed by the
 * user or 1 second after the last data entry
 *
 * @param {any} callback -
 * @param allowEmptyValue if this is set to true the callback is also called when the value is empty
 */
jQuery.fn.keyUpDelayedOrEnter = function (callback, allowEmptyValue) {
	var cb = callback;
	var that = this;

	this.on('input', _.debounce(function (event) {
		// enter is already handled in keypress
		if (event.keyCode === 13) {
			return;
		}
		if (allowEmptyValue || that.val() !== '') {
			cb(event);
		}
	}, 1000));

	this.keypress(function (event) {
		if (event.keyCode === 13 && (allowEmptyValue || that.val() !== '')) {
			event.preventDefault();
			cb(event);
		}
	});
};

window.addEventListener('DOMContentLoaded', function () {
	if($('#pass2').length) {
		$('#pass2').showPassword().keyup();
	}

	var showVerifyDialog = function(dialog, howToVerify, verificationCode) {
		var dialogContent = dialog.children('.verification-dialog-content');
		dialogContent.children(".explainVerification").text(howToVerify);
		dialogContent.children(".verificationCode").text(verificationCode);
		dialog.css('display', 'block');
	};

	$(".verify").click(function (event) {

		event.stopPropagation();

		var verify = $(this);
		var indicator = $(this).children('img');
		var accountId = indicator.attr('id');
		var status = indicator.data('status');

		var onlyVerificationCode = false;
		if (parseInt(status) === 1) {
			onlyVerificationCode = true;
		}

		if (indicator.hasClass('verify-action')) {
			$.ajax(
				OC.generateUrl('/settings/users/{account}/verify', {account: accountId}),
				{
					method: 'GET',
					data: {onlyVerificationCode: onlyVerificationCode}
				}
			).done(function (data) {
				var dialog = verify.children('.verification-dialog');
				showVerifyDialog($(dialog), data.msg, data.code);
				indicator.attr('data-origin-title', t('settings', 'Verifying …'));
				indicator.attr('src', OC.imagePath('core', 'actions/verifying.svg'));
				indicator.data('status', '1');
			});
		}

	});

	// When the user clicks anywhere outside of the verification dialog we close it
	$(document).click(function(event){
		var element = event.target;
		var isDialog = $(element).hasClass('verificationCode')
			|| $(element).hasClass('explainVerification')
			|| $(element).hasClass('verification-dialog-content')
			|| $(element).hasClass('verification-dialog');
		if (!isDialog) {
			$(document).find('.verification-dialog').css('display', 'none');
		}
	});


	var settingsEl = $('#personal-settings')
	var userSettings = new OC.Settings.UserSettings();
	var federationSettingsView = new OC.Settings.FederationSettingsView({
		el: settingsEl,
		config: userSettings,
		showFederatedScope: !!settingsEl.data('federation-enabled'),
		showPublishedScope: !!settingsEl.data('lookup-server-upload-enabled'),
	});

	federationSettingsView.render();
});
