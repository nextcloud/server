<template>
	<div class="daemon">
		<NcListItem :name="itemTitle"
			:details="isDefault ? t('settings', 'Default') : ''"
			:force-display-actions="true"
			:counter-number="daemon.exAppsCount"
			:class="{'daemon-default': isDefault }"
			counter-type="highlighted"
			@click.stop="selectDaemonAndInstall">
			<template #subname>
				{{ daemon.accepts_deploy_id }}
			</template>
		</NcListItem>
	</div>
</template>

<script>
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AppManagement from '../../mixins/AppManagement.js'
import { useAppsStore } from '../../store/apps-store'
import { useAppApiStore } from '../../store/app-api-store'

export default {
	name: 'DaemonEnableSelection',
	components: {
		NcListItem,
	},
	mixins: [AppManagement],
	props: {
		daemon: {
			type: Object,
			required: true,
			default: () => {},
		},
		isDefault: {
			type: Boolean,
			required: true,
			default: () => false,
		},
		app: {
			type: Object,
			required: true,
			default: () => {},
		},
		deployOptions: {
			type: Object,
			required: false,
			default: () => null,
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
		itemTitle() {
			return this.daemon.name + ' - ' + this.daemon.display_name
		},
		daemons() {
			return this.appApiStore.dockerDaemons
		},
	},
	methods: {
		closeModal() {
			this.$emit('close')
		},
		selectDaemonAndInstall() {
			this.closeModal()
			this.enable(this.app.id, this.daemon, this.deployOptions)
		},
	},
}
</script>

<style lang="scss">
.daemon-default > .list-item {
	background-color: var(--color-background-dark);
}
</style>
