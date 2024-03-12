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
	<article :id="domId"
		class="app-discover-post"
		:class="{ 'app-discover-post--reverse': media && media.alignment === 'start' }">
		<component :is="link ? 'a' : 'div'"
			v-if="headline || text"
			:href="link"
			:target="link ? '_blank' : undefined"
			class="app-discover-post__text">
			<component :is="inline ? 'h4' : 'h3'">{{ translatedHeadline }}</component>
			<p>{{ translatedText }}</p>
		</component>
		<component :is="mediaLink ? 'a' : 'div'"
			v-if="mediaSources"
			:href="mediaLink"
			:target="mediaLink ? '_blank' : undefined"
			class="app-discover-post__media"
			:class="{
				'app-discover-post__media--fullwidth': isFullWidth,
				'app-discover-post__media--start': media?.alignment === 'start',
				'app-discover-post__media--end': media?.alignment === 'end',
			}">
			<component :is="isImage ? 'picture' : 'video'"
				ref="mediaElement"
				class="app-discover-post__media-element"
				:muted="!isImage"
				:playsinline="!isImage"
				:preload="!isImage && 'auto'"
				@ended="hasPlaybackEnded = true">
				<source v-for="source of mediaSources"
					:key="source.src"
					:src="isImage ? undefined : source.src"
					:srcset="isImage ? source.src : undefined"
					:type="source.mime">
				<img v-if="isImage"
					:src="mediaSources[0].src"
					:alt="mediaAlt">
			</component>
			<div class="app-discover-post__play-icon-wrapper">
				<NcIconSvgWrapper v-if="!isImage && showPlayVideo"
					class="app-discover-post__play-icon"
					:path="mdiPlayCircleOutline"
					:size="92" />
			</div>
		</component>
	</article>
</template>

<script lang="ts">
import type { IAppDiscoverPost } from '../../constants/AppDiscoverTypes.ts'
import type { PropType } from 'vue'

import { computed, defineComponent, ref, watchEffect } from 'vue'
import { commonAppDiscoverProps } from './common'
import { useLocalizedValue } from '../../composables/useGetLocalizedValue'
import { useElementVisibility } from '@vueuse/core'
import { mdiPlayCircleOutline } from '@mdi/js'

import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

export default defineComponent({
	components: {
		NcIconSvgWrapper,
	},

	props: {
		...commonAppDiscoverProps,

		text: {
			type: Object as PropType<IAppDiscoverPost['text']>,
			required: false,
			default: () => null,
		},

		media: {
			type: Object as PropType<IAppDiscoverPost['media']>,
			required: false,
			default: () => null,
		},

		inline: {
			type: Boolean,
			required: false,
			default: false,
		},

		domId: {
			type: String,
			required: false,
			default: null,
		},
	},

	setup(props) {
		const translatedHeadline = useLocalizedValue(computed(() => props.headline))
		const translatedText = useLocalizedValue(computed(() => props.text))

		const localizedMedia = useLocalizedValue(computed(() => props.media?.content))
		const mediaSources = computed(() => localizedMedia.value !== null ? [localizedMedia.value.src].flat() : undefined)
		const mediaAlt = computed(() => localizedMedia.value?.alt ?? '')

		const isImage = computed(() => mediaSources?.value?.[0].mime.startsWith('image/') === true)
		/**
		 * Is the media is shown full width
		 */
		const isFullWidth = computed(() => !translatedHeadline.value && !translatedText.value)

		/**
		 * Link on the media
		 * Fallback to post link to prevent link inside link (which is invalid HTML)
		 */
		const mediaLink = computed(() => localizedMedia.value?.link ?? props.link)

		const hasPlaybackEnded = ref(false)
		const showPlayVideo = computed(() => localizedMedia.value?.link && hasPlaybackEnded.value)

		const mediaElement = ref<HTMLVideoElement|HTMLPictureElement>()
		const mediaIsVisible = useElementVisibility(mediaElement, { threshold: 0.3 })
		watchEffect(() => {
			// Only if media is video
			if (!isImage.value && mediaElement.value) {
				const video = mediaElement.value as HTMLVideoElement

				if (mediaIsVisible.value) {
					// Ensure video is muted - otherwise .play() will be blocked by browsers
					video.muted = true
					// If visible start playback
					video.play()
				} else {
					// If not visible pause the playback
					video.pause()
					// If the animation has ended reset
					if (video.ended) {
						video.currentTime = 0
						hasPlaybackEnded.value = false
					}
				}
			}
		})

		return {
			mdiPlayCircleOutline,

			translatedText,
			translatedHeadline,
			mediaElement,
			mediaSources,
			mediaAlt,
			mediaLink,

			hasPlaybackEnded,
			showPlayVideo,

			isFullWidth,
			isImage,
		}
	},
})
</script>

<style scoped lang="scss">
.app-discover-post {
	width: 100%;
	background-color: var(--color-primary-element-light);
	border-radius: var(--border-radius-rounded);

	display: flex;
	flex-direction: row;
	&--reverse {
		flex-direction: row-reverse;
	}

	h3, h4 {
		font-size: 24px;
		font-weight: 600;
		margin-block: 0 1em;
	}

	&__text {
		display: block;
		padding: var(--border-radius-rounded);
		width: 100%;
	}

	&__media {
		display: block;
		overflow: hidden;

		max-height: 300px;
		max-width: 450px;
		border-radius: var(--border-radius-rounded);

		&--fullwidth {
			max-width: unset;
			max-height: unset;
		}

		&--end {
			border-end-start-radius: 0;
			border-start-start-radius: 0;
		}

		&--start {
			border-end-end-radius: 0;
			border-start-end-radius: 0;
		}

		img, &-element {
			height: 100%;
			width: 100%;
			object-fit: cover;
			object-position: center;
		}
	}

	&__play-icon {
		&-wrapper {
			position: relative;
			top: -50%;
			left: -50%;
		}

		position: absolute;
		top: -46px; // half of the icon height
		right: -46px; // half of the icon width
	}
}

// Ensure section works on mobile devices
@media only screen and (max-width: 699px) {
	.app-discover-post {
		flex-direction: column;

		&--reverse {
			flex-direction: column-reverse;
		}

		&__media {
			min-width: 100%;

			&--end {
				border-radius: var(--border-radius-rounded);
				border-start-end-radius: 0;
				border-start-start-radius: 0;
			}

			&--start {
				border-radius: var(--border-radius-rounded);
				border-end-end-radius: 0;
				border-end-start-radius: 0;
			}
		}
	}
}
</style>
