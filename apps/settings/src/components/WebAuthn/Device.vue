<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li class="webauthn-device">
		<span class="icon-webauthn-device" />
		{{ name || t('settings', 'Unnamed device') }}
		<NcActions :force-menu="true">
			<NcActionButton icon="icon-delete" @click="$emit('delete')">
				{{ t('settings', 'Delete') }}
			</NcActionButton>
		</NcActions>
	</li>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'

export default {
	name: 'Device',
	components: {
		NcActionButton,
		NcActions,
	},
	props: {
		name: {
			type: String,
			required: true,
		},
	},
}
</script>

<style scoped>
	.webauthn-device {
		line-height: 300%;
		display: flex;
	}

	.icon-webauthn-device {
		display: inline-block;
		background-size: 100%;
		padding: 3px;
		margin: 3px;
	}
</style>
