<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- eslint-disable vue/no-unused-refs -- plyr/audio refs are consumed by usePlyrPlayer via useTemplateRef -->
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div>
		<VuePlyr
			ref="plyr"
			:options="options">
			<audio
				ref="audio"
				:autoplay="true"
				:src="src"
				preload="metadata"
				@error.capture.prevent.stop.once="onFail"
				@ended="donePlaying"
				@canplay="doneLoading">

				<!-- Omitting `type` on purpose because most of the
					browsers auto detect the appropriate codec.
					Having it set force the browser to comply to
					the provided mime instead of detecting a potential
					compatibility. -->

				{{ t('viewer', 'Your browser does not support audio.') }}
			</audio>
		</VuePlyr>
	</div>
</template>

<script setup lang="ts">
import type { ViewerEmits, ViewerProps } from '../api_package/viewer.ts'

import { translate as t } from '@nextcloud/l10n'
import VuePlyr from '@skjnldsv/vue-plyr'
import { usePlyrPlayer } from '../composables/usePlyrPlayer.ts'
import { useViewerProps } from '../composables/useViewerProps.ts'

defineOptions({
	name: 'ViewerAudios',
})

const props = defineProps<ViewerProps>()
const emit = defineEmits<ViewerEmits>()

const {
	onFail,
	donePlaying,
	doneLoading,
	options,
} = usePlyrPlayer(true, props, emit)

const {
	src,
} = useViewerProps(props)

</script>

<style scoped lang="scss">
audio {
	/* over arrows in tiny screens */
	z-index: 20050;
	align-self: center;
	max-width: 100%;
	max-height: 100%;
	background-color: black;

	justify-self: center;
}

:deep() {
	.plyr__progress__container {
		flex: 1 1;
	}

	.plyr {
		// stylelint-disable-next-line no-invalid-position-at-import-rule -- scoped scss partial
		@import '../mixins/Plyr';
	}

	// The settings menu (e.g. speed selection) is absolutely positioned and
	// opens upward from the controls. On short viewports its full height does
	// not fit and options get clipped off-screen. Cap its height to the
	// available viewport space and let it scroll internally so no option is
	// ever clipped, regardless of where the centered player sits.
	.plyr__menu__container {
		// The player is vertically centered, so the controls bar sits at
		// ~50vh and the menu opens upward into the top half of the viewport.
		// Cap to that available space (half the viewport, minus half the
		// controls bar and a top margin) so options never clip off-screen.
		max-height: calc(40vh - var(--plyr-button-size, 44px) / 2 - 20px);
		overflow-y: auto;
	}

	// make it a bit off-center in order to fix mobile controls
	@media only screen and (max-width: 500px) {
		.plyr--audio {
			top: calc(35vw / 2 + 60px / 2);
		}
	}
}

</style>
