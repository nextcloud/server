/* global t */

/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

import $ from 'jquery'

/**
 * @todo Write documentation
 * @namespace OC.Notification
 */

/**
 * @todo remove because it seems unused
 * @type {Array}
 */
export let queuedNotifications = [];

/**
 * @todo make private because it seems unused outside of this module
 */
export let getDefaultNotificationFunction = null;

/**
 * @type Array<int>
 * @description array of notification timers
 * @todo make private and const because it seems unused outside of this module
 */
export let notificationTimers = [];

/**
 * @param callback
 * @todo Write documentation
 */
export function setDefault (callback) {
	getDefaultNotificationFunction = callback;
}

/**
 * Hides a notification.
 *
 * If a row is given, only hide that one.
 * If no row is given, hide all notifications.
 *
 * @param {jQuery} [$row] notification row
 * @param {Function} [callback] callback
 */
export function hide ($row, callback) {
	const $notification = $('#notification');

	if (_.isFunction($row)) {
		// first arg is the callback
		callback = $row;
		$row = undefined;
	}

	if (!$row) {
		console.warn('Missing argument $row in OC.Notification.hide() call, caller needs to be adjusted to only dismiss its own notification');
		// assume that the row to be hidden is the first one
		$row = $notification.find('.row:first');
	}

	if ($row && $notification.find('.row').length > 1) {
		// remove the row directly
		$row.remove();
		if (callback) {
			callback.call();
		}
		return;
	}

	_.defer(() => {
		// fade out is supposed to only fade when there is a single row
		// however, some code might call hide() and show() directly after,
		// which results in more than one element
		// in this case, simply delete that one element that was supposed to
		// fade out
		//
		// FIXME: remove once all callers are adjusted to only hide their own notifications
		if ($notification.find('.row').length > 1) {
			$row.remove();
			return;
		}

		// else, fade out whatever was present
		$notification.fadeOut('400', function () {
			if (isHidden()) {
				if (getDefaultNotificationFunction) {
					getDefaultNotificationFunction.call();
				}
			}
			if (callback) {
				callback.call();
			}
			$notification.empty();
		});
	});
}

/**
 * Shows a notification as HTML without being sanitized before.
 * If you pass unsanitized user input this may lead to a XSS vulnerability.
 * Consider using show() instead of showHTML()
 *
 * @param {string} html Message to display
 * @param {Object} [options] options
 * @param {string} [options.type] notification type
 * @param {int} [options.timeout=0] timeout value, defaults to 0 (permanent)
 * @return {jQuery} jQuery element for notification row
 */
export function showHtml (html, options) {
	options = options || {};
	_.defaults(options, {
		timeout: 0
	});

	const $notification = $('#notification');
	if (isHidden()) {
		$notification.fadeIn().css('display', 'inline-block');
	}
	const $row = $('<div class="row"></div>');
	if (options.type) {
		$row.addClass('type-' + options.type);
	}
	if (options.type === 'error') {
		// add a close button
		const $closeButton = $('<a class="action close icon-close" href="#"></a>');
		$closeButton.attr('alt', t('core', 'Dismiss'));
		$row.append($closeButton);
		$closeButton.one('click', function () {
			hide($row);
			return false;
		});
		$row.addClass('closeable');
	}

	$row.prepend(html);
	$notification.append($row);

	if (options.timeout > 0) {
		// register timeout to vanish notification
		notificationTimers.push(setTimeout(function () {
			hide($row);
		}, (options.timeout * 1000)));
	}

	return $row;
}


/**
 * Shows a sanitized notification
 *
 * @param {string} text Message to display
 * @param {Object} [options] options
 * @param {string} [options.type] notification type
 * @param {int} [options.timeout=0] timeout value, defaults to 0 (permanent)
 * @return {jQuery} jQuery element for notification row
 */
export function show (text, options) {
	return showHtml($('<div/>').text(text).html(), options);
}

/**
 * Updates (replaces) a sanitized notification.
 *
 * @param {string} text Message to display
 * @return {jQuery} JQuery element for notificaiton row
 */
export function showUpdate (text) {
	const $notification = $('#notification');
	// sanitise
	const $html = $('<div/>').text(text).html();

	// new notification
	if (text && $notification.find('.row').length == 0) {
		return showHtml($html);
	}

	const $row = $('<div class="row"></div>').prepend($html);

	// just update html in notification
	$notification.html($row);

	return $row;
}

/**
 * Shows a notification that disappears after x seconds, default is
 * 7 seconds
 *
 * @param {string} text Message to show
 * @param {array} [options] options array
 * @param {int} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
 * @param {string} [options.type] notification type
 */
export function showTemporary (text, options) {
	const defaults = {
		isHTML: false,
		timeout: 7
	};
	options = options || {};
	// merge defaults with passed in options
	_.defaults(options, defaults);

	if (options.isHTML) {
		return showHtml(text, options);
	} else {
		return show(text, options);
	}
}

/**
 * Returns whether a notification is hidden.
 * @return {boolean}
 */
export function isHidden () {
	return !$("#notification").find('.row').length;
}
