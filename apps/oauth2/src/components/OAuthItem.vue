<!--
  - @copyright Copyright (c) 2018 Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @author Roeland Jago Douma <roeland@famdouma.nl>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<tr>
		<td>{{ name }}</td>
		<td>{{ redirectUri }}</td>
		<td><code>{{ clientId }}</code></td>
		<td>
			<div class="action-secret">
				<code>{{ renderedSecret }}</code>
				<NcButton type="tertiary-no-background"
					:aria-label="t('oauth2', 'Show client secret')"
					@click="toggleSecret">
					<template #icon>
						<EyeOutline :size="20"
							:title="t('oauth2', 'Show client secret')" />
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
		padding-right: 0;
	}
</style>
