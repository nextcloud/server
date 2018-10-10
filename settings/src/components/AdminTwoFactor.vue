<template>
	<div>
		<p>
			{{ t('settings', 'Two-factor authentication can be enforced for all users. If they do not have a two-factor provider configured, they will be unable to log into the system.') }}
		</p>
		<p v-if="loading">
			<span class="icon-loading-small two-factor-loading"></span>
			<span>{{ t('settings', 'Enforce two-factor authentication') }}</span>
		</p>
		<p v-else>
			<input type="checkbox"
				   id="two-factor-enforced"
				   class="checkbox"
				   v-model="enabled"
				   v-on:change="onEnforcedChanged">
			<label for="two-factor-enforced">{{ t('settings', 'Enforce two-factor authentication') }}</label>
		</p>
	</div>
</template>

<script>
	import Axios from 'nextcloud-axios'

	export default {
		name: "AdminTwoFactor",
		data () {
			return {
				enabled: false,
				loading: false
			}
		},
		mounted () {
			this.loading = true
			Axios.get(OC.generateUrl('/settings/api/admin/twofactorauth'))
				.then(resp => resp.data)
				.then(state => {
					this.enabled = state.enabled
					this.loading = false
					console.info('loaded')
				})
				.catch(err => {
					console.error(error)
					this.loading = false
					throw err
				})
		},
		methods: {
			onEnforcedChanged () {
				this.loading = true
				const data = {
					enabled: this.enabled
				}
				Axios.put(OC.generateUrl('/settings/api/admin/twofactorauth'), data)
					.then(resp => resp.data)
					.then(state => {
						this.enabled = state.enabled
						this.loading = false
					})
					.catch(err => {
						console.error(error)
						this.loading = false
						throw err
					})
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