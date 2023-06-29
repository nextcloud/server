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
			if (!value) {
				axios({
					url: generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
						appId: 'contactsinteraction',
						configKey: 'disableContactsInteractionAddressBook',
					}),
					data: {
						configValue: 'yes',
					},
					method: 'POST',
				})
			} else {
				axios.delete(generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
					appId: 'contactsinteraction',
					configKey: 'disableContactsInteractionAddressBook',
				}))
			}
		},
	},
}
</script>
