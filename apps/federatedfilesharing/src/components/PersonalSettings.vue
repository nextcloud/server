<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('federatedfilesharing', 'Federated Cloud')"
		:description="t('federatedfilesharing', 'You can share with anyone who uses a Nextcloud server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com')"
		:doc-url="docUrlFederated">
		<p class="cloud-id-text">
			{{ t('federatedfilesharing', 'Your Federated Cloud ID:') }}
			<strong id="cloudid">{{ cloudId }}</strong>
			<NcButton ref="clipboard"
				:title="copyLinkTooltip"
				:aria-label="copyLinkTooltip"
				class="clipboard"
				type="tertiary-no-background"
				@click.prevent="copyCloudId">
				<template #icon>
					<Clipboard :size="20" />
				</template>
			</NcButton>
		</p>

		<p class="social-button">
			{{ t('federatedfilesharing', 'Share it so your friends can share files with you:') }}<br>
			<NcButton @click="goTo(shareFacebookUrl)">
				{{ t('federatedfilesharing', 'Facebook') }}
				<template #icon>
					<Facebook :size="20" />
				</template>
			</NcButton>
			<NcButton @click="goTo(shareXUrl)">
				{{ t('federatedfilesharing', 'formerly Twitter') }}
				<template #icon>
					<svg width="20"
						height="20"
						viewBox="0 0 15 15"
						xmlns="http://www.w3.org/2000/svg"><path fill="black" d="m 3.384891,2.6 c -0.3882,0 -0.61495,0.4362184 -0.39375,0.7558594 L 6.5841098,8.4900156 2.9770785,12.707422 C 2.7436785,12.979821 2.9370285,13.4 3.2958285,13.4 H 3.694266 c 0.176,0 0.3430313,-0.07714 0.4570313,-0.210938 L 7.294266,9.5065156 9.6602817,12.887891 C 9.8762817,13.208984 10.25229,13.4 10.743485,13.4 h 1.900391 c 0.3882,0 0.61575,-0.436688 0.39375,-0.754688 L 9.2466097,7.2195156 12.682547,3.1941408 C 12.881744,2.9601408 12.715528,2.6 12.407473,2.6 h -0.506566 c -0.175,0 -0.34186,0.076453 -0.45586,0.2197656 L 8.5405785,6.2058438 6.3790317,3.1132812 C 6.1568442,2.7913687 5.6965004,2.6 5.3958285,2.6 Z" /></svg>
				</template>
			</NcButton>
			<NcButton @click="goTo(shareMastodonUrl)">
				{{ t('federatedfilesharing', 'Mastodon') }}
				<template #icon>
					<Mastodon :size="20" />
				</template>
			</NcButton>
			<NcButton class="social-button__website-button"
				@click="showHtml = !showHtml">
				<template #icon>
					<Web :size="20" />
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

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Mastodon from 'vue-material-design-icons/Mastodon.vue'
import Facebook from 'vue-material-design-icons/Facebook.vue'
import Web from 'vue-material-design-icons/Web.vue'
import Clipboard from 'vue-material-design-icons/ContentCopy.vue'

export default {
	name: 'PersonalSettings',
	components: {
		NcButton,
		NcSettingsSection,
		Mastodon,
		Facebook,
		Web,
		Clipboard,
	},
	data() {
		return {
			color: loadState('federatedfilesharing', 'color'),
			textColor: loadState('federatedfilesharing', 'textColor'),
			logoPath: loadState('federatedfilesharing', 'logoPath'),
			reference: loadState('federatedfilesharing', 'reference'),
			cloudId: loadState('federatedfilesharing', 'cloudId'),
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
			return `https://https://mastodon.xyz/?text=${encodeURIComponent(this.messageWithoutURL)}&url=${encodeURIComponent(this.reference)}`
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
			return `padding:10px;background-color:${this.color};color:${this.textColor};border-radius:3px;padding-left:4px;`
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
		async copyCloudId() {
			if (!navigator.clipboard) {
				// Clipboard API not available
				showError(t('federatedfilesharing', 'Clipboard is not available'))
				return
			}
			await navigator.clipboard.writeText(this.cloudId)
			this.isCopied = true
			showSuccess(t('federatedfilesharing', 'Copied!'))
			this.$refs.clipboard.$el.focus()
		},
		goTo(url) {
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
			margin-left: 0.5rem;
			margin-top: 1rem;
		}
		&__website-button {
			width: min(100%, 400px) !important;
		}
	}
	.cloud-id-text {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		button {
			display: inline-flex;
		}
	}
	pre {
		margin-top: 0;
		white-space: pre-wrap;
	}
	#cloudid {
		margin-left: 0.25rem;
	}
</style>
