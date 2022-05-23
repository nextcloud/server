<template>
	<SettingsSection :title="t('settings', 'Setup Checks')"
		:description="t('settings', `It's important for the security and performance of your instance that everything is configured correctly. To help you with that we are doing some automatic checks. Please see the linked documentation for more information.`)">
		<div v-for="(checks, category) in results"
			:key="category"
			class="card">
			<div class="card__header">
				<h3>{{ category }}</h3>
			</div>
			<div class="card__body">
				<div v-for="(check, name) in checks" :key="name" class="check">
					<template v-if="check.severity === 'success'">
						<Check :size="20" :fill-color="'var(--color-success)'" />
					</template>
					{{ name }}
				</div>
			</div>
		</div>
	</SettingsSection>
</template>

<script>
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Check from 'vue-material-design-icons/Check'

export default {
	name: 'SetupCheck',
	components: {
		SettingsSection,
		Check,
	},
	data() {
		return {
			results: [],
		}
	},
	mounted() {
		this.loadSetupChecks()
	},
	methods: {
		async loadSetupChecks() {
			const { data } = await axios.get(generateUrl('/settings/setupcheck'))
			console.debug(data)
			this.results = data
		},
	},
}
</script>

<style lang="scss" scoped>
.card {
	box-shadow: 0 4px 8px 0 rgba(var(--color-box-shadow-rgb), 0.4);
	border-radius: var(--border-radius);
	&__body, &__header {
		padding: 4px 8px;
	}
	.check {
		display: flex;
		align-items: center;
	}
}
</style>
