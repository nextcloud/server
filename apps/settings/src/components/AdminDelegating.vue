<template>
	<NcSettingsSection :title="t('settings', 'Administration privileges')"
		:description="t('settings', 'Here you can decide which group can access certain sections of the administration settings.')"
		:doc-url="authorizedSettingsDocLink">
		<div class="setting-list">
			<div v-for="setting in availableSettings" :key="setting.class">
				<h3>{{ setting.sectionName }}</h3>
				<GroupSelect :available-groups="availableGroups" :authorized-groups="authorizedGroups" :setting="setting" />
			</div>
		</div>
	</NcSettingsSection>
</template>

<script>
import GroupSelect from './AdminDelegation/GroupSelect'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'
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
