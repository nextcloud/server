/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js":
/*!***************************************************************!*\
  !*** ./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   sanitizeSVG: () => (/* binding */ sanitizeSVG)
/* harmony export */ });
/* harmony import */ var buffer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! buffer */ "./node_modules/buffer/index.js");
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! is-svg */ "./node_modules/@skjnldsv/sanitize-svg/node_modules/is-svg/index.js");
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(is_svg__WEBPACK_IMPORTED_MODULE_1__);



const readAsText = (svg) => new Promise((resolve) => {
    if (!isFile(svg)) {
        resolve(svg.toString('utf-8'));
    }
    else {
        const fileReader = new FileReader();
        fileReader.onload = () => {
            resolve(fileReader.result);
        };
        fileReader.readAsText(svg);
    }
});
const isFile = (obj) => {
    return obj.size !== undefined;
};
const sanitizeSVG = async (svg) => {
    if (!svg) {
        throw new Error('Not an svg');
    }
    let svgText = '';
    if (buffer__WEBPACK_IMPORTED_MODULE_0__.Buffer.isBuffer(svg) || svg instanceof File) {
        svgText = await readAsText(svg);
    }
    else {
        svgText = svg;
    }
    if (!is_svg__WEBPACK_IMPORTED_MODULE_1___default()(svgText)) {
        throw new Error('Not an svg');
    }
    const div = document.createElement('div');
    div.innerHTML = svgText;
    const svgEl = div.firstElementChild;
    const attributes = Array.from(svgEl.attributes).map(({ name }) => name);
    const hasScriptAttr = !!attributes.find((attr) => attr.startsWith('on'));
    const scripts = svgEl.getElementsByTagName('script');
    return scripts.length === 0 && !hasScriptAttr ? svg : null;
};


//# sourceMappingURL=index.esm.js.map


/***/ }),

/***/ "./node_modules/@skjnldsv/sanitize-svg/node_modules/is-svg/index.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@skjnldsv/sanitize-svg/node_modules/is-svg/index.js ***!
  \**************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

"use strict";

const {XMLParser, XMLValidator} = __webpack_require__(/*! fast-xml-parser */ "./node_modules/fast-xml-parser/src/fxp.js");

const isSvg = input => {
	if (input === undefined || input === null) {
		return false;
	}

	input = input.toString().trim();

	if (input.length === 0) {
		return false;
	}

	// Has to be `!==` as it can also return an object with error info.
	if (XMLValidator.validate(input) !== true) {
		return false;
	}

	let jsonObject;
	const parser = new XMLParser();

	try {
		jsonObject = parser.parse(input);
	} catch (_) {
		return false;
	}

	if (!jsonObject) {
		return false;
	}

	if (!('svg' in jsonObject)) {
		return false;
	}

	return true;
};

module.exports = isSvg;
// TODO: Remove this for the next major release
module.exports["default"] = isSvg;


/***/ }),

/***/ "./apps/files/src/models/Tab.js":
/*!**************************************!*\
  !*** ./apps/files/src/models/Tab.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Tab)
/* harmony export */ });
/* harmony import */ var _skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @skjnldsv/sanitize-svg */ "./node_modules/@skjnldsv/sanitize-svg/dist/index.esm.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

class Tab {
  /**
   * Create a new tab instance
   *
   * @param {object} options destructuring object
   * @param {string} options.id the unique id of this tab
   * @param {string} options.name the translated tab name
   * @param {?string} options.icon the icon css class
   * @param {?string} options.iconSvg the icon in svg format
   * @param {Function} options.mount function to mount the tab
   * @param {Function} [options.setIsActive] function to forward the active state of the tab
   * @param {Function} options.update function to update the tab
   * @param {Function} options.destroy function to destroy the tab
   * @param {Function} [options.enabled] define conditions whether this tab is active. Must returns a boolean
   * @param {Function} [options.scrollBottomReached] executed when the tab is scrolled to the bottom
   */
  constructor() {
    let {
      id,
      name,
      icon,
      iconSvg,
      mount,
      setIsActive,
      update,
      destroy,
      enabled,
      scrollBottomReached
    } = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    _defineProperty(this, "_id", void 0);
    _defineProperty(this, "_name", void 0);
    _defineProperty(this, "_icon", void 0);
    _defineProperty(this, "_iconSvgSanitized", void 0);
    _defineProperty(this, "_mount", void 0);
    _defineProperty(this, "_setIsActive", void 0);
    _defineProperty(this, "_update", void 0);
    _defineProperty(this, "_destroy", void 0);
    _defineProperty(this, "_enabled", void 0);
    _defineProperty(this, "_scrollBottomReached", void 0);
    if (enabled === undefined) {
      enabled = () => true;
    }
    if (scrollBottomReached === undefined) {
      scrollBottomReached = () => {};
    }

    // Sanity checks
    if (typeof id !== 'string' || id.trim() === '') {
      throw new Error('The id argument is not a valid string');
    }
    if (typeof name !== 'string' || name.trim() === '') {
      throw new Error('The name argument is not a valid string');
    }
    if ((typeof icon !== 'string' || icon.trim() === '') && typeof iconSvg !== 'string') {
      throw new Error('Missing valid string for icon or iconSvg argument');
    }
    if (typeof mount !== 'function') {
      throw new Error('The mount argument should be a function');
    }
    if (setIsActive !== undefined && typeof setIsActive !== 'function') {
      throw new Error('The setIsActive argument should be a function');
    }
    if (typeof update !== 'function') {
      throw new Error('The update argument should be a function');
    }
    if (typeof destroy !== 'function') {
      throw new Error('The destroy argument should be a function');
    }
    if (typeof enabled !== 'function') {
      throw new Error('The enabled argument should be a function');
    }
    if (typeof scrollBottomReached !== 'function') {
      throw new Error('The scrollBottomReached argument should be a function');
    }
    this._id = id;
    this._name = name;
    this._icon = icon;
    this._mount = mount;
    this._setIsActive = setIsActive;
    this._update = update;
    this._destroy = destroy;
    this._enabled = enabled;
    this._scrollBottomReached = scrollBottomReached;
    if (typeof iconSvg === 'string') {
      (0,_skjnldsv_sanitize_svg__WEBPACK_IMPORTED_MODULE_0__.sanitizeSVG)(iconSvg).then(sanitizedSvg => {
        this._iconSvgSanitized = sanitizedSvg;
      });
    }
  }
  get id() {
    return this._id;
  }
  get name() {
    return this._name;
  }
  get icon() {
    return this._icon;
  }
  get iconSvg() {
    return this._iconSvgSanitized;
  }
  get mount() {
    return this._mount;
  }
  get setIsActive() {
    return this._setIsActive || (() => undefined);
  }
  get update() {
    return this._update;
  }
  get destroy() {
    return this._destroy;
  }
  get enabled() {
    return this._enabled;
  }
  get scrollBottomReached() {
    return this._scrollBottomReached;
  }
}

