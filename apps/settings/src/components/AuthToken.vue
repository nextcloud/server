<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr :class="['auth-token', { 'auth-token--wiping': wiping }]" :data-id="token.id">
		<td class="auth-token__name">
			<NcIconSvgWrapper :path="tokenIcon" />
			<div class="auth-token__name-wrapper">
				<form v-if="token.canRename && renaming"
					class="auth-token__name-form"
					@submit.prevent.stop="rename">
					<NcTextField ref="input"
						:value.sync="newName"
						:label="t('settings', 'Device name')"
						:show-trailing-button="true"
						:trailing-button-label="t('settings', 'Cancel renaming')"
						@trailing-button-click="cancelRename"
						@keyup.esc="cancelRename" />
					<NcButton :aria-label="t('settings', 'Save new name')" type="tertiary" native-type="submit">
						<template #icon>
							<NcIconSvgWrapper :path="mdiCheck" />
						</template>
					</NcButton>
				</form>
				<span v-else>{{ tokenLabel }}</span>
				<span v-if="wiping" class="wiping-warning">({{ t('settings', 'Marked for remote wipe') }})</span>
			</div>
		</td>
		<td>
			<NcDateTime class="auth-token__last-activity"
				:ignore-seconds="true"
				:timestamp="tokenLastActivity" />
		</td>
		<td class="auth-token__actions">
			<NcActions v-if="!token.current"
				:title="t('settings', 'Device settings')"
				:aria-label="t('settings', 'Device settings')"
				:open.sync="actionOpen">
				<NcActionCheckbox v-if="canChangeScope"
					:checked="token.scope.filesystem"
					@update:checked="updateFileSystemScope">
					<!-- TODO: add text/longtext with some description -->
					{{ t('settings', 'Allow filesystem access') }}
				</NcActionCheckbox>
				<NcActionButton v-if="token.canRename"
					icon="icon-rename"
					@click.stop.prevent="startRename">
					<!-- TODO: add text/longtext with some description -->
					{{ t('settings', 'Rename') }}
				</NcActionButton>

				<!-- revoke & wipe -->
				<template v-if="token.canDelete">
					<template v-if="token.type !== 2">
						<NcActionButton icon="icon-delete"
							@click.stop.prevent="revoke">
							<!-- TODO: add text/longtext with some description -->
							{{ t('settings', 'Revoke') }}
						</NcActionButton>
						<NcActionButton icon="icon-delete"
							@click.stop.prevent="wipe">
							{{ t('settings', 'Wipe device') }}
						</NcActionButton>
					</template>
					<NcActionButton v-else-if="token.type === 2"
						icon="icon-delete"
						:name="t('settings', 'Revoke')"
						@click.stop.prevent="revoke">
						{{ t('settings', 'Revoking this token might prevent the wiping of your device if it has not started the wipe yet.') }}
					</NcActionButton>
				</template>
			</NcActions>
		</td>
	</tr>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { IToken } from '../store/authtoken'

import { mdiCheck, mdiCellphone, mdiTablet, mdiMonitor, mdiWeb, mdiKey, mdiMicrosoftEdge, mdiFirefox, mdiGoogleChrome, mdiAppleSafari, mdiAndroid, mdiAppleIos } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'

import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionCheckbox from '@nextcloud/vue/dist/Components/NcActionCheckbox.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDateTime from '@nextcloud/vue/dist/Components/NcDateTime.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

// When using capture groups the following parts are extracted the first is used as the version number, the second as the OS
const userAgentMap = {
	ie: /(?:MSIE|Trident|Trident\/7.0; rv)[ :](\d+)/,
	// Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
	edge: /^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+ Edge\/[0-9.]+$/,
	// Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
	firefox: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) Gecko\/[0-9.]+ Firefox\/(\d+)(?:\.\d)?$/,
	// Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
	chrome: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/(\d+)[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+$/,
	// Safari User Agent from http://www.useragentstring.com/pages/Safari/
	safari: /^Mozilla\/5\.0 \([^)]*(Windows|OS X)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)(?: Version\/([0-9]+)[0-9.]+)? Safari\/[0-9.A-Z]+$/,
	// Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
	androidChrome: /Android.*(?:; (.*) Build\/).*Chrome\/(\d+)[0-9.]+/,
	iphone: / *CPU +iPhone +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
	ipad: /\(iPad; *CPU +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
	iosClient: /^Mozilla\/5\.0 \(iOS\) (?:ownCloud|Nextcloud)-iOS.*$/,
	androidClient: /^Mozilla\/5\.0 \(Android\) (?:ownCloud|Nextcloud)-android.*$/,
	iosTalkClient: /^Mozilla\/5\.0 \(iOS\) Nextcloud-Talk.*$/,
	androidTalkClient: /^Mozilla\/5\.0 \(Android\) Nextcloud-Talk.*$/,
	// DAVx5/3.3.8-beta2-gplay (2021/01/02; dav4jvm; okhttp/4.9.0) Android/10
	davx5: /DAV(?:droid|x5)\/([^ ]+)/,
	// Mozilla/5.0 (U; Linux; Maemo; Jolla; Sailfish; like Android 4.3) AppleWebKit/538.1 (KHTML, like Gecko) WebPirate/2.0 like Mobile Safari/538.1 (compatible)
	webPirate: /(Sailfish).*WebPirate\/(\d+)/,
	// Mozilla/5.0 (Maemo; Linux; U; Jolla; Sailfish; Mobile; rv:31.0) Gecko/31.0 Firefox/31.0 SailfishBrowser/1.0
	sailfishBrowser: /(Sailfish).*SailfishBrowser\/(\d+)/,
	// Neon 1.0.0+1
	neon: /Neon \d+\.\d+\.\d+\+\d+/,
}
const nameMap = {
	edge: 'Microsoft Edge',
	firefox: 'Firefox',
	chrome: 'Google Chrome',
	safari: 'Safari',
	androidChrome: t('settings', 'Google Chrome for Android'),
	iphone: 'iPhone',
	ipad: 'iPad',
	iosClient: t('settings', '{productName} iOS app', { productName: window.oc_defaults.productName }),
	androidClient: t('settings', '{productName} Android app', { productName: window.oc_defaults.productName }),
	iosTalkClient: t('settings', '{productName} Talk for iOS', { productName: window.oc_defaults.productName }),
	androidTalkClient: t('settings', '{productName} Talk for Android', { productName: window.oc_defaults.productName }),
	syncClient: t('settings', 'Sync client'),
	davx5: 'DAVx5',
	webPirate: 'WebPirate',
	sailfishBrowser: 'SailfishBrowser',
	neon: 'Neon',
}

