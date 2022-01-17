<template>
	<Multiselect
		v-model="selected"
		class="group-multiselect"
		:placeholder="t('settings', 'None')"
		track-by="gid"
		label="displayName"
		:options="availableGroups"
		open-direction="bottom"
		:multiple="true"
		:allow-empty="true" />
</template>

<script>
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import logger from '../../logger'

export default {
	name: 'GroupSelect',
	components: {
		Multiselect,
	},
	props: {
		availableGroups: {
			type: Array,
			default: () => [],
		},
		setting: {
			type: Object,
			required: true,
		},
		authorizedGroups: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			selected: this.authorizedGroups
				.filter((group) => group.class === this.setting.class)
				.map((groupToMap) => this.availableGroups.find((group) => group.gid === groupToMap.group_id))
				.filter((group) => group !== undefined),
		}
	},
	watch: {
		selected() {
			this.saveGroups()
		},
	},
	methods: {
		async saveGroups() {
			const data = {
				newGroups: this.selected,
				class: this.setting.class,
			}
			try {
				await axios.post(generateUrl('/apps/settings/') + '/settings/authorizedgroups/saveSettings', data)
			} catch (e) {
				showError(t('settings', 'Unable to modify setting'))
				logger.error('Unable to modify setting', e)
			}
		},
	},
}
</script>

<style lang="scss">
.group-multiselect {
	width: 100%;
	margin-right: 0;
}
</style>
