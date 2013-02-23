/*! http://mths.be/visibility v1.0.5 by @mathias */
(function (window, document, $, undefined) {

	var prefix,
		property,
		// In Opera, `'onfocusin' in document == true`, hence the extra `hasFocus` check to detect IE-like behavior
		eventName = 'onfocusin' in document && 'hasFocus' in document ? 'focusin focusout' : 'focus blur',
		prefixes = ['', 'moz', 'ms', 'o', 'webkit'],
		$support = $.support,
		$event = $.event;

	while ((property = prefix = prefixes.pop()) != undefined) {
		property = (prefix ? prefix + 'H' : 'h') + 'idden';
		if ($support.pageVisibility = typeof document[property] == 'boolean') {
			eventName = prefix + 'visibilitychange';
			break;
		}
	}

	$(/blur$/.test(eventName) ? window : document).on(eventName, function (event) {
		var type = event.type,
			originalEvent = event.originalEvent;
		// If it’s a `{focusin,focusout}` event (IE), `fromElement` and `toElement` should both be `null` or `undefined`;
		// else, the page visibility hasn’t changed, but the user just clicked somewhere in the doc.
		// In IE9, we need to check the `relatedTarget` property instead.
		if (!/^focus./.test(type) || originalEvent == undefined || (originalEvent.toElement == undefined && originalEvent.fromElement == undefined && originalEvent.relatedTarget == undefined)) {
			$event.trigger((property && document[property] || /^(?:blur|focusout)$/.test(type) ? 'hide' : 'show') + '.visibility');
		}
	});

}(this, document, jQuery));
