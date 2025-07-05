<template>
	<div class="daemon-selection-list">
		<ul v-if="dockerDaemons.length > 0"
			:aria-label="t('settings', 'Registered Deploy daemons list')">
			<DaemonEnableSelection v-for="daemon in dockerDaemons"
				:key="daemon.id"
				:daemon="daemon"
				:is-default="defaultDaemon.name === daemon.name"
				:app="app"
				:deploy-options="deployOptions"
				@close="closeModal" />
		</ul>
		<NcEmptyContent v-else
			:name="t('settings', 'No Deploy daemons configured')"
			:description="t('settings', 'Register a custom one or setup from available templates')">
			<template #icon>
				<FormatListBullet :size="20" />
			</template>
		</NcEmptyContent>
	</div>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import FormatListBullet from 'vue-material-design-icons/FormatListBulleted.vue'
import DaemonEnableSelection from './DaemonEnableSelection.vue'
import { useAppApiStore } from '../../store/app-api-store.ts'
import { useAppsStore } from '../../store/apps-store.ts'

export default {
	name: 'DaemonSelectionList',
	components: {
		FormatListBullet,
		DaemonEnableSelection,
		NcEmptyContent,
	},
	props: {
		app: {
			type: Object,
			required: true,
		},
		deployOptions: {
			type: Object,
			required: false,
			default: () => ({}),
		},
	},
	setup() {
		const store = useAppsStore()
		const appApiStore = useAppApiStore()

		return {
			store,
			appApiStore,
		}
	},
	computed: {
		dockerDaemons() {
			return this.appApiStore.dockerDaemons
		},
		defaultDaemon() {
			return this.appApiStore.defaultDaemon
		},
	},
	methods: {
		closeModal() {
			this.$emit('close')
		},
	},
}
</script>

<style scoped lang="scss">
.daemon-selection-list {
	max-height: 300px;
	overflow-y: scroll;
	padding: 2rem;

	.empty-content {
		margin-top: 0;
		text-align: center;
	}
}
</style>
