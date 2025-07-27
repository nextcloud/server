<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Bluesky handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.ts'
import AccountPropertySection from './shared/AccountPropertySection.vue'

const { bsky } = loadState<AccountProperties>('settings', 'personalInfoParameters')

const value = ref({ ...bsky })
const readable = NAME_READABLE_ENUM[bsky.name]

/**
 * Validate that the text might be a bluesky handle
 * @param text The potential bluesky handle
 */

function onValidate(text: string): boolean {
	if (text === '') return true;

	const validateRegex = /^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/;
	if (!validateRegex.test(text)) return false;

	const firstLabel = text.split('.')[0];
	if (firstLabel.length <= 2 || firstLabel.length >= 19 || text.length >= 254) return false;

	// Must start and end with alphanumeric, only allow letters, numbers, hyphens
	return /^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i.test(firstLabel);
}
</script>
