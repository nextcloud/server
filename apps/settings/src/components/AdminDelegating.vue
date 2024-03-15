<template>
	<NcSettingsSection :name="t('settings', 'Administration privileges')"
		:description="t('settings', 'Here you can decide which group can access certain sections of the administration settings.')"
		:doc-url="authorizedSettingsDocLink">
		<div class="setting-list">
			<div v-for="setting in availableSettings" :key="setting.class">
				<label :for="setting.id">{{ setting.sectionName }}</label>
				<GroupSelect :available-groups="availableGroups" :authorized-groups="authorizedGroups" :setting="setting" />
			</div>
		</div>
	</NcSettingsSection>
</template>

<script>
import GroupSelect from './AdminDelegation/GroupSelect.vue'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'AdminDelegating',
	components: {
		GroupSelect,
		NcSettingsSection,
	},
	data() {
		return {
			availableSettings: loadState('settings', 'available-settings'),
			availableGroups: loadState('settings', 'available-groups'),
			authorizedGroups: loadState('settings', 'authorized-groups'),
			authorizedSettingsDocLink: loadState('settings', 'authorized-settings-doc-link'),
		}
	},
}
</script>

<style lang="scss" scoped>
label {
	display: block;
	font-size: 16px;
	margin: 12px 0;
	color: var(--color-text-light);
}
</style>
