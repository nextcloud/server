/* global OC */

/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 *               2013, Morris Jobke <morris.jobke@gmail.com>
 *               2016, Christoph Wurst <christoph@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Settings = OC.Settings || {};

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
			cb(event);
		}
	}, 1000));

	this.keypress(function (event) {
		if (event.keyCode === 13 && (allowEmptyValue || that.val() !== '')) {
			event.preventDefault();
			cb(event);
		}
	});

	this.bind('paste', null, function (event) {
		if(!event.keyCode){
			if (allowEmptyValue || that.val() !== '') {
				cb(event);
			}
		}
	});
};

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
		if($('#displayavatar img').length === 0) {
			$('#removeavatar').removeClass('inlineblock').addClass('hidden');
		} else {
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
	var $warning = $('#avatarform .warning');
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

	var federationSettingsView = new OC.Settings.FederationSettingsView({
		el: '#personal-settings'
	});
	federationSettingsView.render();

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

	// Load the big avatar
	if (oc_config.enable_avatars) {
		$('#avatarform .avatardiv').avatar(OC.currentUser, 145, true, null, function() {
			if($('#displayavatar img').length === 0) {
				$('#removeavatar').removeClass('inlineblock').addClass('hidden');
			} else {
				$('#removeavatar').removeClass('hidden').addClass('inlineblock');
			}
		});
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

OC.Settings.updateAvatar = updateAvatar;
