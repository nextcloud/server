/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, Bjoern Schiessle
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your opinion) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

function setThemingValue(setting, value) {
	OC.msg.startSaving('#theming_settings_msg');
	$.post(
		OC.generateUrl('/apps/theming/ajax/updateStylesheet'), {'setting' : setting, 'value' : value}
	).done(function(response) {
		OC.msg.finishedSaving('#theming_settings_msg', response);
	}).fail(function(response) {
		OC.msg.finishedSaving('#theming_settings_msg', response);
	});
	preview(setting, value);
}

function calculateLuminance(rgb) {
	var hexValue = rgb.replace(/[^0-9A-Fa-f]/, '');
	var r,g,b;
	if (hexValue.length === 3) {
		hexValue = hexValue[0] + hexValue[0] + hexValue[1] + hexValue[1] + hexValue[2] + hexValue[2];
	}
	if (hexValue.length !== 6) {
		return 0;
	}
	r = parseInt(hexValue.substring(0,2), 16);
	g = parseInt(hexValue.substring(2,4), 16);
	b = parseInt(hexValue.substring(4,6), 16);
	return (0.299*r + 0.587*g + 0.114*b)/255;
}

function generateRadioButton(color) {
	var radioButton = '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="16">' +
		'<path d="M8 1a7 7 0 0 0-7 7 7 7 0 0 0 7 7 7 7 0 0 0 7-7 7 7 0 0 0-7-7zm0 1a6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6zm0 2a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="' + color + '"/></svg>';
	return btoa(radioButton);
}

function preview(setting, value) {
	if (setting === 'color') {
		var headerClass = document.getElementById('header');
		var expandDisplayNameClass = document.getElementById('expandDisplayName');
		var headerAppName = headerClass.getElementsByClassName('header-appname')[0];
		var textColor, icon;
		var luminance = calculateLuminance(value);
		var elementColor = value;

		if (luminance > 0.5) {
			textColor = "#000000";
			icon = 'caret-dark';
		} else {
			textColor = "#ffffff";
			icon = 'caret';
		}
		if (luminance>0.8) {
			elementColor = '#555555';
		}

		headerClass.style.background = value;
		headerClass.style.backgroundImage = '../img/logo-icon.svg';
		expandDisplayNameClass.style.color = textColor;
		headerAppName.style.color = textColor;

		$('#previewStyles').html(
			'#header .icon-caret { background-image: url(\'' + OC.getRootPath() + '/core/img/actions/' + icon + '.svg\') }' +
			'input[type="checkbox"].checkbox:checked:enabled:not(.checkbox--white) + label:before {' +
			'background-image:url(\'' + OC.getRootPath() + '/core/img/actions/checkmark-white.svg\');' +
			'background-color: ' + elementColor + '; background-position: center center; background-size:contain;' +
			'width:12px; height:12px; padding:0; margin:2px 6px 6px 2px; border-radius:1px;}' +
			'input[type="radio"].radio:checked:not(.radio--white):not(:disabled) + label:before {' +
			'background-image: url(\'data:image/svg+xml;base64,' + generateRadioButton(elementColor) + '\'); }'
		);
	}
	if (setting === 'logoMime') {
		console.log(setting);
		var logos = document.getElementsByClassName('logo-icon');
		var timestamp = new Date().getTime();
		if (value !== '') {
			logos[0].style.backgroundImage = "url('" + OC.generateUrl('/apps/theming/logo') + "?v" + timestamp + "')";
			logos[0].style.backgroundSize = "contain";
		} else {
			logos[0].style.backgroundImage = "url('" + OC.getRootPath() + '/core/img/logo-icon.svg?v' + timestamp +"')";
			logos[0].style.backgroundSize = "contain";
		}
	}
}

$(document).ready(function () {
	$('#theming [data-toggle="tooltip"]').tooltip();

	$('html > head').append($('<style type="text/css" id="previewStyles"></style>'));

	var uploadParamsLogo = {
		pasteZone: null,
		dropZone: null,
		done: function (e, response) {
			preview('logoMime', response.result.data.name);
			OC.msg.finishedSaving('#theming_settings_msg', response.result);
			$('label#uploadlogo').addClass('icon-upload').removeClass('icon-loading-small');
		},
		submit: function(e, response) {
			OC.msg.startSaving('#theming_settings_msg');
			$('label#uploadlogo').removeClass('icon-upload').addClass('icon-loading-small');
		},
		fail: function (e, response){
			OC.msg.finishedError('#theming_settings_msg', response._response.jqXHR.responseJSON.data.message);
			$('label#uploadlogo').addClass('icon-upload').removeClass('icon-loading-small');
		}
	};
	var uploadParamsLogin = {
		pasteZone: null,
		dropZone: null,
		done: function (e, response) {
			preview('backgroundMime', response.result.data.name);
			OC.msg.finishedSaving('#theming_settings_msg', response.result);
			$('label#upload-login-background').addClass('icon-upload').removeClass('icon-loading-small');
		},
		submit: function(e, response) {
			OC.msg.startSaving('#theming_settings_msg');
			$('label#upload-login-background').removeClass('icon-upload').addClass('icon-loading-small');
		},
		fail: function (e, response){
			$('label#upload-login-background').removeClass('icon-loading-small').addClass('icon-upload');
			OC.msg.finishedError('#theming_settings_msg', response._response.jqXHR.responseJSON.data.message);
		}
	};

	$('#uploadlogo').fileupload(uploadParamsLogo);
	$('#upload-login-background').fileupload(uploadParamsLogin);

	$('#theming-name').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('name', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('name', $(this).val());
		}
	});

	$('#theming-url').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('url', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('url', $(this).val());
		}
	});

	$('#theming-slogan').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('slogan', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('slogan', $(this).val());
		}
	});

	$('#theming-color').change(function (e) {
		setThemingValue('color', '#' + $(this).val());
	});

	$('.theme-undo').click(function (e) {
		var setting = $(this).data('setting');
		OC.msg.startSaving('#theming_settings_msg');
		$.post(
			OC.generateUrl('/apps/theming/ajax/undoChanges'), {'setting' : setting}
		).done(function(response) {
			if (setting === 'color') {
				var colorPicker = document.getElementById('theming-color');
				colorPicker.style.backgroundColor = response.data.value;
				colorPicker.value = response.data.value.slice(1);
			} else if (setting !== 'logoMime' && setting !== 'backgroundMime') {
				var input = document.getElementById('theming-'+setting);
				input.value = response.data.value;
			}
			preview(setting, response.data.value);
			OC.msg.finishedSaving('#theming_settings_msg', response);
		});
	});
});
