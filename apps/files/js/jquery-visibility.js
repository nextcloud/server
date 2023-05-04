/*!
 * jquery-visibility v1.0.11
 * Page visibility shim for jQuery.
 *
 * Project Website: http://mths.be/visibility
 *
 * @version 1.0.11
 * @license MIT.
 * @author Mathias Bynens - @mathias
 * @author Jan Paepke - @janpaepke
 */
;(function (root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define(['jquery'], function ($) {
			return factory(root, $);
		});
	} else if (typeof exports === 'object') {
		// Node/CommonJS
		module.exports = factory(root, require('jquery'));
	} else {
		// Browser globals
		factory(root, jQuery);
	}
}(this, function(window, $, undefined) {
	"use strict";

	var
		document = window.document,
		property, // property name of document, that stores page visibility
		vendorPrefixes = ['webkit', 'o', 'ms', 'moz', ''],
		$support = $.support || {},
	// In Opera, `'onfocusin' in document == true`, hence the extra `hasFocus` check to detect IE-like behavior
		eventName = 'onfocusin' in document && 'hasFocus' in document ?
			'focusin focusout' :
			'focus blur';

	var prefix;
	while ((prefix = vendorPrefixes.pop()) !== undefined) {
		property = (prefix ? prefix + 'H': 'h') + 'idden';
		$support.pageVisibility = document[property] !== undefined;
		if ($support.pageVisibility) {
			eventName = prefix + 'visibilitychange';
			break;
		}
	}

	// normalize to and update document hidden property
	function updateState() {
		if (property !== 'hidden') {
			document.hidden = $support.pageVisibility ? document[property] : undefined;
		}
	}
	updateState();

	$(/blur$/.test(eventName) ? window : document).on(eventName, function(event) {
		var type = event.type;
		var originalEvent = event.originalEvent;

		// Avoid errors from triggered native events for which `originalEvent` is
		// not available.
		if (!originalEvent) {
			return;
		}

		var toElement = originalEvent.toElement;

		// If it’s a `{focusin,focusout}` event (IE), `fromElement` and `toElement`
		// should both be `null` or `undefined`; else, the page visibility hasn’t
		// changed, but the user just clicked somewhere in the doc. In IE9, we need
		// to check the `relatedTarget` property instead.
		if (
			!/^focus./.test(type) || (
				toElement === undefined &&
				originalEvent.fromElement === undefined &&
				originalEvent.relatedTarget === undefined
			)
		) {
			$(document).triggerHandler(
				property && document[property] || /^(?:blur|focusout)$/.test(type) ?
					'hide' :
					'show'
			);
		}
		// and update the current state
		updateState();
	});
}));
