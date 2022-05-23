<!--
SPDX-FileLicenseText: 2022 Carl Schwan <carl@carlschwan.eu>
SPDX-License-Identifier: AGPL-3.0-or-later

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<SettingsSection :title="t('federatedfilesharing', 'Federated Cloud')"
		:description="t('federatedfilesharing', 'You can share with anyone who uses a Nextcloud server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com')"
		:doc-url="docUrlFederated">
		<p class="cloud-id-text">
			{{ t('federatedfilesharing', 'Your Federated Cloud ID:') }}
			<strong id="cloudid">{{ cloudId }}</strong>
			<Button ref="clipboard"
				v-tooltip="copyLinkTooltip"
				class="clipboard"
				type="tertiary-no-background"
				@click.prevent="copyCloudId">
				<template #icon>
					<Clipboard :size="20" />
				</template>
			</Button>
		</p>

		<p class="social-button">
			{{ t('federatedfilesharing', 'Share it so your friends can share files with you:') }}<br>
			<Button @click="goTo(shareFacebookUrl)">
				{{ t('federatedfilesharing', 'Facebook') }}
				<template #icon>
					<Facebook :size="20" />
				</template>
			</Button>
			<Button @click="goTo(shareTwitterUrl)">
				{{ t('federatedfilesharing', 'Twitter') }}
				<template #icon>
					<Twitter :size="20" />
				</template>
			</Button>
			<Button @click="goTo(shareDiasporaUrl)">
				{{ t('federatedfilesharing', 'Diaspora') }}
				<template #icon>
					<svg width="20"
						height="20"
						viewBox="-10 -5 1034 1034"
						xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M502 197q-96 0-96.5 1.5t-1.5 137-1.5 138-2 2.5T266 432.5 132.5 390t-30 94T74 578l232 77q21 8 21 10t-79.5 117.5T168 899t79.5 56.5T328 1011t81-110 82-110 41 55l83 115q43 60 44 60t79.5-58 79-59-76-112.5-76-113.5T795 632.5t129.5-44-28-94T867 400t-128 42-128.5 43-2.5-7.5-1-38.5l-3-108q-4-133-5-133.5t-97-.5z" /></svg>
				</template>
			</Button>
			<Button @click="showHtml = !showHtml">
				<template #icon>
					<Web :size="20" />
				</template>
				{{ t('federatedfilesharing', 'Add to your website') }}
			</Button>
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
	</SettingsSection>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import Button from '@nextcloud/vue/dist/Components/Button'
import Twitter from 'vue-material-design-icons/Twitter'
import Facebook from 'vue-material-design-icons/Facebook'
import Web from 'vue-material-design-icons/Web'
import Clipboard from 'vue-material-design-icons/Clipboard'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'

export default {
	name: 'PersonalSettings',
	components: {
		Button,
		SettingsSection,
		Twitter,
		Facebook,
		Web,
		Clipboard,
	},
	directives: {
		Tooltip,
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
		shareDiasporaUrl() {
			return `https://share.diasporafoundation.org/?title=${encodeURIComponent(this.messageWithoutURL)}&url=${encodeURIComponent(this.reference)}`
		},
		shareTwitterUrl() {
			return `https://twitter.com/intent/tweet?text=${encodeURIComponent(this.messageWithURL)}`
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
			return this.isCopied ? t('federatedfilesharing', 'CloudId copied to the clipboard') : t('federatedfilesharing', 'Copy to clipboard')
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
	}
	.cloud-id-text {
		display: flex;
		align-items: center;
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
