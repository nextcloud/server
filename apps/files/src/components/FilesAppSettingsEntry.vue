<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div />
</template>

<script>
export default {
	name: 'FilesAppSettingsEntry',
	props: {
		el: {
			type: Function,
			required: true,
		},
	},

	mounted() {
		this.$el.appendChild(this.el())
	},
}
</script>
