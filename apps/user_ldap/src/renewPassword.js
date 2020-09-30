/**
 *
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
 *
 * @license GNU AGPL version 3 or any later version
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
import $ from 'jquery'
import _ from 'underscore'
import { linkTo } from '@nextcloud/router'

window.OCA = window.OCA || {}
window.OCA.LDAP = _.extend(window.OC.LDAP || {}, {
	onRenewPassword() {
		$('#submit')
			.removeClass('icon-confirm-white')
			.addClass('icon-loading-small')
			.attr('value', t('core', 'Renewing …'))
		return true
	},
})

window.addEventListener('DOMContentLoaded', function() {
	('form[name=renewpassword]').submit(OCA.LDAP.onRenewPassword)

	const newPasswordField = $('#newPassword')
	if (newPasswordField.length) {
		newPasswordField.showPassword().keyup()
	}
	newPasswordField.strengthify({
		zxcvbn: linkTo('core', 'vendor/zxcvbn/dist/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password'),
		],
		drawTitles: true,
		$addAfter: $('input[name="newPassword-clone"]'),
	})
})
