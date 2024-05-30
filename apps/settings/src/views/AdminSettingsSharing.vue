<!--
	- @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->
<template>
	<NcSettingsSection data-cy-settings-sharing-section
		:limit-width="true"
		:doc-url="documentationLink"
		:name="t('settings', 'Sharing')"
		:description="t('settings', 'As admin you can fine-tune the sharing behavior. Please see the documentation for more information.')">
		<NcNoteCard v-if="!sharingAppEnabled" type="warning">
			{{ t('settings', 'You need to enable the File sharing App.') }}
		</NcNoteCard>
		<AdminSettingsSharingForm v-else />
	</NcSettingsSection>
</template>

<script lang="ts">
import {
	NcNoteCard,
	NcSettingsSection,
} from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import AdminSettingsSharingForm from '../components/AdminSettingsSharingForm.vue'

export default defineComponent({
	name: 'AdminSettingsSharing',
	components: {
		AdminSettingsSharingForm,
		NcNoteCard,
		NcSettingsSection,
	},
	data() {
		return {
			documentationLink: loadState<string>('settings', 'sharingDocumentation', ''),
			sharingAppEnabled: loadState<boolean>('settings', 'sharingAppEnabled', false),
		}
	},
	methods: {
		t,
	},
})
</script>
