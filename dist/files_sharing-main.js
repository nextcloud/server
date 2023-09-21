/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};
/*!****************************************!*\
  !*** ./apps/files_sharing/src/main.ts ***!
  \****************************************/


/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
// register default shares types
if (!window.OC) {
  window.OC = {};
}
Object.assign(window.OC, {
  Share: {
    SHARE_TYPE_USER: 0,
    SHARE_TYPE_GROUP: 1,
    SHARE_TYPE_LINK: 3,
    SHARE_TYPE_EMAIL: 4,
    SHARE_TYPE_REMOTE: 6,
    SHARE_TYPE_CIRCLE: 7,
    SHARE_TYPE_GUEST: 8,
    SHARE_TYPE_REMOTE_GROUP: 9,
    SHARE_TYPE_ROOM: 10,
    SHARE_TYPE_DECK: 12,
    SHARE_TYPE_SCIENCEMESH: 15
  }
});
/******/ })()
;
//# sourceMappingURL=files_sharing-main.js.map?v=d1aea237b64caf61cd2d