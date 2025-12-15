<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios, { isAxiosError } from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import OAuthItem from '../components/OAuthItem.vue'

export interface IOauthClient {
	id: string
	name: string
	redirectUri: string
	clientId: string
	clientSecret: string
}

const clients = defineModel<IOauthClient[]>({ required: true })

// @ts-expect-error -- missing typing of the API
const instanceName = getCapabilities().theming.name
const oauthDocLink = loadState<string>('oauth2', 'oauth2-doc-link')

const showSecretWarning = ref(false)
const newClient = ref({
	name: '',
	redirectUri: '',
	errorMsg: '',
	error: false,
})

/**
 * @param id - The id of the client to delete
 */
async function deleteClient(id: string) {
	await axios.delete(generateUrl('apps/oauth2/clients/{id}', { id }))
	clients.value = clients.value.filter((client) => client.id !== id)
}

/**
 * Add the generated client to the backend and display it in the list
 */
async function addClient() {
	newClient.value.error = false

	try {
		const { data } = await axios.post(generateUrl('apps/oauth2/clients'), {
			name: newClient.value.name,
			redirectUri: newClient.value.redirectUri,
		})
		clients.value.push(data)
		showSecretWarning.value = true

		newClient.value.name = ''
		newClient.value.redirectUri = ''
	} catch (error) {
		newClient.value.error = true
		if (isAxiosError(error) && error.response) {
			newClient.value.errorMsg = error.response.data.message
		} else {
			newClient.value.errorMsg = t('oauth2', 'An unknown error occurred.')
		}
	}
}
</script>

<template>
	<NcSettingsSection
		:name="t('oauth2', 'OAuth 2.0 clients')"
		:description="t('oauth2', 'OAuth 2.0 allows external services to request access to {instanceName}.', { instanceName })"
		:doc-url="oauthDocLink">
		<table v-if="clients.length > 0" :class="[$style.oauthApp__table, { [$style.oauthApp__table_withSecret]: showSecretWarning }]">
			<thead>
				<tr>
					<th>
						{{ t('oauth2', 'Name') }}
					</th>
					<th>
						{{ t('oauth2', 'Redirection URI') }}
					</th>
					<th>
						{{ t('oauth2', 'Client identifier') }}
					</th>
					<th>
						{{ t('oauth2', 'Secret key') }}
					</th>
					<th>
						<span class="hidden-visually">{{ t('oauth2', 'Delete client') }}</span>
					</th>
				</tr>
			</thead>
			<tbody>
				<OAuthItem
					v-for="client in clients"
					:key="client.id"
					:client="client"
					@delete="deleteClient(client.id)" />
			</tbody>
		</table>
		<NcNoteCard
			v-if="showSecretWarning"
			type="warning">
			{{ t('oauth2', 'Make sure you store the secret key, it cannot be recovered.') }}
		</NcNoteCard>

		<br>
		<h3>{{ t('oauth2', 'Add client') }}</h3>
		<NcNoteCard v-if="newClient.error" type="error">
			{{ newClient.errorMsg }}
		</NcNoteCard>
		<form :class="$style.oauthApp__form" @submit.prevent="addClient">
			<NcTextField
				id="name"
				v-model="newClient.name"
				:class="$style.oauthApp__form__input"
				name="name"
				:label="t('oauth2', 'Name')"
				:placeholder="t('oauth2', 'Name')" />
			<NcTextField
				id="redirectUri"
				v-model="newClient.redirectUri"
				type="url"
				:class="$style.oauthApp__form__input"
				name="redirectUri"
				:label="t('oauth2', 'Redirection URI')"
				:placeholder="t('oauth2', 'Redirection URI')" />
			<NcButton type="submit" :class="$style.oauthApp__submitButton">
				{{ t('oauth2', 'Add') }}
			</NcButton>
		</form>
	</NcSettingsSection>
</template>

<style module lang="scss">
.oauthApp__form {
	display: flex;
	flex-direction: row;
}

.oauthApp__form__input {
	max-width: 260px;
	margin-inline-end: 10px;
}

.oauthApp__table {
	width: 100%;
	border-collapse: collapse;
	table-layout: fixed;

	th, td {
		overflow: hidden;
		padding: var(--default-grid-baseline);
		text-wrap: wrap;
		word-wrap: break-word;
	}

	tbody tr {
		border-top: 1px solid var(--color-border);
	}

	th:nth-of-type(2), td:nth-of-type(2) {
		width: 33%;
	}

	th:nth-of-type(3), td:nth-of-type(3) {
		width: 50%;
	}

	// by default hide the secret column
	th:nth-of-type(4), td:nth-of-type(4) {
		display: none;
	}

	// the action column only needs to have the button size
	th:nth-of-type(5), td:nth-of-type(5) {
		width: calc(var(--default-clickable-area) + 2 * var(--default-grid-baseline));
	}
}

.oauthApp__table_withSecret {
	th:nth-of-type(2), td:nth-of-type(2) {
		width: 25%;
	}

	th:nth-of-type(3), td:nth-of-type(3) {
		width: 40%;
	}
	th:nth-of-type(4), td:nth-of-type(4) {
		display: table-cell;
		width: calc(200px + 2 * var(--default-grid-baseline));
	}
}
</style>