/***/ }),

/***/ "./apps/files/src/services/FileInfo.js":
/*!*********************************************!*\
  !*** ./apps/files/src/services/FileInfo.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * @param {any} url -
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__(url) {
  const response = await (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"])({
    method: 'PROPFIND',
    url,
    data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetDefaultPropfind)()
  });

  // TODO: create new parser or use cdav-lib when available
  const file = OC.Files.getClient()._client.parseMultiStatus(response.data);
  // TODO: create new parser or use cdav-lib when available
  const fileInfo = OC.Files.getClient()._parseFileInfo(file[0]);

  // TODO remove when no more legacy backbone is used
  fileInfo.get = key => fileInfo[key];
  fileInfo.isDirectory = () => fileInfo.mimetype === 'httpd/unix-directory';
  fileInfo.canEdit = () => Boolean(fileInfo.permissions & OC.PERMISSION_UPDATE);
  return fileInfo;
}

/***/ }),

/***/ "./apps/files/src/services/Sidebar.js":
/*!********************************************!*\
  !*** ./apps/files/src/services/Sidebar.js ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Sidebar)
/* harmony export */ });
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

class Sidebar {
  constructor() {
    _defineProperty(this, "_state", void 0);
    // init empty state
    this._state = {};

    // init default values
    this._state.tabs = [];
    this._state.views = [];
    this._state.file = '';
    this._state.activeTab = '';
    console.debug('OCA.Files.Sidebar initialized');
  }

  /**
   * Get the sidebar state
   *
   * @readonly
   * @memberof Sidebar
   * @return {object} the data state
   */
  get state() {
    return this._state;
  }

  /**
   * Register a new tab view
   *
   * @memberof Sidebar
   * @param {object} tab a new unregistered tab
   * @return {boolean}
   */
  registerTab(tab) {
    const hasDuplicate = this._state.tabs.findIndex(check => check.id === tab.id) > -1;
    if (!hasDuplicate) {
      this._state.tabs.push(tab);
      return true;
    }
    console.error(`An tab with the same id ${tab.id} already exists`, tab);
    return false;
  }
  registerSecondaryView(view) {
    const hasDuplicate = this._state.views.findIndex(check => check.id === view.id) > -1;
    if (!hasDuplicate) {
      this._state.views.push(view);
      return true;
    }
    console.error('A similar view already exists', view);
    return false;
  }

  /**
   * Return current opened file
   *
   * @memberof Sidebar
   * @return {string} the current opened file
   */
  get file() {
    return this._state.file;
  }

  /**
   * Set the current visible sidebar tab
   *
   * @memberof Sidebar
   * @param {string} id the tab unique id
   */
  setActiveTab(id) {
    this._state.activeTab = id;
  }
}

/***/ }),

/***/ "./apps/files/src/sidebar.js":
/*!***********************************!*\
  !*** ./apps/files/src/sidebar.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Sidebar.vue */ "./apps/files/src/views/Sidebar.vue");
/* harmony import */ var _services_Sidebar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/Sidebar.js */ "./apps/files/src/services/Sidebar.js");
/* harmony import */ var _models_Tab_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./models/Tab.js */ "./apps/files/src/models/Tab.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






vue__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate;

// Init Sidebar Service
if (!window.OCA.Files) {
  window.OCA.Files = {};
}
Object.assign(window.OCA.Files, {
  Sidebar: new _services_Sidebar_js__WEBPACK_IMPORTED_MODULE_2__["default"]()
});
Object.assign(window.OCA.Files.Sidebar, {
  Tab: _models_Tab_js__WEBPACK_IMPORTED_MODULE_3__["default"]
});
window.addEventListener('DOMContentLoaded', function () {
  const contentElement = document.querySelector('body > .content') || document.querySelector('body > #content');

  // Make sure we have a proper layout
  if (contentElement) {
    // Make sure we have a mountpoint
    if (!document.getElementById('app-sidebar')) {
      const sidebarElement = document.createElement('div');
      sidebarElement.id = 'app-sidebar';
      contentElement.appendChild(sidebarElement);
    }
  }

  // Init vue app
  const View = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_views_Sidebar_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
  const AppSidebar = new View({
    name: 'SidebarRoot'
  });
  AppSidebar.$mount('#app-sidebar');
  window.OCA.Files.Sidebar.open = AppSidebar.open;
  window.OCA.Files.Sidebar.close = AppSidebar.close;
  window.OCA.Files.Sidebar.setFullScreenMode = AppSidebar.setFullScreenMode;
  window.OCA.Files.Sidebar.setShowTagsDefault = AppSidebar.setShowTagsDefault;
});

/***/ }),

/***/ "./apps/files/src/logger.ts":
/*!**********************************!*\
  !*** ./apps/files/src/logger.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files').detectUser().build());

/***/ }),

/***/ "./apps/systemtags/src/logger.ts":
/*!***************************************!*\
  !*** ./apps/systemtags/src/logger.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: () => (/* binding */ logger)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('systemtags').detectUser().build();

/***/ }),

/***/ "./apps/systemtags/src/services/api.ts":
/*!*********************************************!*\
  !*** ./apps/systemtags/src/services/api.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createTag: () => (/* binding */ createTag),
