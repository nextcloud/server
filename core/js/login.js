/**
 * Copyright (c) 2015
 *  Vincent Petry <pvince81@owncloud.com>
 *  Jan-Christoph Borchardt, http://jancborchardt.net
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * @namespace
 * @memberOf OC
 */
OC.Login = _.extend(OC.Login || {}, {
	onLogin: function () {
		// Only if password reset form is not active
		if($('form[name=login][action]').length === 0) {
			$('#submit-wrapper .submit-icon')
				.removeClass('icon-confirm-white')
				.addClass('icon-loading-small-dark');
			$('#submit')
				.attr('value', t('core', 'Logging in …'));
			$('.login-additional').fadeOut();
			return true;
		}
		return false;
	},

	rememberLogin: function(){
		if($(this).is(":checked")){
			if($("#user").val() && $("#password").val()) {
				$('#submit').trigger('click');
			}
		}
	}
});

$(document).ready(function() {
	$('form[name=login]').submit(OC.Login.onLogin);

	$('#remember_login').click(OC.Login.rememberLogin);
});
