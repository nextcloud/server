/**
 * Copyright (c) 2015
 *  Vincent Petry <pvince81@owncloud.com>
 *  Jan-Christoph Borchardt, http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

import $ from 'jquery'

import './lostpassword'
import './Util/visitortimezone'

function onLogin () {
	// Only if password reset form is not active
	if ($('form[name=login][action]').length === 0) {
		$('#submit-wrapper .submit-icon')
			.removeClass('icon-confirm-white')
			.addClass(OCA.Theming && OCA.Theming.inverted
				? 'icon-loading-small'
				: 'icon-loading-small-dark');
		$('#submit')
			.attr('value', t('core', 'Logging in …'));
		$('.login-additional').fadeOut();
		return true;
	}
	return false;
}

function rememberLogin () {
	if ($(this).is(":checked")) {
		if ($("#user").val() && $("#password").val()) {
			$('#submit').trigger('click');
		}
	}
}

$(document).ready(function () {
	$('form[name=login]').submit(onLogin);

	$('#remember_login').click(rememberLogin);

	const clearParamRegex = new RegExp('clear=1');
	if (clearParamRegex.test(window.location.href)) {
		window.localStorage.clear();
		window.sessionStorage.clear();
	}
});