/* harmony export */   deleteTag: () => (/* binding */ deleteTag),
/* harmony export */   fetchLastUsedTagIds: () => (/* binding */ fetchLastUsedTagIds),
/* harmony export */   fetchTags: () => (/* binding */ fetchTags),
/* harmony export */   fetchTagsPayload: () => (/* binding */ fetchTagsPayload),
/* harmony export */   updateTag: () => (/* binding */ updateTag)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _davClient_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./davClient.js */ "./apps/systemtags/src/services/davClient.ts");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.js */ "./apps/systemtags/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */






const fetchTagsPayload = `<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
	</d:prop>
</d:propfind>`;
const fetchTags = async () => {
  const path = '/systemtags';
  try {
    const {
      data: tags
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.getDirectoryContents(path, {
      data: fetchTagsPayload,
      details: true,
      glob: '/systemtags/*' // Filter out first empty tag
    });
    return (0,_utils__WEBPACK_IMPORTED_MODULE_4__.parseTags)(tags);
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'));
  }
};
const fetchLastUsedTagIds = async () => {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/systemtags/lastused');
  try {
    const {
      data: lastUsedTagIds
    } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
    return lastUsedTagIds.map(Number);
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load last used tags'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load last used tags'));
  }
};
/**
 * @param tag
 * @return created tag id
 */
