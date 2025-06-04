/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* globals Snap */
import _ from 'underscore'
import $ from 'jquery'
import moment from 'moment'

import OC from './OC/index.js'
import { initSessionHeartBeat } from './session-heartbeat.ts'
import { setUp as setUpContactsMenu } from './components/ContactsMenu.js'
import { setUp as setUpMainMenu } from './components/MainMenu.js'
import { setUp as setUpUserMenu } from './components/UserMenu.js'
import { interceptRequests } from './utils/xhr-request.js'
import { initFallbackClipboardAPI } from './utils/ClipboardFallback.ts'

// keep in sync with core/css/variables.scss
const breakpointMobileWidth = 1024

const initLiveTimestamps = () => {
	// Update live timestamps every 30 seconds
	setInterval(() => {
		$('.live-relative-timestamp').each(function() {
			const timestamp = parseInt($(this).attr('data-timestamp'), 10)
			$(this).text(moment(timestamp).fromNow())
		})
	}, 30 * 1000)
}

/**
 * Moment doesn't have aliases for every locale and doesn't parse some locale IDs correctly so we need to alias them
 */
const localeAliases = {
	zh: 'zh-cn',
	zh_Hans: 'zh-cn',
	zh_Hans_CN: 'zh-cn',
	zh_Hans_HK: 'zh-cn',
	zh_Hans_MO: 'zh-cn',
	zh_Hans_SG: 'zh-cn',
	zh_Hant: 'zh-hk',
	zh_Hant_HK: 'zh-hk',
	zh_Hant_MO: 'zh-mo',
	zh_Hant_TW: 'zh-tw',
}
let locale = OC.getLocale()
if (Object.prototype.hasOwnProperty.call(localeAliases, locale)) {
	locale = localeAliases[locale]
}

/**
 * Set users locale to moment.js as soon as possible
 */
moment.locale(locale)

/**
 * Initializes core
 */
