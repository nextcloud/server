<template>
	<NcSettingsSection :title="t('contactsinteraction', 'Contacts interaction')"
		:description="t('contactsinteraction', 'Expose contacts you have been interacting with in the Contacts app')">
		<NcCheckboxRadioSwitch id="generateContactsInteraction"
			:checked.sync="generateContactsInteraction"
			type="switch">
			{{ t('contactsinteraction', 'Generate an addressbook for contacts you recently interacted with') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>
</template>

<script>
import { NcSettingsSection, NcCheckboxRadioSwitch } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'PersonalSettings',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},
	data() {
		return {
			generateContactsInteraction: loadState('contactsinteraction', 'generateContactsInteraction'),
		}
	},
	watch: {
		generateContactsInteraction(value) {
			if (value) {
				OCP.AppConfig.setValue('contactsinteraction', 'generateContactsInteraction', 'yes')
			} else {
				axios.post(generateOcsUrl('/apps/contactsinteraction/config/user/disable'))
			}
		},
	},
}
</script>
