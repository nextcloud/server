<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<article :id="domId"
		ref="container"
		class="app-discover-post"
		:class="{
			'app-discover-post--reverse': media && media.alignment === 'start',
			'app-discover-post--small': isSmallWidth
		}">
		<component :is="link ? 'AppLink' : 'div'"
			v-if="headline || text"
			:href="link"
			class="app-discover-post__text">
			<component :is="inline ? 'h4' : 'h3'">
				{{ translatedHeadline }}
			</component>
			<p>{{ translatedText }}</p>
		</component>
		<component :is="mediaLink ? 'AppLink' : 'div'"
			v-if="mediaSources"
			:href="mediaLink"
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
					:src="isImage ? undefined : generatePrivacyUrl(source.src)"
					:srcset="isImage ? generatePrivacyUrl(source.src) : undefined"
					:type="source.mime">
				<img v-if="isImage"
					:src="generatePrivacyUrl(mediaSources[0].src)"
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

import { mdiPlayCircleOutline } from '@mdi/js'
import { generateUrl } from '@nextcloud/router'
import { useElementSize, useElementVisibility } from '@vueuse/core'
import { computed, defineComponent, ref, watchEffect } from 'vue'
import { commonAppDiscoverProps } from './common'
import { useLocalizedValue } from '../../composables/useGetLocalizedValue'

import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import AppLink from './AppLink.vue'

export default defineComponent({
	components: {
		AppLink,
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

		/**
		 * The content is sized / styles are applied based on the container width
		 * To make it responsive even for inline usage and when opening / closing the sidebar / navigation
		 */
		const container = ref<HTMLElement>()
		const { width: containerWidth } = useElementSize(container)
		const isSmallWidth = computed(() => containerWidth.value < 600)

		/**
		 * Generate URL for cached media to prevent user can be tracked
		 * @param url The URL to resolve
		 */
		const generatePrivacyUrl = (url: string) => url.startsWith('/') ? url : generateUrl('/settings/api/apps/media?fileName={fileName}', { fileName: url })

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

			container,

			translatedText,
			translatedHeadline,
			mediaElement,
			mediaSources,
			mediaAlt,
			mediaLink,

			hasPlaybackEnded,
			showPlayVideo,

			isFullWidth,
			isSmallWidth,
			isImage,

			generatePrivacyUrl,
		}
	},
})
</script>

<style scoped lang="scss">
.app-discover-post {
	max-height: 300px;
	width: 100%;
	background-color: var(--color-primary-element-light);
	border-radius: var(--border-radius-rounded);

	display: flex;
	flex-direction: row;
	justify-content: start;

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
		width: 100%;
		padding: var(--border-radius-rounded);
		overflow-y: scroll;
	}

	// If there is media next to the text we do not want a padding on the bottom as this looks weird when scrolling
	&:has(&__media) &__text {
		padding-block-end: 0;
	}

	&__media {
		display: block;
		overflow: hidden;

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
		position: absolute;
		top: -46px; // half of the icon height
		inset-inline-end: -46px; // half of the icon width

		&-wrapper {
			position: relative;
			top: -50%;
			inset-inline-start: -50%;
		}
	}
}

.app-discover-post--small {
	&.app-discover-post {
		flex-direction: column;
		max-height: 500px;

		&--reverse {
			flex-direction: column-reverse;
		}
	}

	.app-discover-post {
		&__text {
			flex: 1 1 50%;
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
