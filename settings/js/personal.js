/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 *               2013, Morris Jobke <morris.jobke@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/* global OC, t */

/**
 * The callback will be fired as soon as enter is pressed by the
 * user or 1 second after the last data entry
 *
 * @param callback
 * @param allowEmptyValue if this is set to true the callback is also called when the value is empty
 */
jQuery.fn.keyUpDelayedOrEnter = function (callback, allowEmptyValue) {
	var cb = callback;
	var that = this;
	this.keyup(_.debounce(function (event) {
		// enter is already handled in keypress
		if (event.keyCode === 13) {
			return;
		}
		if (allowEmptyValue || that.val() !== '') {
			cb();
		}
	}, 1000));

	this.keypress(function (event) {
		if (event.keyCode === 13 && (allowEmptyValue || that.val() !== '')) {
			event.preventDefault();
			cb();
		}
	});
};


/**
 * Post the email address change to the server.
 */
function changeEmailAddress () {
	var emailInfo = $('#email');
	if (emailInfo.val() === emailInfo.defaultValue) {
		return;
	}
	emailInfo.defaultValue = emailInfo.val();
	OC.msg.startSaving('#lostpassword .msg');
	var post = $("#lostpassword").serializeArray();
	$.ajax({
		type: 'PUT',
		url: OC.generateUrl('/settings/users/{id}/mailAddress', {id: OC.currentUser}),
		data: {
			mailAddress: post[0].value
		}
	}).done(function(result){
		// I know the following 4 lines look weird, but that is how it works
		// in jQuery -  for success the first parameter is the result
		//              for failure the first parameter is the result object
		OC.msg.finishedSaving('#lostpassword .msg', result);
	}).fail(function(result){
		OC.msg.finishedSaving('#lostpassword .msg', result.responseJSON);
	});
}

/**
 * Post the display name change to the server.
 */
function changeDisplayName () {
	if ($('#displayName').val() !== '') {
		OC.msg.startSaving('#displaynameform .msg');
		// Serialize the data
		var post = $("#displaynameform").serialize();
		// Ajax foo
		$.post('ajax/changedisplayname.php', post, function (data) {
			if (data.status === "success") {
				$('#oldDisplayName').val($('#displayName').val());
				// update displayName on the top right expand button
				$('#expandDisplayName').text($('#displayName').val());
				updateAvatar();
			}
			else {
				$('#newdisplayname').val(data.data.displayName);
			}
			OC.msg.finishedSaving('#displaynameform .msg', data);
		});
	}
}

function updateAvatar (hidedefault) {
	var $headerdiv = $('#header .avatardiv');
	var $displaydiv = $('#displayavatar .avatardiv');

	if (hidedefault) {
		$headerdiv.hide();
		$('#header .avatardiv').removeClass('avatardiv-shown');
	} else {
		$headerdiv.css({'background-color': ''});
		$headerdiv.avatar(OC.currentUser, 32, true);
		$('#header .avatardiv').addClass('avatardiv-shown');
	}
	$displaydiv.css({'background-color': ''});
	$displaydiv.avatar(OC.currentUser, 128, true);

	$('#removeavatar').show();
}

function showAvatarCropper () {
	var $cropper = $('#cropper');
	$cropper.prepend("<img>");
	var $cropperImage = $('#cropper img');

	$cropperImage.attr('src',
		OC.generateUrl('/avatar/tmp') + '?requesttoken=' + encodeURIComponent(oc_requesttoken) + '#' + Math.floor(Math.random() * 1000));

	// Looks weird, but on('load', ...) doesn't work in IE8
	$cropperImage.ready(function () {
		$('#displayavatar').hide();
		$cropper.show();

		$cropperImage.Jcrop({
			onChange: saveCoords,
			onSelect: saveCoords,
			aspectRatio: 1,
			boxHeight: 500,
			boxWidth: 500,
			setSelect: [0, 0, 300, 300]
		});
	});
}

function sendCropData () {
	cleanCropper();

	var cropperData = $('#cropper').data();
	var data = {
		x: cropperData.x,
		y: cropperData.y,
		w: cropperData.w,
		h: cropperData.h
	};
	$.post(OC.generateUrl('/avatar/cropped'), {crop: data}, avatarResponseHandler);
}

function saveCoords (c) {
	$('#cropper').data(c);
}

function cleanCropper () {
	var $cropper = $('#cropper');
	$('#displayavatar').show();
	$cropper.hide();
	$('.jcrop-holder').remove();
	$('#cropper img').removeData('Jcrop').removeAttr('style').removeAttr('src');
	$('#cropper img').remove();
}

function avatarResponseHandler (data) {
	var $warning = $('#avatar .warning');
	$warning.hide();
	if (data.status === "success") {
		updateAvatar();
	} else if (data.data === "notsquare") {
		showAvatarCropper();
	} else {
		$warning.show();
		$warning.text(data.data.message);
	}
}

