<template>
	<div id="updatenotification" class="followupsection">
		<div class="update">
			<template v-if="isNewVersionAvailable">
				<p v-if="versionIsEol">
					<span class="warning">
						<span class="icon icon-error-white"></span>
						{{ t('updatenotification', 'The version you are running is not maintained anymore. Please make sure to update to a supported version as soon as possible.') }}
					</span>
				</p>

				<p>
					<span v-html="newVersionAvailableString"></span><br>
					<span v-if="!isListFetched" class="icon icon-loading-small"></span>
					<span v-html="statusText"></span>
				</p>

				<template v-if="missingAppUpdates.length">
					<h3 @click="toggleHideMissingUpdates">
						{{ t('updatenotification', 'Apps missing updates') }}
						<span v-if="!hideMissingUpdates" class="icon icon-triangle-n"></span>
						<span v-if="hideMissingUpdates" class="icon icon-triangle-s"></span>
					</h3>
					<ul class="applist" v-if="!hideMissingUpdates">
						<li v-for="app in missingAppUpdates"><a :href="'https://apps.nextcloud.com/apps/' + app.appId" :title="t('settings', 'View in store')">{{app.appName}} ↗</a></li>
					</ul>
				</template>

				<template v-if="availableAppUpdates.length">
					<h3 @click="toggleHideAvailableUpdates">
						{{ t('updatenotification', 'Apps with available updates') }}
						<span v-if="!hideAvailableUpdates" class="icon icon-triangle-n"></span>
						<span v-if="hideAvailableUpdates" class="icon icon-triangle-s"></span>
					</h3>
					<ul class="applist">
						<li v-for="app in availableAppUpdates" v-if="!hideAvailableUpdates"><a :href="'https://apps.nextcloud.com/apps/' + app.appId" :title="t('settings', 'View in store')">{{app.appName}} ↗</a></li>
					</ul>
				</template>

				<p>
					<a v-if="updaterEnabled" href="#" class="button" @click="clickUpdaterButton">{{ t('updatenotification', 'Open updater') }}</a>
					<a v-if="downloadLink" :href="downloadLink" class="button" :class="{ hidden: !updaterEnabled }">{{ t('updatenotification', 'Download now') }}</a>
				</p>
				<div class="whatsNew" v-if="whatsNew">
					<div class="toggleWhatsNew">
						<span v-click-outside="hideMenu" @click="toggleMenu">{{ t('updatenotification', 'What\'s new?') }}</span>
						<div class="popovermenu" :class="{ 'menu-center': true, open: openedWhatsNew }">
							<popover-menu :menu="whatsNew" />
						</div>
					</div>
				</div>
			</template>
			<template v-else-if="!isUpdateChecked">{{ t('updatenotification', 'The update check is not yet finished. Please refresh the page.') }}</template>
			<template v-else>
				{{ t('updatenotification', 'Your version is up to date.') }}
				<span class="icon-info svg" :title="lastCheckedOnString"></span>
			</template>

			<template v-if="!isDefaultUpdateServerURL">
				<p>
					<em>{{ t('updatenotification', 'A non-default update server is in use to be checked for updates:') }} <code>{{updateServerURL}}</code></em>
				</p>
			</template>
		</div>

		<p>
			<label for="release-channel">{{ t('updatenotification', 'Update channel:') }}</label>
			<select id="release-channel" v-model="currentChannel" @change="changeReleaseChannel">
				<option v-for="channel in channels" :value="channel">{{channel}}</option>
			</select>
			<span id="channel_save_msg" class="msg"></span><br />
			<em>{{ t('updatenotification', 'You can always update to a newer version / experimental channel. But you can never downgrade to a more stable channel.') }}</em><br />
			<em>{{ t('updatenotification', 'Note that after a new release it can take some time before it shows up here. We roll out new versions spread out over time to our users and sometimes skip a version when issues are found.') }}</em>
		</p>

		<p class="channel-description">
			<span v-html="productionInfoString"></span><br>
			<span v-html="stableInfoString"></span><br>
			<span v-html="betaInfoString"></span>
		</p>

		<p id="oca_updatenotification_groups">
			{{ t('updatenotification', 'Notify members of the following groups about available updates:') }}
			<v-select multiple :value="notifyGroups" :options="availableGroups"></v-select><br />
			<em v-if="currentChannel === 'daily' || currentChannel === 'git'">{{ t('updatenotification', 'Only notification for app updates are available.') }}</em>
			<em v-if="currentChannel === 'daily'">{{ t('updatenotification', 'The selected update channel makes dedicated notifications for the server obsolete.') }}</em>
			<em v-if="currentChannel === 'git'">{{ t('updatenotification', 'The selected update channel does not support updates of the server.') }}</em>
		</p>
	</div>
