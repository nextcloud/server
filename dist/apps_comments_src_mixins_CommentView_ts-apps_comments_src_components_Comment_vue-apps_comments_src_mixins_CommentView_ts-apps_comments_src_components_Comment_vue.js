"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_comments_src_mixins_CommentView_ts-apps_comments_src_components_Comment_vue"],{

/***/ "./apps/comments/src/mixins/CommentMixin.js":
/*!**************************************************!*\
  !*** ./apps/comments/src/mixins/CommentMixin.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _services_NewComment_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/NewComment.js */ "./apps/comments/src/services/NewComment.js");
/* harmony import */ var _services_DeleteComment_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/DeleteComment.js */ "./apps/comments/src/services/DeleteComment.js");
/* harmony import */ var _services_EditComment_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/EditComment.js */ "./apps/comments/src/services/EditComment.js");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _store_deletedCommentLimbo_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../store/deletedCommentLimbo.js */ "./apps/comments/src/store/deletedCommentLimbo.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.js */ "./apps/comments/src/logger.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  props: {
    id: {
      type: Number,
      default: null
    },
    message: {
      type: String,
      default: ''
    },
    resourceId: {
      type: [String, Number],
      required: true
    },
    resourceType: {
      type: String,
      default: 'files'
    }
  },
  data() {
    return {
      deleted: false,
      editing: false,
      loading: false
    };
  },
  computed: {
    ...(0,pinia__WEBPACK_IMPORTED_MODULE_6__.mapStores)(_store_deletedCommentLimbo_js__WEBPACK_IMPORTED_MODULE_4__.useDeletedCommentLimbo)
  },
  methods: {
    // EDITION
    onEdit() {
      this.editing = true;
    },
    onEditCancel() {
      this.editing = false;
      // Restore original value
      this.updateLocalMessage(this.message);
    },
    async onEditComment(message) {
      this.loading = true;
      try {
        await (0,_services_EditComment_js__WEBPACK_IMPORTED_MODULE_3__["default"])(this.resourceType, this.resourceId, this.id, message);
        _logger_js__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Comment edited', {
          resourceType: this.resourceType,
          resourceId: this.resourceId,
          id: this.id,
          message
        });
        this.$emit('update:message', message);
        this.editing = false;
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('comments', 'An error occurred while trying to edit the comment'));
        console.error(error);
      } finally {
        this.loading = false;
      }
    },
    // DELETION
    onDeleteWithUndo() {
      this.$emit('delete');
      this.deleted = true;
      this.deletedCommentLimboStore.addId(this.id);
      const timeOutDelete = setTimeout(this.onDelete, _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.TOAST_UNDO_TIMEOUT);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showUndo)(t('comments', 'Comment deleted'), () => {
        clearTimeout(timeOutDelete);
        this.deleted = false;
        this.deletedCommentLimboStore.removeId(this.id);
      });
    },
    async onDelete() {
      try {
        await (0,_services_DeleteComment_js__WEBPACK_IMPORTED_MODULE_2__["default"])(this.resourceType, this.resourceId, this.id);
        _logger_js__WEBPACK_IMPORTED_MODULE_5__["default"].debug('Comment deleted', {
          resourceType: this.resourceType,
          resourceId: this.resourceId,
          id: this.id
        });
        this.$emit('delete', this.id);
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('comments', 'An error occurred while trying to delete the comment'));
        console.error(error);
        this.deleted = false;
        this.deletedCommentLimboStore.removeId(this.id);
      }
    },
    // CREATION
    async onNewComment(message) {
      this.loading = true;
      try {
        const newComment = await (0,_services_NewComment_js__WEBPACK_IMPORTED_MODULE_1__["default"])(this.resourceType, this.resourceId, message);
        _logger_js__WEBPACK_IMPORTED_MODULE_5__["default"].debug('New comment posted', {
          resourceType: this.resourceType,
          resourceId: this.resourceId,
          newComment
        });
        this.$emit('new', newComment);

        // Clear old content
        this.$emit('update:message', '');
        this.localMessage = '';
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('comments', 'An error occurred while trying to create the comment'));
        console.error(error);
      } finally {
        this.loading = false;
      }
    }
  }
});