export const initCore = () => {
	interceptRequests()
	initFallbackClipboardAPI()

	$(window).on('unload.main', () => { OC._unloadCalled = true })
	$(window).on('beforeunload.main', () => {
		// super-trick thanks to http://stackoverflow.com/a/4651049
		// in case another handler displays a confirmation dialog (ex: navigating away
		// during an upload), there are two possible outcomes: user clicked "ok" or
		// "cancel"

		// first timeout handler is called after unload dialog is closed
		setTimeout(() => {
			OC._userIsNavigatingAway = true

			// second timeout event is only called if user cancelled (Chrome),
			// but in other browsers it might still be triggered, so need to
			// set a higher delay...
			setTimeout(() => {
				if (!OC._unloadCalled) {
					OC._userIsNavigatingAway = false
				}
			}, 10000)
		}, 1)
	})
	$(document).on('ajaxError.main', function(event, request, settings) {
		if (settings && settings.allowAuthErrors) {
			return
		}
		OC._processAjaxError(request)
	})

	initSessionHeartBeat()

	OC.registerMenu($('#expand'), $('#expanddiv'), false, true)

	// toggle for menus
	$(document).on('mouseup.closemenus', event => {
		const $el = $(event.target)
		if ($el.closest('.menu').length || $el.closest('.menutoggle').length) {
			// don't close when clicking on the menu directly or a menu toggle
			return false
		}

		OC.hideMenus()
	})

	setUpMainMenu()
	setUpUserMenu()
	setUpContactsMenu()

	// just add snapper for logged in users
	// and if the app doesn't handle the nav slider itself
	if ($('#app-navigation').length && !$('html').hasClass('lte9')
		&& !$('#app-content').hasClass('no-snapper')) {

		// App sidebar on mobile
		const snapper = new Snap({
			element: document.getElementById('app-content'),
			disable: 'right',
			maxPosition: 300, // $navigation-width
			minDragDistance: 100,
		})

		$('#app-content').prepend('<div id="app-navigation-toggle" class="icon-menu" style="display:none" tabindex="0"></div>')

		// keep track whether snapper is currently animating, and
		// prevent to call open or close while that is the case
		// to avoid duplicating events (snap.js doesn't check this)
		let animating = false
		snapper.on('animating', () => {
			// we need this because the trigger button
			// is also implicitly wired to close by snapper
			animating = true
		})
		snapper.on('animated', () => {
			animating = false
		})
		snapper.on('start', () => {
			// we need this because dragging triggers that
			animating = true
		})
		snapper.on('end', () => {
			// we need this because dragging stop triggers that
			animating = false
		})
		snapper.on('open', () => {
			$appNavigation.attr('aria-hidden', 'false')
		})
		snapper.on('close', () => {
			$appNavigation.attr('aria-hidden', 'true')
		})

		// These are necessary because calling open or close
		// on snapper during an animation makes it trigger an
		// unfinishable animation, which itself will continue
		// triggering animating events and cause high CPU load,
		//
		// Ref https://github.com/jakiestfu/Snap.js/issues/216
		const oldSnapperOpen = snapper.open
		const oldSnapperClose = snapper.close
		const _snapperOpen = () => {
			if (animating || snapper.state().state !== 'closed') {
				return
			}
			oldSnapperOpen('left')
		}

		const _snapperClose = () => {
			if (animating || snapper.state().state === 'closed') {
				return
			}
			oldSnapperClose()
		}

		// Needs to be deferred to properly catch in-between
		// events that snap.js is triggering after dragging.
		//
		// Skipped when running unit tests as we are not testing
		// the snap.js workarounds...
		if (!window.TESTING) {
			snapper.open = () => {
				_.defer(_snapperOpen)
			}
			snapper.close = () => {
				_.defer(_snapperClose)
			}
		}

		$('#app-navigation-toggle').click((e) => {
			// close is implicit in the button by snap.js
			if (snapper.state().state !== 'left') {
				snapper.open()
			}
		})
		$('#app-navigation-toggle').keypress(e => {
			if (snapper.state().state === 'left') {
				snapper.close()
			} else {
				snapper.open()
			}
		})

		// close sidebar when switching navigation entry
		const $appNavigation = $('#app-navigation')
		$appNavigation.attr('aria-hidden', 'true')
		$appNavigation.delegate('a, :button', 'click', event => {
			const $target = $(event.target)
			// don't hide navigation when changing settings or adding things
			if ($target.is('.app-navigation-noclose')
				|| $target.closest('.app-navigation-noclose').length) {
				return
			}
			if ($target.is('.app-navigation-entry-utils-menu-button')
				|| $target.closest('.app-navigation-entry-utils-menu-button').length) {
				return
			}
			if ($target.is('.add-new')
				|| $target.closest('.add-new').length) {
				return
			}
			if ($target.is('#app-settings')
				|| $target.closest('#app-settings').length) {
				return
			}
			snapper.close()
		})

		let navigationBarSlideGestureEnabled = false
		let navigationBarSlideGestureAllowed = true
		let navigationBarSlideGestureEnablePending = false

		OC.allowNavigationBarSlideGesture = () => {
			navigationBarSlideGestureAllowed = true

			if (navigationBarSlideGestureEnablePending) {
				snapper.enable()

				navigationBarSlideGestureEnabled = true
				navigationBarSlideGestureEnablePending = false
			}
		}

		OC.disallowNavigationBarSlideGesture = () => {
			navigationBarSlideGestureAllowed = false

			if (navigationBarSlideGestureEnabled) {
				const endCurrentDrag = true
				snapper.disable(endCurrentDrag)

				navigationBarSlideGestureEnabled = false
				navigationBarSlideGestureEnablePending = true
			}
		}

		const toggleSnapperOnSize = () => {
			if ($(window).width() > breakpointMobileWidth) {
				$appNavigation.attr('aria-hidden', 'false')
				snapper.close()
				snapper.disable()

				navigationBarSlideGestureEnabled = false
				navigationBarSlideGestureEnablePending = false
			} else if (navigationBarSlideGestureAllowed) {
				snapper.enable()

				navigationBarSlideGestureEnabled = true
				navigationBarSlideGestureEnablePending = false
			} else {
				navigationBarSlideGestureEnablePending = true
			}
		}

		$(window).resize(_.debounce(toggleSnapperOnSize, 250))

		// initial call
		toggleSnapperOnSize()

	}

	initLiveTimestamps()
}
