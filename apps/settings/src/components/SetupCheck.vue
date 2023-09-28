<template>
	<NcSettingsSection :title="t('settings', 'Setup Checks')"
		:description="t('settings', `It's important for the security and performance of your instance that everything is configured correctly. To help you with that we are doing some automatic checks. Please see the linked documentation for more information.`)">
		<div v-for="(checks, category) in results"
			:key="category"
			class="check-card">
			<div class="check-card__header" @click="toggleCollapse(category)">
				<h3>{{ category }}</h3>
				<Check v-if="stats[category].successes === stats[category].total"
					:size="20"
					:fill-color="'var(--color-success)'" />
				<Check v-else-if="stats[category].errors > 0"
					:size="20"
					:fill-color="'var(--color-error)'" />
				<Check v-else-if="stats[category].warnings > 0"
					:size="20"
					:fill-color="'var(--color-warning)'" />
				<span>
					{{ stats[category].successes }} / {{ stats[category].total }}
				</span>
			</div>
			<div class="card__body" v-if="!collapsed[category]">
				<div v-for="(check, name) in checks" :key="name" class="row-check" :class="['row-check__' + check.severity]">
					<template v-if="check.severity === 'success'">
						<Check :size="20" :fill-color="'var(--color-success)'" />
					</template>
					{{ name }}
				</div>
			</div>
		</div>
	</NcSettingsSection>
</template>

<script>
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Check from 'vue-material-design-icons/Check'

export default {
	name: 'SetupCheck',
	components: {
		NcSettingsSection,
		Check,
	},
	data() {
		return {
			results: [],
			collapsed: {},
			stats: {},
		}
	},
	mounted() {
		this.loadSetupChecks()
	},
	methods: {
		toggleCollapse(category) {
			this.collapsed[category] = !this.collapsed[category]
		},
		async loadSetupChecks() {
			const { data } = await axios.get(generateUrl('/settings/setupcheck'))
			const collapsed = {}
			const stats = {}
			for (const [category, checks] of Object.entries(data)) {
				const values = Object.values(checks)
				stats[category] = {
					total: values.length,
					successes: values.filter((check) => check.severity === 'success').length,
					warnings: values.filter((check) => check.severity === 'warning').length,
					errors: values.filter((check) => check.severity === 'errors').length,
				}
				collapsed[category] = stats[category].errors > 0
			}
			this.collapsed = collapsed
			this.stats = stats
			this.results = data
		},
	},
}
</script>

<style lang="scss" scoped>
.check-card {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	&__header {
		padding: 0.5rem 1rem;
		display: flex;
		align-items: center;
		h3 {
			margin: 0;
		}
		.material-design-icon {
			margin-left: auto;
			margin-right: 0.5rem;
		}
	}
}
.row-check {
	color: var(--color-text-light);
	background-color: var(--note-background);
	box-shadow: rgba(43, 42, 51, 0.05) 0 1px 2px 0;
	margin: 0;
	padding: 0.5rem 1rem;
	display: flex;
	align-items: center;
	&__success {
		--note-background: rgba(var(--color-success-rgb), 0.2);
		--note-theme: var(--color-success);
	}
	&__error {
		--note-background: rgba(var(--color-error-rgb), 0.2);
		--note-theme: var(--color-error);
	}
	&__warning {
		--note-background: rgba(var(--color-warning-rgb), 0.2);
		--note-theme: var(--color-warning);
	}
}
</style>
