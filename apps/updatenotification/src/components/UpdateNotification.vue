<template>
	<NcSettingsSection id="updatenotification" :title="t('updatenotification', 'Update')">
		<div class="update">
			<template v-if="isNewVersionAvailable">
				<NcNoteCard v-if="versionIsEol" type="warning">
					{{ t('updatenotification', 'The version you are running is not maintained anymore. Please make sure to update to a supported version as soon as possible.') }}
				</NcNoteCard>

				<p>
					<span v-html="newVersionAvailableString" /><br>
					<span v-if="!isListFetched" class="icon icon-loading-small" />
					<span v-html="statusText" />
				</p>

				<template v-if="missingAppUpdates.length">
					<h3 @click="toggleHideMissingUpdates">
						{{ t('updatenotification', 'Apps missing compatible version') }}
						<span v-if="!hideMissingUpdates" class="icon icon-triangle-n" />
						<span v-if="hideMissingUpdates" class="icon icon-triangle-s" />
					</h3>
					<ul v-if="!hideMissingUpdates" class="applist">
						<li v-for="(app, index) in missingAppUpdates" :key="index">
							<a :href="'https://apps.nextcloud.com/apps/' + app.appId" :title="t('settings', 'View in store')">{{ app.appName }} ↗</a>
						</li>
					</ul>
				</template>

				<template v-if="availableAppUpdates.length">
					<h3 @click="toggleHideAvailableUpdates">
						{{ t('updatenotification', 'Apps with compatible version') }}
						<span v-if="!hideAvailableUpdates" class="icon icon-triangle-n" />
						<span v-if="hideAvailableUpdates" class="icon icon-triangle-s" />
					</h3>
					<ul v-if="!hideAvailableUpdates" class="applist">
						<li v-for="(app, index) in availableAppUpdates" :key="index">
							<a :href="'https://apps.nextcloud.com/apps/' + app.appId" :title="t('settings', 'View in store')">{{ app.appName }} ↗</a>
						</li>
					</ul>
				</template>

				<template v-if="!isWebUpdaterRecommended && updaterEnabled && webUpdaterEnabled">
					<h3 class="warning">
						{{ t('updatenotification', 'Please note that the web updater is not recommended with more than 100 users! Please use the command line updater instead!') }}
					</h3>
				</template>

				<div>
					<a v-if="updaterEnabled && webUpdaterEnabled"
						href="#"
						class="button primary"
						@click="clickUpdaterButton">{{ t('updatenotification', 'Open updater') }}</a>
					<a v-if="downloadLink"
						:href="downloadLink"
						class="button"
						:class="{ hidden: !updaterEnabled }">{{ t('updatenotification', 'Download now') }}</a>
					<span v-if="updaterEnabled && !webUpdaterEnabled">
						{{ t('updatenotification', 'Please use the command line updater to update.') }}
					</span>
					<div v-if="whatsNew" class="whatsNew">
						<div class="toggleWhatsNew">
							<a v-click-outside="hideMenu" class="button" @click="toggleMenu">{{ t('updatenotification', 'What\'s new?') }}</a>
							<div class="popovermenu" :class="{ 'menu-center': true, open: openedWhatsNew }">
								<NcPopoverMenu :menu="whatsNew" />
							</div>
						</div>
					</div>
				</div>
			</template>
			<template v-else-if="!isUpdateChecked">
				{{ t('updatenotification', 'The update check is not yet finished. Please refresh the page.') }}
			</template>
			<template v-else>
				{{ t('updatenotification', 'Your version is up to date.') }}
				<span :title="lastCheckedOnString" :aria-label="lastCheckedOnString" class="icon-info svg" />
			</template>

			<template v-if="!isDefaultUpdateServerURL">
				<p class="topMargin">
					<em>{{ t('updatenotification', 'A non-default update server is in use to be checked for updates:') }} <code>{{ updateServerURL }}</code></em>
				</p>
			</template>
		</div>

		<div>
			{{ t('updatenotification', 'You can change the update channel below which also affects the apps management page. E.g. after switching to the beta channel, beta app updates will be offered to you in the apps management page.') }}
		</div>

		<h3 class="update-channel-selector">
			{{ t('updatenotification', 'Update channel:') }}
			<div v-click-outside="closeUpdateChannelMenu" class="update-menu">
				<span class="icon-update-menu" @click="toggleUpdateChannelMenu">
					{{ localizedChannelName }}
					<span class="icon-triangle-s" />
				</span>
				<div class="popovermenu menu menu-center" :class="{ 'show-menu': openedUpdateChannelMenu}">
					<NcPopoverMenu :menu="channelList" />
				</div>
			</div>
		</h3>
		<span id="channel_save_msg" class="msg" /><br>
		<p>
			<em>{{ t('updatenotification', 'You can always update to a newer version. But you can never downgrade to a more stable version.') }}</em><br>
			<em v-html="noteDelayedStableString" />
		</p>

		<p id="oca_updatenotification_groups">
			{{ t('updatenotification', 'Notify members of the following groups about available updates:') }}
			<NcSelect v-model="notifyGroups"
				:options="groups"
				:multiple="true"
				label="displayname"
				:loading="loadingGroups"
				:close-on-select="false"
				@search="searchGroup">
				<template #no-options>
					{{ t('updatenotification', 'No groups') }}
				</template>
			</NcSelect>
			<br>
			<em v-if="currentChannel === 'daily' || currentChannel === 'git'">{{ t('updatenotification', 'Only notifications for app updates are available.') }}</em>
			<em v-if="currentChannel === 'daily'">{{ t('updatenotification', 'The selected update channel makes dedicated notifications for the server obsolete.') }}</em>
			<em v-if="currentChannel === 'git'">{{ t('updatenotification', 'The selected update channel does not support updates of the server.') }}</em>
		</p>
	</NcSettingsSection>
