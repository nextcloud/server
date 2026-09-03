<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="avatar-stack">
		<span
			v-for="(recipient, index) in displayed"
			:key="recipient.class + recipient.value"
			class="avatar-stack__item"
			:style="{ zIndex: displayed.length - index }">
			<NcAvatar
				:size="32"
				:isNoUser="isNoUserRecipient(recipient)"
				:user="isNoUserRecipient(recipient) ? undefined : recipient.value"
				:displayName="recipient.display_name"
				disableMenu
				disableTooltip />
		</span>
		<span v-if="overflow > 0" class="avatar-stack__overflow" :aria-hidden="true">
			+{{ overflow }}
		</span>
	</div>
</template>

<script setup lang="ts">
import type { SharingRecipient } from '../types/unifiedSharing.ts'

import { computed } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import { isNoUserRecipient } from '../lib/unifiedSharing.ts'

const props = defineProps<{
	recipients: SharingRecipient[]
}>()

const MAX_AVATARS = 3

const displayed = computed(() => props.recipients.slice(0, MAX_AVATARS))
const overflow = computed(() => Math.max(0, props.recipients.length - MAX_AVATARS))
</script>

<style lang="scss" scoped>
.avatar-stack {
	display: flex;
	align-items: center;

	// Wrapper we own, so the ring cannot be overridden by the avatar's own styles.
	&__item {
		display: flex;
		border-radius: 50%;
		// Ring in the main background colour separates overlapping avatars. Using
		// a box-shadow (not a border) keeps the avatar exactly 32px, matching the
		// avatars in the other entries.
		box-shadow: 0 0 0 2px var(--color-main-background);
		// Each avatar sits under the previous one (first on top); z-index is set
		// inline, descending, so the ring overlaps correctly.
		position: relative;

		&:not(:first-child) {
			margin-inline-start: -12px;
		}
	}

	&__overflow {
		margin-inline-start: 4px;
		color: var(--color-text-maxcontrast);
		font-size: 12px;
	}
}
</style>
