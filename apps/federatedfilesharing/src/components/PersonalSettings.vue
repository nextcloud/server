<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClipboard from 'vue-material-design-icons/ContentCopy.vue'
import IconWeb from 'vue-material-design-icons/Web.vue'

const productName = window.OC.theme.productName
const color = loadState<string>('federatedfilesharing', 'color')
const textColor = loadState<string>('federatedfilesharing', 'textColor')
const cloudId = loadState<string>('federatedfilesharing', 'cloudId')
const docUrlFederated = loadState<string>('federatedfilesharing', 'docUrlFederated')
const logoPath = loadState<string>('federatedfilesharing', 'logoPath')
const reference = loadState<string>('federatedfilesharing', 'reference')
const urlFacebookIcon = imagePath('core', 'facebook')
const urlMastodonIcon = imagePath('core', 'mastodon')
const urlBlueSkyIcon = imagePath('core', 'bluesky')
const messageWithURL = t('federatedfilesharing', 'Share with me through my #Nextcloud Federated Cloud ID, see {url}', { url: reference })
const messageWithoutURL = t('federatedfilesharing', 'Share with me through my #Nextcloud Federated Cloud ID')
const shareMastodonUrl = `https://mastodon.social/?text=${encodeURIComponent(messageWithoutURL)}&url=${encodeURIComponent(reference)}`
const shareFacebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(reference)}`
const shareBlueSkyUrl = `https://bsky.app/intent/compose?text=${encodeURIComponent(messageWithURL)}`
const logoPathAbsolute = new URL(logoPath, location.origin)

const showHtml = ref(false)
const isCopied = ref(false)

const backgroundStyle = computed(() => `
	padding:10px;
	background-color:${color};
	color:${textColor};
	border-radius:3px;
	padding-inline-start:4px;`)

const linkStyle = `background-image:url(${logoPathAbsolute});width:50px;height:30px;position:relative;top:8px;background-size:contain;display:inline-block;background-repeat:no-repeat; background-position: center center;`
const htmlCode = computed(() => `<a target="_blank" rel="noreferrer noopener" href="${reference}" style="${backgroundStyle.value}">
	<span style="${linkStyle}"></span>
	${t('federatedfilesharing', 'Share with me via Nextcloud')}
</a>`)

const copyLinkTooltip = computed(() => isCopied.value
	? t('federatedfilesharing', 'Cloud ID copied')
	: t('federatedfilesharing', 'Copy'))

/**
 *
 */
async function copyCloudId(): Promise<void> {
	try {
		await navigator.clipboard.writeText(cloudId)
		showSuccess(t('federatedfilesharing', 'Cloud ID copied'))
	} catch {
		// no secure context or really old browser - need a fallback
		window.prompt(t('federatedfilesharing', 'Clipboard not available. Please copy the cloud ID manually.'), cloudId)
	}
	isCopied.value = true
	showSuccess(t('federatedfilesharing', 'Copied!'))
	setTimeout(() => {
		isCopied.value = false
	}, 2000)
}
</script>

<template>
	<NcSettingsSection
		:name="t('federatedfilesharing', 'Federated Cloud')"
		:description="t('federatedfilesharing', 'You can share with anyone who uses a {productName} server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com', { productName })"
		:doc-url="docUrlFederated">
		<NcInputField
			class="federated-cloud__cloud-id"
			readonly
			:label="t('federatedfilesharing', 'Your Federated Cloud ID')"
			:model-value="cloudId"
			:success="isCopied"
			show-trailing-button
			:trailing-button-label="copyLinkTooltip"
			@trailing-button-click="copyCloudId">
			<template #trailing-button-icon>
				<IconCheck v-if="isCopied" :size="20" fill-color="var(--color-border-success)" />
				<IconClipboard v-else :size="20" />
			</template>
		</NcInputField>

		<p class="social-button">
			{{ t('federatedfilesharing', 'Share it so your friends can share files with you:') }}<br>
			<NcButton :href="shareBlueSkyUrl">
				{{ t('federatedfilesharing', 'Bluesky') }}
				<template #icon>
					<img class="social-button__icon" :src="urlBlueSkyIcon">
				</template>
			</NcButton>
			<NcButton :href="shareFacebookUrl">
				{{ t('federatedfilesharing', 'Facebook') }}
				<template #icon>
					<img class="social-button__icon social-button__icon--bright" :src="urlFacebookIcon">
				</template>
			</NcButton>
			<NcButton :href="shareMastodonUrl">
				{{ t('federatedfilesharing', 'Mastodon') }}
				<template #icon>
					<img class="social-button__icon" :src="urlMastodonIcon">
				</template>
			</NcButton>
			<NcButton
				class="social-button__website-button"
				@click="showHtml = !showHtml">
				<template #icon>
					<IconWeb :size="20" />
				</template>
				{{ t('federatedfilesharing', 'Add to your website') }}
			</NcButton>
		</p>

		<template v-if="showHtml">
			<p style="margin: 10px 0">
				<a
					target="_blank"
					rel="noreferrer noopener"
					:href="reference"
					:style="backgroundStyle">
					<span :style="linkStyle" />
					{{ t('federatedfilesharing', 'Share with me via {productName}', { productName }) }}
				</a>
			</p>

			<div>
				<p>{{ t('federatedfilesharing', 'HTML Code:') }}</p>
				<br>
				<pre><code>{{ htmlCode }}</code></pre>
			</div>
		</template>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
	.social-button {
		margin-top: 0.5rem;

		button, a {
			display: inline-flex;
			margin-inline-start: 0.5rem;
			margin-top: 1rem;
		}

		&__website-button {
			width: min(100%, 400px) !important;
		}

		&__icon {
			height: 20px;
			width: 20px;
			filter: var(--background-invert-if-dark);

			&--bright {
				// Some logos like the Facebook logo have bright color schema
				filter: var(--background-invert-if-bright);
			}
		}
	}

	.federated-cloud__cloud-id {
		max-width: 300px;
	}

	pre {
		margin-top: 0;
		white-space: pre-wrap;
	}
</style>
