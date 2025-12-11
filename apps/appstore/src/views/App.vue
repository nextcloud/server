<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="settings">
		<router-view name="navigation" />
		<router-view />
		<router-view name="sidebar" />
	</NcContent>
</template>

<script setup lang="ts">
import NcContent from '@nextcloud/vue/components/NcContent'
</script>
