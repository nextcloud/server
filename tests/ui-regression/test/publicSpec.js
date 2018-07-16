/**
 * @copyright 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

const puppeteer = require('puppeteer');
const helper = require('../helper.js');
const config = require('../config.js');

describe('public', function () {

	before(async () => {
		await helper.init(this)
		await helper.login(this)
	});
	after(async () => await helper.exit());

	/**
	 * Test invalid file share rendering
	 */
	config.resolutions.forEach(function (resolution) {
		it('file-share-invalid.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/s/invalid', async function () {
			}, {waitUntil: 'networkidle2', viewport: resolution});
		});
	});

	/**
	 * Share a file via public link
	 */

	var shareLink = {};
	it('file-share-link', async function () {
		return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
			const element = await page.$('[data-file="welcome.txt"] .action-share');
			await element.click('[data-file="welcome.txt"] .action-share');
			await page.waitForSelector('input.linkCheckbox');
			const linkCheckbox = await page.$('.linkShareView label');
			await Promise.all([
				linkCheckbox.click(),
				page.waitForSelector('.linkText')
			]);
			await helper.delay(500);
			const text = await page.waitForSelector('.linkText');
			const link = await (await text.getProperty('value')).jsonValue();
			shareLink[page.url()] = link;
			return await helper.delay(500);
		}, {
			runOnly: true,
			waitUntil: 'networkidle2',
			viewport: {w: 1920, h: 1080}
		});
	});

	config.resolutions.forEach(function (resolution) {
		it('file-share-valid.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				await page.goto(shareLink[page.url()]);
				await helper.delay(500);
			}, {waitUntil: 'networkidle2', viewport: resolution});
		});
		it('file-share-valid-actions.' + resolution.title, async function () {
			return helper.takeAndCompare(this, undefined, async function (page) {
				const moreButton = await page.waitForSelector('#header-secondary-action');
				await moreButton.click();
				await page.evaluate((data) => {
					return document.querySelector('#directLink').value = 'http://nextcloud.example.com/';
				});
				await helper.delay(500);
			}, {waitUntil: 'networkidle2', viewport: resolution});
		});
	});

	it('file-unshare', async function () {
		return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
			const element = await page.$('[data-file="welcome.txt"] .action-share');
			await element.click('[data-file="welcome.txt"] .action-share');
			await page.waitForSelector('input.linkCheckbox');
			const linkCheckbox = await page.$('.linkShareView label');
			await linkCheckbox.click();
			await helper.delay(500);
		}, { waitUntil: 'networkidle2', viewport: {w: 1920, h:1080}});
	});

});
