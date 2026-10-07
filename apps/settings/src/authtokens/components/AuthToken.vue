<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IToken } from '../store/authtoken.ts'

import { mdiAndroid, mdiAppleIos, mdiAppleSafari, mdiCellphone, mdiCheck, mdiFirefox, mdiGoogleChrome, mdiKeyOutline, mdiMicrosoftEdge, mdiMonitor, mdiTablet, mdiWeb } from '@mdi/js'
import { showConfirmation } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref, useTemplateRef } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import AuthTokenDeleteDialog from './AuthTokenDeleteDialog.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'
import { detect } from '../utils/userAgentDetect.ts'

const props = defineProps<{
	/** The token shown in this row */
	token: IToken
}>()

const productName = window.OC.theme.productName as string

const nameMap = {
	edge: 'Microsoft Edge',
	firefox: 'Firefox',
	chrome: 'Google Chrome',
	safari: 'Safari',
	androidChrome: t('settings', 'Google Chrome for Android'),
	iphone: 'iPhone',
	ipad: 'iPad',
	iosClient: t('settings', '{productName} iOS app', { productName }),
	androidClient: t('settings', '{productName} Android app', { productName }),
	iosTalkClient: t('settings', '{productName} Talk for iOS', { productName }),
	androidTalkClient: t('settings', '{productName} Talk for Android', { productName }),
	syncClient: t('settings', 'Sync client'),
	davx5: 'DAVx5',
	webPirate: 'WebPirate',
	sailfishBrowser: 'SailfishBrowser',
	neon: 'Neon',
}

const authTokenStore = useAuthTokenStore()

const nameField = useTemplateRef('input')
const actions = useTemplateRef('actions')

const actionOpen = ref(false)
const renaming = ref(false)
const newName = ref('')
const deleteDialogOpen = ref(false)

const canChangeScope = computed(() => props.token.type === TokenType.PERMANENT_TOKEN)

/**
 * Object ob the current user agent used by the token
 * This either returns an object containing user agent information or `null` if unknown
 */
const client = computed(() => {
	// pretty format sync client user agent
	const matches = props.token.name.match(/Mozilla\/5\.0 \((\w+)\) (?:mirall|csyncoC)\/(\d+\.\d+\.\d+)/)

	if (matches) {
		return {
			id: 'syncClient',
			os: matches[1],
			version: matches[2],
		}
	}

	return detect(props.token.name)
})

/**
 * Last activity of the token as ECMA timestamp (in ms)
 */
const tokenLastActivity = computed(() => props.token.lastActivity * 1000)

/**
 * Icon to use for the current token
 */
const tokenIcon = computed(() => {
	// For custom created app tokens / app passwords
	if (props.token.type === TokenType.PERMANENT_TOKEN) {
		return mdiKeyOutline
	}

	switch (client.value?.id) {
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
})

/**
 * Label to be shown for current token
 */
const tokenLabel = computed(() => {
	if (props.token.current) {
		return t('settings', 'This session')
	}
	if (client.value === null) {
		return props.token.name
	}

	const name = nameMap[client.value.id]
	if (client.value.os) {
		return t('settings', '{client} - {version} ({system})', { client: name, system: client.value.os, version: client.value.version })
	} else if (client.value.version) {
		return t('settings', '{client} - {version}', { client: name, version: client.value.version })
	}
	return name
})

/**
 * If the current token is considered for remote wiping
 */
const wiping = computed(() => props.token.type === TokenType.WIPING_TOKEN)

/**
 * Allow or deny filesystem access for this token
 *
 * @param state - Whether the token may access the filesystem
 */
function updateFileSystemScope(state: boolean) {
	authTokenStore.setTokenScope(props.token, 'filesystem', state)
}

/**
 * Show the rename form for this token
 */
function startRename() {
	// Close action (popover menu)
	actionOpen.value = false

	newName.value = props.token.name
	renaming.value = true
	nextTick(() => {
		nameField.value!.select()
	})
}

/**
 * Close the rename form without saving
 */
function cancelRename() {
	renaming.value = false
	focusActions()
}

/**
 * Move focus back to the row's actions menu
 */
async function focusActions() {
	await nextTick()
	actions.value?.$el.querySelector('button')?.focus()
}

/**
 * Ask for confirmation before revoking the token
 */
function revoke() {
	actionOpen.value = false
	deleteDialogOpen.value = true
}

/**
 * Revoke the token once the dialog was confirmed
 */
function confirmDelete() {
	authTokenStore.deleteToken(props.token)
}

/**
 * Save the new name and close the rename form
 */
async function rename() {
	renaming.value = false
	// The password confirmation holds focus until it closes, so refocus after it
	await authTokenStore.renameToken(props.token, newName.value)
	focusActions()
}

/**
 * Ask for confirmation, then mark the device for remote wipe
 */
async function wipe() {
	actionOpen.value = false
	const confirmed = await showConfirmation({
		name: t('settings', 'Confirm wipe'),
		text: t('settings', 'Do you really want to wipe your data from this device?'),
		labelConfirm: t('settings', 'Wipe device'),
		labelReject: t('settings', 'Cancel'),
		severity: 'warning',
	})
	if (confirmed) {
		authTokenStore.wipeToken(props.token)
	}
}
</script>

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
						:showTrailingButton="true"
						:trailingButtonLabel="t('settings', 'Cancel renaming')"
						@trailingButtonClick="cancelRename"
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
				:ignoreSeconds="true"
				:timestamp="tokenLastActivity" />
		</td>
		<td class="auth-token__actions">
			<NcActions
				v-if="!token.current"
				ref="actions"
				v-model:open="actionOpen"
				:title="t('settings', 'Device settings')"
				:aria-label="t('settings', 'Device settings')">
				<!-- TODO: add text/longtext with some description -->
				<NcActionCheckbox
					v-if="canChangeScope"
					:modelValue="token.scope.filesystem"
					@update:modelValue="updateFileSystemScope">
					{{ t('settings', 'Allow filesystem access') }}
				</NcActionCheckbox>
				<!-- TODO: add text/longtext with some description -->
				<NcActionButton
					v-if="token.canRename"
					icon="icon-rename"
					@click.stop.prevent="startRename">
					{{ t('settings', 'Rename') }}
				</NcActionButton>

				<!-- revoke & wipe -->
				<template v-if="token.canDelete">
					<template v-if="token.type !== TokenType.WIPING_TOKEN">
						<!-- TODO: add text/longtext with some description -->
						<NcActionButton
							icon="icon-delete"
							@click.stop.prevent="revoke">
							{{ t('settings', 'Revoke') }}
						</NcActionButton>
						<NcActionButton
							icon="icon-delete"
							@click.stop.prevent="wipe">
							{{ t('settings', 'Wipe device') }}
						</NcActionButton>
					</template>
					<NcActionButton
						v-else
						icon="icon-delete"
						:name="t('settings', 'Revoke')"
						@click.stop.prevent="revoke">
						{{ t('settings', 'Revoking this token might prevent the wiping of your device if it has not started the wipe yet.') }}
					</NcActionButton>
				</template>
			</NcActions>
		</td>
		<AuthTokenDeleteDialog
			v-if="deleteDialogOpen"
			v-model:open="deleteDialogOpen"
			:token="token"
			@confirm="confirmDelete" />
	</tr>
</template>

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
