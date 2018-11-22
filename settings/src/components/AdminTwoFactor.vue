<template>
	<div>
		<p class="settings-hint">
			{{ t('settings', 'Two-factor authentication can be enforced for all	users and specific groups. If they do not have a two-factor provider configured, they will be unable to log into the system.') }}
		</p>
		<p v-if="loading">
			<span class="icon-loading-small two-factor-loading"></span>
			<span>{{ t('settings', 'Enforce two-factor authentication') }}</span>
		</p>
		<p v-else>
			<input type="checkbox"
				   id="two-factor-enforced"
				   class="checkbox"
				   v-model="state.enforced"
				   v-on:change="saveChanges">
			<label for="two-factor-enforced">{{ t('settings', 'Enforce two-factor authentication') }}</label>
		</p>
		<h3>{{ t('settings', 'Limit to groups') }}</h3>
		{{ t('settings', 'Enforcement of two-factor authentication can be set for certain groups only.') }}
		<p>
			{{ t('settings', 'Two-factor authentication is enforced for all	members of the following groups.') }}
		</p>
		<p>
			<Multiselect v-model="state.enforcedGroups"
						 :options="groups"
						 :placeholder="t('settings', 'Enforced groups')"
						 :disabled="loading"
						 :multiple="true"
						 :searchable="true"
						 @search-change="searchGroup"
						 :loading="loadingGroups"
						 :show-no-options="false"
						 :close-on-select="false">
			</Multiselect>
		</p>
		<p>
			{{ t('settings', 'Two-factor authentication is not enforced for	members of the following groups.') }}
		</p>
		<p>
			<Multiselect v-model="state.excludedGroups"
						 :options="groups"
						 :placeholder="t('settings', 'Excluded groups')"
						 :disabled="loading"
						 :multiple="true"
						 :searchable="true"
						 @search-change="searchGroup"
						 :loading="loadingGroups"
						 :show-no-options="false"
						 :close-on-select="false">
			</Multiselect>
		</p>
		<p>
			<em>
				<!-- this text is also found in the documentation. update it there as well if it ever changes -->
				{{ t('settings', 'When groups are selected/excluded, they use the following logic to determine if a user has 2FA enforced: If no groups are selected, 2FA is enabled for everyone except members of the excluded groups. If groups are selected, 2FA is enabled for all members of these. If a user is both in a selected and excluded group, the selected takes precedence and 2FA is enforced.') }}
			</em>
		</p>
		<p>
			<button class="button primary"
					v-on:click="saveChanges"
					:disabled="loading">
				{{ t('settings', 'Save changes') }}
			</button>
		</p>
	</div>
</template>

<script>
	import Axios from 'nextcloud-axios'
	import {Multiselect} from 'nextcloud-vue'
	import _ from 'lodash'

	export default {
		name: "AdminTwoFactor",
		components: {
			Multiselect
		},
		data () {
			return {
				state: {
					enforced: false,
					enforcedGroups: [],
					excludedGroups: [],
				},
				loading: false,
				groups: [],
				loadingGroups: false,
			}
		},
		mounted () {
			this.loading = true
			Axios.get(OC.generateUrl('/settings/api/admin/twofactorauth'))
				.then(resp => resp.data)
				.then(state => {
					this.state = state

					// Groups are loaded dynamically, but the assigned ones *should*
					// be valid groups, so let's add them as initial state
					this.groups = _.sortedUniq(this.state.enforcedGroups.concat(this.state.excludedGroups))

					this.loading = false
				})
				.catch(err => {
					console.error('Could not load two-factor state', err)
					throw err
				})
		},
		methods: {
			searchGroup: _.debounce(function (query) {
				this.loadingGroups = true
				Axios.get(OC.linkToOCS(`cloud/groups?offset=0&search=${encodeURIComponent(query)}&limit=20`, 2))
					.then(res => res.data.ocs)
					.then(ocs => ocs.data.groups)
					.then(groups => this.groups = _.sortedUniq(this.groups.concat(groups)))
					.catch(err => console.error('could not search groups', err))
					.then(() => this.loadingGroups = false)
			}, 500),

			saveChanges () {
				this.loading = true

				const oldState = this.state

				Axios.put(OC.generateUrl('/settings/api/admin/twofactorauth'), this.state)
					.then(resp => resp.data)
					.then(state => this.state = state)
					.catch(err => {
						console.error('could not save changes', err)

						// Restore
						this.state = oldState
					})
					.then(() => this.loading = false)
			}
		}
	}
</script>

<style>
	.two-factor-loading {
		display: inline-block;
		vertical-align: sub;
		margin-left: -2px;
		margin-right: 1px;
	}
</style>
