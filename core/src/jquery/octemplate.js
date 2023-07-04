/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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
import escapeHTML from 'escape-html'

/**
 * jQuery plugin for micro templates
 *
 * Strings are automatically escaped, but that can be disabled by setting
 * escapeFunction to null.
 *
 * Usage examples:
 *
 *    var htmlStr = '<p>Bake, uncovered, until the {greasystuff} is melted and the {pasta} is heated through, about {min} minutes.</p>'
 *    $(htmlStr).octemplate({greasystuff: 'cheese', pasta: 'macaroni', min: 10});
 *
 *    var htmlStr = '<p>Welcome back {user}</p>';
 *    $(htmlStr).octemplate({user: 'John Q. Public'}, {escapeFunction: null});
 *
 * Be aware that the target string must be wrapped in an HTML element for the
 * plugin to work. The following won't work:
 *
 *      var textStr = 'Welcome back {user}';
 *      $(textStr).octemplate({user: 'John Q. Public'});
 *
 * For anything larger than one-liners, you can use a simple $.get() ajax
 * request to get the template, or you can embed them it the page using the
 * text/template type:
 *
 * <script id="contactListItemTemplate" type="text/template">
 *    <tr class="contact" data-id="{id}">
 *        <td class="name">
 *            <input type="checkbox" name="id" value="{id}" /><span class="nametext">{name}</span>
 *        </td>
 *        <td class="email">
 *            <a href="mailto:{email}">{email}</a>
 *        </td>
 *        <td class="phone">{phone}</td>
 *    </tr>
 * </script>
 *
 * var $tmpl = $('#contactListItemTemplate');
 * var contacts = // fetched in some ajax call
 *
 * $.each(contacts, function(idx, contact) {
 *         $contactList.append(
 *             $tmpl.octemplate({
 *                 id: contact.getId(),
 *                 name: contact.getDisplayName(),
 *                 email: contact.getPreferredEmail(),
 *                 phone: contact.getPreferredPhone(),
 *             });
 *         );
 * });
 */
/**
 * Object Template
 * Inspired by micro templating done by e.g. underscore.js
 */
const Template = {
	init(vars, options, elem) {
		// Mix in the passed in options with the default options
		this.vars = vars
		this.options = $.extend({}, this.options, options)

		this.elem = elem
		const self = this

		if (typeof this.options.escapeFunction === 'function') {
			const keys = Object.keys(this.vars)
			for (let key = 0; key < keys.length; key++) {
				if (typeof this.vars[keys[key]] === 'string') {
					this.vars[keys[key]] = self.options.escapeFunction(this.vars[keys[key]])
				}
			}
		}

		const _html = this._build(this.vars)
		return $(_html)
	},
	// From stackoverflow.com/questions/1408289/best-way-to-do-variable-interpolation-in-javascript
	_build(o) {
		const data = this.elem.attr('type') === 'text/template' ? this.elem.html() : this.elem.get(0).outerHTML
		try {
			return data.replace(/{([^{}]*)}/g,
				function(a, b) {
					const r = o[b]
					return typeof r === 'string' || typeof r === 'number' ? r : a
				},
			)
		} catch (e) {
			console.error(e, 'data:', data)
		}
	},
	options: {
		escapeFunction: escapeHTML,
	},
}

$.fn.octemplate = function(vars, options) {
	vars = vars || {}
	if (this.length) {
		const _template = Object.create(Template)
		return _template.init(vars, options, this)
	}
}
