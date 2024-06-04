/* global OC */

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2011-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', function () {
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
		if ($('#pass1').val() !== '' && $('#pass2').val() !== '') {
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
								'message' : t('settings', 'Unable to change password')
							}
						}
					);
				}
				$(".personal-show-label").show();
				$(".password-loading").remove();
				$("#passwordbutton").removeAttr('disabled');
			});
			return false;
		} else {
			OC.msg.finishedSaving('#password-error-msg',
				{
					'status' : 'error',
					'data' : {
						'message' : t('settings', 'Unable to change password')
					}
				}
			);
			return false;
		}
	});

	$('#pass2').strengthify({
		zxcvbn: OC.linkTo('core','vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			t('settings', 'Very weak password'),
			t('settings', 'Weak password'),
			t('settings', 'So-so password'),
			t('settings', 'Good password'),
			t('settings', 'Strong password')
		],
		drawTitles: true,
		$addAfter: $('input[name="newpassword-clone"]'),
		nonce: btoa(OC.requestToken),
	});
});
