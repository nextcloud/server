/**
 * @copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 * @copyright (c) 2016 ownCloud Inc
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

(function(OC, OCA, OCP, t, $) {
	"use strict";

	OCA.UpdateNotification = OCA.UpdateNotification || {};
	OCA.UpdateNotification.Components = OCA.UpdateNotification.Components || {};

	OCA.UpdateNotification.Components.Root = {
		template: '' +
		'<div id="updatenotification" class="followupsection">' +
		'	<p>' +
		'		<template v-if="isNewVersionAvailable">' +
		'			<strong>{{newVersionAvailableString}}</strong>' +
		'			<input v-if="updaterEnabled" type="button" @click="clickUpdaterButton" id="oca_updatenotification_button" value="' + t('updatenotification', 'Open updater') + '">' +
		'				<a v-if="downloadLink" :href="downloadLink" class="button" :class="{ hidden: !updaterEnabled }">' + t('updatenotification', 'Download now') + '</a>' +
		'		</template>' +
		'		<template v-else-if="!isUpdateChecked">' + t('updatenotification', 'The update check is not yet finished. Please refresh the page.') + '</template>' +
		'		<template v-else>' +
		'			' + t('updatenotification', 'Your version is up to date.') + '' +
		'			<span class="icon-info svg" :title="lastCheckedOnString"></span>' +
		'		</template>' +
		'' +
		'		<template v-if="!isDefaultUpdateServerURL">' +
		'		<br />' +
		'		<em>' +
		'		' + t('updatenotification', 'A non-default update server is in use to be checked for updates:') +
		'		<code>{{updateServerURL}}</code>' +
		'		</em>' +
		'		</template>' +
		'	</p>' +
		'' +
		'	<p>' +
		'		<label for="release-channel">' + t('updatenotification', 'Update channel:') + '</label>' +
		'		<select id="release-channel" v-model="currentChannel" @change="changeReleaseChannel">' +
		'			<option v-for="channel in channels" :value="channel">{{channel}}</option>' +
		'		</select>' +
		'		<span id="channel_save_msg" class="msg"></span><br />' +
		'		<em>' + t('updatenotification', 'You can always update to a newer version / experimental channel. But you can never downgrade to a more stable channel.') + '</em><br />' +
		'		<em>' + t('updatenotification', 'Note that after a new release it can take some time before it shows up here. We roll out new versions spread out over time to our users and sometimes skip a version when issues are found.') + '</em>' +
		'	</p>' +
		'' +
		'	<p id="oca_updatenotification_groups">' +
		'		' + t('updatenotification', 'Notify members of the following groups about available updates:') +
		'		<input name="oca_updatenotification_groups_list" type="hidden" id="oca_updatenotification_groups_list" v-model="notifyGroups" @change="saveNotifyGroups" :value="notifyGroups" style="width: 400px"><br />' +
		'		<em v-if="currentChannel === \'daily\' || currentChannel === \'git\'">' + t('updatenotification', 'Only notification for app updates are available.') + '</em>' +
		'		<em v-if="currentChannel === \'daily\'">' + t('updatenotification', 'The selected update channel makes dedicated notifications for the server obsolete.') + '</em>' +
		'		<em v-if="currentChannel === \'git\'">' + t('updatenotification', 'The selected update channel does not support updates of the server.') + '</em>' +
		'	</p>' +
		'</div>',

		el: '#updatenotification',
		data: {
			newVersionString: '',
			lastCheckedDate: '',
			isUpdateChecked: false,
			updaterEnabled: true,
			downloadLink: '',
			isNewVersionAvailable: false,
			updateServerURL: '',
			currentChannel: '',
			channels: [],
			notifyGroups: '',
			isDefaultUpdateServerURL: true
		},

		_$el: null,
		_$releaseChannel: null,
		_$notifyGroups: null,

		computed: {
			newVersionAvailableString: function() {
				return t('updatenotification', 'A new version is available: {newVersionString}', {
					newVersionString: this.newVersionString
				});
			},
			lastCheckedOnString: function() {
				return t('updatenotification', 'Checked on {lastCheckedDate}', {
					lastCheckedDate: this.lastCheckedDate
				});
			}
		},

		methods: {
			/**
			 * Creates a new authentication token and loads the updater URL
			 */
			clickUpdaterButton: function() {
				$.ajax({
					url: OC.generateUrl('/apps/updatenotification/credentials')
				}).success(function(data) {
					$.ajax({
						url: OC.getRootPath()+'/updater/',
						headers: {
							'X-Updater-Auth': data
						},
						method: 'POST',
						success: function(data){
							if(data !== 'false') {
								var body = $('body');
								$('head').remove();
								body.html(data);

								// Eval the script elements in the response
								var dom = $(data);
								dom.filter('script').each(function() {
									eval(this.text || this.textContent || this.innerHTML || '');
								});

								body.removeAttr('id');
								body.attr('id', 'body-settings');
							}
						},
						error: function() {
							OC.Notification.showTemporary(t('updatenotification', 'Could not start updater, please try the manual update'));
							this.updaterEnabled = false;
						}.bind(this)
					});
				}.bind(this));
			},
			changeReleaseChannel: function() {
				this.currentChannel = this._$releaseChannel.val();

				$.ajax({
					url: OC.generateUrl('/apps/updatenotification/channel'),
					type: 'POST',
					data: {
						'channel': this.currentChannel
					},
					success: function (data) {
						OC.msg.finishedAction('#channel_save_msg', data);
					}
				});
			},
			saveNotifyGroups: function(e) {
				var groups = e.val || [];
				groups = JSON.stringify(groups);
				OCP.AppConfig.setValue('updatenotification', 'notify_groups', groups);
			}
		},

		mounted: function () {
			this._$el = $(this.$el);
			this._$releaseChannel = this._$el.find('#release-channel');
			this._$notifyGroups = this._$el.find('#oca_updatenotification_groups_list');
			this._$notifyGroups.on('change', function () {
				this.$emit('input');
			}.bind(this));
		},

		updated: function () {
			OC.Settings.setupGroupsSelect(this._$notifyGroups);
			this._$el.find('.icon-info').tooltip({placement: 'right'});
		}
	};
})(OC, OCA, OCP, t, $);
