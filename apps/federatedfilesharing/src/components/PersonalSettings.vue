<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('federatedfilesharing', 'Federated Cloud')"
		:description="t('federatedfilesharing', 'You can share with anyone who uses a Nextcloud server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com')"
		:doc-url="docUrlFederated">
		<NcInputField class="federated-cloud__cloud-id"
			readonly
			:label="t('federatedfilesharing', 'Your Federated Cloud ID')"
			:value="cloudId"
			:success="isCopied"
			show-trailing-button
			:trailing-button-label="copyLinkTooltip"
			@trailing-button-click="copyCloudId">
			<template #trailing-button-icon>
				<IconCheck v-if="isCopied" :size="20" fill-color="var(--color-success)" />
				<IconClipboard v-else :size="20" />
			</template>
		</NcInputField>

		<p class="social-button">
			{{ t('federatedfilesharing', 'Share it so your friends can share files with you:') }}<br>
			<NcButton @click="goTo(shareFacebookUrl)">
				{{ t('federatedfilesharing', 'Facebook') }}
				<template #icon>
					<img class="social-button__icon social-button__icon--bright" :src="urlFacebookIcon">
				</template>
			</NcButton>
			<NcButton :aria-label="t('federatedfilesharing', 'X (formerly Twitter)')"
				@click="goTo(shareXUrl)">
				{{ t('federatedfilesharing', 'formerly Twitter') }}
				<template #icon>
					<img class="social-button__icon" :src="urlXIcon">
				</template>
			</NcButton>
			<NcButton @click="goTo(shareMastodonUrl)">
				{{ t('federatedfilesharing', 'Mastodon') }}
				<template #icon>
					<img class="social-button__icon" :src="urlMastodonIcon">
				</template>
			</NcButton>
			<NcButton class="social-button__website-button"
				@click="showHtml = !showHtml">
				<template #icon>
					<IconWeb :size="20" />
				</template>
				{{ t('federatedfilesharing', 'Add to your website') }}
			</NcButton>
		</p>

		<template v-if="showHtml">
			<p style="margin: 10px 0">
				<a target="_blank"
					rel="noreferrer noopener"
					:href="reference"
					:style="backgroundStyle">
					<span :style="linkStyle" />
					{{ t('federatedfilesharing', 'Share with me via Nextcloud') }}
				</a>
			</p>

			<p>
				{{ t('federatedfilesharing', 'HTML Code:') }}
				<br>
				<pre>{{ htmlCode }}</pre>
			</p>
		</template>
	</NcSettingsSection>
</template>

<script lang="ts">
import { showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import IconWeb from 'vue-material-design-icons/Web.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconClipboard from 'vue-material-design-icons/ContentCopy.vue'

export default {
	name: 'PersonalSettings',
	components: {
		NcButton,
		NcInputField,
		NcSettingsSection,
		IconCheck,
		IconClipboard,
		IconWeb,
	},
	setup() {
		return {
			t,

			cloudId: loadState<string>('federatedfilesharing', 'cloudId'),
			reference: loadState<string>('federatedfilesharing', 'reference'),
			urlFacebookIcon: imagePath('core', 'facebook'),
			urlMastodonIcon: imagePath('core', 'mastodon'),
			urlXIcon: imagePath('core', 'x'),
		}
	},
	data() {
		return {
			color: loadState('federatedfilesharing', 'color'),
			textColor: loadState('federatedfilesharing', 'textColor'),
			logoPath: loadState('federatedfilesharing', 'logoPath'),
			docUrlFederated: loadState('federatedfilesharing', 'docUrlFederated'),
			showHtml: false,
			isCopied: false,
		}
	},
	computed: {
		messageWithURL() {
			return t('federatedfilesharing', 'Share with me through my #Nextcloud Federated Cloud ID, see {url}', { url: this.reference })
		},
		messageWithoutURL() {
			return t('federatedfilesharing', 'Share with me through my #Nextcloud Federated Cloud ID')
		},
		shareMastodonUrl() {
			return `https://mastodon.social/?text=${encodeURIComponent(this.messageWithoutURL)}&url=${encodeURIComponent(this.reference)}`
		},
		shareXUrl() {
			return `https://x.com/intent/tweet?text=${encodeURIComponent(this.messageWithURL)}`
		},
		shareFacebookUrl() {
			return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(this.reference)}`
		},
		logoPathAbsolute() {
			return window.location.protocol + '//' + window.location.host + this.logoPath
		},
		backgroundStyle() {
			return `padding:10px;background-color:${this.color};color:${this.textColor};border-radius:3px;padding-inline-start:4px;`
		},
		linkStyle() {
			return `background-image:url(${this.logoPathAbsolute});width:50px;height:30px;position:relative;top:8px;background-size:contain;display:inline-block;background-repeat:no-repeat; background-position: center center;`
		},
		htmlCode() {
			return `<a target="_blank" rel="noreferrer noopener" href="${this.reference}" style="${this.backgroundStyle}">
	<span style="${this.linkStyle}"></span>
	${t('federatedfilesharing', 'Share with me via Nextcloud')}
</a>`
		},
		copyLinkTooltip() {
			return this.isCopied ? t('federatedfilesharing', 'Cloud ID copied to the clipboard') : t('federatedfilesharing', 'Copy to clipboard')
		},
	},
	methods: {
		async copyCloudId(): Promise<void> {
			try {
				await navigator.clipboard.writeText(this.cloudId)
				showSuccess(t('federatedfilesharing', 'Cloud ID copied to the clipboard'))
			} catch (e) {
				// no secure context or really old browser - need a fallback
				window.prompt(t('federatedfilesharing', 'Clipboard not available. Please copy the cloud ID manually.'), this.reference)
			}
			this.isCopied = true
			showSuccess(t('federatedfilesharing', 'Copied!'))
			setTimeout(() => {
				this.isCopied = false
			}, 2000)
		},

		goTo(url: string): void {
			window.location.href = url
		},
	},
}
</script>

<style lang="scss" scoped>
	.social-button {
		margin-top: 0.5rem;

		button {
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
