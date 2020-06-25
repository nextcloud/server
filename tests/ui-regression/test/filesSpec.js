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

describe('files', function () {

	before(async () => {
		await helper.init(this)
		await helper.login(this)
	});
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {

		it('file-sidebar-share.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				let element = await page.$('[data-file="welcome.txt"] .action-share');
				await element.click('[data-file="welcome.txt"] .action-share');
				await page.waitForSelector('.shareWithField');
				await helper.delay(500);
				await page.$eval('body', e => { $('.shareWithField').blur() });
			}, {viewport: resolution});
		});
		it('file-popover.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-menu');
				await page.waitForSelector('.fileActionsMenu');
			}, {viewport: resolution});
		});
		it('file-sidebar-details.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .fileActionsMenu [data-action=\'Details\']');
				await page.waitForSelector('[data-tabid=\'commentsTabView\']');
				await page.$eval('body', e => { $('.shareWithField').blur() });
				await helper.delay(500); // wait for animation
			}, {viewport: resolution});
		});
		it('file-sidebar-details-sharing.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				let tab = await helper.childOfClassByText(page, 'tabHeaders', 'Sharing');
				tab[0].click();
				await page.waitForSelector('input.shareWithField');
				await page.$eval('body', e => { $('.shareWithField').blur() });
				await helper.delay(500); // wait for animation
			}, {viewport: resolution});
		});
		it('file-sidebar-details-versions.' + resolution.title, async function() {
			return helper.takeAndCompare(this, undefined, async function (page) {
				let tab = await helper.childOfClassByText(page, 'tabHeaders', 'Versions');
				tab[0].click();
				await helper.delay(100); // wait for animation
			}, {viewport: resolution});
		});
		it('file-popover.favorite.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				await page.click('[data-file=\'welcome.txt\'] .action-menu');
				await page.waitForSelector('.fileActionsMenu')
				await page.click('[data-file=\'welcome.txt\'] .fileActionsMenu [data-action=\'Favorite\']');;
			}, {viewport: resolution});
		});

		it('file-favorites.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/apps/files', async function (page) {
				try {
					await page.waitForSelector('#app-navigation-toggle', {
						visible: true,
						timeout: 1000,
					}).then((element) => element.click())
				} catch (err) {}
				await page.click('#app-navigation [data-id=\'favorites\'] a');
				await helper.delay(500); // wait for animation
			}, {viewport: resolution});
		});


	});



});
