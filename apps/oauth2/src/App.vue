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
	<div id="oauth2" class="section">
		<h2>{{ t('oauth2', 'OAuth 2.0 clients') }}</h2>
		<p class="settings-hint">{{ t('oauth2', 'OAuth 2.0 allows external services to request access to {instanceName}.', { instanceName: oc_defaults.name}) }}</p>
		<table class="grid" v-if="clients.length > 0">
			<thead>
				<tr>
					<th id="headerName" scope="col">{{ t('oauth2', 'Name') }}</th>
					<th id="headerRedirectUri" scope="col">{{ t('oauth2', 'Redirection URI') }}</th>
					<th id="headerClientIdentifier" scope="col">{{ t('oauth2', 'Client Identifier') }}</th>
					<th id="headerSecret" scope="col">{{ t('oauth2', 'Secret') }}</th>
					<th id="headerRemove">&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<OAuthItem v-for="client in clients"
					:key="client.id"
					:client="client"
					@delete="deleteClient"
				/>
			</tbody>
		</table>

		<br/>
		<h3>{{ t('oauth2', 'Add client') }}</h3>
		<span v-if="newClient.error" class="msg error">{{newClient.errorMsg}}</span>
		<form @submit.prevent="addClient">
			<input type="text" id="name" name="name" :placeholder="t('oauth2', 'Name')" v-model="newClient.name">
			<input type="url" id="redirectUri" name="redirectUri" :placeholder="t('oauth2', 'Redirection URI')" v-model="newClient.redirectUri">
			<input type="submit" class="button" :value="t('oauth2', 'Add')">
		</form>
	</div>
</template>

<script>
import axios from 'axios';
import OAuthItem from './components/OAuthItem';

export default {
	name: 'App',
	components: {
		OAuthItem
	},
	data: function() {
		return {
			clients: [],
			newClient: {
				name: '',
				redirectUri: '',
				errorMsg: '',
				error: false
			}
		};
	},
	beforeMount: function() {
		let requestToken = OC.requestToken;
		let tokenHeaders = { headers: { requesttoken: requestToken } };

		axios.get(OC.generateUrl('apps/oauth2/clients'), tokenHeaders)
			.then((response) => {
			this.clients = response.data;
		});
	},
	methods: {
		deleteClient(id) {
			let requestToken = OC.requestToken;
			let tokenHeaders = { headers: { requesttoken: requestToken } };

			axios.delete(OC.generateUrl('apps/oauth2/clients/{id}', {id: id}), tokenHeaders)
				.then((response) => {
					this.clients = this.clients.filter(client => client.id !== id);
				});
		},
		addClient() {
			let requestToken = OC.requestToken;
			let tokenHeaders = { headers: { requesttoken: requestToken } };
			this.newClient.error = false;

			axios.post(
				OC.generateUrl('apps/oauth2/clients'),
				{
					name: this.newClient.name,
					redirectUri: this.newClient.redirectUri
				},
				tokenHeaders
			).then(response => {
				this.clients.push(response.data);

				this.newClient.name = '';
				this.newClient.redirectUri = '';
			}).catch(reason => {
				this.newClient.error = true;
				this.newClient.errorMsg = reason.response.data.message;
			});
		}
	},
}
</script>
