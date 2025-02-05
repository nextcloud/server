<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<AccountPropertySection v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Your handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

import AccountPropertySection from './shared/AccountPropertySection.vue'

const { fediverse } = loadState<AccountProperties>('settings', 'personalInfoParameters', {})

const value = ref({ ...fediverse })
const readable = NAME_READABLE_ENUM[fediverse.name]

/**
 * Validate a fediverse handle
 * @param text The potential fediverse handle
 */
function onValidate(text: string): boolean {
	const result = text.match(/^@?([^@/]+)@([^@/]+)$/)
	if (result === null) {
		return false
	}

	try {
		return URL.parse(`https://${result[2]}/`) !== null
	} catch {
		return false
	}
}
</script>
