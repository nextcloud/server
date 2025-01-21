<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('oauth2', 'OAuth 2.0 clients')"
		:description="t('oauth2', 'OAuth 2.0 allows external services to request access to {instanceName}.', { instanceName })"
		:doc-url="oauthDocLink">
		<table v-if="clients.length > 0" class="grid">
			<thead>
				<tr>
					<th>
						{{ t('oauth2', 'Name') }}
					</th>
					<th>
						{{ t('oauth2', 'Redirection URI') }}
					</th>
					<th>
						{{ t('oauth2', 'Client Identifier') }}
					</th>
					<th>
						{{ t('oauth2', 'Secret key') }}
					</th>
					<th>
						{{ t('oauth2', 'Delete client') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<OAuthItem v-for="client in clients"
					:key="client.id"
					:client="client"
					@delete="deleteClient" />
			</tbody>
		</table>
		<NcNoteCard v-if="showSecretWarning"
			type="warning">
			{{ t('oauth2', 'Make sure you store the secret key, it cannot be recovered.') }}
		</NcNoteCard>

		<br>
		<h3>{{ t('oauth2', 'Add client') }}</h3>
		<span v-if="newClient.error" class="msg error">{{ newClient.errorMsg }}</span>
		<form class="oauth2-form" @submit.prevent="addClient">
			<NcTextField id="name"
				:value.sync="newClient.name"
				type="text"
				class="oauth2-form--input"
				name="name"
				:label="t('oauth2', 'Name')"
				:placeholder="t('oauth2', 'Name')" />
			<NcTextField id="redirectUri"
				:value.sync="newClient.redirectUri"
				type="url"
				class="oauth2-form--input"
				name="redirectUri"
				:label="t('oauth2', 'Redirection URI')"
				:placeholder="t('oauth2', 'Redirection URI')" />
			<NcButton native-type="submit" class="inline-button">
				{{ t('oauth2', 'Add') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import OAuthItem from './components/OAuthItem.vue'
import { generateUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import { loadState } from '@nextcloud/initial-state'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default {
	name: 'App',
	components: {
		OAuthItem,
		NcSettingsSection,
		NcButton,
		NcTextField,
		NcNoteCard,
	},
	props: {
		clients: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			newClient: {
				name: '',
				redirectUri: '',
				errorMsg: '',
				error: false,
			},
			oauthDocLink: loadState('oauth2', 'oauth2-doc-link'),
			showSecretWarning: false,
		}
	},
	computed: {
		instanceName() {
			return getCapabilities().theming.name
		},
	},
	methods: {
		deleteClient(id) {
			axios.delete(generateUrl('apps/oauth2/clients/{id}', { id }))
				.then(() => {
					// eslint-disable-next-line vue/no-mutating-props
					this.clients = this.clients.filter(client => client.id !== id)
				})
		},
		addClient() {
			this.newClient.error = false

			axios.post(
				generateUrl('apps/oauth2/clients'),
				{
					name: this.newClient.name,
					redirectUri: this.newClient.redirectUri,
				},
			).then(response => {
				// eslint-disable-next-line vue/no-mutating-props
				this.clients.push(response.data)
				this.showSecretWarning = true

				this.newClient.name = ''
				this.newClient.redirectUri = ''
			}).catch(reason => {
				this.newClient.error = true
				this.newClient.errorMsg = reason.response.data.message
			})
		},
	},
}
</script>
<style scoped>
	table {
		max-width: 800px;
	}

	/** Overwrite button height and position to be aligned with the text input */
	.inline-button {
		min-height: 34px !important;
		display: inline-flex !important;
	}

	.oauth2-form {
		display: flex;
		flex-direction: row;
	}

	.oauth2-form--input {
		max-width: 200px;
		margin-inline-end: 10px;
	}
</style>
