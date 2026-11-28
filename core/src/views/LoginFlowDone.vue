<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import LoginFlowContainer from '../components/LoginFlow/LoginFlowContainer.vue'

const completion = loadState<{ securitySettingsUrl: string } | null>('core', 'loginFlowDone', null)
</script>

<template>
	<LoginFlowContainer :heading="t('core', 'Account connected')" :class="{ [$style.completed]: completion }">
		<template v-if="completion">
			<p>{{ t('core', 'Your client should now be connected!') }}</p>
			<NcRichText
				:text="t('core', 'You can revoke this app’s access in [Security settings]({url}).', { url: completion.securitySettingsUrl })"
				useMarkdown
				:referenceLimit="0"
				:autolink="false" />
			<p>{{ t('core', 'You can close this window.') }}</p>
		</template>
		<NcNoteCard v-else type="info">
			{{ t('core', 'Your client should now be connected!') }}
			<br>
			{{ t('core', 'You can close this window.') }}
		</NcNoteCard>
	</LoginFlowContainer>
</template>

<style module>
.completed {
	gap: calc(var(--default-grid-baseline) * 4);

	h2 {
		margin: 0;
	}
}
</style>
