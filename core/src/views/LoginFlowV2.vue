<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LoginFlowV2Auth, LoginFlowV2Context, LoginFlowV2Grant } from '../types/loginFlowV2.ts'

import { mdiAlertOutline } from '@mdi/js'
import { getRequestToken } from '@nextcloud/auth'
import axios, { isAxiosError } from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { confirmPassword, isPasswordConfirmationRequired, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { nextTick, ref, useTemplateRef } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import LoginFlowContext from '../components/LoginFlow/LoginFlowContext.vue'

const context = loadState<LoginFlowV2Context>('core', 'loginFlowContext')
const auth = loadState<LoginFlowV2Auth | null>('core', 'loginFlowAuth', null)
const grant = loadState<LoginFlowV2Grant | null>('core', 'loginFlowGrant', null)
const stateToken = auth?.stateToken ?? grant?.stateToken
const requestToken = getRequestToken()

const useAppTokenLogin = ref(false)
const declining = ref(false)
const declined = ref(false)
const submitting = ref(false)
const declineError = ref('')
const unavailable = ref(false)
const declinedHeading = useTemplateRef('declinedHeading')

/** Cancel the pending request before showing confirmation. */
async function decline() {
	if (declining.value || submitting.value) {
		return
	}
	declining.value = true
	declineError.value = ''
	try {
		await axios.post(context.declineUrl, { stateToken })
		declined.value = true
		await nextTick()
		declinedHeading.value?.focus()
	} catch (error) {
		const status = isAxiosError(error) ? error.response?.status : undefined
		if (status === 409 || status === 410) {
			unavailable.value = true
			declineError.value = t('core', 'This request is no longer pending. If you already granted access, you can revoke it in Security settings.')
		} else if (status === 403) {
			unavailable.value = true
			declineError.value = t('core', 'This request is no longer valid. Start a new connection from the app.')
		} else {
			declineError.value = t('core', 'Could not decline this request. Please try again.')
		}
	} finally {
		declining.value = false
	}
}

/**
 * Confirm the password when required before granting access.
 *
 * @param event - The form submission
 */
async function onSubmit(event: SubmitEvent) {
	event.preventDefault()
	if (submitting.value || declining.value || unavailable.value) {
		return
	}
	submitting.value = true
	try {
		if (grant && isPasswordConfirmationRequired(PwdConfirmationMode.Lax)) {
			await confirmPassword()
		}
		(event.target as HTMLFormElement).submit()
	} catch {
		submitting.value = false
	}
}
</script>

<template>
	<NcGuestContent class="picker-window" :class="$style.panel">
		<template v-if="declined">
			<h2 ref="declinedHeading" :class="$style.heading" tabindex="-1">
				{{ t('core', 'Request declined') }}
			</h2>
			<p>{{ t('core', 'This request was cancelled without granting access. You can close this window.') }}</p>
		</template>
		<template v-else>
			<header :class="$style.header">
				<h2 :class="$style.heading">
					{{ auth ? t('core', 'Sign in to connect an app') : t('core', 'Allow account access?') }}
				</h2>
			</header>

			<p v-if="auth && !useAppTokenLogin">
				{{ t('core', 'An app is requesting access to your account. Sign in to review the app details and choose whether to grant access.') }}
			</p>
			<section
				v-if="grant || useAppTokenLogin"
				:class="$style.reviewDetails"
				:aria-label="t('core', 'App access details')">
				<div
					v-if="grant && context.userId"
					:class="$style.account"
					role="group"
					:aria-label="t('core', 'Account to connect')">
					<NcAvatar
						:user="context.userId"
						:displayName="context.userDisplayName || context.userId"
						:size="48"
						hideStatus
						disableMenu
						disableTooltip
						aria-hidden="true" />
					<div :class="$style.accountDetails">
						<strong><bdi>{{ context.userDisplayName || context.userId }}</bdi></strong>
						<span v-if="context.userDisplayName && context.userDisplayName !== context.userId">
							<bdi>{{ context.userId }}</bdi>
						</span>
						<span :class="$style.server"><bdi>{{ context.serverUrl }}</bdi></span>
					</div>
				</div>
				<p v-else-if="useAppTokenLogin" :class="$style.server">
					<bdi>{{ context.serverUrl }}</bdi>
				</p>
				<p :class="$style.access">
					<NcIconSvgWrapper
						:path="mdiAlertOutline"
						:class="$style.warningIcon"
						aria-hidden="true" />
					<span>{{ t('core', 'The requesting app will get ongoing access to your account, including your files. This can include reading, changing, and deleting files.') }}</span>
				</p>
				<LoginFlowContext :context="context" />
			</section>

			<p v-if="declineError" role="alert">
				{{ declineError }}
			</p>
			<NcButton v-if="unavailable" :href="context.securitySettingsUrl" variant="secondary">
				{{ t('core', 'Security settings') }}
			</NcButton>
			<form
				v-else
				:action="useAppTokenLogin ? auth?.appTokenUrl : grant?.actionUrl"
				:class="$style.form"
				method="post"
				@submit="onSubmit">
				<input type="hidden" name="requesttoken" :value="requestToken">
				<input type="hidden" name="stateToken" :value="stateToken">
				<input
					v-if="auth?.direct || grant?.direct"
					type="hidden"
					name="direct"
					value="1">

				<template v-if="auth && useAppTokenLogin">
					<p>{{ t('core', 'Submitting an existing app password grants access immediately.') }}</p>
					<NcFormBox>
						<NcTextField
							name="user"
							:label="t('core', 'Login')"
							required
							:disabled="submitting || declining" />
						<NcPasswordField
							name="password"
							:label="t('core', 'App password')"
							required
							:disabled="submitting || declining" />
					</NcFormBox>
				</template>

				<p :class="$style.reminder">
					{{ t('core', 'If you weren’t setting up an app or device, decline this request.') }}
				</p>
				<div :class="$style.actions">
					<NcButton
						type="button"
						variant="secondary"
						wide
						:disabled="declining || submitting"
						@click="decline">
						{{ declineError ? t('core', 'Retry decline') : t('core', 'Decline') }}
					</NcButton>
					<NcButton
						v-if="auth && !useAppTokenLogin"
						:href="auth.loginRedirectUrl"
						:disabled="declining"
						variant="secondary"
						wide>
						{{ t('core', 'Continue') }}
					</NcButton>
					<NcButton
						v-else
						type="submit"
						variant="secondary"
						wide
						:disabled="declining || submitting">
						{{ t('core', 'Grant access') }}
					</NcButton>
				</div>
			</form>
			<NcButton
				v-if="auth && !unavailable"
				type="button"
				variant="tertiary"
				:aria-expanded="useAppTokenLogin"
				:disabled="declining || submitting"
				@click="useAppTokenLogin = !useAppTokenLogin">
				{{ useAppTokenLogin ? t('core', 'Back to sign in') : t('core', 'Use an existing app password') }}
			</NcButton>
		</template>
	</NcGuestContent>
</template>

<style module>
.panel {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
	box-sizing: border-box;
	width: min(36rem, calc(100vw - 2rem));
	padding: calc(var(--default-grid-baseline) * 6) !important;
	text-align: start;
}

.header {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
}

.heading {
	font-size: 1.5rem;
}

.server {
	color: var(--color-text-maxcontrast);
	overflow-wrap: anywhere;
}

.access {
	display: flex;
	align-items: start;
	gap: calc(var(--default-grid-baseline) * 2);
}

.reviewDetails {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
}

.warningIcon {
	flex-shrink: 0;
	color: var(--color-warning-text);
}

.account {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 3);
	padding: calc(var(--default-grid-baseline) * 4);
	border-radius: var(--border-radius-large);
	background-color: var(--color-background-dark);
}

.accountDetails {
	display: flex;
	flex-direction: column;
	min-width: 0;
	gap: var(--default-grid-baseline);
	overflow-wrap: anywhere;
}

.form {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 3);
	margin: 0;
}

.reminder {
	font-weight: bold;
}

.actions {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: calc(var(--default-grid-baseline) * 3);
}

@media (width <= 480px) {
	.panel {
		padding: calc(var(--default-grid-baseline) * 4) !important;
	}

	.actions {
		grid-template-columns: minmax(0, 1fr);
	}
}
</style>
