<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts" setup>
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import NcAppSettingsSection from '@nextcloud/vue/components/NcAppSettingsSection'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormBoxCopyButton from '@nextcloud/vue/components/NcFormBoxCopyButton'

const webDavUrl = generateRemoteUrl('dav/files/' + encodeURIComponent(getCurrentUser()!.uid))
const webDavDocsUrl = 'https://docs.nextcloud.com/server/stable/go.php?to=user-webdav'
const appPasswordUrl = generateUrl('/settings/user/security#generate-app-token-section')
const isTwoFactorEnabled = loadState('files', 'isTwoFactorEnabled', false)
</script>

<template>
	<NcAppSettingsSection id="webdav" name="WebDAV">
		<NcFormBox>
			<NcFormBoxCopyButton :label="t('files', 'WebDAV URL')" :value="webDavUrl" />
			<NcFormBoxButton
				v-if="isTwoFactorEnabled"
				:label="t('files', 'Create an app password')"
				:description="t('files', 'Required for WebDAV authentication because Two-Factor Authentication is enabled for this account.')"
				:href="appPasswordUrl"
				target="_blank" />
			<NcFormBoxButton
				:label="t('files', 'How to access files using WebDAV')"
				:href="webDavDocsUrl"
				target="_blank" />
		</NcFormBox>
	</NcAppSettingsSection>
</template>
