/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@mdi/svg/svg/message-reply-text.svg?raw":
/*!**************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/message-reply-text.svg?raw ***!
  \**************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-message-reply-text\" viewBox=\"0 0 24 24\"><path d=\"M18,8H6V6H18V8M18,11H6V9H18V11M18,14H6V12H18V14M22,4A2,2 0 0,0 20,2H4A2,2 0 0,0 2,4V16A2,2 0 0,0 4,18H18L22,22V4Z\" /></svg>";

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!*******************************************!*\
  !*** ./apps/comments/src/comments-tab.js ***!
  \*******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _mdi_svg_svg_message_reply_text_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/message-reply-text.svg?raw */ "./node_modules/@mdi/svg/svg/message-reply-text.svg?raw");
/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

// eslint-disable-next-line n/no-missing-import, import/no-unresolved


// Init Comments tab component
let TabInstance = null;
const commentTab = new OCA.Files.Sidebar.Tab({
  id: 'comments',
  name: t('comments', 'Comments'),
  iconSvg: _mdi_svg_svg_message_reply_text_svg_raw__WEBPACK_IMPORTED_MODULE_0__,
  async mount(el, fileInfo, context) {
    if (TabInstance) {
      TabInstance.$destroy();
    }
    TabInstance = new OCA.Comments.View('files', {
      // Better integration with vue parent component
      parent: context
    });
    // Only mount after we have all the info we need
    await TabInstance.update(fileInfo.id);
    TabInstance.$mount(el);
  },
  update(fileInfo) {
    TabInstance.update(fileInfo.id);
  },
  destroy() {
    TabInstance.$destroy();
    TabInstance = null;
  },
  scrollBottomReached() {
    TabInstance.onScrollBottomReached();
  }
});
window.addEventListener('DOMContentLoaded', function () {
  if (OCA.Files && OCA.Files.Sidebar) {
    OCA.Files.Sidebar.registerTab(commentTab);
  }
});
})();

/******/ })()
;
//# sourceMappingURL=comments-comments-tab.js.map?v=96fbbcb8cc2eef22b032