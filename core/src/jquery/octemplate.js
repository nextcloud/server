/**
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import $ from 'jquery'
import escapeHTML from 'escape-html'
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
				}
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
