<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection
		v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Bluesky handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.ts'

const { bluesky } = loadState<AccountProperties>('settings', 'personalInfoParameters')

const value = ref({ ...bluesky })
const readable = NAME_READABLE_ENUM[bluesky.name]

/**
 * Validate that the text might be a bluesky handle
 *
 * @param text The potential bluesky handle
 */
function onValidate(text: string): boolean {
	if (text === '') {
		return true
	}

	const lowerText = text.toLowerCase()

	if (lowerText === 'bsky.social') {
		// Standalone bsky.social is invalid
		return false
	}

	if (lowerText.endsWith('.bsky.social')) {
		// Enforce format: exactly one label + '.bsky.social'
		const parts = lowerText.split('.')

		// Must be in form: [username, 'bsky', 'social']
		if (parts.length !== 3 || parts[1] !== 'bsky' || parts[2] !== 'social') {
			return false
		}

		const username = parts[0]
		const validateRegex = /^[a-z0-9][a-z0-9-]{2,17}$/
		return validateRegex.test(username)
	}

	// Else, treat as a custom domain
	try {
		const url = new URL(`https://${text}`)
		// Ensure the parsed host matches exactly (case-insensitive already)
		return url.host === lowerText
	} catch {
		return false
	}
}
</script>
