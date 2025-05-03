<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<Fragment>
		<ExternalStoragesSection />
		<GlobalCredentialsSection />
	</Fragment>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { Fragment } from 'vue-frag'

import ExternalStoragesSection from './ExternalStoragesSection.vue'
import GlobalCredentialsSection from './GlobalCredentialsSection.vue'

export default defineComponent({
	name: 'FilesExternalSettings',

	components: {
		Fragment,

		ExternalStoragesSection,
		GlobalCredentialsSection,
	},
})
</script>