const createTag = async tag => {
  const path = '/systemtags';
  const tagToPost = (0,_utils__WEBPACK_IMPORTED_MODULE_4__.formatTag)(tag);
  try {
    const {
      headers
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
      method: 'POST',
      data: tagToPost
    });
    const contentLocation = headers.get('content-location');
    if (contentLocation) {
      return (0,_utils__WEBPACK_IMPORTED_MODULE_4__.parseIdFromLocation)(contentLocation);
    }
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Missing "Content-Location" header'));
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Missing "Content-Location" header'));
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'));
  }
};
const updateTag = async tag => {
  const path = '/systemtags/' + tag.id;
  const data = `<?xml version="1.0"?>
	<d:propertyupdate  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${tag.displayName}</oc:display-name>
				<oc:user-visible>${tag.userVisible}</oc:user-visible>
				<oc:user-assignable>${tag.userAssignable}</oc:user-assignable>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
      method: 'PROPPATCH',
      data
    });
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to update tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to update tag'));
  }
};
const deleteTag = async tag => {
  const path = '/systemtags/' + tag.id;
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.deleteFile(path);
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_5__.logger.error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'));
  }
};

/***/ }),

/***/ "./apps/systemtags/src/services/davClient.ts":
/*!***************************************************!*\
  !*** ./apps/systemtags/src/services/davClient.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   davClient: () => (/* binding */ davClient)
/* harmony export */ });
/* harmony import */ var webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav/dist/node/index.js */ "./node_modules/webdav/dist/node/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



// init webdav client
const rootUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateRemoteUrl)('dav');
const davClient = (0,webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__.createClient)(rootUrl);
// set CSRF token header
const setHeaders = token => {
  davClient.setHeaders({
    // Add this so the server knows it is an request from the browser
    'X-Requested-With': 'XMLHttpRequest',
    // Inject user auth
    requesttoken: token ?? ''
  });
};
// refresh headers when request token changes
(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.onRequestTokenUpdate)(setHeaders);
setHeaders((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getRequestToken)());

/***/ }),

/***/ "./apps/systemtags/src/services/files.ts":
/*!***********************************************!*\
  !*** ./apps/systemtags/src/services/files.ts ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createTagForFile: () => (/* binding */ createTagForFile),
/* harmony export */   deleteTagForFile: () => (/* binding */ deleteTagForFile),
/* harmony export */   fetchTagsForFile: () => (/* binding */ fetchTagsForFile),
/* harmony export */   setTagForFile: () => (/* binding */ setTagForFile)
/* harmony export */ });
/* harmony import */ var _davClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./davClient.js */ "./apps/systemtags/src/services/davClient.ts");
/* harmony import */ var _api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./api.js */ "./apps/systemtags/src/services/api.ts");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils.js */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../logger.js */ "./apps/systemtags/src/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const fetchTagsForFile = async fileId => {
  const path = '/systemtags-relations/files/' + fileId;
  try {
    const {
      data: tags
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_0__.davClient.getDirectoryContents(path, {
      data: _api_js__WEBPACK_IMPORTED_MODULE_1__.fetchTagsPayload,
      details: true,
      glob: '/systemtags-relations/files/*/*' // Filter out first empty tag
    });
    return (0,_utils_js__WEBPACK_IMPORTED_MODULE_2__.parseTags)(tags);
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_3__.logger.error(t('systemtags', 'Failed to load tags for file'), {
      error
    });
    throw new Error(t('systemtags', 'Failed to load tags for file'));
  }
};
/**
 * @param tag
 * @param fileId
 * @return created tag id
 */
const createTagForFile = async (tag, fileId) => {
  const tagToCreate = (0,_utils_js__WEBPACK_IMPORTED_MODULE_2__.formatTag)(tag);
  const tagId = await (0,_api_js__WEBPACK_IMPORTED_MODULE_1__.createTag)(tagToCreate);
  const tagToSet = {
    ...tagToCreate,
    id: tagId
  };
  await setTagForFile(tagToSet, fileId);
  return tagToSet.id;
};
const setTagForFile = async (tag, fileId) => {
  const path = '/systemtags-relations/files/' + fileId + '/' + tag.id;
  const tagToPut = (0,_utils_js__WEBPACK_IMPORTED_MODULE_2__.formatTag)(tag);
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_0__.davClient.customRequest(path, {
      method: 'PUT',
      data: tagToPut
    });
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_3__.logger.error(t('systemtags', 'Failed to set tag for file'), {
      error
    });
    throw new Error(t('systemtags', 'Failed to set tag for file'));
  }
};
const deleteTagForFile = async (tag, fileId) => {
  const path = '/systemtags-relations/files/' + fileId + '/' + tag.id;
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_0__.davClient.deleteFile(path);
  } catch (error) {
    _logger_js__WEBPACK_IMPORTED_MODULE_3__.logger.error(t('systemtags', 'Failed to delete tag for file'), {
      error
    });
    throw new Error(t('systemtags', 'Failed to delete tag for file'));
  }
};

/***/ }),

/***/ "./apps/systemtags/src/utils.ts":
/*!**************************************!*\
  !*** ./apps/systemtags/src/utils.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultBaseTag: () => (/* binding */ defaultBaseTag),
/* harmony export */   formatTag: () => (/* binding */ formatTag),
/* harmony export */   parseIdFromLocation: () => (/* binding */ parseIdFromLocation),
/* harmony export */   parseTags: () => (/* binding */ parseTags)
/* harmony export */ });
/* harmony import */ var camelcase__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! camelcase */ "./node_modules/camelcase/index.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const defaultBaseTag = {
  userVisible: true,
  userAssignable: true,
  canAssign: true
};
const parseTags = tags => {
  return tags.map(_ref => {
    let {
      props
    } = _ref;
    return Object.fromEntries(Object.entries(props).map(_ref2 => {
      let [key, value] = _ref2;
      return [(0,camelcase__WEBPACK_IMPORTED_MODULE_0__["default"])(key), (0,camelcase__WEBPACK_IMPORTED_MODULE_0__["default"])(key) === 'displayName' ? String(value) : value];
    }));
  });
};
/**
 * Parse id from `Content-Location` header
 * @param url URL to parse
 */
const parseIdFromLocation = url => {
  const queryPos = url.indexOf('?');
  if (queryPos > 0) {
    url = url.substring(0, queryPos);
  }
  const parts = url.split('/');
  let result;
  do {
    result = parts[parts.length - 1];
    parts.pop();
    // note: first result can be empty when there is a trailing slash,
    // so we take the part before that
  } while (!result && parts.length > 0);
  return Number(result);
};
const formatTag = initialTag => {
  if ('name' in initialTag && !('displayName' in initialTag)) {
    return {
      ...initialTag
    };
  }
  const tag = {
    ...initialTag
  };
  tag.name = tag.displayName;
  delete tag.displayName;
  return tag;
};

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts":
/*!*********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts ***!
  \*********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelectTags.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelectTags.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils.js */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _services_api_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/api.js */ "./apps/systemtags/src/services/api.ts");
/* harmony import */ var _services_files_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/files.js */ "./apps/systemtags/src/services/files.ts");
// FIXME Vue TypeScript ESLint errors
/* eslint-disable */








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_7__["default"].extend({
  name: 'SystemTags',
  components: {
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcSelectTags: _nextcloud_vue_dist_Components_NcSelectTags_js__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    fileId: {
      type: Number,
      required: true
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      sortedTags: [],
      selectedTags: [],
      loadingTags: false,
      loading: false
    };
  },
  async created() {
    try {
      const tags = await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_5__.fetchTags)();
      const lastUsedOrder = await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_5__.fetchLastUsedTagIds)();
      const lastUsedTags = [];
      const remainingTags = [];
      for (const tag of tags) {
        if (lastUsedOrder.includes(tag.id)) {
          lastUsedTags.push(tag);
          continue;
        }
        remainingTags.push(tag);
      }
      const sortByLastUsed = (a, b) => {
        return lastUsedOrder.indexOf(a.id) - lastUsedOrder.indexOf(b.id);
      };
      lastUsedTags.sort(sortByLastUsed);
      this.sortedTags = [...lastUsedTags, ...remainingTags];
    } catch (error) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'));
    }
  },
  watch: {
    fileId: {
      immediate: true,
      async handler() {
        this.loadingTags = true;
        try {
          this.selectedTags = await (0,_services_files_js__WEBPACK_IMPORTED_MODULE_6__.fetchTagsForFile)(this.fileId);
          this.$emit('has-tags', this.selectedTags.length > 0);
        } catch (error) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load selected tags'));
        }
        this.loadingTags = false;
      }
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
    createOption(newDisplayName) {
      for (const tag of this.sortedTags) {
        const {
          id,
          displayName,
          ...baseTag
        } = tag;
        if (displayName === newDisplayName && Object.entries(baseTag).every(_ref => {
          let [key, value] = _ref;
          return _utils_js__WEBPACK_IMPORTED_MODULE_4__.defaultBaseTag[key] === value;
        })) {
          // Return existing tag to prevent vue-select from thinking the tags are different and showing duplicate options
          return tag;
        }
      }
      return {
        ..._utils_js__WEBPACK_IMPORTED_MODULE_4__.defaultBaseTag,
        displayName: newDisplayName
      };
    },
    handleInput(selectedTags) {
      /**
       * Filter out tags with no id to prevent duplicate selected options
       *
       * Created tags are added programmatically by `handleCreate()` with
       * their respective ids returned from the server
       */
      this.selectedTags = selectedTags.filter(selectedTag => Boolean(selectedTag.id));
    },
    async handleSelect(tags) {
      const lastTag = tags[tags.length - 1];
      if (!lastTag.id) {
        // Ignore created tags handled by `handleCreate()`
        return;
      }
      const selectedTag = lastTag;
      this.loading = true;
      try {
        await (0,_services_files_js__WEBPACK_IMPORTED_MODULE_6__.setTagForFile)(selectedTag, this.fileId);
        const sortToFront = (a, b) => {
          if (a.id === selectedTag.id) {
            return -1;
          } else if (b.id === selectedTag.id) {
            return 1;
          }
          return 0;
        };
        this.sortedTags.sort(sortToFront);
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to select tag'));
      }
      this.loading = false;
    },
    async handleCreate(tag) {
      this.loading = true;
      try {
        const id = await (0,_services_files_js__WEBPACK_IMPORTED_MODULE_6__.createTagForFile)(tag, this.fileId);
        const createdTag = {
          ...tag,
          id
        };
        this.sortedTags.unshift(createdTag);
        this.selectedTags.push(createdTag);
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to create tag'));
      }
      this.loading = false;
    },
    async handleDeselect(tag) {
      this.loading = true;
      try {
        await (0,_services_files_js__WEBPACK_IMPORTED_MODULE_6__.deleteTagForFile)(tag, this.fileId);
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to delete tag'));
      }
      this.loading = false;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'LegacyView',
  props: {
    component: {
      type: Object,
      required: true
    },
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    }
  },
  watch: {
    fileInfo(fileInfo) {
      // update the backbone model FileInfo
      this.setFileInfo(fileInfo);
    }
  },
  mounted() {
    // append the backbone element and set the FileInfo
    this.component.$el.replaceAll(this.$el);
    this.setFileInfo(this.fileInfo);
  },
  methods: {
    setFileInfo(fileInfo) {
      this.component.setFileInfo(new OCA.Files.FileInfoModel(fileInfo));
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebarTab.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebarTab.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SidebarTab',
  components: {
    NcAppSidebarTab: _nextcloud_vue_dist_Components_NcAppSidebarTab_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcEmptyContent: _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    id: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: false
    },
    /**
     * Lifecycle methods.
     * They are prefixed with `on` to avoid conflict with Vue
     * methods like this.destroy
     */
    onMount: {
      type: Function,
      required: true
    },
    onUpdate: {
      type: Function,
      required: true
    },
    onDestroy: {
      type: Function,
      required: true
    },
    onScrollBottomReached: {
      type: Function,
      default: () => {}
    }
  },
  data() {
    return {
      loading: true
    };
  },
  computed: {
    // TODO: implement a better way to force pass a prop from Sidebar
    activeTab() {
      return this.$parent.activeTab;
    }
  },
  watch: {
    async fileInfo(newFile, oldFile) {
      // Update fileInfo on change
      if (newFile.id !== oldFile.id) {
        this.loading = true;
        await this.onUpdate(this.fileInfo);
        this.loading = false;
      }
    }
  },
  async mounted() {
    this.loading = true;
    // Mount the tab:  mounting point,   fileInfo,      vue context
    await this.onMount(this.$refs.mount, this.fileInfo, this.$refs.tab);
    this.loading = false;
  },
  async beforeDestroy() {
    // unmount the tab
    await this.onDestroy();
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js":
/*!**********************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! jquery */ "./node_modules/jquery/dist/jquery.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSidebar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTime.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _services_FileInfo_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../services/FileInfo.js */ "./apps/files/src/services/FileInfo.js");
/* harmony import */ var _components_LegacyView_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../components/LegacyView.vue */ "./apps/files/src/components/LegacyView.vue");
/* harmony import */ var _components_SidebarTab_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../components/SidebarTab.vue */ "./apps/files/src/components/SidebarTab.vue");
/* harmony import */ var _systemtags_src_components_SystemTags_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../../../systemtags/src/components/SystemTags.vue */ "./apps/systemtags/src/components/SystemTags.vue");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");





















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Sidebar',
  components: {
    LegacyView: _components_LegacyView_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcAppSidebar: _nextcloud_vue_dist_Components_NcAppSidebar_js__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcDateTime: _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcEmptyContent: _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_14__["default"],
    SidebarTab: _components_SidebarTab_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    SystemTags: _systemtags_src_components_SystemTags_vue__WEBPACK_IMPORTED_MODULE_18__["default"]
  },
  setup() {
    const currentUser = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)();

    // Non reactive properties
    return {
      currentUser,
      mdiStar: _mdi_js__WEBPACK_IMPORTED_MODULE_20__.mdiStar,
      mdiStarOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_20__.mdiStarOutline
    };
  },
  data() {
    return {
      // reactive state
      Sidebar: OCA.Files.Sidebar.state,
      showTags: false,
      showTagsDefault: true,
      error: null,
      loading: true,
      fileInfo: null,
      isFullScreen: false,
      hasLowHeight: false
    };
  },
  computed: {
    /**
     * Current filename
     * This is bound to the Sidebar service and
     * is used to load a new file
     *
     * @return {string}
     */
    file() {
      return this.Sidebar.file;
    },
    /**
     * List of all the registered tabs
     *
     * @return {Array}
     */
    tabs() {
      return this.Sidebar.tabs;
    },
    /**
     * List of all the registered views
     *
     * @return {Array}
     */
    views() {
      return this.Sidebar.views;
    },
    /**
     * Current user dav root path
     *
     * @return {string}
     */
    davPath() {
      return `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.davRemoteURL}/${_nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.davRootPath}${(0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_5__.encodePath)(this.file)}`;
    },
    /**
     * Current active tab handler
     *
     * @return {string} the current active tab
     */
    activeTab() {
      return this.Sidebar.activeTab;
    },
    /**
     * File size formatted string
     *
     * @return {string}
     */
    size() {
      return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.formatFileSize)(this.fileInfo?.size);
    },
    /**
     * File background/figure to illustrate the sidebar header
     *
     * @return {string}
     */
    background() {
      return this.getPreviewIfAny(this.fileInfo);
    },
    /**
     * App sidebar v-binding object
     *
     * @return {object}
     */
    appSidebar() {
      if (this.fileInfo) {
        return {
          'data-mimetype': this.fileInfo.mimetype,
          active: this.activeTab,
          background: this.background,
          class: {
            'app-sidebar--has-preview': this.fileInfo.hasPreview && !this.isFullScreen,
            'app-sidebar--full': this.isFullScreen
          },
          compact: this.hasLowHeight || !this.fileInfo.hasPreview || this.isFullScreen,
          loading: this.loading,
          name: this.fileInfo.name,
          title: this.fileInfo.name
        };
      } else if (this.error) {
        return {
          key: 'error',
          // force key to re-render
          subname: '',
          name: '',
          class: {
            'app-sidebar--full': this.isFullScreen
          }
        };
      }
      // no fileInfo yet, showing empty data
      return {
        loading: this.loading,
        subname: '',
        name: '',
        class: {
          'app-sidebar--full': this.isFullScreen
        }
      };
    },
    /**
     * Default action object for the current file
     *
     * @return {object}
     */
    defaultAction() {
      return this.fileInfo && OCA.Files && OCA.Files.App && OCA.Files.App.fileList && OCA.Files.App.fileList.fileActions && OCA.Files.App.fileList.fileActions.getDefaultFileAction && OCA.Files.App.fileList.fileActions.getDefaultFileAction(this.fileInfo.mimetype, this.fileInfo.type, OC.PERMISSION_READ);
    },
    /**
     * Dynamic header click listener to ensure
     * nothing is listening for a click if there
     * is no default action
     *
     * @return {string|null}
     */
    defaultActionListener() {
      return this.defaultAction ? 'figure-click' : null;
    },
    isSystemTagsEnabled() {
      return (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_1__.getCapabilities)()?.systemtags?.enabled === true;
    }
  },
  created() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.subscribe)('files:node:deleted', this.onNodeDeleted);
    window.addEventListener('resize', this.handleWindowResize);
    this.handleWindowResize();
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.unsubscribe)('file:node:deleted', this.onNodeDeleted);
    window.removeEventListener('resize', this.handleWindowResize);
  },
  methods: {
    /**
     * Can this tab be displayed ?
     *
     * @param {object} tab a registered tab
     * @return {boolean}
     */
    canDisplay(tab) {
      return tab.enabled(this.fileInfo);
    },
    resetData() {
      this.error = null;
      this.fileInfo = null;
      this.$nextTick(() => {
        if (this.$refs.tabs) {
          this.$refs.tabs.updateTabs();
        }
      });
    },
    getPreviewIfAny(fileInfo) {
      if (fileInfo?.hasPreview && !this.isFullScreen) {
        const etag = fileInfo?.etag || '';
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateUrl)(`/core/preview?fileId=${fileInfo.id}&x=${screen.width}&y=${screen.height}&a=true&v=${etag.slice(0, 6)}`);
      }
      return this.getIconUrl(fileInfo);
    },
    /**
     * Copied from https://github.com/nextcloud/server/blob/16e0887ec63591113ee3f476e0c5129e20180cde/apps/files/js/filelist.js#L1377
     * TODO: We also need this as a standalone library
     *
     * @param {object} fileInfo the fileinfo
     * @return {string} Url to the icon for mimeType
     */
    getIconUrl(fileInfo) {
      const mimeType = fileInfo?.mimetype || 'application/octet-stream';
      if (mimeType === 'httpd/unix-directory') {
        // use default folder icon
        if (fileInfo.mountType === 'shared' || fileInfo.mountType === 'shared-root') {
          return OC.MimeType.getIconUrl('dir-shared');
        } else if (fileInfo.mountType === 'external-root') {
          return OC.MimeType.getIconUrl('dir-external');
        } else if (fileInfo.mountType !== undefined && fileInfo.mountType !== '') {
          return OC.MimeType.getIconUrl('dir-' + fileInfo.mountType);
        } else if (fileInfo.shareTypes && (fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Link) > -1 || fileInfo.shareTypes.indexOf(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Email) > -1)) {
          return OC.MimeType.getIconUrl('dir-public');
        } else if (fileInfo.shareTypes && fileInfo.shareTypes.length > 0) {
          return OC.MimeType.getIconUrl('dir-shared');
        }
        return OC.MimeType.getIconUrl('dir');
      }
      return OC.MimeType.getIconUrl(mimeType);
    },
    /**
     * Set current active tab
     *
     * @param {string} id tab unique id
     */
    setActiveTab(id) {
      OCA.Files.Sidebar.setActiveTab(id);
      this.tabs.forEach(tab => {
        try {
          tab.setIsActive(id === tab.id);
        } catch (error) {
          _logger_ts__WEBPACK_IMPORTED_MODULE_19__["default"].error('Error while setting tab active state', {
            error,
            id: tab.id,
            tab
          });
        }
      });
    },
    /**
     * Toggle favourite state
     * TODO: better implementation
     *
     * @param {boolean} state favourited or not
     */
    async toggleStarred(state) {
      try {
        await (0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_8__["default"])({
          method: 'PROPPATCH',
          url: this.davPath,
          data: `<?xml version="1.0"?>
						<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
						${state ? '<d:set>' : '<d:remove>'}
							<d:prop>
								<oc:favorite>1</oc:favorite>
							</d:prop>
						${state ? '</d:set>' : '</d:remove>'}
						</d:propertyupdate>`
        });

        /**
         * TODO: adjust this when the Sidebar is finally using File/Folder classes
         * @see https://github.com/nextcloud/server/blob/8a75cb6e72acd42712ab9fea22296aa1af863ef5/apps/files/src/views/favorites.ts#L83-L115
         */
        const isDir = this.fileInfo.type === 'dir';
        const Node = isDir ? _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.Folder : _nextcloud_files__WEBPACK_IMPORTED_MODULE_4__.File;
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)(state ? 'files:favorites:added' : 'files:favorites:removed', new Node({
          fileid: this.fileInfo.id,
          source: this.davPath,
          root: `/files/${(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid}`,
          mime: isDir ? undefined : this.fileInfo.mimetype
        }));
        this.fileInfo.isFavourited = state;
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('files', 'Unable to change the favourite state of the file'));
        _logger_ts__WEBPACK_IMPORTED_MODULE_19__["default"].error('Unable to change favourite state', {
          error
        });
      }
    },
    onDefaultAction() {
      if (this.defaultAction) {
        // generate fake context
        this.defaultAction.action(this.fileInfo.name, {
          fileInfo: this.fileInfo,
          dir: this.fileInfo.dir,
          fileList: OCA.Files.App.fileList,
          $file: jquery__WEBPACK_IMPORTED_MODULE_9___default()('body')
        });
      }
    },
    /**
     * Toggle the tags selector
     */
    toggleTags() {
      this.showTagsDefault = this.showTags = !this.showTags;
    },
    /**
     * Open the sidebar for the given file
     *
     * @param {string} path the file path to load
     * @return {Promise}
     * @throws {Error} loading failure
     */
    async open(path) {
      if (!path || path.trim() === '') {
        throw new Error(`Invalid path '${path}'`);
      }

      // Only focus the tab when the selected file/tab is changed in already opened sidebar
      // Focusing the sidebar on first file open is handled by NcAppSidebar
      const focusTabAfterLoad = !!this.Sidebar.file;

      // update current opened file
      this.Sidebar.file = path;

      // reset data, keep old fileInfo to not reload all tabs and just hide them
      this.error = null;
      this.loading = true;
      try {
        this.fileInfo = await (0,_services_FileInfo_js__WEBPACK_IMPORTED_MODULE_15__["default"])(this.davPath);
        // adding this as fallback because other apps expect it
        this.fileInfo.dir = this.file.split('/').slice(0, -1).join('/');

        // DEPRECATED legacy views
        // TODO: remove
        this.views.forEach(view => {
          view.setFileInfo(this.fileInfo);
        });
        await this.$nextTick();
        this.setActiveTab(this.Sidebar.activeTab || this.tabs[0].id);
        this.loading = false;
        await this.$nextTick();
        if (focusTabAfterLoad && this.$refs.sidebar) {
          this.$refs.sidebar.focusActiveTabContent();
        }
      } catch (error) {
        this.loading = false;
        this.error = t('files', 'Error while loading the file data');
        console.error('Error while loading the file data', error);
        throw new Error(error);
      }
    },
    /**
     * Close the sidebar
     */
    close() {
      this.Sidebar.file = '';
      this.showTags = false;
      this.resetData();
    },
    /**
     * Handle if the current node was deleted
     * @param {import('@nextcloud/files').Node} node The deleted node
     */
    onNodeDeleted(node) {
      if (this.fileInfo && node && this.fileInfo.id === node.fileid) {
        this.close();
      }
    },
    /**
     * Allow to set the Sidebar as fullscreen from OCA.Files.Sidebar
     *
     * @param {boolean} isFullScreen - Whether or not to render the Sidebar in fullscreen.
     */
    setFullScreenMode(isFullScreen) {
      this.isFullScreen = isFullScreen;
      if (isFullScreen) {
        document.querySelector('#content')?.classList.add('with-sidebar--full') || document.querySelector('#content-vue')?.classList.add('with-sidebar--full');
      } else {
        document.querySelector('#content')?.classList.remove('with-sidebar--full') || document.querySelector('#content-vue')?.classList.remove('with-sidebar--full');
      }
    },
    /**
     * Allow to set whether tags should be shown by default from OCA.Files.Sidebar
     *
     * @param {boolean} showTagsDefault - Whether or not to show the tags by default.
     */
    setShowTagsDefault(showTagsDefault) {
      this.showTagsDefault = showTagsDefault;
    },
    /**
     * Emit SideBar events.
     */
    handleOpening() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:opening');
    },
    handleOpened() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:opened');
    },
    handleClosing() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:closing');
    },
    handleClosed() {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:sidebar:closed');
    },
    handleWindowResize() {
      this.hasLowHeight = document.documentElement.clientHeight < 1024;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5":
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5 ***!
  \*****************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div");
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080":
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080 ***!
  \*****************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppSidebarTab", {
    ref: "tab",
    attrs: {
      id: _vm.id,
      name: _vm.name,
      icon: _vm.icon
    },
    on: {
      bottomReached: _vm.onScrollBottomReached
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm._t("icon")];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm.loading ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-loading"
    }
  }) : _vm._e(), _vm._v(" "), _c("div", {
    ref: "mount"
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true":
/*!*********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _vm.file ? _c("NcAppSidebar", _vm._b({
    ref: "sidebar",
    attrs: {
      "data-cy-sidebar": "",
      "force-menu": true
    },
    on: _vm._d({
      close: _vm.close,
      "update:active": _vm.setActiveTab,
      opening: _vm.handleOpening,
      opened: _vm.handleOpened,
      closing: _vm.handleClosing,
      closed: _vm.handleClosed
    }, [_vm.defaultActionListener, function ($event) {
      $event.stopPropagation();
      $event.preventDefault();
      return _vm.onDefaultAction.apply(null, arguments);
    }]),
    scopedSlots: _vm._u([_vm.fileInfo ? {
      key: "subname",
      fn: function () {
        return [_vm.fileInfo.isFavourited ? _c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiStar,
            name: _vm.t("files", "Favorite"),
            inline: ""
          }
        }) : _vm._e(), _vm._v("\n\t\t" + _vm._s(_vm.size) + "\n\t\t"), _c("NcDateTime", {
          attrs: {
            timestamp: _vm.fileInfo.mtime
          }
        })];
      },
      proxy: true
    } : null, _vm.fileInfo ? {
      key: "description",
      fn: function () {
        return [_c("div", {
          staticClass: "sidebar__description"
        }, [_vm.isSystemTagsEnabled && _vm.showTagsDefault ? _c("SystemTags", {
          directives: [{
            name: "show",
            rawName: "v-show",
            value: _vm.showTags,
            expression: "showTags"
          }],
          attrs: {
            disabled: !_vm.fileInfo?.canEdit(),
            "file-id": _vm.fileInfo.id
          },
          on: {
            "has-tags": value => _vm.showTags = value
          }
        }) : _vm._e(), _vm._v(" "), _vm._l(_vm.views, function (view) {
          return _c("LegacyView", {
            key: view.cid,
            attrs: {
              component: view,
              "file-info": _vm.fileInfo
            }
          });
        })], 2)];
      },
      proxy: true
    } : null, _vm.fileInfo ? {
      key: "secondary-actions",
      fn: function () {
        return [_c("NcActionButton", {
          attrs: {
            "close-after-click": true
          },
          on: {
            click: function ($event) {
              return _vm.toggleStarred(!_vm.fileInfo.isFavourited);
            }
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("NcIconSvgWrapper", {
                attrs: {
                  path: _vm.fileInfo.isFavourited ? _vm.mdiStarOutline : _vm.mdiStar
                }
              })];
            },
            proxy: true
          }], null, false, 3772937801)
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.fileInfo.isFavourited ? _vm.t("files", "Remove from favorites") : _vm.t("files", "Add to favorites")) + "\n\t\t")]), _vm._v(" "), _vm.isSystemTagsEnabled ? _c("NcActionButton", {
          attrs: {
            "close-after-click": true,
            icon: "icon-tag"
          },
          on: {
            click: _vm.toggleTags
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files", "Tags")) + "\n\t\t")]) : _vm._e()];
      },
      proxy: true
    } : null], null, true)
  }, "NcAppSidebar", _vm.appSidebar, false), [_vm._v(" "), _vm._v(" "), _vm._v(" "), _vm.error ? _c("NcEmptyContent", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.error) + "\n\t")]) : _vm.fileInfo ? _vm._l(_vm.tabs, function (tab) {
    return [tab.enabled(_vm.fileInfo) ? _c("SidebarTab", {
      directives: [{
        name: "show",
        rawName: "v-show",
        value: !_vm.loading,
        expression: "!loading"
      }],
      key: tab.id,
      attrs: {
        id: tab.id,
        name: tab.name,
        icon: tab.icon,
        "on-mount": tab.mount,
        "on-update": tab.update,
        "on-destroy": tab.destroy,
        "on-scroll-bottom-reached": tab.scrollBottomReached,
        "file-info": _vm.fileInfo
      },
      scopedSlots: _vm._u([tab.iconSvg !== undefined ? {
        key: "icon",
        fn: function () {
          return [_c("span", {
            staticClass: "svg-icon",
            domProps: {
              innerHTML: _vm._s(tab.iconSvg)
            }
          })];
        },
        proxy: true
      } : null], null, true)
    }) : _vm._e()];
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", {
    staticClass: "system-tags"
  }, [_vm.loadingTags ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("systemtags", "Loading collaborative tags …"),
      size: 32
    }
  }) : [_c("NcSelectTags", {
    staticClass: "system-tags__select",
    attrs: {
      "input-label": _vm.t("systemtags", "Search or create collaborative tags"),
      placeholder: _vm.t("systemtags", "Collaborative tags …"),
      options: _vm.sortedTags,
      value: _vm.selectedTags,
      "create-option": _vm.createOption,
      disabled: _vm.disabled,
      taggable: true,
      passthru: true,
      "fetch-tags": false,
      loading: _vm.loading
    },
    on: {
      input: _vm.handleInput,
      "option:selected": _vm.handleSelect,
      "option:created": _vm.handleCreate,
      "option:deselected": _vm.handleDeselect
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function () {
        return [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("systemtags", "No tags to select, type to create a new tag")) + "\n\t\t\t")];
      },
      proxy: true
    }])
  })]], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-sidebar--has-preview[data-v-7c6102ee] .app-sidebar-header__figure {
  background-size: cover;
}
.app-sidebar--has-preview[data-v-7c6102ee][data-mimetype="text/plain"] .app-sidebar-header__figure, .app-sidebar--has-preview[data-v-7c6102ee][data-mimetype="text/markdown"] .app-sidebar-header__figure {
  background-size: contain;
}
.app-sidebar--full[data-v-7c6102ee] {
  position: fixed !important;
  z-index: 2025 !important;
  top: 0 !important;
  height: 100% !important;
}
.app-sidebar[data-v-7c6102ee] .app-sidebar-header__description {
  margin: 0 16px 4px 16px !important;
}
.app-sidebar .svg-icon[data-v-7c6102ee] svg {
  width: 20px;
  height: 20px;
  fill: currentColor;
}
.sidebar__description[data-v-7c6102ee] {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: 8px 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.system-tags[data-v-3f7729e4] {
  display: flex;
  flex-direction: column;
}
.system-tags__select[data-v-3f7729e4] {
  width: 100%;
}
.system-tags__select[data-v-3f7729e4] .vs__deselect {
  padding: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=template&id=6ac0bcb5 */ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5");
/* harmony import */ var _LegacyView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LegacyView.vue?vue&type=script&lang=js */ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LegacyView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__.render,
  _LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/LegacyView.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue":
/*!**************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=template&id=9d2bd080 */ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080");
/* harmony import */ var _SidebarTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTab.vue?vue&type=script&lang=js */ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/components/SidebarTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue":
/*!******************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true */ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true");
/* harmony import */ var _Sidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=script&lang=js */ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js");
/* harmony import */ var _Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true */ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Sidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7c6102ee",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files/src/views/Sidebar.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue":
/*!*******************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true");
/* harmony import */ var _SystemTags_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=script&lang=ts */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts");
/* harmony import */ var _SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true */ "./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SystemTags_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3f7729e4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/systemtags/src/components/SystemTags.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts":
/*!*******************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js":
/*!**************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js":
/*!**************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js":
/*!******************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5":
/*!********************************************************************************!*\
  !*** ./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5 ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LegacyView_vue_vue_type_template_id_6ac0bcb5__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LegacyView.vue?vue&type=template&id=6ac0bcb5 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/LegacyView.vue?vue&type=template&id=6ac0bcb5");


/***/ }),

/***/ "./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080":
/*!********************************************************************************!*\
  !*** ./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080 ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTab_vue_vue_type_template_id_9d2bd080__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTab.vue?vue&type=template&id=9d2bd080 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/SidebarTab.vue?vue&type=template&id=9d2bd080");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true":
/*!************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_template_id_7c6102ee_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=template&id=7c6102ee&scoped=true");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true":
/*!*************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_template_id_3f7729e4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=template&id=3f7729e4&scoped=true");


/***/ }),

/***/ "./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true":
/*!***************************************************************************************************!*\
  !*** ./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true ***!
  \***************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Sidebar_vue_vue_type_style_index_0_id_7c6102ee_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/views/Sidebar.vue?vue&type=style&index=0&id=7c6102ee&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true":
/*!****************************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true ***!
  \****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTags_vue_vue_type_style_index_0_id_3f7729e4_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTags.vue?vue&type=style&index=0&id=3f7729e4&lang=scss&scoped=true");


/***/ }),

/***/ "?4f7e":
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?3e83":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?19e6":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?0cc0":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?aeb7":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs":
/*!**********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSidebar.mjs ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSidebar_DZb0qhUN_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSidebar_DZb0qhUN_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSidebar-DZb0qhUN.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSidebar-DZb0qhUN.mjs");




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
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-CWnkpNim_mjs":"4b496e25fdc85bcc8255","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-019035":"f005f04e196c41e04984"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "nextcloud:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
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
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files-sidebar": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files/src/sidebar.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-sidebar.js.map?v=7bd8097aad41924ac3d8