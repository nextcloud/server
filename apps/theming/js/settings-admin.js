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
	$.post(
		OC.generateUrl('/apps/theming/ajax/updateStylesheet'), {'setting' : setting, 'value' : value}
	);
	preview(setting, value);
}

function preview(setting, value) {
	if (setting === 'color') {
		var headerClass = document.getElementById('header');
		headerClass.style.background = value;
		headerClass.style.backgroundImage = '../img/logo-icon.svg';

	}
	if (setting === 'logoName') {
		var logos = document.getElementsByClassName('logo-icon');
		for (var i = 0; i < logos.length; i++) {
			logos[i].style.background= "url('" + OC.getRootPath() + "/themes/theming-app/core/img/" + value + "')";
		}
	}
}

$(document).ready(function () {

	var uploadparms = {
		pasteZone: null,
		done: function (e, data) {
			preview('logoName', data.result.name);
		},
		submit: function(e, data) {
		},
		fail: function (e, data){
		}
	};
	
	$('#uploadlogo').fileupload(uploadparms);

	$('#theming-name').keyup(function (e) {
		if (e.keyCode == 13) {
			setThemingValue('name', $(this).val());
		}
	}).focusout(function (e) {
		setThemingValue('name', $(this).val());
	});

	$('#theming-url').keyup(function (e) {
		if (e.keyCode == 13) {
			setThemingValue('url', $(this).val());
		}
	}).focusout(function (e) {
		setThemingValue('url', $(this).val());
	});

	$('#theming-slogan').keyup(function (e) {
		if (e.keyCode == 13) {
			setThemingValue('slogan', $(this).val());
		}
	}).focusout(function (e) {
		setThemingValue('slogan', $(this).val());
	});

	$('#theming-color').change(function (e) {
		setThemingValue('color', '#' + $(this).val());
	});
	
	$('.theme-undo').click(function (e) {
		var setting = $(this).data('setting');
		$.post(
			OC.generateUrl('/apps/theming/ajax/undoChanges'), {'setting' : setting}
		).done(function(data) {
			if (setting === 'color') {
				var colorPicker = document.getElementById('theming-color');
				colorPicker.style.backgroundColor = data.value;
				colorPicker.value = data.value.slice(1);
			} else if (setting !== 'logoName') {
				var input = document.getElementById('theming-'+setting);
				input.value = data.value;
			}
			preview(setting, data.value);
		});
	});
});
