<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<tr class="auth-token" :class="[{ 'auth-token--wiping': wiping }]" :data-id="token.id">
		<td class="auth-token__name">
			<NcIconSvgWrapper :path="tokenIcon" />
			<div class="auth-token__name-wrapper">
				<form
					v-if="token.canRename && renaming"
					class="auth-token__name-form"
					@submit.prevent.stop="rename">
					<NcTextField
						ref="input"
						v-model="newName"
						:label="t('settings', 'Device name')"
						:show-trailing-button="true"
						:trailing-button-label="t('settings', 'Cancel renaming')"
						@trailing-button-click="cancelRename"
						@keyup.esc="cancelRename" />
					<NcButton :aria-label="t('settings', 'Save new name')" variant="tertiary" type="submit">
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
			<NcDateTime
				class="auth-token__last-activity"
				:ignore-seconds="true"
				:timestamp="tokenLastActivity" />
		</td>
		<td class="auth-token__actions">
			<NcActions
				v-if="!token.current"
				:title="t('settings', 'Device settings')"
				:aria-label="t('settings', 'Device settings')"
				:open.sync="actionOpen">
				<NcActionCheckbox
					v-if="canChangeScope"
					:model-value="token.scope.filesystem"
					@update:modelValue="updateFileSystemScope">
					<!-- TODO: add text/longtext with some description -->
					{{ t('settings', 'Allow filesystem access') }}
				</NcActionCheckbox>
				<NcActionButton
					v-if="token.canRename"
					icon="icon-rename"
					@click.stop.prevent="startRename">
					<!-- TODO: add text/longtext with some description -->
					{{ t('settings', 'Rename') }}
				</NcActionButton>

				<!-- revoke & wipe -->
				<template v-if="token.canDelete">
					<template v-if="token.type !== 2">
						<NcActionButton
							icon="icon-delete"
							@click.stop.prevent="revoke">
							<!-- TODO: add text/longtext with some description -->
							{{ t('settings', 'Revoke') }}
						</NcActionButton>
						<NcActionButton
							icon="icon-delete"
							@click.stop.prevent="wipe">
							{{ t('settings', 'Wipe device') }}
						</NcActionButton>
					</template>
					<NcActionButton
						v-else-if="token.type === 2"
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
import type { IToken } from '../store/authtoken.ts'

import { mdiAndroid, mdiAppleIos, mdiAppleSafari, mdiCellphone, mdiCheck, mdiFirefox, mdiGoogleChrome, mdiKeyOutline, mdiMicrosoftEdge, mdiMonitor, mdiTablet, mdiWeb } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'
import { detect } from '../utils/userAgentDetect.ts'

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

			return detect(this.token.name)
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
				return mdiKeyOutline
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
