/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import _ from 'underscore'
/** @typedef {import('jquery')} jQuery */
import $ from 'jquery'

import { menuSpeed } from './constants.js'

export let currentMenu = null
export let currentMenuToggle = null

/**
 * For menu toggling
 *
 * @param {jQuery} $toggle the toggle element
 * @param {jQuery} $menuEl the menu container element
 * @param {Function | undefined} toggle callback invoked everytime the menu is opened
 * @param {boolean} headerMenu is this a top right header menu?
 * @return {void}
 */
export const registerMenu = function($toggle, $menuEl, toggle, headerMenu) {
	$menuEl.addClass('menu')
	const isClickableElement = $toggle.prop('tagName') === 'A' || $toggle.prop('tagName') === 'BUTTON'

	// On link and button, the enter key trigger a click event
	// Only use the click to avoid two fired events
	$toggle.on(isClickableElement ? 'click.menu' : 'click.menu keyup.menu', function(event) {
		// prevent the link event (append anchor to URL)
		event.preventDefault()

		// allow enter key as a trigger
		if (event.key && event.key !== 'Enter') {
			return
		}

		if ($menuEl.is(currentMenu)) {
			hideMenus()
			return
		} else if (currentMenu) {
			// another menu was open?
			// close it
			hideMenus()
		}

		if (headerMenu === true) {
			$menuEl.parent().addClass('openedMenu')
		}

		// Set menu to expanded
		$toggle.attr('aria-expanded', true)

		$menuEl.slideToggle(menuSpeed, toggle)
		currentMenu = $menuEl
		currentMenuToggle = $toggle
	})
}

/**
 * Unregister a previously registered menu
 *
 * @param {jQuery} $toggle the toggle element
 * @param {jQuery} $menuEl the menu container element
 */
export const unregisterMenu = ($toggle, $menuEl) => {
	// close menu if opened
	if ($menuEl.is(currentMenu)) {
		hideMenus()
	}
	$toggle.off('click.menu').removeClass('menutoggle')
	$menuEl.removeClass('menu')
}

/**
 * Hides any open menus
 *
 * @param {Function} complete callback when the hiding animation is done
 */
export const hideMenus = function(complete) {
	if (currentMenu) {
		const lastMenu = currentMenu
		currentMenu.trigger(new $.Event('beforeHide'))
		currentMenu.slideUp(menuSpeed, function() {
			lastMenu.trigger(new $.Event('afterHide'))
			if (complete) {
				complete.apply(this, arguments)
			}
		})
	}

	// Set menu to closed
	$('.menutoggle').attr('aria-expanded', false)
	if (currentMenuToggle) {
		currentMenuToggle.attr('aria-expanded', false)
	}

	$('.openedMenu').removeClass('openedMenu')
	currentMenu = null
	currentMenuToggle = null
}

/**
 * Shows a given element as menu
 *
 * @param {object} [$toggle] menu toggle
 * @param {object} $menuEl menu element
 * @param {Function} complete callback when the showing animation is done
 */
export const showMenu = ($toggle, $menuEl, complete) => {
	if ($menuEl.is(currentMenu)) {
		return
	}
	hideMenus()
	currentMenu = $menuEl
	currentMenuToggle = $toggle
	$menuEl.trigger(new $.Event('beforeShow'))
	$menuEl.show()
	$menuEl.trigger(new $.Event('afterShow'))
	// no animation
	if (_.isFunction(complete)) {
		complete()
	}
}
