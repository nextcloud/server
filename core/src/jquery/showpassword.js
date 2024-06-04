/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * @name Show Password
 * @description
 * @version 1.3.0
 * @requires Jquery 1.5
 *
 * @author Jan Jarfalk <jan.jarfalk@unwrongest.com>
 * author-website http://www.unwrongest.com
 *
 * special-thanks Michel Gratton
 *
 * @license MIT
 */
$.fn.extend({
	showPassword(c) {

		// Setup callback object
		const callback = { fn: null, args: {} }
		callback.fn = c

		// Clones passwords and turn the clones into text inputs
		const cloneElement = function(element) {

			const $element = $(element)

			const $clone = $('<input />')

			// Name added for JQuery Validation compatibility
			// Element name is required to avoid script warning.
			$clone.attr({
				type: 'text',
				class: $element.attr('class'),
				style: $element.attr('style'),
				size: $element.attr('size'),
				name: $element.attr('name') + '-clone',
				tabindex: $element.attr('tabindex'),
				autocomplete: 'off',
			})

			if ($element.attr('placeholder') !== undefined) {
				$clone.attr('placeholder', $element.attr('placeholder'))
			}

			return $clone

		}

		// Transfers values between two elements
		const update = function(a, b) {
			b.val(a.val())
		}

		// Shows a or b depending on checkbox
		const setState = function(checkbox, a, b) {

			if (checkbox.is(':checked')) {
				update(a, b)
				b.show()
				a.hide()
			} else {
				update(b, a)
				b.hide()
				a.show()
			}

		}

		return this.each(function() {

			const $input = $(this)
			const $checkbox = $($input.data('typetoggle'))

			// Create clone
			const $clone = cloneElement($input)
			$clone.insertAfter($input)

			// Set callback arguments
			if (callback.fn) {
				callback.args.input = $input
				callback.args.checkbox = $checkbox
				callback.args.clone = $clone
			}

			$checkbox.bind('click', function() {
				setState($checkbox, $input, $clone)
			})

			$input.bind('keyup', function() {
				update($input, $clone)
			})

			$clone.bind('keyup', function() {
				update($clone, $input)

				// Added for JQuery Validation compatibility
				// This will trigger validation if it's ON for keyup event
				$input.trigger('keyup')

			})

			// Added for JQuery Validation compatibility
			// This will trigger validation if it's ON for blur event
			$clone.bind('blur', function() {
				$input.trigger('focusout')
			})

			setState($checkbox, $input, $clone)

			// set type of password field clone (type=text) to password right on submit
			// to prevent browser save the value of this field
			$clone.closest('form').submit(function(e) {
				// .prop has to be used, because .attr throws
				// an error while changing a type of an input
				// element
				$clone.prop('type', 'password')
			})

			if (callback.fn) {
				callback.fn(callback.args)
			}

		})
	},
})