/***/ }),

/***/ "./apps/comments/src/services/DeleteComment.js":
/*!*****************************************************!*\
  !*** ./apps/comments/src/services/DeleteComment.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _DavClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DavClient.js */ "./apps/comments/src/services/DavClient.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * Delete a comment
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {number} commentId the comment iD
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__(resourceType, resourceId, commentId) {
  const commentPath = ['', resourceType, resourceId, commentId].join('/');

  // Fetch newly created comment data
  await _DavClient_js__WEBPACK_IMPORTED_MODULE_0__["default"].deleteFile(commentPath);
}

/***/ }),

/***/ "./apps/comments/src/services/EditComment.js":
/*!***************************************************!*\
  !*** ./apps/comments/src/services/EditComment.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _DavClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DavClient.js */ "./apps/comments/src/services/DavClient.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 * Edit an existing comment
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {number} commentId the comment iD
 * @param {string} message the message content
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__(resourceType, resourceId, commentId, message) {
  const commentPath = ['', resourceType, resourceId, commentId].join('/');
  return await _DavClient_js__WEBPACK_IMPORTED_MODULE_0__["default"].customRequest(commentPath, Object.assign({
    method: 'PROPPATCH',
    data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:message>${message}</oc:message>
				</d:prop>
			</d:set>
			</d:propertyupdate>`
  }));
}

/***/ }),

/***/ "./apps/comments/src/services/NewComment.js":
/*!**************************************************!*\
  !*** ./apps/comments/src/services/NewComment.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _utils_davUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/davUtils.js */ "./apps/comments/src/utils/davUtils.js");
/* harmony import */ var _utils_decodeHtmlEntities_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/decodeHtmlEntities.js */ "./apps/comments/src/utils/decodeHtmlEntities.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _DavClient_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DavClient.js */ "./apps/comments/src/services/DavClient.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







/**
 * Retrieve the comments list
 *
 * @param {string} resourceType the resource type
 * @param {number} resourceId the resource ID
 * @param {string} message the message
 * @return {object} the new comment
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__(resourceType, resourceId, message) {
  const resourcePath = ['', resourceType, resourceId].join('/');
  const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].post((0,_utils_davUtils_js__WEBPACK_IMPORTED_MODULE_1__.getRootPath)() + resourcePath, {
    actorDisplayName: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().displayName,
    actorId: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid,
    actorType: 'users',
    creationDateTime: new Date().toUTCString(),
    message,
    objectType: resourceType,
    verb: 'comment'
  });

  // Retrieve comment id from resource location
  const commentId = parseInt(response.headers['content-location'].split('/').pop());
  const commentPath = resourcePath + '/' + commentId;

  // Fetch newly created comment data
  const comment = await _DavClient_js__WEBPACK_IMPORTED_MODULE_4__["default"].stat(commentPath, {
    details: true
  });
  const props = comment.data.props;
  // Decode twice to handle potentially double-encoded entities
  // FIXME Remove this once https://github.com/nextcloud/server/issues/29306
  // is resolved
  props.actorDisplayName = (0,_utils_decodeHtmlEntities_js__WEBPACK_IMPORTED_MODULE_2__.decodeHtmlEntities)(props.actorDisplayName, 2);
  props.message = (0,_utils_decodeHtmlEntities_js__WEBPACK_IMPORTED_MODULE_2__.decodeHtmlEntities)(props.message, 2);
  return comment.data;
}

/***/ }),

/***/ "./apps/comments/src/store/deletedCommentLimbo.js":
/*!********************************************************!*\
  !*** ./apps/comments/src/store/deletedCommentLimbo.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useDeletedCommentLimbo: () => (/* binding */ useDeletedCommentLimbo)
/* harmony export */ });
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const useDeletedCommentLimbo = (0,pinia__WEBPACK_IMPORTED_MODULE_0__.defineStore)('deletedCommentLimbo', {
  state: () => ({
    idsInLimbo: []
  }),
  actions: {
    addId(id) {
      this.idsInLimbo.push(id);
    },
    removeId(id) {
      const index = this.idsInLimbo.indexOf(id);
      if (index > -1) {
        this.idsInLimbo.splice(index, 1);
      }
    },
    checkForId(id) {
      this.idsInLimbo.includes(id);
    }
  }
});

/***/ }),

/***/ "./apps/comments/src/utils/decodeHtmlEntities.js":
/*!*******************************************************!*\
  !*** ./apps/comments/src/utils/decodeHtmlEntities.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   decodeHtmlEntities: () => (/* binding */ decodeHtmlEntities)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {any} value -
 * @param {any} passes -
 */
function decodeHtmlEntities(value) {
  let passes = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 1;
  const parser = new DOMParser();
  let decoded = value;
  for (let i = 0; i < passes; i++) {
    decoded = parser.parseFromString(decoded, 'text/html').documentElement.textContent;
  }
  return decoded;
}

/***/ }),

/***/ "./apps/comments/src/mixins/CommentView.ts":
/*!*************************************************!*\
  !*** ./apps/comments/src/mixins/CommentView.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_4__.defineComponent)({
  props: {
    resourceId: {
      type: Number,
      required: true
    },
    resourceType: {
      type: String,
      default: 'files'
    }
  },
  data() {
    return {
      editorData: {
        actorDisplayName: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().displayName,
        actorId: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid,
        key: 'editor'
      },
      userData: {}
    };
  },
  methods: {
    /**
     * Autocomplete @mentions
     *
     * @param {string} search the query
     * @param {Function} callback the callback to process the results with
     */
    async autoComplete(search, callback) {
      const {
        data
      } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateOcsUrl)('core/autocomplete/get'), {
        params: {
          search,
          itemType: 'files',
          itemId: this.resourceId,
          sorter: 'commenters|share-recipients',
          limit: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('comments', 'maxAutoCompleteResults')
        }
      });
      // Save user data so it can be used by the editor to replace mentions
      data.ocs.data.forEach(user => {
        this.userData[user.id] = user;
      });
      return callback(Object.values(this.userData));
    },
    /**
     * Make sure we have all mentions as Array of objects
     *
     * @param mentions the mentions list
     */
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    genMentionsData(mentions) {
      Object.values(mentions).flat().forEach(mention => {
        this.userData[mention.mentionId] = {
          // TODO: support groups
          icon: 'icon-user',
          id: mention.mentionId,
          label: mention.mentionDisplayName,
          source: 'users',
          primary: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)()?.uid === mention.mentionId
        };
      });
      return this.userData;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionSeparator.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTime.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_dist_Mixins_richEditor_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Mixins/richEditor.js */ "./node_modules/@nextcloud/vue/dist/Mixins/richEditor.mjs");
/* harmony import */ var vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var _mixins_CommentMixin_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../mixins/CommentMixin.js */ "./apps/comments/src/mixins/CommentMixin.js");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _store_deletedCommentLimbo_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../store/deletedCommentLimbo.js */ "./apps/comments/src/store/deletedCommentLimbo.js");


















// Dynamic loading
const NcRichContenteditable = () => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("node_modules_nextcloud_vue_dist_Components_NcRichContenteditable_mjs")]).then(__webpack_require__.bind(__webpack_require__, /*! @nextcloud/vue/dist/Components/NcRichContenteditable.js */ "./node_modules/@nextcloud/vue/dist/Components/NcRichContenteditable.mjs"));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Comment',
  components: {
    IconArrowRight: vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    IconClose: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    IconDelete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    IconEdit: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcActionSeparator: _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcDateTime: _nextcloud_vue_dist_Components_NcDateTime_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcRichContenteditable
  },
  mixins: [_nextcloud_vue_dist_Mixins_richEditor_js__WEBPACK_IMPORTED_MODULE_9__["default"], _mixins_CommentMixin_js__WEBPACK_IMPORTED_MODULE_14__["default"]],
  inheritAttrs: false,
  props: {
    actorDisplayName: {
      type: String,
      required: true
    },
    actorId: {
      type: String,
      required: true
    },
    creationDateTime: {
      type: String,
      default: null
    },
    /**
     * Force the editor display
     */
    editor: {
      type: Boolean,
      default: false
    },
    /**
     * Provide the autocompletion data
     */
    autoComplete: {
      type: Function,
      required: true
    },
    tag: {
      type: String,
      default: 'div'
    }
  },
  data() {
    return {
      expanded: false,
      // Only change data locally and update the original
      // parent data when the request is sent and resolved
      localMessage: '',
      submitted: false
    };
  },
  computed: {
    ...(0,pinia__WEBPACK_IMPORTED_MODULE_16__.mapStores)(_store_deletedCommentLimbo_js__WEBPACK_IMPORTED_MODULE_15__.useDeletedCommentLimbo),
    /**
     * Is the current user the author of this comment
     *
     * @return {boolean}
     */
    isOwnComment() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid === this.actorId;
    },
    /**
     * Rendered content as html string
     *
     * @return {string}
     */
    renderedContent() {
      if (this.isEmptyMessage) {
        return '';
      }
      return this.renderContent(this.localMessage);
    },
    isEmptyMessage() {
      return !this.localMessage || this.localMessage.trim() === '';
    },
    /**
     * Timestamp of the creation time (in ms UNIX time)
     */
    timestamp() {
      return Date.parse(this.creationDateTime);
    },
    isLimbo() {
      return this.deletedCommentLimboStore.checkForId(this.id);
    }
  },
  watch: {
    // If the data change, update the local value
    message(message) {
      this.updateLocalMessage(message);
    }
  },
  beforeMount() {
    // Init localMessage
    this.updateLocalMessage(this.message);
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
    /**
     * Update local Message on outer change
     *
     * @param {string} message the message to set
     */
    updateLocalMessage(message) {
      this.localMessage = message.toString();
      this.submitted = false;
    },
    /**
     * Dispatch message between edit and create
     */
    onSubmit() {
      // Do not submit if message is empty
      if (this.localMessage.trim() === '') {
        return;
      }
      if (this.editor) {
        this.onNewComment(this.localMessage.trim());
        this.$nextTick(() => {
          // Focus the editor again
          this.$refs.editor.$el.focus();
        });
        return;
      }
      this.onEditComment(this.localMessage.trim());
    },
    onExpand() {
      this.expanded = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.tag, {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.deleted && !_vm.isLimbo,
      expression: "!deleted && !isLimbo"
    }],
    tag: "component",
    staticClass: "comment",
    class: {
      "comment--loading": _vm.loading
    }
  }, [_c("div", {
    staticClass: "comment__side"
  }, [_c("NcAvatar", {
    staticClass: "comment__avatar",
    attrs: {
      "display-name": _vm.actorDisplayName,
      user: _vm.actorId,
      size: 32
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "comment__body"
  }, [_c("div", {
    staticClass: "comment__header"
  }, [_c("span", {
    staticClass: "comment__author"
  }, [_vm._v(_vm._s(_vm.actorDisplayName))]), _vm._v(" "), _vm.isOwnComment && _vm.id && !_vm.loading ? _c("NcActions", {
    staticClass: "comment__actions"
  }, [!_vm.editing ? [_c("NcActionButton", {
    attrs: {
      "close-after-click": ""
    },
    on: {
      click: _vm.onEdit
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconEdit", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 649782975)
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("comments", "Edit comment")) + "\n\t\t\t\t\t")]), _vm._v(" "), _c("NcActionSeparator"), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      "close-after-click": ""
    },
    on: {
      click: _vm.onDeleteWithUndo
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconDelete", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 881161434)
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("comments", "Delete comment")) + "\n\t\t\t\t\t")])] : _c("NcActionButton", {
    on: {
      click: _vm.onEditCancel
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconClose", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2888946197)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("comments", "Cancel edit")) + "\n\t\t\t\t")])], 2) : _vm._e(), _vm._v(" "), _vm.id && _vm.loading ? _c("div", {
    staticClass: "comment_loading icon-loading-small"
  }) : _vm.creationDateTime ? _c("NcDateTime", {
    staticClass: "comment__timestamp",
    attrs: {
      timestamp: _vm.timestamp,
      "ignore-seconds": true
    }
  }) : _vm._e()], 1), _vm._v(" "), _vm.editor || _vm.editing ? _c("form", {
    staticClass: "comment__editor",
    on: {
      submit: function ($event) {
        $event.preventDefault();
      }
    }
  }, [_c("div", {
    staticClass: "comment__editor-group"
  }, [_c("NcRichContenteditable", {
    ref: "editor",
    attrs: {
      "auto-complete": _vm.autoComplete,
      contenteditable: !_vm.loading,
      label: _vm.editor ? _vm.t("comments", "New comment") : _vm.t("comments", "Edit comment"),
      placeholder: _vm.t("comments", "Write a comment …"),
      value: _vm.localMessage,
      "user-data": _vm.userData,
      "aria-describedby": "tab-comments__editor-description"
    },
    on: {
      "update:value": _vm.updateLocalMessage,
      submit: _vm.onSubmit
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "comment__submit"
  }, [_c("NcButton", {
    attrs: {
      type: "tertiary-no-background",
      "native-type": "submit",
      "aria-label": _vm.t("comments", "Post comment"),
      disabled: _vm.isEmptyMessage
    },
    on: {
      click: _vm.onSubmit
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.loading ? _c("NcLoadingIcon") : _c("IconArrowRight", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 758946661)
  })], 1)], 1), _vm._v(" "), _c("div", {
    staticClass: "comment__editor-description",
    attrs: {
      id: "tab-comments__editor-description"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("comments", "@ for mentions, : for emoji, / for smart picker")) + "\n\t\t\t")])]) : _c("div", {
    staticClass: "comment__message",
    class: {
      "comment__message--expanded": _vm.expanded
    },
    domProps: {
      innerHTML: _vm._s(_vm.renderedContent)
    },
    on: {
      click: _vm.onExpand
    }
  })])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

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
___CSS_LOADER_EXPORT___.push([module.id, `.comment[data-v-5aee423d] {
  display: flex;
  gap: 8px;
  padding: 5px 10px;
}
.comment__side[data-v-5aee423d] {
  display: flex;
  align-items: flex-start;
  padding-top: 6px;
}
.comment__body[data-v-5aee423d] {
  display: flex;
  flex-grow: 1;
  flex-direction: column;
}
.comment__header[data-v-5aee423d] {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.comment__actions[data-v-5aee423d] {
  margin-inline-start: 10px !important;
}
.comment__author[data-v-5aee423d] {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--color-text-maxcontrast);
}
.comment_loading[data-v-5aee423d], .comment__timestamp[data-v-5aee423d] {
  margin-inline-start: auto;
  text-align: end;
  white-space: nowrap;
  color: var(--color-text-maxcontrast);
}
.comment__editor-group[data-v-5aee423d] {
  position: relative;
}
.comment__editor-description[data-v-5aee423d] {
  color: var(--color-text-maxcontrast);
  padding-block: var(--default-grid-baseline);
}
.comment__submit[data-v-5aee423d] {
  position: absolute !important;
  bottom: 5px;
  inset-inline-end: 0;
}
.comment__message[data-v-5aee423d] {
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 70px;
  overflow: hidden;
  margin-top: -6px;
}
.comment__message--expanded[data-v-5aee423d] {
  max-height: none;
  overflow: visible;
}
.rich-contenteditable__input[data-v-5aee423d] {
  min-height: 44px;
  margin: 0;
  padding: 10px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/comments/src/components/Comment.vue":
/*!**************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Comment.vue?vue&type=template&id=5aee423d&scoped=true */ "./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true");
/* harmony import */ var _Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Comment.vue?vue&type=script&lang=js */ "./apps/comments/src/components/Comment.vue?vue&type=script&lang=js");
/* harmony import */ var _Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true */ "./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5aee423d",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/comments/src/components/Comment.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=script&lang=js":
/*!**************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true":
/*!********************************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=template&id=5aee423d&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true");


/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!***********************************************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true");


/***/ })

}]);
//# sourceMappingURL=apps_comments_src_mixins_CommentView_ts-apps_comments_src_components_Comment_vue-apps_comments_src_mixins_CommentView_ts-apps_comments_src_components_Comment_vue.js.map?v=e572fa335743f760a679