/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Gary Kim <gary@garykim.dev>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import { isA11yActivation } from '../Util/a11y'

$.widget('oc.ocdialog', {
	options: {
		width: 'auto',
		height: 'auto',
		closeButton: true,
		closeOnEscape: true,
		closeCallback: null,
		modal: false,
	},
	_create() {
		const self = this

		this.originalCss = {
			display: this.element[0].style.display,
			width: this.element[0].style.width,
			height: this.element[0].style.height,
		}

		this.originalTitle = this.element.attr('title')
		this.options.title = this.options.title || this.originalTitle

		this.$dialog = $('<div class="oc-dialog"></div>')
			.attr({
				// Setting tabIndex makes the div focusable
				tabIndex: -1,
				role: 'dialog',
			})
			.insertBefore(this.element)
		this.$dialog.append(this.element.detach())
		this.element.removeAttr('title').addClass('oc-dialog-content').appendTo(this.$dialog)

		// Activate the primary button on enter if there is a single input
		if (self.element.find('input').length === 1) {
			const $input = self.element.find('input')
			$input.on('keydown', function(event) {
				if (isA11yActivation(event)) {
					if (self.$buttonrow) {
						const $button = self.$buttonrow.find('button.primary')
						if ($button && !$button.prop('disabled')) {
							$button.click()
						}
					}
				}
			})
		}

		this.$dialog.css({
			display: 'inline-block',
			position: 'fixed',
		})

		this.enterCallback = null

		$(document).on('keydown keyup', function(event) {
			if (
				event.target !== self.$dialog.get(0)
				&& self.$dialog.find($(event.target)).length === 0
			) {
				return
			}
			// Escape
			if (
				event.keyCode === 27
				&& event.type === 'keydown'
				&& self.options.closeOnEscape
			) {
				event.stopImmediatePropagation()
				self.close()
				return false
			}
			// Enter
			if (event.keyCode === 13) {
				event.stopImmediatePropagation()
				if (self.enterCallback !== null) {
					self.enterCallback()
					event.preventDefault()
					return false
				}
				if (event.type === 'keyup') {
					event.preventDefault()
					return false
				}
				return false
			}
		})

		this._setOptions(this.options)
		this._createOverlay()
	},
	_init() {
		this.$dialog.focus()
		this._trigger('open')
	},
	_setOption(key, value) {
		const self = this
		switch (key) {
		case 'title':
			if (this.$title) {
				this.$title.text(value)
			} else {
				const $title = $('<h2 class="oc-dialog-title">'
						+ value
						+ '</h2>')
				this.$title = $title.prependTo(this.$dialog)
			}
			this._setSizes()
			break
		case 'buttons':
			if (this.$buttonrow) {
				this.$buttonrow.empty()
			} else {
				const $buttonrow = $('<div class="oc-dialog-buttonrow"></div>')
				this.$buttonrow = $buttonrow.appendTo(this.$dialog)
			}
			if (value.length === 1) {
				this.$buttonrow.addClass('onebutton')
			} else if (value.length === 2) {
				this.$buttonrow.addClass('twobuttons')
			} else if (value.length === 3) {
				this.$buttonrow.addClass('threebuttons')
			}
			$.each(value, function(idx, val) {
				const $button = $('<button>').text(val.text)
				if (val.classes) {
					$button.addClass(val.classes)
				}
				if (val.defaultButton) {
					$button.addClass('primary')
					self.$defaultButton = $button
				}
				self.$buttonrow.append($button)
				$button.on('click keydown', function(event) {
					if (isA11yActivation(event)) {
						val.click.apply(self.element[0], arguments)
					}
				})
			})
			this.$buttonrow.find('button')
				.on('focus', function(event) {
					self.$buttonrow.find('button').removeClass('primary')
					$(this).addClass('primary')
				})
			this._setSizes()
			break
		case 'style':
			if (value.buttons !== undefined) {
				this.$buttonrow.addClass(value.buttons)
			}
			break
		case 'closeButton':
			if (value) {
				const $closeButton = $('<a class="oc-dialog-close" tabindex="0"></a>')
				this.$dialog.prepend($closeButton)
				$closeButton.on('click keydown', function(event) {
					if (isA11yActivation(event)) {
						self.options.closeCallback && self.options.closeCallback()
						self.close()
					}
				})
			} else {
				this.$dialog.find('.oc-dialog-close').remove()
			}
			break
		case 'width':
			this.$dialog.css('width', value)
			break
		case 'height':
			this.$dialog.css('height', value)
			break
		case 'close':
			this.closeCB = value
			break
		}
		// this._super(key, value);
		$.Widget.prototype._setOption.apply(this, arguments)
	},
	_setOptions(options) {
		// this._super(options);
		$.Widget.prototype._setOptions.apply(this, arguments)
	},
	_setSizes() {
		let lessHeight = 0
		if (this.$title) {
			lessHeight += this.$title.outerHeight(true)
		}
		if (this.$buttonrow) {
			lessHeight += this.$buttonrow.outerHeight(true)
		}
		this.element.css({
			height: 'calc(100% - ' + lessHeight + 'px)',
		})
	},
	_createOverlay() {
		if (!this.options.modal) {
			return
		}

		const self = this
		let contentDiv = $('#content')
		if (contentDiv.length === 0) {
			// nextcloud-vue compatibility
			contentDiv = $('.content')
		}
		this.overlay = $('<div>')
			.addClass('oc-dialog-dim')
			.appendTo(contentDiv)
		this.overlay.on('click keydown keyup', function(event) {
			if (event.target !== self.$dialog.get(0) && self.$dialog.find($(event.target)).length === 0) {
				event.preventDefault()
				event.stopPropagation()

			}
		})
	},
	_destroyOverlay() {
		if (!this.options.modal) {
			return
		}

		if (this.overlay) {
			this.overlay.off('click keydown keyup')
			this.overlay.remove()
			this.overlay = null
		}
	},
	widget() {
		return this.$dialog
	},
	setEnterCallback(callback) {
		this.enterCallback = callback
	},
	unsetEnterCallback() {
		this.enterCallback = null
	},
	close() {
		this._destroyOverlay()
		const self = this
		// Ugly hack to catch remaining keyup events.
		setTimeout(function() {
			self._trigger('close', self)
		}, 200)

		self.$dialog.remove()
		this.destroy()
	},
	destroy() {
		if (this.$title) {
			this.$title.remove()
		}
		if (this.$buttonrow) {
			this.$buttonrow.remove()
		}

		if (this.originalTitle) {
			this.element.attr('title', this.originalTitle)
		}
		this.element.removeClass('oc-dialog-content')
			.css(this.originalCss).detach().insertBefore(this.$dialog)
		this.$dialog.remove()
	},
})
