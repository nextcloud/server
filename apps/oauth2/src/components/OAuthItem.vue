<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr>
		<td>{{ name }}</td>
		<td>{{ redirectUri }}</td>
		<td><code>{{ clientId }}</code></td>
		<td>
			<div class="action-secret">
				<code>{{ renderedSecret }}</code>
				<NcButton v-if="clientSecret !== ''"
					type="tertiary-no-background"
					:aria-label="toggleAriaLabel"
					@click="toggleSecret">
					<template #icon>
						<EyeOutline :size="20" />
					</template>
				</NcButton>
			</div>
		</td>
		<td class="action-column">
			<NcButton type="tertiary-no-background"
				:aria-label="t('oauth2', 'Delete')"
				@click="$emit('delete', id)">
				<template #icon>
					<Delete :size="20"
						:title="t('oauth2', 'Delete')" />
				</template>
			</NcButton>
		</td>
	</tr>
</template>

<script>

import Delete from 'vue-material-design-icons/Delete.vue'
import EyeOutline from 'vue-material-design-icons/EyeOutline.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'OAuthItem',
	components: {
		Delete,
		NcButton,
		EyeOutline,
	},
	props: {
		client: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			id: this.client.id,
			name: this.client.name,
			redirectUri: this.client.redirectUri,
			clientId: this.client.clientId,
			clientSecret: this.client.clientSecret,
			renderSecret: false,
		}
	},
	computed: {
		renderedSecret() {
			if (this.renderSecret) {
				return this.clientSecret
			} else {
				return '****'
			}
		},
		toggleAriaLabel() {
			if (!this.renderSecret) {
				return t('oauth2', 'Show client secret')
			}
			return t('oauth2', 'Hide client secret')
		},
	},
	methods: {
		toggleSecret() {
			this.renderSecret = !this.renderSecret
		},
	},
}
</script>

<style scoped>
	.action-secret {
		display: flex;
		align-items: center;
	}

	.action-secret code {
		padding-top: 5px;
	}

	td code {
		display: inline-block;
		vertical-align: middle;
	}

	table.inline td {
		border: none;
		padding: 5px;
	}

	.action-column {
		display: flex;
		justify-content: flex-end;
		padding-inline-end: 0;
	}
</style>
