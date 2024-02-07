/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * We need to use a separate file as imports are hoisted, so otherwise the globals are not setup when we extend jQuery.
 */

import $ from 'jquery'
import Backbone from 'backbone'
import ClipboardJS from 'clipboard'
import Handlebars from 'handlebars'
import md5 from 'blueimp-md5'
import moment from 'moment'

import { setDeprecatedProp } from './utils/deprecate.js'

// This is wrongly used in legacy files
$.fn.exists = function() {
	return $(this).length > 0
}

setDeprecatedProp(['$', 'jQuery'], () => $, 'The global jQuery is deprecated. It will be removed in a later versions without another warning. Please ship your own.')
setDeprecatedProp('Backbone', () => Backbone, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp(['Clipboard', 'ClipboardJS'], () => ClipboardJS, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp('Handlebars', () => Handlebars, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp('md5', () => md5, 'please ship your own, this will be removed in Nextcloud 20')
setDeprecatedProp('moment', () => moment, 'please ship your own, this will be removed in Nextcloud 20')