</template>

<script>
	import vSelect from 'vue-select';
	import popoverMenu from './popoverMenu';
	import ClickOutside from 'vue-click-outside';

	export default {
		name: 'root',
		components: {
			vSelect,
			popoverMenu,
		},
		directives: {
			ClickOutside
		},
		data: function () {
			return {
				newVersionString: '',
				lastCheckedDate: '',
				isUpdateChecked: false,
				updaterEnabled: true,
				versionIsEol: false,
				downloadLink: '',
				isNewVersionAvailable: false,
				updateServerURL: '',
				changelogURL: '',
				whatsNewData: [],
				currentChannel: '',
				channels: [],
				notifyGroups: '',
				availableGroups: [],
				isDefaultUpdateServerURL: true,
				enableChangeWatcher: false,

				availableAppUpdates: [],
				missingAppUpdates: [],
				appStoreFailed: false,
				appStoreDisabled: false,
				isListFetched: false,
				hideMissingUpdates: false,
				hideAvailableUpdates: true,
				openedWhatsNew: false,
			};
		},

		_$el: null,
		_$releaseChannel: null,
		_$notifyGroups: null,

		watch: {
			notifyGroups: function(selectedOptions) {
				if (!this.enableChangeWatcher) {
					return;
				}

				var selectedGroups = [];
				_.each(selectedOptions, function(group) {
					selectedGroups.push(group.value);
				});

				OCP.AppConfig.setValue('updatenotification', 'notify_groups', JSON.stringify(selectedGroups));
			},
			isNewVersionAvailable: function() {
				if (!this.isNewVersionAvailable) {
					return;
				}

				$.ajax({
					url: OC.linkToOCS('apps/updatenotification/api/v1/applist', 2) + this.newVersion,
					type: 'GET',
					beforeSend: function (request) {
						request.setRequestHeader('Accept', 'application/json');
					},
					success: function(response) {
						this.availableAppUpdates = response.ocs.data.available;
						this.missingAppUpdates = response.ocs.data.missing;
						this.isListFetched = true;
						this.appStoreFailed = false;
					}.bind(this),
					error: function(xhr) {
						this.availableAppUpdates = [];
						this.missingAppUpdates = [];
						this.appStoreDisabled = xhr.responseJSON.ocs.data.appstore_disabled;
						this.isListFetched = true;
						this.appStoreFailed = true;
					}.bind(this)
				});
			}
		},

		computed: {
			newVersionAvailableString: function() {
				return t('updatenotification', 'A new version is available: <strong>{newVersionString}</strong>', {
					newVersionString: this.newVersionString
				});
			},

			lastCheckedOnString: function() {
				return t('updatenotification', 'Checked on {lastCheckedDate}', {
					lastCheckedDate: this.lastCheckedDate
				});
			},

			statusText: function() {
				if (!this.isListFetched) {
					return t('updatenotification', 'Checking apps for compatible updates');
				}

				if (this.appStoreDisabled) {
					return t('updatenotification', 'Please make sure your config.php does not set <samp>appstoreenabled</samp> to false.');
				}

				if (this.appStoreFailed) {
					return t('updatenotification', 'Could not connect to the appstore or the appstore returned no updates at all. Search manually for updates or make sure your server has access to the internet and can connect to the appstore.');
				}

				return this.missingAppUpdates.length === 0 ? t('updatenotification', '<strong>All</strong> apps have an update for this version available', this) : n('updatenotification',
					'<strong>%n</strong> app has no update for this version available',
					'<strong>%n</strong> apps have no update for this version available',
					this.missingAppUpdates.length);
			},

			productionInfoString: function() {
				return t('updatenotification', '<strong>production</strong> will always provide the latest patch level, but not update to the next major release immediately. That update usually happens with the second minor release (x.0.2).');
			},

			stableInfoString: function() {
				return t('updatenotification', '<strong>stable</strong> is the most recent stable version. It is suited for regular use and will always update to the latest major version.');
			},

			betaInfoString: function() {
				return t('updatenotification', '<strong>beta</strong> is a pre-release version only for testing new features, not for production environments.');
			},

			whatsNew: function () {
				if(this.whatsNewData.length === 0) {
					return null;
				}
				var whatsNew = [];
				for (var i in this.whatsNewData) {
					whatsNew[i] = { icon: 'icon-checkmark', longtext: this.whatsNewData[i] };
				}
				if(this.changelogURL) {
					whatsNew.push({
						href: this.changelogURL,
						text: t('updatenotificaiton', 'View changelog'),
						icon: 'icon-link',
						target: '_blank',
						action: ''
					});
				}
				return whatsNew;
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
			toggleHideMissingUpdates: function() {
				this.hideMissingUpdates = !this.hideMissingUpdates;
			},
			toggleHideAvailableUpdates: function() {
				this.hideAvailableUpdates = !this.hideAvailableUpdates;
			},
			toggleMenu: function() {
				this.openedWhatsNew = !this.openedWhatsNew;
			},
			hideMenu: function() {
				this.openedWhatsNew = false;
			},
		},
		beforeMount: function() {
			// Parse server data
			var data = JSON.parse($('#updatenotification').attr('data-json'));

			this.newVersion = data.newVersion;
			this.newVersionString = data.newVersionString;
			this.lastCheckedDate = data.lastChecked;
			this.isUpdateChecked = data.isUpdateChecked;
			this.updaterEnabled = data.updaterEnabled;
			this.downloadLink = data.downloadLink;
			this.isNewVersionAvailable = data.isNewVersionAvailable;
			this.updateServerURL = data.updateServerURL;
			this.currentChannel = data.currentChannel;
			this.channels = data.channels;
			this.notifyGroups = data.notifyGroups;
			this.isDefaultUpdateServerURL = data.isDefaultUpdateServerURL;
			this.versionIsEol = data.versionIsEol;
			if(data.changes && data.changes.changelogURL) {
				this.changelogURL = data.changes.changelogURL;
			}
			if(data.changes && data.changes.whatsNew) {
				if(data.changes.whatsNew.admin) {
					this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.admin);
				}
				this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.regular);
			}
		},
		mounted: function () {
			this._$el = $(this.$el);
			this._$releaseChannel = this._$el.find('#release-channel');
			this._$notifyGroups = this._$el.find('#oca_updatenotification_groups_list');
			this._$notifyGroups.on('change', function () {
				this.$emit('input');
			}.bind(this));

			$.ajax({
				url: OC.linkToOCS('cloud', 2)+ '/groups',
				dataType: 'json',
				success: function(data) {
					var results = [];
					$.each(data.ocs.data.groups, function(i, group) {
						results.push({value: group, label: group});
					});

					this.availableGroups = results;
					this.enableChangeWatcher = true;
				}.bind(this)
			});
		},

		updated: function () {
			this._$el.find('.icon-info').tooltip({placement: 'right'});
		}
	}
</script>
