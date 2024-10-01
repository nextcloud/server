/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/systemtags/src/files_actions/inlineSystemTagsAction.ts":
/*!*********************************************************************!*\
  !*** ./apps/systemtags/src/files_actions/inlineSystemTagsAction.ts ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _css_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../css/fileEntryInlineSystemTags.scss */ "./apps/systemtags/src/css/fileEntryInlineSystemTags.scss");



const getNodeSystemTags = function (node) {
  const tags = node.attributes?.['system-tags']?.['system-tag'];
  if (tags === undefined) {
    return [];
  }
  return [tags].flat();
};
const renderTag = function (tag) {
  let isMore = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  const tagElement = document.createElement('li');
  tagElement.classList.add('files-list__system-tag');
  tagElement.textContent = tag;
  if (isMore) {
    tagElement.classList.add('files-list__system-tag--more');
  }
  return tagElement;
};
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'system-tags',
  displayName: () => '',
  iconSvgInline: () => '',
  enabled(nodes) {
    // Only show the action on single nodes
    if (nodes.length !== 1) {
      return false;
    }
    const node = nodes[0];
    const tags = getNodeSystemTags(node);
    // Only show the action if the node has system tags
    if (tags.length === 0) {
      return false;
    }
    return true;
  },
  exec: async () => null,
  async renderInline(node) {
    // Ensure we have the system tags as an array
    const tags = getNodeSystemTags(node);
    if (tags.length === 0) {
      return null;
    }
    const systemTagsElement = document.createElement('ul');
    systemTagsElement.classList.add('files-list__system-tags');
    systemTagsElement.setAttribute('aria-label', (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Assigned collaborative tags'));
    systemTagsElement.append(renderTag(tags[0]));
    if (tags.length === 2) {
      // Special case only two tags:
      // the overflow fake tag would take the same space as this, so render it
      systemTagsElement.append(renderTag(tags[1]));
    } else if (tags.length > 1) {
      // More tags than the one we're showing
      // So we add a overflow element indicating there are more tags
      const moreTagElement = renderTag('+' + (tags.length - 1), true);
      moreTagElement.setAttribute('title', tags.slice(1).join(', '));
      // because the title is not accessible we hide this element for screen readers (see alternative below)
      moreTagElement.setAttribute('aria-hidden', 'true');
      moreTagElement.setAttribute('role', 'presentation');
      systemTagsElement.append(moreTagElement);
      // For accessibility the tags are listed, as the title is not accessible
      // but those tags are visually hidden
      for (const tag of tags.slice(1)) {
        const tagElement = renderTag(tag);
        tagElement.classList.add('hidden-visually');
        systemTagsElement.append(tagElement);
      }
    }
    return systemTagsElement;
  },
  order: 0
});

/***/ }),

/***/ "./apps/systemtags/src/files_actions/openInFilesAction.ts":
/*!****************************************************************!*\
  !*** ./apps/systemtags/src/files_actions/openInFilesAction.ts ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: 'systemtags:open-in-files',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('systemtags', 'Open in Files'),
  iconSvgInline: () => '',
  enabled(nodes, view) {
    // Only for the system tags view
    if (view.id !== 'tags') {
      return false;
    }
    // Only for single nodes
    if (nodes.length !== 1) {
      return false;
    }
    // Do not open tags (keep the default action) and only open folders
    return nodes[0].attributes['is-tag'] !== true && nodes[0].type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder;
  },
  async exec(node) {
    let dir = node.dirname;
    if (node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
      dir = node.path;
    }
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files',
      fileid: String(node.fileid)
    }, {
      dir,
      openfile: 'true'
    });
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.DefaultType.HIDDEN
});

/***/ }),

/***/ "./apps/systemtags/src/files_views/systemtagsView.ts":
/*!***********************************************************!*\
  !*** ./apps/systemtags/src/files_views/systemtagsView.ts ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerSystemTagsView: () => (/* binding */ registerSystemTagsView)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _services_systemtags_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/systemtags.js */ "./apps/systemtags/src/services/systemtags.ts");
/* harmony import */ var _mdi_svg_svg_tag_multiple_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/tag-multiple.svg?raw */ "./node_modules/@mdi/svg/svg/tag-multiple.svg?raw");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




/**
 * Register the system tags files view
 */
function registerSystemTagsView() {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: 'tags',
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('systemtags', 'Tags'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('systemtags', 'List of tags and their associated files and folders.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('systemtags', 'No tags found'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('systemtags', 'Tags you have created will show up here.'),
    icon: _mdi_svg_svg_tag_multiple_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
    order: 25,
    getContents: _services_systemtags_js__WEBPACK_IMPORTED_MODULE_2__.getContents
  }));
}

/***/ }),

/***/ "./apps/systemtags/src/init.ts":
/*!*************************************!*\
  !*** ./apps/systemtags/src/init.ts ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _files_actions_inlineSystemTagsAction_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./files_actions/inlineSystemTagsAction.js */ "./apps/systemtags/src/files_actions/inlineSystemTagsAction.ts");
