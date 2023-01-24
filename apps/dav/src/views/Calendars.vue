<template>
	<NcSettingsSection :title="$t('dav', 'Birthday calendar')">
		<NcCheckboxRadioSwitch :checked.sync="showBirthdaysFromDeceasedContacts">
			{{ $t('dav', 'Show birthdays from deceased contacts') }}
		</NcCheckboxRadioSwitch>
		<p class="show_birthdays_from_deceased_contacts-hint">
			{{ $t('dav', "You can also exclude specific contacts from the birthday calendar in the Contacts app.") }}
		</p>
	</NcSettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'

export default {
	name: 'Calendars',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
	},
	data() {
		return {
			showBirthdaysFromDeceasedContacts: loadState('dav', 'show_birthdays_from_deceased_contacts') === 'yes',
		}
	},
	watch: {
		showBirthdaysFromDeceasedContacts(value) {
			OCP.AppConfig.setValue(
				'dav',
				'show_birthdays_from_deceased_contacts',
				value ? 'yes' : 'no'
			)
		},
	},
}
</script>
<style lang="scss">
.show_birthdays_from_deceased_contacts-hint {
	opacity: 0.7;
	padding-left: 1.5rem;
}
</style>
