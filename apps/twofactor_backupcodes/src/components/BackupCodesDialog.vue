<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiDownload, mdiPrinter } from '@mdi/js'
import { getCapabilities } from '@nextcloud/capabilities'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { print } from '../service/PrintService.ts'

const props = defineProps<{
	/** The freshly generated codes, only readable now */
	codes: string[]
}>()

const emit = defineEmits<{
	close: []
}>()

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const instanceName = (getCapabilities() as any).theming.name ?? 'Nextcloud'
const downloadFilename = instanceName + '-backup-codes.txt'
const downloadUrl = computed(() => 'data:text/plain,' + encodeURIComponent(props.codes.join('\n') + '\n'))
</script>

<template>
	<NcDialog
		:name="t('twofactor_backupcodes', 'New backup codes')"
		size="small"
		open
		@update:open="emit('close')">
		<ul :class="$style.backupCodesDialog__codes" :aria-label="t('twofactor_backupcodes', 'List of backup codes')">
			<li v-for="code in codes" :key="code">
				{{ code }}
			</li>
		</ul>
		<p>
			{{ t('twofactor_backupcodes', 'These are your backup codes. Please save and/or print them as you will not be able to read the codes again later.') }}
		</p>

		<template #actions>
			<NcButton wide @click="print(codes)">
				<template #icon>
					<NcIconSvgWrapper :path="mdiPrinter" />
				</template>
				{{ t('twofactor_backupcodes', 'Print') }}
			</NcButton>
			<NcButton
				:href="downloadUrl"
				:download="downloadFilename"
				variant="primary"
				wide>
				<template #icon>
					<NcIconSvgWrapper :path="mdiDownload" />
				</template>
				{{ t('twofactor_backupcodes', 'Download') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style module>
.backupCodesDialog__codes {
	margin-block: calc(4 * var(--default-grid-baseline));
	font-family: monospace;
	font-size: 1.2em;
	letter-spacing: 0.02em;
	text-align: center;
}
</style>
