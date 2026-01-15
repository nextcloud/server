<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AdminThemingInfo } from '../types.d.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import TextField from './admin/TextField.vue'

const ADMIN_INFO = loadState<AdminThemingInfo>('theming', 'adminThemingInfo')
</script>

<template>
	<NcSettingsSection
		:name="t('theming', 'Theming')"
		:description="t('theming', 'Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users.')"
		:doc-url="ADMIN_INFO.docUrl"
		data-admin-theming-settings>
		<div :class="$style.adminSectionTheming">
			<!-- Name, web link, slogan... fields -->
			<TextField
				name="name"
				:label="t('theming', 'Name')"
				default-value="Nextcloud"
				maxlength="250" />
			<TextField
				name="url"
				:label="t('theming', 'Web link')"
				default-value="https://nextcloud.com"
				placeholder="https://…"
				type="url"
				maxlength="500" />
			<TextField
				name="slogan"
				:label="t('theming', 'Slogan')"
				:default-value="t('settings', 'a safe home for all your data')"
				maxlength="500" />
			<hr>

			<TextField
				name="legalNoticeUrl"
				:label="t('theming', 'Legal notice link')"
				default-value=""
				type="url"
				placeholder="https://…"
				maxlength="500" />
			<TextField
				name="privacyPolicyUrl"
				:label="t('theming', 'Privacy policy link')"
				default-value=""
				type="url"
				placeholder="https://…"
				maxlength="500" />
		</div>
	</NcSettingsSection>
</template>

<style module>
.adminSectionTheming {
	max-width: 650px;
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
}
</style>
