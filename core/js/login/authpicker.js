/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
jQuery(document).ready(function() {
	$('#app-token-login').click(function (e) {
		e.preventDefault();
		$(this).addClass('hidden');
		$('#redirect-link').addClass('hidden');
		$('#app-token-login-field').removeClass('hidden');
	});

	document.getElementById('login-form').addEventListener('submit', function (e) {
		e.preventDefault();
		document.location.href = e.target.attributes.action.value
	})

	$('#login-form input').removeAttr('disabled');
})
