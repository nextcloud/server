/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { setLivePhotoMetadata, uploadContent } from './dav.ts'

export interface LivePhoto {
	/** The base name shared by the .jpg and .mov (without extension). */
	fileName: string
	jpgFileId: number
	movFileId: number
}

/**
 * Seed a live photo for the given user: upload a paired .jpg + .mov at the user
 * root and cross-link them via the `nc:metadata-files-live-photo` metadata so the
 * Files app treats them as a single unit. Returns the base name and both file ids.
 *
 * The acting user is isolated per test, so a fixed base name is unambiguous; pass
 * one only when a test needs a specific value.
 *
 * @param request - The Playwright API request context (authenticated as the user)
 * @param user - The user to seed the live photo for
 * @param fileName - The base name to use (defaults to `live-photo`)
 */
export async function setupLivePhotos(request: APIRequestContext, user: User, fileName = 'live-photo'): Promise<LivePhoto> {
	const jpgFileId = Number(await uploadContent(request, user, 'jpg file', 'image/jpg', `/${fileName}.jpg`))
	const movFileId = Number(await uploadContent(request, user, 'mov file', 'video/mov', `/${fileName}.mov`))

	await setLivePhotoMetadata(request, user, `/${fileName}.jpg`, movFileId)
	await setLivePhotoMetadata(request, user, `/${fileName}.mov`, jpgFileId)

	return { fileName, jpgFileId, movFileId }
}
