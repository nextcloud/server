/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 *               2013, Morris Jobke <morris.jobke@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

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

	this.bind('paste', null, function (e) {
		if(!e.keyCode){
			if (allowEmptyValue || that.val() !== '') {
				cb();
			}
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
		OC.msg.finishedError('#lostpassword .msg', result.responseJSON.message);
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
		$.post(OC.generateUrl('/settings/users/{id}/displayName', {id: OC.currentUser}), post, function (data) {
			if (data.status === "success") {
				$('#oldDisplayName').val($('#displayName').val());
				// update displayName on the top right expand button
				$('#expandDisplayName').text($('#displayName').val());
				// update avatar if avatar is available
				if(!$('#removeavatar').hasClass('hidden')) {
					updateAvatar();
				}
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

	//Bump avatar avatarversion
	oc_userconfig.avatar.version = -(Math.floor(Math.random() * 1000));

	if (hidedefault) {
		$headerdiv.hide();
		$('#header .avatardiv').removeClass('avatardiv-shown');
	} else {
		$headerdiv.css({'background-color': ''});
		$headerdiv.avatar(OC.currentUser, 32, true);
		$('#header .avatardiv').addClass('avatardiv-shown');
	}
	$displaydiv.css({'background-color': ''});
	$displaydiv.avatar(OC.currentUser, 145, true, null, function() {
		$displaydiv.removeClass('loading');
		$('#displayavatar img').show();
	});
	$.get(OC.generateUrl(
		'/avatar/{user}/{size}',
		{
			user: OC.currentUser,
			size: 1
		}
	), function (result) {
		if (typeof(result) === 'string') {
			// Show the delete button when the avatar is custom
			$('#removeavatar').removeClass('hidden').addClass('inlineblock');
		}
	});
}

function showAvatarCropper () {
	var $cropper = $('#cropper');
	var $cropperImage = $('<img/>');
	$cropperImage.css('opacity', 0); // prevent showing the unresized image
	$cropper.children('.inner-container').prepend($cropperImage);

	$cropperImage.attr('src',
		OC.generateUrl('/avatar/tmp') + '?requesttoken=' + encodeURIComponent(oc_requesttoken) + '#' + Math.floor(Math.random() * 1000));

	$cropperImage.load(function () {
		var img = $cropperImage.get()[0];
		var selectSize = Math.min(img.width, img.height);
		var offsetX = (img.width - selectSize) / 2;
		var offsetY = (img.height - selectSize) / 2;
		$cropperImage.Jcrop({
			onChange: saveCoords,
			onSelect: saveCoords,
			aspectRatio: 1,
			boxHeight: Math.min(500, $('#app-content').height() -100),
			boxWidth: Math.min(500, $('#app-content').width()),
			setSelect: [offsetX, offsetY, selectSize, selectSize]
		}, function() {
			$cropper.show();
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
	if (typeof data === 'string') {
		data = JSON.parse(data);
	}
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

	var removeloader = function () {
		setTimeout(function(){
			if ($('.password-state').length > 0) {
				$('.password-state').remove();
			}
		}, 5000)
	};

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
			$("#passwordbutton").attr('disabled', 'disabled');
			$("#passwordbutton").after("<span class='password-loading icon icon-loading-small-dark password-state'></span>");
			$(".personal-show-label").hide();
			// Ajax foo
			$.post(OC.generateUrl('/settings/personal/changepassword'), post, function (data) {
				if (data.status === "success") {
					$("#passwordbutton").after("<span class='checkmark icon icon-checkmark password-state'></span>");
					removeloader();
					$(".personal-show-label").show();
					$('#pass1').val('');
					$('#pass2').val('').change();
				}
				if (typeof(data.data) !== "undefined") {
					OC.msg.finishedSaving('#password-error-msg', data);
				} else {
					OC.msg.finishedSaving('#password-error-msg',
						{
							'status' : 'error',
							'data' : {
								'message' : t('core', 'Unable to change password')
							}
						}
					);
				}
				$(".password-loading").remove();
				$("#passwordbutton").removeAttr('disabled');
			});
			return false;
		} else {
			OC.msg.finishedSaving('#password-error-msg',
				{
					'status' : 'error',
					'data' : {
						'message' : t('core', 'Unable to change password')
					}
				}
			);
			return false;
		}
	});

	$('#displayName').keyUpDelayedOrEnter(changeDisplayName);
	$('#email').keyUpDelayedOrEnter(changeEmailAddress, true);

	$("#languageinput").change(function () {
		// Serialize the data
		var post = $("#languageinput").serialize();
		// Ajax foo
		$.ajax(
			'ajax/setlanguage.php',
			{
				method: 'POST',
				data: post
			}
		).done(function() {
			location.reload();
		}).fail(function(jqXHR) {
			$('#passworderror').text(jqXHR.responseJSON.message);
		});
		return false;
	});

	var uploadparms = {
		pasteZone: null,
		done: function (e, data) {
			var response = data;
			if (typeof data.result === 'string') {
				response = JSON.parse(data.result);
			} else if (data.result && data.result.length) {
				// fetch response from iframe
				response = JSON.parse(data.result[0].body.innerText);
			} else {
				response = data.result;
			}
			avatarResponseHandler(response);
		},
		submit: function(e, data) {
			$('#displayavatar img').hide();
			$('#displayavatar .avatardiv').addClass('loading');
			data.formData = _.extend(data.formData || {}, {
				requesttoken: OC.requestToken
			});
		},
		fail: function (e, data){
			var msg = data.jqXHR.statusText + ' (' + data.jqXHR.status + ')';
			if (!_.isUndefined(data.jqXHR.responseJSON) &&
				!_.isUndefined(data.jqXHR.responseJSON.data) &&
				!_.isUndefined(data.jqXHR.responseJSON.data.message)
			) {
				msg = data.jqXHR.responseJSON.data.message;
			}
			avatarResponseHandler({
				data: {
					message: msg
				}
			});
		}
	};

	$('#uploadavatar').fileupload(uploadparms);

	$('#selectavatar').click(function () {
		OC.dialogs.filepicker(
			t('settings', "Select a profile picture"),
			function (path) {
				$('#displayavatar img').hide();
				$('#displayavatar .avatardiv').addClass('loading');
				$.ajax({
					type: "POST",
					url: OC.generateUrl('/avatar/'),
					data: { path: path }
				}).done(avatarResponseHandler)
					.fail(function(jqXHR) {
						var msg = jqXHR.statusText + ' (' + jqXHR.status + ')';
						if (!_.isUndefined(jqXHR.responseJSON) &&
							!_.isUndefined(jqXHR.responseJSON.data) &&
							!_.isUndefined(jqXHR.responseJSON.data.message)
						) {
							msg = jqXHR.responseJSON.data.message;
						}
						avatarResponseHandler({
							data: {
								message: msg
							}
						});
					});
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
				$('#removeavatar').addClass('hidden').removeClass('inlineblock');
			}
		});
	});

	$('#abortcropperbutton').click(function () {
		$('#displayavatar .avatardiv').removeClass('loading');
		$('#displayavatar img').show();
		cleanCropper();
	});

	$('#sendcropperbutton').click(function () {
		sendCropData();
	});

	$('#pass2').strengthify({
		zxcvbn: OC.linkTo('core','vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password')
		],
		drawTitles: true,
	});

	// does the user have a custom avatar? if he does show #removeavatar
	$.get(OC.generateUrl(
		'/avatar/{user}/{size}',
		{
			user: OC.currentUser,
			size: 1
		}
	), function (result) {
		if (typeof(result) === 'string') {
			// Show the delete button when the avatar is custom
			$('#removeavatar').removeClass('hidden').addClass('inlineblock');
		}
	});

	// Load the big avatar
	if (oc_config.enable_avatars) {
		$('#avatar .avatardiv').avatar(OC.currentUser, 145);
	}

	// Show token views
	var collection = new OC.Settings.AuthTokenCollection();
	var view = new OC.Settings.AuthTokenView({
		collection: collection
	});
	view.reload();

	// 'redirect' to anchor sections
	// anchors are lost on redirects (e.g. while solving the 2fa challenge) otherwise
	// example: /settings/person?section=devices will result in /settings/person?#devices
	if (!window.location.hash) {
		var query = OC.parseQueryString(location.search);
		if (query && query.section) {
			OC.Util.History.replaceState({});
			window.location.hash = query.section;
		}
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
