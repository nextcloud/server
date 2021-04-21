/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robbert Gurdeep Singh <git@beardhatcode.be>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { randHash } from "../utils/";

/**
 * Make a name aimed to break the viewer in case of escaping errors
 *
 * @param {String} realName
 * @returns {String} a name for the file to be uploaded as
 */
function naughtyFileName(realName) {
	const ext = realName.split(".").pop();
	return (
		"~⛰️ shot of a ${big} mountain`, " +
		"realy #1's " +
		'" #_+="%2520%27%22%60%25%21%23 was this called ' +
		realName +
		"in the" +
		"☁️" +
		"👩‍💻" +
		"? :* ." +
		ext.toUpperCase()
	);
}

for (let [file, type] of [
	["audio.mp3", "audio/mpeg"],
	["audio.ogg", "audio/mpeg"],
	["audio.ogg", "audio/ogg"],
	["image1.jpg", "image/jpeg"],
	["image.gif", "image/gif"],
	["image.heic", "image/heic"],
	["image.png", "image/png"],
	["image-small.png", "image/png"],
	["image.svg", "image/svg"],
	["image.webp", "image/webp"],
	["video1.mp4", "video/mp4"],
	["video.mkv", "video/mkv"],
	["video.ogv", "video/ogv"],
	["video.webm", "video/webm"],
]) {
	const placedName = naughtyFileName(file);

	// We'll escape all the characters in the name to match it with css
	const placedNameCss = CSS.escape(placedName);

	// fresh user for each file
	const randUser = randHash() + "@-" + randHash(); // @ is allowed, so use it

	const folderName =
		'Nextcloud "%27%22%60%25%21%23" >`⛰️<' + file + "><` e*'rocks!#?#%~";

	describe(`Open ${file} in viewer with a naughty name`, function () {
		before(function () {
			// Init user
			cy.nextcloudCreateUser(randUser, "password");
			cy.login(randUser, "password");

			// Upload test files
			cy.createFolder(folderName);
			cy.uploadFile(file, type, "/" + folderName, placedName);
			cy.visit("/apps/files");

			// wait a bit for things to be settled
			cy.wait(1000);
			cy.openFile(folderName);
			cy.wait(1000);
		});
		after(function () {
			// no need to log out we do this in the test to check the public link
		});

		it(`See ${file} as ${placedName} in the list`, function () {
			cy.get(`#fileList tr[data-file="${placedNameCss}"]`, {
				timeout: 10000,
			}).should("contain", placedName);
		});

		it("Open the viewer on file click", function () {
			cy.openFile(placedName);
			cy.get("body > .viewer").should("be.visible");
		});

		it("Does not see a loading animation", function () {
			cy.get("body > .viewer", { timeout: 10000 })
				.should("be.visible")
				.and("have.class", "modal-mask")
				.and("not.have.class", "icon-loading");
		});

		it("See the menu icon and title on the viewer header", function () {
			cy.get("body > .viewer .modal-title").should("contain", placedName);
			cy.get(
				"body > .viewer .modal-header button.action-item__menutoggle"
			).should("be.visible");
			cy.get("body > .viewer .modal-header button.icon-close").should(
				"be.visible"
			);
		});

		it("Does not see navigation arrows", function () {
			cy.get("body > .viewer a.prev").should("not.be.visible");
			cy.get("body > .viewer a.next").should("not.be.visible");
		});

		it("Share the folder with a share link and access the share link", function () {
			cy.createLinkShare(folderName).then((token) => {
				cy.logout();
				cy.visit(`/s/${token}`);
			});
		});

		it("Open the viewer on file click (public)", function () {
			cy.openFile(placedName);
			cy.get("body > .viewer").should("be.visible");
		});

		it("Does not see a loading animation (public)", function () {
			cy.get("body > .viewer", { timeout: 10000 })
				.should("be.visible")
				.and("have.class", "modal-mask")
				.and("not.have.class", "icon-loading");
		});

		it("See the menu icon and title on the viewer header (public)", function () {
			cy.get("body > .viewer .modal-title").should("contain", placedName);
			cy.get("body > .viewer .modal-header button.icon-close").should(
				"be.visible"
			);
		});

		it("Does not see navigation arrows (public)", function () {
			cy.get("body > .viewer a.prev").should("not.be.visible");
			cy.get("body > .viewer a.next").should("not.be.visible");
		});
	});
}
