/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { ZipReader } from '@zip.js/zip.js'
/**
 * Assert that a file contains a list of expected files
 * @param expectedFiles List of expected filenames
 * @example
 * ```js
 * cy.readFile('file', null, { ... })
 *    .should(zipFileContains(['file.txt']))
 * ```
 */
export function zipFileContains(expectedFiles: string[]) {
	return async (buffer: Buffer) => {
		const blob = new Blob([buffer])
		const zip = new ZipReader(blob.stream())
		// check the real file names
		const entries = (await zip.getEntries()).map((e) => e.filename).sort()
		console.info('Zip contains entries:', entries)
		expect(entries).to.deep.equal(expectedFiles.sort())
	}
}

/**
 * Check validity of an input element
 * @param validity The expected validity message (empty string means it is valid)
 * @example
 * ```js
 * cy.findByRole('textbox')
 *     .should(haveValidity(/must not be empty/i))
 * ```
 */
export const haveValidity = (validity: string | RegExp) => {
	if (typeof validity === 'string') {
		return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.equal(validity)
	}
	return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.match(validity)
}
