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
const pixelmatch = require('pixelmatch');
const expect = require('chai').expect;
const PNG = require('pngjs2').PNG;
const fs = require('fs');
const config = require('./config.js');


module.exports = {
	browser: null,
	pageBase: null,
	pageCompare: null,
	lastBase: 0,
	lastCompare: 0,
	init: async function (test) {
		this._outputDirectory = `${config.outputDirectory}/${test.title}`;
		if (!fs.existsSync(config.outputDirectory)) fs.mkdirSync(config.outputDirectory);
		if (!fs.existsSync(this._outputDirectory)) fs.mkdirSync(this._outputDirectory);
		await this.resetBrowser();
	},
	exit: async function () {
		await this.browser.close();
	},
	resetBrowser: async function () {
		if (this.browser) {
			await this.browser.close();
		}
		this.browser = await puppeteer.launch({
			args: ['--no-sandbox', '--disable-setuid-sandbox'],
			headless: config.headless,
			slowMo: config.slowMo,
		});
		this.pageBase = await this.browser.newPage();
		this.pageCompare = await this.browser.newPage();
		this.pageBase.setDefaultNavigationTimeout(60000);
		this.pageCompare.setDefaultNavigationTimeout(60000);

		const self = this;
		this.pageCompare.on('requestfinished', function() {
			self.lastCompare = Date.now();
		});
		this.pageBase.on('requestfinished', function() {
			self.lastBase = Date.now();
		});
	},

	awaitNetworkIdle: async function (seconds) {
		var self = this;
		return new Promise(function (resolve, reject) {
			const timeout = setTimeout(function() {
				reject();
			}, 10000)
			const waitForFoo = function() {
				const currentTime = Date.now() - seconds*1000;
				if (self.lastBase < currentTime && self.lastCompare < currentTime) {
					clearTimeout(timeout);
					return resolve();
				}
				setTimeout(waitForFoo, 100);
			};
			waitForFoo();

		});
	},

	login: async function (test) {
		test.timeout(20000);
		await this.resetBrowser();
		await Promise.all([
			this.performLogin(this.pageBase, config.urlBase),
			this.performLogin(this.pageCompare, config.urlChange)
		]);
	},

	performLogin: async function (page, baseUrl) {
		await page.bringToFront();
		await page.goto(baseUrl + '/index.php/login', {waitUntil: 'networkidle0'});
		await page.type('#user', 'admin');
		await page.type('#password', 'admin');
		const inputElement = await page.$('input[type=submit]');
		await inputElement.click();
		await page.waitForNavigation({waitUntil: 'networkidle2'});
		return await page.waitForSelector('#header');
	},

	takeAndCompare: async function (test, route, action, options) {
		// use Promise.all
		if (options === undefined)
			options = {};
		if (options.waitUntil === undefined) {
			options.waitUntil = 'networkidle0';
		}
		if (options.viewport) {
			if (options.viewport.scale === undefined) {
				options.viewport.scale = 1;
			}
			await Promise.all([
				this.pageBase.setViewport({
					width: options.viewport.w,
					height: options.viewport.h,
					deviceScaleFactor: options.viewport.scale
				}),
				this.pageCompare.setViewport({
					width: options.viewport.w,
					height: options.viewport.h,
					deviceScaleFactor: options.viewport.scale
				})
			]);
 			await this.delay(100);
		}
		let fileName = test.test.title
		if (route !== undefined) {
			await Promise.all([
				this.pageBase.goto(`${config.urlBase}${route}`, {waitUntil: options.waitUntil}),
				this.pageCompare.goto(`${config.urlChange}${route}`, {waitUntil: options.waitUntil})
			]);
		}
		await this.pageBase.$eval('body', function (e) {
			$('.live-relative-timestamp').removeClass('live-relative-timestamp').text('5 minutes ago');
			$(':focus').blur();
		});
		await this.pageCompare.$eval('body', function (e) {
			$('.live-relative-timestamp').removeClass('live-relative-timestamp').text('5 minutes ago');
			$(':focus').blur();
		});
		var failed = null;
		try {
			await this.pageBase.bringToFront();
			await action(this.pageBase);
			await this.pageCompare.bringToFront();
			await action(this.pageCompare);
		} catch (err) {
			failed = err;
		}
		await this.awaitNetworkIdle(3);
		await this.pageBase.$eval('body', function (e) {
			$('.live-relative-timestamp').removeClass('live-relative-timestamp').text('5 minutes ago');
			$(':focus').blur();
		});
		await this.pageCompare.$eval('body', function (e) {
			$('.live-relative-timestamp').removeClass('live-relative-timestamp').text('5 minutes ago');
			$(':focus').blur();
		});
		await Promise.all([
			this.pageBase.screenshot({
				path: `${this._outputDirectory}/${fileName}.base.png`,
				fullPage: false,
			}),
			this.pageCompare.screenshot({
				path: `${this._outputDirectory}/${fileName}.change.png`,
				fullPage: false
			})
		]);

		if (options.runOnly === true) {
			fs.unlinkSync(`${this._outputDirectory}/${fileName}.base.png`);
			fs.renameSync(`${this._outputDirectory}/${fileName}.change.png`, `${this._outputDirectory}/${fileName}.png`);
		}

		return new Promise(async (resolve, reject) => {
			try {
				if (options.runOnly !== true) {
					await this.compareScreenshots(fileName);
				}
			} catch (err) {
				if (failed) {
					console.log('Failure during takeAndCompare action callback');
					console.log(failed);
				}
				console.log('Failure when comparing images');
				return reject(err);
			}
			if (options.runOnly !== true && failed) {
				console.log('Failure during takeAndCompare action callback');
				console.log(failed);
				failed.failedAction = true;
				return reject(failed);
			}
			return resolve();
		});
	},

	compareScreenshots: function (fileName) {
		let self = this;
		return new Promise((resolve, reject) => {
			const img1 = fs.createReadStream(`${self._outputDirectory}/${fileName}.base.png`).pipe(new PNG()).on('parsed', doneReading);
			const img2 = fs.createReadStream(`${self._outputDirectory}/${fileName}.change.png`).pipe(new PNG()).on('parsed', doneReading);

			let filesRead = 0;

			function doneReading () {
				// Wait until both files are read.
				if (++filesRead < 2) return;

				// The files should be the same size.
				expect(img1.width, 'image widths are the same').equal(img2.width);
				expect(img1.height, 'image heights are the same').equal(img2.height);

				// Do the visual diff.
				const diff = new PNG({width: img1.width, height: img2.height});
				const numDiffPixels = pixelmatch(
					img1.data, img2.data, diff.data, img1.width, img1.height,
					{threshold: 0.3});
				if (numDiffPixels > 0) {
					diff.pack().pipe(fs.createWriteStream(`${self._outputDirectory}/${fileName}.diff.png`));
				} else {
					fs.unlinkSync(`${self._outputDirectory}/${fileName}.base.png`);
					fs.renameSync(`${self._outputDirectory}/${fileName}.change.png`, `${self._outputDirectory}/${fileName}.png`);
				}

				// The files should look the same.
				expect(numDiffPixels, 'number of different pixels').equal(0);
				resolve();
			}
		});
	},
	/**
	 * Helper function to wait
	 * to make sure that initial animations are done
	 */
	delay: async function (timeout) {
		return new Promise((resolve) => {
			setTimeout(resolve, timeout);
		});
	},

	childOfClassByText: async function (page, classname, text) {
		return page.$x('//*[contains(concat(" ", normalize-space(@class), " "), " ' + classname + ' ")]//text()[normalize-space() = \'' + text + '\']/..');
	},

	childOfIdByText: async function (page, classname, text) {
		return page.$x('//*[contains(concat(" ", normalize-space(@id), " "), " ' + classname + ' ")]//text()[normalize-space() = \'' + text + '\']/..');
	}
};