</template>

<script>
import { generateUrl, getRootUrl, generateOcsUrl } from '@nextcloud/router'
import NcPopoverMenu from '@nextcloud/vue/dist/Components/NcPopoverMenu.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import ClickOutside from 'vue-click-outside'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { showSuccess } from '@nextcloud/dialogs'
import debounce from 'debounce'
import { getLoggerBuilder } from '@nextcloud/logger'

const logger = getLoggerBuilder()
	.setApp('updatenotification')
	.detectUser()
	.build()

export default {
	name: 'UpdateNotification',
	components: {
		NcSelect,
		NcPopoverMenu,
		NcSettingsSection,
		NcNoteCard,
	},
	directives: {
		ClickOutside,
	},
	data() {
		return {
			loadingGroups: false,
			newVersionString: '',
			lastCheckedDate: '',
			isUpdateChecked: false,
			webUpdaterEnabled: true,
			isWebUpdaterRecommended: true,
			updaterEnabled: true,
			versionIsEol: false,
			downloadLink: '',
			isNewVersionAvailable: false,
			hasValidSubscription: false,
			updateServerURL: '',
			changelogURL: '',
			whatsNewData: [],
			currentChannel: '',
			channels: [],
			notifyGroups: '',
			groups: [],
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
			openedUpdateChannelMenu: false,
		}
	},

	computed: {
		newVersionAvailableString() {
			return t('updatenotification', 'A new version is available: <strong>{newVersionString}</strong>', {
				newVersionString: this.newVersionString,
			})
		},

		noteDelayedStableString() {
			return t('updatenotification', 'Note that after a new release the update only shows up after the first minor release or later. We roll out new versions spread out over time to our users and sometimes skip a version when issues are found. Learn more about updates and release channels at {link}')
				.replace('{link}', '<a href="https://nextcloud.com/release-channels/">https://nextcloud.com/release-channels/</a>')
		},

		lastCheckedOnString() {
			return t('updatenotification', 'Checked on {lastCheckedDate}', {
				lastCheckedDate: this.lastCheckedDate,
			})
		},

		statusText() {
			if (!this.isListFetched) {
				return t('updatenotification', 'Checking apps for compatible versions')
			}

			if (this.appStoreDisabled) {
				return t('updatenotification', 'Please make sure your config.php does not set <samp>appstoreenabled</samp> to false.')
			}

			if (this.appStoreFailed) {
				return t('updatenotification', 'Could not connect to the App Store or no updates have been returned at all. Search manually for updates or make sure your server has access to the internet and can connect to the App Store.')
			}

			return this.missingAppUpdates.length === 0
				? t('updatenotification', '<strong>All</strong> apps have a compatible version for this Nextcloud version available.', this)
				: n('updatenotification', '<strong>%n</strong> app has no compatible version for this Nextcloud version available.', '<strong>%n</strong> apps have no compatible version for this Nextcloud version available.', this.missingAppUpdates.length)
		},

		whatsNew() {
			if (this.whatsNewData.length === 0) {
				return null
			}
			const whatsNew = []
			for (const i in this.whatsNewData) {
				whatsNew[i] = { icon: 'icon-checkmark', longtext: this.whatsNewData[i] }
			}
			if (this.changelogURL) {
				whatsNew.push({
					href: this.changelogURL,
					text: t('updatenotification', 'View changelog'),
					icon: 'icon-link',
					target: '_blank',
					action: '',
				})
			}
			return whatsNew
		},

		channelList() {
			const channelList = []

			channelList.push({
				text: t('updatenotification', 'Enterprise'),
				longtext: t('updatenotification', 'For enterprise use. Provides always the latest patch level, but will not update to the next major release immediately. That update happens once Nextcloud GmbH has done additional hardening and testing for large-scale and mission-critical deployments. This channel is only available to customers and provides the Nextcloud Enterprise package.'),
				icon: 'icon-star',
				active: this.currentChannel === 'enterprise',
				disabled: !this.hasValidSubscription,
				action: this.changeReleaseChannelToEnterprise,
			})

			channelList.push({
				text: t('updatenotification', 'Stable'),
				longtext: t('updatenotification', 'The most recent stable version. It is suited for regular use and will always update to the latest major version.'),
				icon: 'icon-checkmark',
				active: this.currentChannel === 'stable',
				action: this.changeReleaseChannelToStable,
			})

			channelList.push({
				text: t('updatenotification', 'Beta'),
				longtext: t('updatenotification', 'A pre-release version only for testing new features, not for production environments.'),
				icon: 'icon-category-customization',
				active: this.currentChannel === 'beta',
				action: this.changeReleaseChannelToBeta,
			})

			if (this.isNonDefaultChannel) {
				channelList.push({
					text: this.currentChannel,
					icon: 'icon-rename',
					active: true,
				})
			}

			return channelList
		},

		isNonDefaultChannel() {
			return this.currentChannel !== 'enterprise' && this.currentChannel !== 'stable' && this.currentChannel !== 'beta'
		},

		localizedChannelName() {
			switch (this.currentChannel) {
			case 'enterprise':
				return t('updatenotification', 'Enterprise')
			case 'stable':
				return t('updatenotification', 'Stable')
			case 'beta':
				return t('updatenotification', 'Beta')
			default:
				return this.currentChannel
			}
		},
	},

	watch: {
		notifyGroups(selectedOptions) {
			if (!this.enableChangeWatcher) {
				// The first time is when loading the app
				this.enableChangeWatcher = true
				return
			}

			const groups = this.notifyGroups.map(group => {
				return group.id
			})

			OCP.AppConfig.setValue('updatenotification', 'notify_groups', JSON.stringify(groups))
		},
		isNewVersionAvailable() {
			if (!this.isNewVersionAvailable) {
				return
			}

			axios.get(generateOcsUrl('apps/updatenotification/api/v1/applist/{newVersion}', {
				newVersion: this.newVersion,
			})).then(({ data }) => {
				this.availableAppUpdates = data.ocs.data.available
				this.missingAppUpdates = data.ocs.data.missing
				this.isListFetched = true
				this.appStoreFailed = false
			}).catch(({ data }) => {
				this.availableAppUpdates = []
				this.missingAppUpdates = []
				this.appStoreDisabled = data.ocs.data.appstore_disabled
				this.isListFetched = true
				this.appStoreFailed = true
			})
		},
	},
	beforeMount() {
		// Parse server data
		const data = loadState('updatenotification', 'data')

		this.newVersion = data.newVersion
		this.newVersionString = data.newVersionString
		this.lastCheckedDate = data.lastChecked
		this.isUpdateChecked = data.isUpdateChecked
		this.webUpdaterEnabled = data.webUpdaterEnabled
		this.isWebUpdaterRecommended = data.isWebUpdaterRecommended
		this.updaterEnabled = data.updaterEnabled
		this.downloadLink = data.downloadLink
		this.isNewVersionAvailable = data.isNewVersionAvailable
		this.updateServerURL = data.updateServerURL
		this.currentChannel = data.currentChannel
		this.channels = data.channels
		this.notifyGroups = data.notifyGroups
		this.isDefaultUpdateServerURL = data.isDefaultUpdateServerURL
		this.versionIsEol = data.versionIsEol
		this.hasValidSubscription = data.hasValidSubscription
		if (data.changes && data.changes.changelogURL) {
			this.changelogURL = data.changes.changelogURL
		}
		if (data.changes && data.changes.whatsNew) {
			if (data.changes.whatsNew.admin) {
				this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.admin)
			}
			this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.regular)
		}
	},

	mounted() {
		this.searchGroup()
	},

	methods: {
		searchGroup: debounce(async function(query) {
			this.loadingGroups = true
			try {
				const response = await axios.get(generateOcsUrl('cloud/groups/details'), {
					search: query,
					limit: 20,
					offset: 0,
				})
				this.groups = response.data.ocs.data.groups.sort(function(a, b) {
					return a.displayname.localeCompare(b.displayname)
				})
			} catch (err) {
				logger.error('Could not fetch groups', err)
			} finally {
				this.loadingGroups = false
			}
		}, 500),
		/**
		 * Creates a new authentication token and loads the updater URL
		 */
		clickUpdaterButton() {
			axios.get(generateUrl('/apps/updatenotification/credentials'))
				.then(({ data }) => {
				// create a form to send a proper post request to the updater
					const form = document.createElement('form')
					form.setAttribute('method', 'post')
					form.setAttribute('action', getRootUrl() + '/updater/')

					const hiddenField = document.createElement('input')
					hiddenField.setAttribute('type', 'hidden')
					hiddenField.setAttribute('name', 'updater-secret-input')
					hiddenField.setAttribute('value', data)

					form.appendChild(hiddenField)

					document.body.appendChild(form)
					form.submit()
				})
		},
		changeReleaseChannelToEnterprise() {
			this.changeReleaseChannel('enterprise')
		},
		changeReleaseChannelToStable() {
			this.changeReleaseChannel('stable')
		},
		changeReleaseChannelToBeta() {
			this.changeReleaseChannel('beta')
		},
		changeReleaseChannel(channel) {
			this.currentChannel = channel

			axios.post(generateUrl('/apps/updatenotification/channel'), {
				channel: this.currentChannel,
			}).then(({ data }) => {
				showSuccess(data.data.message)
			})

			this.openedUpdateChannelMenu = false
		},
		toggleUpdateChannelMenu() {
			this.openedUpdateChannelMenu = !this.openedUpdateChannelMenu
		},
		toggleHideMissingUpdates() {
			this.hideMissingUpdates = !this.hideMissingUpdates
		},
		toggleHideAvailableUpdates() {
			this.hideAvailableUpdates = !this.hideAvailableUpdates
		},
		toggleMenu() {
			this.openedWhatsNew = !this.openedWhatsNew
		},
		closeUpdateChannelMenu() {
			this.openedUpdateChannelMenu = false
		},
		hideMenu() {
			this.openedWhatsNew = false
		},
	},
}
</script>

