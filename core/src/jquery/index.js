/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

import $ from 'jquery'

import './avatar.js'
import './contactsmenu.js'
import './exists.js'
import './filterattr.js'
import './ocdialog.js'
import './octemplate.js'
import './placeholder.js'
import './requesttoken.js'
import './selectrange.js'
import './showpassword.js'
import './ui-fixes.js'

import './css/jquery-ui-fixes.scss'
import './css/jquery.ocdialog.scss'

/**
 * Disable automatic evaluation of responses for $.ajax() functions (and its
 * higher-level alternatives like $.get() and $.post()).
 *
 * If a response to a $.ajax() request returns a content type of "application/javascript"
 * JQuery would previously execute the response body. This is a pretty unexpected
 * behaviour and can result in a bypass of our Content-Security-Policy as well as
 * multiple unexpected XSS vectors.
 */
$.ajaxSetup({
	contents: {
		script: false,
	},
})

/**
 * Disable execution of eval in jQuery. We do require an allowed eval CSP
 * configuration at the moment for handlebars et al. But for jQuery there is
 * not much of a reason to execute JavaScript directly via eval.
 *
 * This thus mitigates some unexpected XSS vectors.
 */
$.globalEval = function() {
}
