<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiPlus } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import EmailSectionEntry from './EmailSectionEntry.vue'
import { DEFAULT_ADDITIONAL_EMAIL_SCOPE } from '../../../../constants/AccountPropertyConstants.ts'
import { removeAdditionalEmail, savePrimaryEmail } from '../../../../service/PersonalInfo/EmailService.js'
import { handleError } from '../../../../utils/handlers.ts'
import { validateEmail } from '../../../../utils/validate.js'

interface Email {
	name?: string
	value: string
	scope: string
	locallyVerified?: string
}

const { emailMap } = loadState<{ emailMap: { additionalEmails: Email[], primaryEmail: Email, notificationEmail: string } }>('settings', 'personalInfoParameters')
const { emailChangeSupported } = loadState<{ emailChangeSupported: boolean }>('settings', 'accountParameters')

const additionalEmails = ref(emailMap.additionalEmails.map((properties) => ({ ...properties, key: generateUniqueKey() })))
const primaryEmail = ref({ ...emailMap.primaryEmail, key: generateUniqueKey() })
const notificationEmail = ref(emailMap.notificationEmail)

const isValidSection = computed(() => validateEmail(primaryEmail.value.value)
	&& additionalEmails.value.every(({ value }) => validateEmail(value)))

/**
 * Add an empty additional address
 */
function onAddAdditionalEmail() {
	if (isValidSection.value) {
		additionalEmails.value.push({ value: '', scope: DEFAULT_ADDITIONAL_EMAIL_SCOPE, key: generateUniqueKey() })
	}
}

/**
 * Promote the first additional address once the primary one was removed
 */
async function onDeletePrimaryEmail() {
	const firstAdditionalEmail = additionalEmails.value[0]?.value
	if (!firstAdditionalEmail) {
		return
	}

	try {
		await removeAdditionalEmail(firstAdditionalEmail)
		additionalEmails.value.splice(0, 1)
	} catch (error) {
		handleError(error, t('settings', 'Unable to delete additional email address'))
		return
	}

	// the server stops notifying the removed address, and a new key resets the state of the primary entry
	if (notificationEmail.value === firstAdditionalEmail) {
		notificationEmail.value = ''
	}
	primaryEmail.value = { ...primaryEmail.value, value: firstAdditionalEmail, key: generateUniqueKey() }
	try {
		await savePrimaryEmail(firstAdditionalEmail)
	} catch (error) {
		handleError(error, t('settings', 'Unable to update primary email address'))
	}
}

/**
 * Key for the list, additional addresses are not unique while being edited
 */
function generateUniqueKey() {
	return Math.random().toString(36).substring(2)
}
</script>

<template>
	<section :class="$style.sectionEmails">
		<template v-if="emailChangeSupported">
			<EmailSectionEntry
				:key="primaryEmail.key"
				v-model:scope="primaryEmail.scope"
				v-model:email="primaryEmail.value"
				:inputId="`account-property-${primaryEmail.name}`"
				primary
				:activeNotificationEmail="notificationEmail"
				@update:notificationEmail="notificationEmail = $event"
				@deletePrimaryEmail="onDeletePrimaryEmail" />

			<!-- TODO use unique key for additional email when uniqueness can be guaranteed, see https://github.com/nextcloud/server/issues/26866 -->
			<EmailSectionEntry
				v-for="(additionalEmail, index) in additionalEmails"
				:key="additionalEmail.key"
				v-model:scope="additionalEmail.scope"
				v-model:email="additionalEmail.value"
				:index="index"
				:localVerificationState="parseInt(additionalEmail.locallyVerified ?? '0', 10)"
				:activeNotificationEmail="notificationEmail"
				@update:notificationEmail="notificationEmail = $event"
				@deleteAdditionalEmail="additionalEmails.splice(index, 1)" />

			<NcFormBox>
				<NcFormBoxButton
					:label="t('settings', 'Additional address')"
					:disabled="!isValidSection"
					@click="onAddAdditionalEmail">
					<template #icon>
						<NcIconSvgWrapper :path="mdiPlus" :size="20" />
					</template>
				</NcFormBoxButton>
			</NcFormBox>
		</template>

		<span v-else>
			{{ primaryEmail.value || t('settings', 'No email address set') }}
		</span>
	</section>
</template>

<style module>
.sectionEmails {
	display: flex;
	flex-direction: column;
	gap: 6px;
	padding: 6px 0;
}
</style>
