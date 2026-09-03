/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ViewerProps } from '../api_package/viewer.ts'

import { computed, ref, watch } from 'vue'

/**
 * Composable to extract common viewer props.
 *
 * @param props The viewer props
 */
export function useViewerProps(props: ViewerProps) {
	const filename = computed(() => props.file.basename)

	// Src is not a computed as we want to be able to change it on error.
	// Use the encoded source so special characters in the name don't break the
	// media element's `src` URL.
	const src = ref(props.file.encodedSource)

	// Update the src when the file changes
	watch(filename, () => {
		src.value = props.file.encodedSource
	})

	return {
		filename,
		src,
	}
}
