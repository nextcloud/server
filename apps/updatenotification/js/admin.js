/**
 * Copyright (c) 2016 ownCloud Inc
 *
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * Creates a new authentication token and loads the updater URL
 */
var loginToken = '';
$(document).ready(function(){
	$('#oca_updatenotification_button').click(function() {
		// Load the new token
		$.ajax({
			url: OC.generateUrl('/apps/updatenotification/credentials')
		}).success(function(data) {
			loginToken = data;
			$.ajax({
				url: OC.webroot+'/updater/',
				headers: {
					'X-Updater-Auth': loginToken
				},
				method: 'POST',
				success: function(data){
					if(data !== 'false') {
						var body = $('body');
						$('head').remove();
						body.html(data);
						body.removeAttr('id');
						body.attr('id', 'body-settings');
					}
				},
				error: function(){
					OC.Notification.showTemporary(t('updatenotification', 'Could not start updater, please try the manual update'));
					$('#oca_updatenotification_button').addClass('hidden');
					$('#oca_updatenotification_section .button').removeClass('hidden');
				}
			});
		});
	});

	$('#release-channel').change(function() {
		var newChannel = $('#release-channel').find(":selected").val();

		if (newChannel === 'git' || newChannel === 'daily') {
			$('#oca_updatenotification_groups em').removeClass('hidden');
		} else {
			$('#oca_updatenotification_groups em').addClass('hidden');
		}

		$.post(
			OC.generateUrl('/apps/updatenotification/channel'),
			{
				'channel': newChannel
			},
			function(data){
				OC.msg.finishedAction('#channel_save_msg', data);
			}
		);
	});

	var $notificationTargetGroups = $('#oca_updatenotification_groups_list');
	OC.Settings.setupGroupsSelect($notificationTargetGroups);
	$notificationTargetGroups.change(function(ev) {
		var groups = ev.val || [];
		groups = JSON.stringify(groups);
		OC.AppConfig.setValue('updatenotification', 'notify_groups', groups);
	});
});
