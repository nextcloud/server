/**
 * Copyright (c) 2015 ownCloud Inc
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * This only gets loaded if an update is available, but the notifications app has been disabled (but updatechecker isn't disabled).
 * The goal here is to make sure admins still get an indication there is an update available even if push notifications are disabled.
 *
 * Since this is a fallback method that only gets used when the notifications app is disabled, we use toasts to notify, but there's no 
 * intelligence. To avoid constant nagging upon every page reload, we only generate the toasts one day a week on the half hour. This tries to strike 
 * a balance for an admin that disables push notifications. The idea is they still get a periodic reminder of an update being available, 
 * without constant nagging every day upon every page reload. This hopefully reduces likelihood of needing to disable update 
 * checks outright (e.g. by setting `updatechecker => false` in their `config.php` or disabling this app outright too).
 *
 */
window.addEventListener('DOMContentLoaded', function(){
	var text = t('core', '{version} is available. Get more information on how to update.', {version: oc_updateState.updateVersion}),
		element = $('<a>').attr('href', oc_updateState.updateLink).attr('target','_blank').text(text);
	
    today = new Date();
	dayOfMonth = today.getDate();
	minuteOfHour = today.getMinutes();
	if (dayOfMonth % 7 === 0 && minuteOfHour % 30 === 0) {	// only toast once a week on the half hour
		OC.Notification.showHtml(element.prop('outerHTML'), { type: 'error' });
	}
});
