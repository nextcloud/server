/**
 * SPDX-FileCopyrightText: 2012 Jakob Sack owncloud@jakobsack.de
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
 
// start worker once page has loaded
window.addEventListener('DOMContentLoaded', function(){
	$.get( OC.getRootPath()+'/cron.php' );

	$('.section .icon-info').tooltip({
		placement: 'right'
	});
});
