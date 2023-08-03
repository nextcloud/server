<template>
	<NcSelect v-model="selected"
		:input-id="setting.id"
		class="group-select"
		:placeholder="t('settings', 'None')"
		label="displayName"
		:options="availableGroups"
		:multiple="true"
		:close-on-select="false" />
</template>

<script>
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import logger from '../../logger.js'

export default {
	name: 'GroupSelect',
	components: {
		NcSelect,
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
.group-select {
	width: 100%;
}
</style>
