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

const helper = require('../helper.js');
const config = require('../config.js');

describe('install', function () {

	before(async () => await helper.init(this));
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('show-page.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, 'index.php', async (page) => {
				await helper.delay(100);
				await page.$eval('body', function (e) {
					$('#adminlogin').blur();
				});
				await helper.delay(100);
			}, { waitUntil: 'networkidle0', viewport: resolution});
		});

		it('show-advanced.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, undefined, async (page) => {
				await page.click('#showAdvanced');
				await helper.delay(300);
			}, { waitUntil: 'networkidle0', viewport: resolution});
		});
		it('show-advanced-mysql.' + resolution.title, async function () {
			// (test, route, prepare, action, options
			return helper.takeAndCompare(this, undefined, async (page) => {
				await page.click('label.mysql');
				await helper.delay(300);
			}, { waitUntil: 'networkidle0', viewport: resolution});
		});
	});

	it('runs', async function () {
		this.timeout(5*60*1000);
		helper.pageBase.setDefaultNavigationTimeout(5*60*1000);
		helper.pageCompare.setDefaultNavigationTimeout(5*60*1000);
		// just run for one resolution since we can only install once
		return helper.takeAndCompare(this, 'index.php',  async function (page) {
			const login = await page.type('#adminlogin', 'admin');
			const password = await page.type('#adminpass', 'admin');
			const inputElement = await page.$('input[type=submit]');
			await inputElement.click();
			await page.waitForNavigation({waitUntil: 'networkidle2'});
			await page.waitForSelector('#header');
			helper.pageBase.setDefaultNavigationTimeout(60000);
			helper.pageCompare.setDefaultNavigationTimeout(60000);
		}, { waitUntil: 'networkidle0', viewport: {w: 1920, h: 1080}});
	});

});
