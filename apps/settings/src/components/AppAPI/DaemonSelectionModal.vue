<template>
	<div class="daemon-selection-modal">
		<NcModal :show="show"
			:name="t('settings', 'Daemon selection')"
			size="normal"
			@close="closeModal">
			<div class="select-modal-body">
				<h3>{{ t('settings', 'Choose Deploy Daemon for {appName}', {appName: app.name }) }}</h3>
				<DaemonSelectionList :app="app"
					:deploy-options="deployOptions"
					@close="closeModal" />
			</div>
		</NcModal>
	</div>
</template>

<script>
import NcModal from '@nextcloud/vue/components/NcModal'
import DaemonSelectionList from './DaemonSelectionList.vue'
import { useAppsStore } from '../../store/apps-store'
import { useAppApiStore } from '../../store/app-api-store'

export default {
	name: 'DaemonSelectionModal',
	components: {
		NcModal,
		DaemonSelectionList,
	},
	props: {
		show: {
			type: Boolean,
			required: true,
			default: false,
		},
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
	data() {
		return {
			selectDaemonModal: false,
		}
	},
	methods: {
		closeModal() {
			this.$emit('update:show', false)
		},
	},
}
</script>
<style scoped>
.select-modal-body h3 {
	text-align: center;
}
</style>
