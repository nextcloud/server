
OC.Lostpassword = {
	sendErrorMsg : t('core', 'Couldn\'t send reset email. Please contact your administrator.'),

	sendSuccessMsg : t('core', 'The link to reset your password has been sent to your email. If you do not receive it within a reasonable amount of time, check your spam/junk folders.<br>If it is not there ask your local administrator.'),

	encryptedMsg : t('core', "Your files are encrypted. There will be no way to get your data back after your password is reset.<br />If you are not sure what to do, please contact your administrator before you continue. <br />Do you really want to continue?")
			+ ('<br /><input type="checkbox" id="encrypted-continue" value="Yes" />')
			+ '<label for="encrypted-continue">'
			+ t('core', 'I know what I\'m doing')
			+ '</label><br />',

	resetErrorMsg : t('core', 'Password can not be changed. Please contact your administrator.'),

	init : function() {
		$('#lost-password').click(OC.Lostpassword.resetLink);
		$('#lost-password-back').click(OC.Lostpassword.backToLogin);
		$('form[name=login]').submit(OC.Lostpassword.onSendLink);
		OC.Lostpassword.resetButtons();
	},

	resetButtons : function() {
		$('#reset-password-wrapper .submit-icon')
			.addClass('icon-confirm-white')
			.removeClass('icon-loading-small-dark');
		$('#reset-password-submit')
			.attr('value', t('core', 'Reset password'))
			.prop('disabled', false);
		$('#user').prop('disabled', false);
		$('.login-additional').fadeIn();
	},

	backToLogin : function(event) {
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
	},

	resetLink : function(event){
		event.preventDefault();

		$('#lost-password').hide();
		$('#lost-password-back').slideDown().fadeIn();
		$('.remember-login-container').slideUp().fadeOut();
		$('#submit-wrapper').slideUp().fadeOut();
		$('.groupbottom').slideUp().fadeOut(function(){
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
				OC.Lostpassword.onSendLink();
			}
		}
	},

	onSendLink: function (event) {
		// Only if password reset form is active
		if($('form[name=login][action]').length === 1) {
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
					user : $('#user').val()
				},
				OC.Lostpassword.sendLinkDone
			).fail(function() {
				OC.Lostpassword.sendLinkError(OC.Lostpassword.sendErrorMsg);
			});
		}
	},

	sendLinkDone : function(result){
		var sendErrorMsg;

		if (result && result.status === 'success'){
			OC.Lostpassword.sendLinkSuccess();
		} else {
			if (result && result.msg){
				sendErrorMsg = result.msg;
			} else {
				sendErrorMsg = OC.Lostpassword.sendErrorMsg;
			}
			OC.Lostpassword.sendLinkError(sendErrorMsg);
		}
	},

	sendLinkSuccess : function(msg){
		var node = OC.Lostpassword.getSendStatusNode();
		// update is the better success message styling
		node.addClass('update').css({width:'auto'});
		node.html(OC.Lostpassword.sendSuccessMsg);
		OC.Lostpassword.resetButtons();
	},

	sendLinkError : function(msg){
		var node = OC.Lostpassword.getSendStatusNode();
		node.addClass('warning');
		node.html(msg);
		OC.Lostpassword.resetButtons();
	},

	getSendStatusNode : function(){
		if (!$('#lost-password').length){
			$('<p id="lost-password"></p>').insertBefore($('#remember_login'));
		} else {
			$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
		}
		return $('#lost-password');
	},

	resetPassword : function(event){
		event.preventDefault();
		if ($('#password').val()){
			$.post(
					$('#password').parents('form').attr('action'),
					{
						password : $('#password').val(),
						proceed: $('#encrypted-continue').is(':checked') ? 'true' : 'false'
					},
					OC.Lostpassword.resetDone
			);
		}
		if($('#encrypted-continue').is(':checked')) {
			$('#reset-password #submit').hide();
			$('#reset-password #float-spinner').removeClass('hidden');
		}
	},

	resetDone : function(result){
		var resetErrorMsg;
		if (result && result.status === 'success'){
			$.post(
					OC.webroot + '/',
					{
						user : window.location.href.split('/').pop(),
						password : $('#password').val()
					},
					OC.Lostpassword.redirect
			);
		} else {
			if (result && result.msg){
				resetErrorMsg = result.msg;
			} else if (result && result.encryption) {
				resetErrorMsg = OC.Lostpassword.encryptedMsg;
			} else {
				resetErrorMsg = OC.Lostpassword.resetErrorMsg;
			}
			OC.Lostpassword.resetError(resetErrorMsg);
		}
	},

	redirect : function(msg){
		if(OC.webroot !== '') {
			window.location = OC.webroot;
		} else {
			window.location = '/';
		}
	},

	resetError : function(msg){
		var node = OC.Lostpassword.getResetStatusNode();
		node.addClass('warning');
		node.html(msg);
	},

	getResetStatusNode : function (){
		if (!$('#lost-password').length){
			$('<p id="lost-password"></p>').insertBefore($('#reset-password fieldset'));
		} else {
			$('#lost-password').replaceWith($('<p id="lost-password"></p>'));
		}
		return $('#lost-password');
	}

};

$(document).ready(OC.Lostpassword.init);
