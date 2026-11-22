<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type Setting from '../../models/Setting.ts'

import { onBeforeMount, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
	setting: Setting
}>()

const el = ref<HTMLElement>()

onBeforeMount(() => props.setting.open())
onBeforeUnmount(() => props.setting.close())
onMounted(() => {
	el.value!.appendChild(props.setting.el())
})
</script>

<template>
	<div ref="el" />
</template>
