/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/** Media kinds handled by the viewer and their custom element tag / inner media tag. */
export type MediaKind = 'image' | 'video' | 'audio'

const HANDLER_TAG: Record<MediaKind, string> = {
	image: 'oca-viewer-image',
	video: 'oca-viewer-video',
	audio: 'oca-viewer-audio',
}

const MEDIA_ELEMENT: Record<MediaKind, string> = {
	image: 'img',
	video: 'video',
	audio: 'audio',
}

/**
 * Page object for the viewer modal.
 *
 * The viewer mounts a `<div id="viewer">` on the body, inside which an NcModal
 * renders with the `viewer__modal` class. The active handler is rendered as a
 * custom element (`oca-viewer-image`, `oca-viewer-video`, `oca-viewer-audio`).
 */
export class ViewerPage {
	public readonly root: Locator
	public readonly modal: Locator
	public readonly container: Locator
	public readonly content: Locator
	public readonly headerName: Locator
	public readonly loading: Locator
	public readonly errorMessage: Locator
	public readonly closeButton: Locator
	public readonly nextButton: Locator
	public readonly previousButton: Locator

	constructor(public readonly page: Page) {
		// NcModal teleports its content to the document body, so the modal is not
		// under the #viewer mount point — match it directly by its class + role.
		this.root = page.locator('.viewer__modal')
		this.modal = this.root
		this.container = this.modal.locator('.modal-container')
		this.content = this.modal.locator('.modal-container__content')
		this.headerName = this.modal.locator('.modal-header__name')
		this.loading = this.modal.locator('.viewer__loading')
		this.errorMessage = this.modal.locator('.empty-content__name')
		this.closeButton = this.modal.getByRole('button', { name: 'Close' })
		this.nextButton = this.modal.getByRole('button', { name: 'Next' })
		this.previousButton = this.modal.getByRole('button', { name: 'Previous' })
	}

	/**
	 * Click the modal content background (outside the media), which closes the
	 * viewer. Clicks near a corner so the centered media is never hit.
	 */
	public async clickOutside(): Promise<void> {
		await this.content.click({ position: { x: 8, y: 8 } })
	}

	/**
	 * Wait for the viewer modal to be gone.
	 */
	public async waitForClosed(): Promise<void> {
		await expect(this.container).toBeHidden()
	}

	/**
	 * Assert the viewer shows an error message and is not stuck loading.
	 */
	public async expectError(): Promise<void> {
		await expect(this.errorMessage).toBeVisible()
		await expect(this.loading).toHaveCount(0)
	}

	/**
	 * The custom element for the given media kind.
	 *
	 * @param kind - The media kind
	 */
	public handlerTag(kind: MediaKind): Locator {
		return this.container.locator(HANDLER_TAG[kind])
	}

	/**
	 * The inner media element (img/video/audio) for the given media kind.
	 *
	 * @param kind - The media kind
	 */
	public mediaElement(kind: MediaKind): Locator {
		return this.handlerTag(kind).locator(MEDIA_ELEMENT[kind])
	}

	/**
	 * Assert the viewer modal is visible.
	 */
	public async isVisible(): Promise<void> {
		await expect(this.container).toBeVisible()
	}

	/**
	 * Wait for the viewer to be open and done loading (spinner gone).
	 */
	public async waitForOpen(): Promise<void> {
		await expect(this.container).toBeVisible()
		await expect(this.loading).toHaveCount(0)
	}

	/**
	 * The basename of the file currently shown in the header.
	 */
	public async currentName(): Promise<string> {
		return (await this.headerName.textContent())?.trim() ?? ''
	}

	/**
	 * Assert the active handler and its media element are visible for the given kind.
	 *
	 * @param kind - The media kind
	 */
	public async expectHandler(kind: MediaKind): Promise<void> {
		await expect(this.handlerTag(kind)).toBeVisible()
		await expect(this.mediaElement(kind)).toHaveAttribute('src', /.+/)
	}

	/**
	 * Navigate to the next file in the slideshow.
	 */
	public async next(): Promise<void> {
		await this.nextButton.click()
	}

	/**
	 * Navigate to the previous file in the slideshow.
	 */
	public async previous(): Promise<void> {
		await this.previousButton.click()
	}

	/**
	 * Close the viewer modal.
	 */
	public async close(): Promise<void> {
		await this.closeButton.click()
		await expect(this.container).toBeHidden()
		// Closing unwinds the history entries the viewer pushed, and the browser
		// applies that asynchronously. Wait for it so a following assertion, or a
		// following open, does not race the navigation still on its way.
		await this.page.waitForURL((url) => !url.searchParams.has('openfile'))
	}

	/**
	 * The header actions menu toggle, if the actions are collapsed into a menu.
	 */
	public actionsToggle(): Locator {
		return this.modal.getByRole('button', { name: /Actions|Open actions menu/i })
	}

	/**
	 * Run a header action by its label, opening the actions menu if needed.
	 *
	 * @param name - The action label (or a matching regexp)
	 */
	public async runAction(name: string | RegExp): Promise<void> {
		const direct = this.modal.getByRole('button', { name })
		if (!(await direct.isVisible())) {
			const toggle = this.actionsToggle()
			if (await toggle.isVisible()) {
				await toggle.click()
			}
		}
		await this.page.getByRole('menuitem', { name })
			.or(direct)
			.first()
			.click()
	}

	/**
	 * Open the Files sidebar from the viewer header actions.
	 * The action emits `viewer:sidebar:open`, which the Files app listens to.
	 */
	public async openSidebar(): Promise<void> {
		// The header actions collapse into a menu; the "Open sidebar" entry is a
		// menuitem there. Open the menu first if the entry is not already shown.
		const directButton = this.modal.getByRole('button', { name: 'Open sidebar' })
		if (!(await directButton.isVisible())) {
			const toggle = this.actionsToggle()
			if (await toggle.isVisible()) {
				await toggle.click()
			}
		}
		await this.page.getByRole('menuitem', { name: 'Open sidebar' })
			.or(directButton)
			.first()
			.click()
	}
}
