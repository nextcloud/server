/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

 window.addEventListener('DOMContentLoaded', function() {
	 var noteElmt = document.getElementById('notemenu')
	 if	(noteElmt) {
		var noteHtml = noteElmt.outerHTML
		$(noteHtml).insertBefore('#header-primary-action');
		$('#notemenu').removeClass('hidden');
		OC.registerMenu($('#notemenu .menutoggle'), $('#notemenu .menu'))
	 }
 })