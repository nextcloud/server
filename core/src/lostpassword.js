import $ from 'jquery'

import OC from './OC/index'

const sendErrorMsg = t('core', 'Couldn\'t send reset email. Please contact your administrator.');
const sendSuccessMsg = t('core', 'We have send a password reset e-mail to the e-mail address known to us for this account. If you do not receive it within a reasonable amount of time, check your spam/junk folders.<br>If it is not there ask your local administrator.');
const encryptedMsg = t('core', "Your files are encrypted. There will be no way to get your data back after your password is reset.<br />If you are not sure what to do, please contact your administrator before you continue. <br />Do you really want to continue?")
	+ ('<br /><input type="checkbox" id="encrypted-continue" class="checkbox checkbox--white" value="Yes" />')
	+ '<label for="encrypted-continue">'
	+ t('core', 'I know what I\'m doing')
	+ '</label><br />';
const resetErrorMsg = t('core', 'Password can not be changed. Please contact your administrator.');

function init () {
	$('#lost-password[href=""]').click(resetLink);
	$('#lost-password-back').click(backToLogin);
	$('form[name=login]').submit(onSendLink);
	$('#reset-password #submit').click(resetPassword);
	resetButtons();
}

function resetButtons () {
	$('#reset-password-wrapper .submit-icon')
		.addClass('icon-confirm-white')
		.removeClass('icon-loading-small-dark');
	$('#reset-password-submit')
		.attr('value', t('core', 'Reset password'))
		.prop('disabled', false);
	$('#user').prop('disabled', false);
	$('.login-additional').fadeIn();
}

function backToLogin (event) {
	event.preventDefault();

	$('#reset-password-wrapper').slideUp().fadeOut();
	$('#lost-password').slideDown().fadeIn();
	$('#lost-password-back').hide();
	$('.remember-login-container').slideDown().fadeIn();
	$('#submit-wrapper').slideDown().fadeIn();
	$('.groupbottom').slideDown().fadeIn();
	$('#user').parent().addClass('grouptop');
	$('#password').attr('required', true);
	$('form[name=login]').removeAttr('action');
	$('#user').focus();
}

function resetLink (event) {
	event.preventDefault();

	$('#lost-password').hide();
	$('.wrongPasswordMsg').hide();
	$('#lost-password-back').slideDown().fadeIn();
	$('.remember-login-container').slideUp().fadeOut();
	$('#submit-wrapper').slideUp().fadeOut();
	$('.groupbottom').slideUp().fadeOut(function () {
		$('#user').parent().removeClass('grouptop');
	});
	$('#reset-password-wrapper').slideDown().fadeIn();
	$('#password').attr('required', false);
	$('form[name=login]').attr('action', 'lostpassword/email');
	$('#user').focus();

	// Generate a browser warning for required fields if field empty
	if ($('#user').val().length === 0) {
		$('#submit').trigger('click');
	} else {
		if (OC.config.lost_password_link === 'disabled') {
			return;
		} else if (OC.config.lost_password_link) {
			window.location = OC.config.lost_password_link;
		} else {
			onSendLink();
		}
	}
}

function onSendLink (event) {
	// Only if password reset form is active
	if ($('form[name=login][action]').length === 1) {
		if (event) {
			event.preventDefault();
		}
		$('#reset-password-wrapper .submit-icon')
			.removeClass('icon-confirm-white')
			.addClass('icon-loading-small-dark');
		$('#reset-password-submit')
			.attr('value', t('core', 'Sending email …'))
			.prop('disabled', true);
		$('#user').prop('disabled', true);
		$('.login-additional').fadeOut();
		$.post(
			OC.generateUrl('/lostpassword/email'),
			{
				user: $('#user').val()
			},
			sendLinkDone
		).fail(function () {
			sendLinkError(sendErrorMsg);
		});
	}
}

function sendLinkDone (result) {
	var sendErrorMsg;

	if (result && result.status === 'success') {
		sendLinkSuccess();
	} else {
		if (result && result.msg) {
			sendErrorMsg = result.msg;
		} else {
			sendErrorMsg = sendErrorMsg;
		}
		sendLinkError(sendErrorMsg);
	}
}

function sendLinkSuccess (msg) {
	const node = getSendStatusNode();
	// update is the better success message styling
	node.addClass('update').css({width: 'auto'});
	node.html(sendSuccessMsg);
	resetButtons();
}

function sendLinkError (msg) {
	const node = getSendStatusNode();
	node.addClass('warning');
	node.html(msg);
	resetButtons();
}

function getSendStatusNode () {
	if (!$('#lost-password').length) {
		$('<p id="lost-password"></p>').insertBefore($('#remember_login'));
	} else {
		$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
	}
	return $('#lost-password');
}

function resetPassword (event) {
	event.preventDefault();
	if ($('#password').val()) {
		$.post(
			$('#password').parents('form').attr('action'),
			{
				password: $('#password').val(),
				proceed: $('#encrypted-continue').is(':checked') ? 'true' : 'false'
			},
			resetDone
		);
	}
	if ($('#encrypted-continue').is(':checked')) {
		$('#reset-password #submit').hide();
		$('#reset-password #float-spinner').removeClass('hidden');
	}
}

function resetDone (result) {
	if (result && result.status === 'success') {
		redirect('/login?user=' + result.user);
	} else {
		if (result && result.msg) {
			resetError(result.msg);
		} else if (result && result.encryption) {
			resetError(encryptedMsg);
		} else {
			resetError(resetErrorMsg);
		}
		resetError(resetErrorMsg);
	}
}

function redirect (url) {
	window.location = OC.generateUrl(url);
}

function resetError (msg) {
	var node = getResetStatusNode();
	node.addClass('warning');
	node.html(msg);
}

function getResetStatusNode () {
	if (!$('#lost-password').length) {
		$('<p id="lost-password"></p>').insertBefore($('#reset-password fieldset'));
	} else {
		$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
	}
	return $('#lost-password');
}

$(document).ready(init);