<style lang="scss" scoped>
	#updatenotification {
		& > * {
			max-width: 900px;
		}

		div.update,
		p:not(.inlineblock) {
			margin-bottom: 25px;
		}
		h2.inlineblock {
			margin-top: 25px;
		}
		h3 {
			cursor: pointer;
			.icon {
				cursor: pointer;
			}
			&.update-channel-selector {
				display: inline-block;
				cursor: inherit;
			}
		}
		.icon {
			display: inline-block;
			margin-bottom: -3px;
		}
		.icon-triangle-s, .icon-triangle-n {
			opacity: 0.5;
		}
		.whatsNew {
			display: inline-block;
		}
		.toggleWhatsNew {
			position: relative;
		}
		.popovermenu {
			p {
				margin-bottom: 0;
				width: 100%;
			}
			margin-top: 5px;
			width: 300px;
		}
		.applist {
			margin-bottom: 25px;
		}

		.update-menu {
			position: relative;
			cursor: pointer;
			margin-left: 3px;
			display: inline-block;
			padding: 10px;
			border-radius: 10px;
			border: 2px solid var(--color-border-dark);
			.icon-update-menu {
				cursor: inherit;
				.icon-triangle-s {
					display: inline-block;
					vertical-align: middle;
					cursor: inherit;
					opacity: 1;
				}
			}
			.popovermenu {
				display: none;
				top: 28px;
				&.show-menu {
					display: block;
				}
			}
		}
	}
</style>
<style lang="scss">
	/* override needed to make menu wider */
	#updatenotification .popovermenu {
		p {
			margin-top: 5px;
			width: 100%;
		}
		margin-top: 5px;
		width: 300px;
	}
	/* override needed to replace yellow hover state with a dark one */
	#updatenotification .update-menu .icon-star:hover,
	#updatenotification .update-menu .icon-star:focus {
		background-image: var(--icon-starred);
	}
	#updatenotification .topMargin {
		margin-top: 15px;
	}
</style>
