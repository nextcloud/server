/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type Plyr from 'plyr'
import type { EmitFn } from 'vue'
import type { ViewerEmits, ViewerProps } from '../api_package/viewer.ts'

import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import { computed, onBeforeUnmount, onUpdated, ref, useTemplateRef } from 'vue'
import { logger } from '../services/logger.ts'
import { preloadMedia } from '../services/mediaPreloader.ts'
import { useViewerProps } from './useViewerProps.ts'

const blankVideo = imagePath('viewer', 'blank.mp4')

/**
 * Composable to setup a Plyr player instance.
 *
 * @param forAudio Whether the player is used for audio files
 * @param props The viewer component props (filename, source, etc.)
 * @param emit The component emit function for viewer events
 */
export function usePlyrPlayer(forAudio: boolean, props: ViewerProps, emit: EmitFn<ViewerEmits>) {
	const { filename, src } = useViewerProps(props)

	const plyr = useTemplateRef<{ player: Plyr, $el: HTMLElement }>('plyr')
	const player = computed<Plyr>(() => plyr.value?.player as Plyr)
	const video = useTemplateRef<HTMLVideoElement>('video')
	const audio = useTemplateRef<HTMLAudioElement>('audio')

	const fallback = ref(false)

	const isFullscreenButtonVisible = ref(false)

	const options = computed(() => {
		return {
			autoplay: true,
			// Used to reset the video streams https://github.com/sampotts/plyr#javascript-1
			blankVideo,
			controls: [
				'play-large',
				'play',
				'progress',
				'current-time',
				'mute',
				'volume',
				...forAudio ? ['settings'] : ['captions', 'settings', 'fullscreen'],
			],
			loadSprite: false,
			fullscreen: {
				iosNative: true,
			},
		}
	})

	/**
	 * Tell Viewer that the video is ready to be shown
	 */
	function doneLoading() {
		emit('loaded')
	}

	/**
	 * Reset video after playing to show poster again
	 */
	function donePlaying() {
		const media = forAudio ? audio : video
		// Should not happen™
		if (!media.value) {
			logger.error('Media element not found in donePlaying')
			return
		}

		// reset and show poster after play
		media.value.autoplay = false
		media.value.load()
	}

	/**
	 * Fallback to the original image if not already done
	 */
	async function onFail() {
		// If we fail on the blank media, don't do anything.
		// This is expected to cancel any network requests when switching files.
		if (src.value === blankVideo) {
			return
		}

		if (fallback.value) {
			logger.error(`Loading of file ${filename.value} failed even after fallback`)
			emit('errored', new Error(t('viewer', 'Failed to load media.')))
			return
		}

		// Try to load E2EE file as a fallback
		logger.error(`Loading of file ${filename.value} failed, falling back to fetching it by hand`)
		fallback.value = true
		try {
			src.value = await preloadMedia(props.file)
		} catch (error) {
			// The fallback fetch failed too: surface the error instead of staying
			// stuck on the loading spinner.
			logger.error(`Fallback fetch of ${filename.value} failed`, { error })
			emit('errored', new Error(t('viewer', 'Failed to load media.')))
		}
	}

	/**
	 * Work around to get the state of the fullscreen button,
	 * aria-selected attribute is not reliable.
	 */
	function hideHeaderAndFooter() {
		isFullscreenButtonVisible.value = !isFullscreenButtonVisible.value
		const main = document.body.querySelector('main')!
		const footer = document.body.querySelector('footer')!
		if (isFullscreenButtonVisible.value) {
			main.classList.add('viewer__hidden-fullscreen')
			footer.classList.add('viewer__hidden-fullscreen')
		} else {
			main.classList.remove('viewer__hidden-fullscreen')
			footer.classList.remove('viewer__hidden-fullscreen')
		}
	}

	// Stable handler references so listeners can be removed again and are never
	// bound more than once, even though onUpdated may run many times.
	const disableSwipe = () => emit('update:canSwipe', false)
	const enableSwipe = () => emit('update:canSwipe', true)

	/**
	 * Get the current plyr control items, or an empty array if not ready.
	 */
	function getPlyrControls(): Element[] {
		if (!plyr.value?.player || !plyr.value.$el) {
			return []
		}
		return Array.from(plyr.value.$el.querySelectorAll('.plyr__controls__item'))
	}

	// For some reason the video controls don't get mounted to
	// the dom until after the component (Videos) is mounted,
	// using the mounted() hook will leave us with an empty array
	onUpdated(() => {
		const plyrControls = getPlyrControls()
		if (plyrControls.length === 0) {
			logger.warn('Plyr player not initialized yet')
			return
		}

		// Prevent swiping to the next/previous item when scrubbing the timeline or changing volume.
		// Remove before adding so repeated onUpdated calls never stack duplicate listeners.
		plyrControls.forEach((control) => {
			if (control.getAttribute('data-plyr') === 'fullscreen') {
				control.removeEventListener('click', hideHeaderAndFooter)
				control.addEventListener('click', hideHeaderAndFooter)
			}
			control.removeEventListener('mouseenter', disableSwipe)
			control.addEventListener('mouseenter', disableSwipe)
			control.removeEventListener('mouseleave', enableSwipe)
			control.addEventListener('mouseleave', enableSwipe)
		})
	})

	onBeforeUnmount(() => {
		// Remove control listeners to avoid leaks
		getPlyrControls().forEach((control) => {
			control.removeEventListener('click', hideHeaderAndFooter)
			control.removeEventListener('mouseenter', disableSwipe)
			control.removeEventListener('mouseleave', enableSwipe)
		})

		// Force stop any ongoing request
		logger.debug('Closing media stream', { filename: props.file.basename })
		video?.value?.pause?.()
		player.value.stop()
		player.value.destroy()
	})

	return {
		doneLoading,
		donePlaying,
		onFail,
		options,
		video,
	}
}
