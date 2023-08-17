<template>
	<NcSettingsSection id="updatenotification" :name="t('updatenotification', 'Update')">
		<div class="update">
			<template v-if="isNewVersionAvailable">
				<NcNoteCard v-if="versionIsEol" type="warning">
					{{ t('updatenotification', 'The version you are running is not maintained anymore. Please make sure to update to a supported version as soon as possible.') }}
				</NcNoteCard>

				<p>
					<!-- eslint-disable-next-line vue/no-v-html -->
					<span v-html="newVersionAvailableString" /><br>
					<span v-if="!isListFetched" class="icon icon-loading-small" />
					<!-- eslint-disable-next-line vue/no-v-html -->
					<span v-html="statusText" />
				</p>

				<template v-if="missingAppUpdates.length">
					<h3 class="clickable" @click="toggleHideMissingUpdates">
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
					<h3 class="clickable" @click="toggleHideAvailableUpdates">
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
					<NcActions v-if="whatsNewData || changelogURL"
						:force-menu="true"
						:menu-name="t('updatenotification', 'What\'s new?')"
						type="tertiary">
						<template #icon>
							<IconNewBox :size="20" />
						</template>
						<template #default>
							<NcActionCaption v-for="changes,index in whatsNewData" :key="index" :name="changes" />
							<NcActionLink v-if="changelogURL"
								:href="changelogURL"
								close-after-click
								target="_blank">
								{{ t('updatenotification', 'View changelog') }}
								<template #icon>
									<IconLink :size="20" />
								</template>
							</NcActionLink>
						</template>
					</NcActions>
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

		<h3>{{ t('updatenotification', 'Update channel') }}</h3>
		<p class="inlineblock">
			{{ t('updatenotification', 'Changing the update channel also affects the apps management page. E.g. after switching to the beta channel, beta app updates will be offered to you in the apps management page.') }}
		</p>
		<div class="update-channel-selector">
			<span>{{ t('updatenotification', 'Current update channel:') }}</span>
			<NcActions :force-menu="true"
				:menu-name="localizedChannelName"
				type="tertiary">
				<template #icon>
					<IconChevronDown :size="20" />
				</template>
				<template #default>
					<!-- TODO use NcActionRadio if it provides long text, e.g. subtitle -->
					<NcActionButton v-for="channel,index in channelList"
						:key="index"
						:aria-checked="channel.active ? 'true' : 'false'"
						:aria-label="channel.text"
						:disabled="!!channel.disabled"
						:icon="channel.icon"
						:name="channel.text"
						class="update-channel-action"
						close-after-click
						role="menuitemradio"
						@click="channel.action">
						{{ channel.longtext }}
					</NcActionButton>
				</template>
			</NcActions>
		</div>
		<p>
			<em>{{ t('updatenotification', 'You can always update to a newer version. But you can never downgrade to a more stable version.') }}</em><br>
			<!-- eslint-disable-next-line vue/no-v-html -->
			<em v-html="noteDelayedStableString" />
		</p>

		<h4>{{ t('updatenotification', 'Notify members of the following groups about available updates:') }}</h4>
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
		<p>
			<em v-if="currentChannel === 'daily' || currentChannel === 'git'">{{ t('updatenotification', 'Only notifications for app updates are available.') }}</em>
			<em v-if="currentChannel === 'daily'">{{ t('updatenotification', 'The selected update channel makes dedicated notifications for the server obsolete.') }}</em>
			<em v-else-if="currentChannel === 'git'">{{ t('updatenotification', 'The selected update channel does not support updates of the server.') }}</em>
		</p>
	</NcSettingsSection>
</template>

<script>
import { showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { getLoggerBuilder } from '@nextcloud/logger'
import { generateUrl, getRootUrl, generateOcsUrl } from '@nextcloud/router'

import axios from '@nextcloud/axios'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionCaption from '@nextcloud/vue/dist/Components/NcActionCaption.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import IconChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import IconLink from 'vue-material-design-icons/Link.vue'
import IconNewBox from 'vue-material-design-icons/NewBox.vue'
import debounce from 'debounce'

const logger = getLoggerBuilder()
	.setApp('updatenotification')
	.detectUser()
	.build()

export default {
	name: 'UpdateNotification',
	components: {
		IconChevronDown,
		IconLink,
		IconNewBox,
		NcActions,
		NcActionButton,
		NcActionCaption,
		NcActionLink,
		NcNoteCard,
		NcSelect,
		NcSettingsSection,
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
					action: () => {},
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
		toggleHideMissingUpdates() {
			this.hideMissingUpdates = !this.hideMissingUpdates
		},
		toggleHideAvailableUpdates() {
			this.hideAvailableUpdates = !this.hideAvailableUpdates
		},
	},
}
</script>

<style lang="scss" scoped>
	#updatenotification {
		& > * {
			max-width: 900px;
		}

		.topMargin {
			margin-top: 15px;
		}

		div.update,
		p:not(.inlineblock) {
			margin-bottom: 25px;
		}
		h2.inlineblock {
			margin-top: 25px;
		}
		h3 {
			&.clickable {
				cursor: pointer;
				.icon {
					cursor: pointer;
				}
			}
		}
		h4 {
			margin-block-end: 0.7rem;
		}
		.update-channel-selector {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.icon {
			display: inline-block;
			margin-bottom: -3px;
		}
		.icon-triangle-s, .icon-triangle-n {
			opacity: 0.5;
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
		}
	}
</style>
<style lang="scss">
// Make current selected update channel visually visible, remove if NcActionRadio is used
.update-channel-action[aria-checked=true] {
	border-inline-start: 4px solid var(--color-primary-element);

	&:hover, &:focus-within {
		background-color: var(--color-primary-element-light-hover);
	}

	button {
		background-color: var(--color-primary-element-light);
		color: var(--color-primary-element-light-text);
	}
}
// Ensure outline for focus-visible works even with background color of selected channel
.update-channel-action[aria-checked] {
	margin-block: 2px;
}

#updatenotification {
	/* override needed to replace yellow hover state with a dark one */
	.update-menu .icon-star:hover,
	.update-menu .icon-star:focus {
		background-image: var(--icon-starred);
	}
}
</style>