$(document).ready(function () {
	if($('#pass2').length) {
		$('#pass2').showPassword().keyup();
	}
	$("#passwordbutton").click(function () {
		var isIE8or9 = $('html').hasClass('lte9');
		// FIXME - TODO - once support for IE8 and IE9 is dropped
		// for IE8 and IE9 this will check additionally if the typed in password
		// is different from the placeholder, because in IE8/9 the placeholder
		// is simply set as the value to look like a placeholder
		if ($('#pass1').val() !== '' && $('#pass2').val() !== ''
			&& !(isIE8or9 && $('#pass2').val() === $('#pass2').attr('placeholder'))) {
			// Serialize the data
			var post = $("#passwordform").serialize();
			$('#passwordchanged').hide();
			$('#passworderror').hide();
			// Ajax foo
			$.post(OC.generateUrl('/settings/personal/changepassword'), post, function (data) {
				if (data.status === "success") {
					$('#pass1').val('');
					$('#pass2').val('');
					// Hide a possible errormsg and show successmsg
					$('#password-changed').removeClass('hidden').addClass('inlineblock');
					$('#password-error').removeClass('inlineblock').addClass('hidden');
				} else {
					if (typeof(data.data) !== "undefined") {
						$('#passworderror').html(data.data.message);
					} else {
						$('#passworderror').html(t('Unable to change password'));
					}
					// Hide a possible successmsg and show errormsg
					$('#password-changed').removeClass('inlineblock').addClass('hidden');
					$('#password-error').removeClass('hidden').addClass('inlineblock');
				}
			});
			return false;
		} else {
			// Hide a possible successmsg and show errormsg
			$('#password-changed').removeClass('inlineblock').addClass('hidden');
			$('#password-error').removeClass('hidden').addClass('inlineblock');
			return false;
		}

	});

	$('#displayName').keyUpDelayedOrEnter(changeDisplayName);
	$('#email').keyUpDelayedOrEnter(changeEmailAddress, true);

	$("#languageinput").change(function () {
		// Serialize the data
		var post = $("#languageinput").serialize();
		// Ajax foo
		$.post('ajax/setlanguage.php', post, function (data) {
			if (data.status === "success") {
				location.reload();
			}
			else {
				$('#passworderror').html(data.data.message);
			}
		});
		return false;
	});

	var uploadparms = {
		done: function (e, data) {
			avatarResponseHandler(data.result);
		}
	};

	$('#uploadavatarbutton').click(function () {
		$('#uploadavatar').click();
	});

	$('#uploadavatar').fileupload(uploadparms);

	$('#selectavatar').click(function () {
		OC.dialogs.filepicker(
			t('settings', "Select a profile picture"),
			function (path) {
				$.post(OC.generateUrl('/avatar/'), {path: path}, avatarResponseHandler);
			},
			false,
			["image/png", "image/jpeg"]
		);
	});

	$('#removeavatar').click(function () {
		$.ajax({
			type: 'DELETE',
			url: OC.generateUrl('/avatar/'),
			success: function () {
				updateAvatar(true);
				$('#removeavatar').hide();
			}
		});
	});

	$('#abortcropperbutton').click(function () {
		cleanCropper();
	});

	$('#sendcropperbutton').click(function () {
		sendCropData();
	});

	$('#pass2').strengthify({
		zxcvbn: OC.linkTo('core','vendor/zxcvbn/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password')
		]
	});

	// does the user have a custom avatar? if he does hide #removeavatar
	// needs to be this complicated because we can't check yet if an avatar has been loaded, because it's async
	var url = OC.generateUrl(
		'/avatar/{user}/{size}',
		{user: OC.currentUser, size: 1}
	) + '?requesttoken=' + encodeURIComponent(oc_requesttoken);
	$.get(url, function (result) {
		if (typeof(result) === 'object') {
			$('#removeavatar').hide();
		}
	});

	$('#sslCertificate').on('click', 'td.remove > img', function () {
		var row = $(this).parent().parent();
		$.ajax(OC.generateUrl('settings/personal/certificate/{certificate}', {certificate: row.data('name')}), {
			type: 'DELETE'
		});
		row.remove();

		if ($('#sslCertificate > tbody > tr').length === 0) {
			$('#sslCertificate').hide();
		}
		return true;
	});

	$('#sslCertificate tr > td').tipsy({gravity: 'n', live: true});

	$('#rootcert_import').fileupload({
		success: function (data) {
			var issueDate = new Date(data.validFrom * 1000);
			var expireDate = new Date(data.validTill * 1000);
			var now = new Date();
			var isExpired = !(issueDate <= now && now <= expireDate);

			var row = $('<tr/>');
			row.data('name', data.name);
			row.addClass(isExpired? 'expired': 'valid');
			row.append($('<td/>').attr('title', data.organization).text(data.commonName));
			row.append($('<td/>').attr('title', t('core,', 'Valid until {date}', {date: data.validTillString}))
				.text(data.validTillString));
			row.append($('<td/>').attr('title', data.issuerOrganization).text(data.issuer));
			row.append($('<td/>').addClass('remove').append(
				$('<img/>').attr({
					alt: t('core', 'Delete'),
					title: t('core', 'Delete'),
					src: OC.imagePath('core', 'actions/delete.svg')
				}).addClass('action')
			));

			$('#sslCertificate tbody').append(row);
			$('#sslCertificate').show();
		},
		fail: function () {
			OC.Notification.showTemporary(
				t('settings', 'An error occurred. Please upload an ASCII-encoded PEM certificate.'));
		}
	});

	$('#rootcert_import_button').click(function () {
		$('#rootcert_import').click();
	});

	if ($('#sslCertificate > tbody > tr').length === 0) {
		$('#sslCertificate').hide();
	}
});

if (!OC.Encryption) {
	OC.Encryption = {};
}

OC.Encryption.msg = {
	start: function (selector, msg) {
		var spinner = '<img src="' + OC.imagePath('core', 'loading-small.gif') + '">';
		$(selector)
			.html(msg + ' ' + spinner)
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},
	finished: function (selector, data) {
		if (data.status === "success") {
			$(selector).html(data.data.message)
				.addClass('success')
				.stop(true, true)
				.delay(3000);
		} else {
			$(selector).html(data.data.message).addClass('error');
		}
	}
};
