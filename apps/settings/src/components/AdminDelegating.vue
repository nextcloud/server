<template>
	<div id="admin-right-sub-granting" class="section">
		<h2>{{ t('settings', 'Administration privileges') }}</h2>
		<p class="settings-hint">
			{{ t('settings', 'Here you can decide which group can access certain sections of the administration settings.') }}
		</p>

		<div class="setting-list">
			<div v-for="setting in availableSettings" :key="setting.class">
				<h3>{{ setting.sectionName }}</h3>
				<GroupSelect :available-groups="availableGroups" :authorized-groups="authorizedGroups" :setting="setting" />
			</div>
		</div>
	</div>
</template>

<script>
import GroupSelect from './AdminDelegation/GroupSelect'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'AdminDelegating',
	components: {
		GroupSelect,
	},
	data() {
		const availableSettings = loadState('settings', 'available-settings')
		const availableGroups = loadState('settings', 'available-groups')
		const authorizedGroups = loadState('settings', 'authorized-groups')
		return {
			availableSettings,
			availableGroups,
			authorizedGroups,
		}
	},
}
</script>
