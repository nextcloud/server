<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiInformationOutline } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcGuestContent from '@nextcloud/vue/components/NcGuestContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

const updateInfo = loadState<{
	tooBig: boolean
	cliUpgradeLink: string
}>('core', 'updateInfo')
</script>

<template>
	<NcGuestContent>
		<h2>{{ t('core', 'Update needed') }}</h2>
		<p>
			{{ updateInfo.tooBig
				? t('core', 'Please use the command line updater because you have a big instance with more than 50 accounts.')
				: t('core', 'Please use the command line updater because updating via browser is disabled in your config.php.') }}

			<NcButton :class="$style.updaterAdminCli__button" :href="updateInfo.cliUpgradeLink">
				<template #icon>
					<NcIconSvgWrapper :path="mdiInformationOutline" />
				</template>
				{{ t('core', 'Documentation') }}
			</NcButton>
		</p>

		<NcNoteCard type="warning">
			{{ t('core', 'I know that if I continue doing the update via web UI has the risk, that the request runs into a timeout and could cause data loss, but I have a backup and know how to restore my instance in case of a failure.') }}
			<NcButton
				href="?IKnowThatThisIsABigInstanceAndTheUpdateRequestCouldRunIntoATimeoutAndHowToRestoreABackup=IAmSuperSureToDoThis"
				variant="tertiary">
				{{ t('core', 'Upgrade via web on my own risk') }}
			</NcButton>
		</NcNoteCard>
	</NcGuestContent>
</template>

<style module>
.updaterAdminCli__button {
	margin-block: 1rem;
	margin-inline: auto;
}
</style>