export default defineComponent({
	name: 'AuthToken',
	components: {
		NcActions,
		NcActionButton,
		NcActionCheckbox,
		NcButton,
		NcDateTime,
		NcIconSvgWrapper,
		NcTextField,
	},
	props: {
		token: {
			type: Object as PropType<IToken>,
			required: true,
		},
	},
	setup() {
		const authTokenStore = useAuthTokenStore()
		return { authTokenStore }
	},
	data() {
		return {
			actionOpen: false,
			renaming: false,
			newName: '',
			oldName: '',
			mdiCheck,
		}
	},
	computed: {
		canChangeScope() {
			return this.token.type === TokenType.PERMANENT_TOKEN
		},
		/**
		 * Object ob the current user agent used by the token
		 * This either returns an object containing user agent information or `null` if unknown
		 */
		client() {
			// pretty format sync client user agent
			const matches = this.token.name.match(/Mozilla\/5\.0 \((\w+)\) (?:mirall|csyncoC)\/(\d+\.\d+\.\d+)/)

			if (matches) {
				return {
					id: 'syncClient',
					os: matches[1],
					version: matches[2],
				}
			}

			for (const client in userAgentMap) {
				const matches = this.token.name.match(userAgentMap[client])
				if (matches) {
					return {
						id: client,
						os: matches[2] && matches[1],
						version: matches[2] ?? matches[1],
					}
				}
			}

			return null
		},
		/**
		 * Last activity of the token as ECMA timestamp (in ms)
		 */
		tokenLastActivity() {
			return this.token.lastActivity * 1000
		},
		/**
		 * Icon to use for the current token
		 */
		tokenIcon() {
			// For custom created app tokens / app passwords
			if (this.token.type === TokenType.PERMANENT_TOKEN) {
				return mdiKey
			}

			switch (this.client?.id) {
			case 'edge':
				return mdiMicrosoftEdge
			case 'firefox':
				return mdiFirefox
			case 'chrome':
				return mdiGoogleChrome
			case 'safari':
				return mdiAppleSafari
			case 'androidChrome':
			case 'androidClient':
			case 'androidTalkClient':
				return mdiAndroid
			case 'iphone':
			case 'iosClient':
			case 'iosTalkClient':
				return mdiAppleIos
			case 'ipad':
				return mdiTablet
			case 'davx5':
				return mdiCellphone
			case 'syncClient':
				return mdiMonitor
			case 'webPirate':
			case 'sailfishBrowser':
			default:
				return mdiWeb
			}
		},
		/**
		 * Label to be shown for current token
		 */
		tokenLabel() {
			if (this.token.current) {
				return t('settings', 'This session')
			}
			if (this.client === null) {
				return this.token.name
			}

			const name = nameMap[this.client.id]
			if (this.client.os) {
				return t('settings', '{client} - {version} ({system})', { client: name, system: this.client.os, version: this.client.version })
			} else if (this.client.version) {
				return t('settings', '{client} - {version}', { client: name, version: this.client.version })
			}
			return name
		},
		/**
		 * If the current token is considered for remote wiping
		 */
		wiping() {
			return this.token.type === TokenType.WIPING_TOKEN
		},
	},
	methods: {
		t,
		updateFileSystemScope(state: boolean) {
			this.authTokenStore.setTokenScope(this.token, 'filesystem', state)
		},
		startRename() {
			// Close action (popover menu)
			this.actionOpen = false

			this.oldName = this.token.name
			this.newName = this.token.name
			this.renaming = true
			this.$nextTick(() => {
				this.$refs.input!.select()
			})
		},
		cancelRename() {
			this.renaming = false
		},
		revoke() {
			this.actionOpen = false
			this.authTokenStore.deleteToken(this.token)
		},
		rename() {
			this.renaming = false
			this.authTokenStore.renameToken(this.token, this.newName)
		},
		wipe() {
			this.actionOpen = false
			this.authTokenStore.wipeToken(this.token)
		},
	},
})
</script>

<style lang="scss" scoped>
.auth-token {
	border-top: 2px solid var(--color-border);
	max-width: 200px;
	white-space: normal;
	vertical-align: middle;
	position: relative;

	&--wiping {
		background-color: var(--color-background-dark);
	}

	&__name {
		padding-block: 10px;
		display: flex;
		align-items: center;
		gap: 6px;
		min-width: 355px; // ensure no jumping when renaming
	}

	&__name-wrapper {
		display: flex;
		flex-direction: column;
	}

	&__name-form {
		align-items: end;
		display: flex;
		gap: 4px;
	}

	&__actions {
		padding: 0 10px;
	}

	&__last-activity {
		padding-inline-start: 10px;
	}

	.wiping-warning {
		color: var(--color-text-maxcontrast);
	}
}
</style>
