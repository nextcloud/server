/**
 *
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

OCA = OCA || {};
OCA.LDAP = _.extend(OC.LDAP || {}, {
	onRenewPassword: function () {
		$('#submit')
			.removeClass('icon-confirm-white')
			.addClass('icon-loading-small')
			.attr('value', t('core', 'Renewing …'));
		return true;
	},
});

window.addEventListener('DOMContentLoaded', function() {
	$('form[name=renewpassword]').submit(OCA.LDAP.onRenewPassword);

	if($('#newPassword').length) {
		$('#newPassword').showPassword().keyup();
	}
	$('#newPassword').strengthify({
		zxcvbn: OC.linkTo('core','vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password')
		],
		drawTitles: true,
		$addAfter: $('input[name="newPassword-clone"]'),
	});
});