/* harmony import */ var _files_actions_openInFilesAction_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./files_actions/openInFilesAction.js */ "./apps/systemtags/src/files_actions/openInFilesAction.ts");
/* harmony import */ var _files_views_systemtagsView_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./files_views/systemtagsView.js */ "./apps/systemtags/src/files_views/systemtagsView.ts");
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerDavProperty)('nc:system-tags');
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_files_actions_inlineSystemTagsAction_js__WEBPACK_IMPORTED_MODULE_1__.action);
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(_files_actions_openInFilesAction_js__WEBPACK_IMPORTED_MODULE_2__.action);
(0,_files_views_systemtagsView_js__WEBPACK_IMPORTED_MODULE_3__.registerSystemTagsView)();

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

/***/ "./apps/systemtags/src/services/systemtags.ts":
/*!****************************************************!*\
  !*** ./apps/systemtags/src/services/systemtags.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./api */ "./apps/systemtags/src/services/api.ts");



const rootPath = '/systemtags';
const client = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetClient)();
const resultToNode = node => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davResultToNode)(node);
const formatReportPayload = tagId => `<?xml version="1.0"?>
<oc:filter-files ${(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getDavNameSpaces)()}>
	<d:prop>
		${(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getDavProperties)()}
	</d:prop>
	<oc:filter-rules>
		<oc:systemtag>${tagId}</oc:systemtag>
	</oc:filter-rules>
</oc:filter-files>`;
const tagToNode = function (tag) {
  return new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
    id: tag.id,
    source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${rootPath}/${tag.id}`,
    owner: String((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid ?? 'anonymous'),
    root: rootPath,
    displayname: tag.displayName,
    permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ,
    attributes: {
      ...tag,
      'is-tag': true
    }
  });
};
const getContents = async function () {
  let path = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '/';
  // List tags in the root
  const tagsCache = (await (0,_api__WEBPACK_IMPORTED_MODULE_2__.fetchTags)()).filter(tag => tag.userVisible);
  if (path === '/') {
    return {
      folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
        id: 0,
        source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${rootPath}`,
        owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid,
        root: rootPath,
        permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE
      }),
      contents: tagsCache.map(tagToNode)
    };
  }
  const tagId = parseInt(path.split('/', 2)[1]);
  const tag = tagsCache.find(tag => tag.id === tagId);
  if (!tag) {
    throw new Error('Tag not found');
  }
  const folder = tagToNode(tag);
  const contentsResponse = await client.getDirectoryContents(_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath, {
    details: true,
    // Only filter favorites if we're at the root
    data: formatReportPayload(tagId),
    headers: {
      // Patched in WebdavClient.ts
      method: 'REPORT'
    }
  });
  return {
    folder,
    contents: contentsResponse.data.map(resultToNode)
  };
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/systemtags/src/css/fileEntryInlineSystemTags.scss":
/*!*********************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/systemtags/src/css/fileEntryInlineSystemTags.scss ***!
  \*********************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.files-list__system-tags {
  --min-size: 32px;
  display: none;
  justify-content: center;
  align-items: center;
  min-width: calc(var(--min-size) * 2);
  max-width: 300px;
}

.files-list__system-tag {
  padding: 5px 10px;
  border: 1px solid;
  border-radius: var(--border-radius-pill);
  border-color: var(--color-border);
  color: var(--color-text-maxcontrast);
  height: var(--min-size);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 22px;
  text-align: center;
}
.files-list__system-tag--more {
  overflow: visible;
  text-overflow: initial;
}
.files-list__system-tag + .files-list__system-tag {
  margin-inline-start: 5px;
}

@media (min-width: 512px) {
  .files-list__system-tags {
    display: flex;
  }
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./apps/systemtags/src/css/fileEntryInlineSystemTags.scss":
/*!****************************************************************!*\
  !*** ./apps/systemtags/src/css/fileEntryInlineSystemTags.scss ***!
  \****************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./fileEntryInlineSystemTags.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/systemtags/src/css/fileEntryInlineSystemTags.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_fileEntryInlineSystemTags_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/@mdi/svg/svg/tag-multiple.svg?raw":
/*!********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/tag-multiple.svg?raw ***!
  \********************************************************/
/***/ ((module) => {

"use strict";
module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-tag-multiple\" viewBox=\"0 0 24 24\"><path d=\"M5.5,9A1.5,1.5 0 0,0 7,7.5A1.5,1.5 0 0,0 5.5,6A1.5,1.5 0 0,0 4,7.5A1.5,1.5 0 0,0 5.5,9M17.41,11.58C17.77,11.94 18,12.44 18,13C18,13.55 17.78,14.05 17.41,14.41L12.41,19.41C12.05,19.77 11.55,20 11,20C10.45,20 9.95,19.78 9.58,19.41L2.59,12.42C2.22,12.05 2,11.55 2,11V6C2,4.89 2.89,4 4,4H9C9.55,4 10.05,4.22 10.41,4.58L17.41,11.58M13.54,5.71L14.54,4.71L21.41,11.58C21.78,11.94 22,12.45 22,13C22,13.55 21.78,14.05 21.42,14.41L16.04,19.79L15.04,18.79L20.75,13L13.54,5.71Z\" /></svg>";

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
/******/ 		// The chunk loading function for additional chunks
/******/ 		// Since all referenced chunks are already included
/******/ 		// in this file, this function is empty here.
/******/ 		__webpack_require__.e = () => (Promise.resolve());
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
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"systemtags-init": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/systemtags/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=systemtags-init.js.map?v=9ec6c248fd006ff0a2fa