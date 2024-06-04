/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

OCA = OCA || {};
OCA.LDAP = _.extend(OC.LDAP || {}, {
	onRenewPassword: function () {
		$('#submit')
			.removeClass('icon-confirm-white')
			.addClass('icon-loading-small')
			.attr('value', t('core', 'Renewing …'));
		return true;
	},
});

window.addEventListener('DOMContentLoaded', function() {
	$('form[name=renewpassword]').submit(OCA.LDAP.onRenewPassword);

	if($('#newPassword').length) {
		$('#newPassword').showPassword().keyup();
	}
	$('#newPassword').strengthify({
		zxcvbn: OC.linkTo('core','vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password')
		],
		drawTitles: true,
		$addAfter: $('input[name="newPassword-clone"]'),
	});
});
