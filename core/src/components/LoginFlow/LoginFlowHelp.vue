<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiHelpCircleOutline } from '@mdi/js'
import { ref, useId } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'

defineProps<{ label: string }>()

const shown = ref(false)
const described = ref(false)
const id = useId()
</script>

<template>
	<NcPopover
		v-model:shown="shown"
		:triggers="{ show: ['hover', 'focus', 'click'], hide: ['hover', 'focus'] }"
		:popoverTriggers="shown ? ['hover'] : []"
		noFocusTrap
		@afterShow="described = true"
		@afterHide="described = false">
		<template #trigger>
			<NcButton
				:aria-label="label"
				:aria-describedby="shown && described ? id : undefined"
				:aria-controls="id"
				:class="$style.trigger"
				variant="tertiary"
				@keydown.esc.stop="shown = false">
				<template #icon>
					<NcIconSvgWrapper :path="mdiHelpCircleOutline" :size="18" />
				</template>
			</NcButton>
		</template>
		<p :id="id" :class="$style.help" role="tooltip">
			<slot />
		</p>
	</NcPopover>
</template>

<style module>
.trigger {
	min-width: 28px !important;
	min-height: 28px !important;
	height: 28px;
}

.help {
	max-width: min(24rem, calc(100vw - 2rem));
	padding-block: calc(var(--default-grid-baseline) * 3);
	padding-inline: calc(var(--default-grid-baseline) * 8);
	font-weight: normal;
}

@media (pointer: coarse) {
	.trigger {
		min-width: var(--default-clickable-area) !important;
		min-height: var(--default-clickable-area) !important;
	}
}
</style>
