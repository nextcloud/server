/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', function () {

	$('#body-public').find('.header-right .menutoggle').click(function() {
		$(this).next('.popovermenu').toggleClass('open');
	});

	$('#save-external-share').click(function () {
		$('#external-share-menu-item').toggleClass('hidden')
		$('#remote_address').focus();
	});

	$(document).mouseup(function(e) {
		var toggle = $('#body-public').find('.header-right .menutoggle');
		var container = toggle.next('.popovermenu');

		// if the target of the click isn't the menu toggle, nor a descendant of the
		// menu toggle, nor the container nor a descendant of the container
		if (!toggle.is(e.target) && toggle.has(e.target).length === 0 &&
			!container.is(e.target) && container.has(e.target).length === 0) {
			container.removeClass('open');
		}
	});

});
