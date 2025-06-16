/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/comments/src/comments-app.js":
/*!*******************************************!*\
  !*** ./apps/comments/src/comments-app.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _services_CommentsInstance_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./services/CommentsInstance.js */ "./apps/comments/src/services/CommentsInstance.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



// Init Comments
if (window.OCA && !window.OCA.Comments) {
  Object.assign(window.OCA, {
    Comments: {}
  });
}

// Init Comments App view
Object.assign(window.OCA.Comments, {
  View: _services_CommentsInstance_js__WEBPACK_IMPORTED_MODULE_0__["default"]
});
console.debug('OCA.Comments.View initialized');

/***/ }),

/***/ "./apps/comments/src/components/Comment.vue":
/*!**************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/comments/src/components/Comment.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=script&lang=js":
/*!**************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!***********************************************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_style_index_0_id_5aee423d_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true":
/*!********************************************************************************************!*\
  !*** ./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comment_vue_vue_type_template_id_5aee423d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comment.vue?vue&type=template&id=5aee423d&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true");


/***/ }),

/***/ "./apps/comments/src/logger.js":
/*!*************************************!*\
  !*** ./apps/comments/src/logger.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('comments').detectUser().build());

/***/ }),

/***/ "./apps/comments/src/mixins/CommentMixin.js":
/*!**************************************************!*\
  !*** ./apps/comments/src/mixins/CommentMixin.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./apps/comments/src/mixins/CommentView.ts":
/*!*************************************************!*\
  !*** ./apps/comments/src/mixins/CommentView.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./apps/comments/src/services/CommentsInstance.js":
/*!********************************************************!*\
  !*** ./apps/comments/src/services/CommentsInstance.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ CommentInstance)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_Comments_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../views/Comments.vue */ "./apps/comments/src/views/Comments.vue");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../logger.js */ "./apps/comments/src/logger.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







vue__WEBPACK_IMPORTED_MODULE_4__["default"].use(pinia__WEBPACK_IMPORTED_MODULE_5__.PiniaVuePlugin);
// eslint-disable-next-line camelcase
__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();

// Add translates functions
vue__WEBPACK_IMPORTED_MODULE_4__["default"].mixin({
  data() {
    return {
      logger: _logger_js__WEBPACK_IMPORTED_MODULE_3__["default"]
    };
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
    n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.n
  }
});
class CommentInstance {
  /**
   * Initialize a new Comments instance for the desired type
   *
   * @param {string} resourceType the comments endpoint type
   * @param  {object} options the vue options (propsData, parent, el...)
   */
  constructor() {
    let resourceType = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'files';
    let options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
    const pinia = (0,pinia__WEBPACK_IMPORTED_MODULE_5__.createPinia)();

    // Merge options and set `resourceType` property
    options = {
      ...options,
      propsData: {
        ...(options.propsData ?? {}),
        resourceType
      },
      pinia
    };
    // Init Comments component
    const View = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_views_Comments_vue__WEBPACK_IMPORTED_MODULE_2__["default"]);
    return new View(options);
  }
}

/***/ }),

/***/ "./apps/comments/src/services/DavClient.js":
/*!*************************************************!*\
  !*** ./apps/comments/src/services/DavClient.js ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav/dist/node/index.js */ "./node_modules/webdav/dist/node/index.js");
/* harmony import */ var _utils_davUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/davUtils.js */ "./apps/comments/src/utils/davUtils.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





// init webdav client
const client = (0,webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__.createClient)((0,_utils_davUtils_js__WEBPACK_IMPORTED_MODULE_1__.getRootPath)());

// set CSRF token header
const setHeaders = token => {
  client.setHeaders({
    // Add this so the server knows it is an request from the browser
    'X-Requested-With': 'XMLHttpRequest',
    // Inject user auth
    requesttoken: token ?? ''
  });
};

// refresh headers when request token changes
(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.onRequestTokenUpdate)(setHeaders);
setHeaders((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getRequestToken)());
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (client);

/***/ }),

/***/ "./apps/comments/src/services/DeleteComment.js":
/*!*****************************************************!*\
  !*** ./apps/comments/src/services/DeleteComment.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

"use strict";
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

/***/ "./apps/comments/src/services/GetComments.ts":
/*!***************************************************!*\
  !*** ./apps/comments/src/services/GetComments.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DEFAULT_LIMIT: () => (/* binding */ DEFAULT_LIMIT),
/* harmony export */   getComments: () => (/* binding */ getComments)
/* harmony export */ });
/* harmony import */ var webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav/dist/node/index.js */ "./node_modules/webdav/dist/node/index.js");
/* harmony import */ var webdav_dist_node_response_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! webdav/dist/node/response.js */ "./node_modules/webdav/dist/node/response.js");
/* harmony import */ var webdav_dist_node_tools_dav_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! webdav/dist/node/tools/dav.js */ "./node_modules/webdav/dist/node/tools/dav.js");
/* harmony import */ var _DavClient_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./DavClient.js */ "./apps/comments/src/services/DavClient.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// https://github.com/perry-mitchell/webdav-client/issues/339



const DEFAULT_LIMIT = 20;
/**
 * Retrieve the comments list
 *
 * @param {object} data destructuring object
 * @param {string} data.resourceType the resource type
 * @param {number} data.resourceId the resource ID
 * @param {object} [options] optional options for axios
 * @param {number} [options.offset] the pagination offset
 * @param {number} [options.limit] the pagination limit, defaults to 20
 * @param {Date} [options.datetime] optional date to query
 * @return {{data: object[]}} the comments list
 */
const getComments = async function (_ref, options) {
  let {
    resourceType,
    resourceId
  } = _ref;
  const resourcePath = ['', resourceType, resourceId].join('/');
  const datetime = options.datetime ? `<oc:datetime>${options.datetime.toISOString()}</oc:datetime>` : '';
  const response = await _DavClient_js__WEBPACK_IMPORTED_MODULE_3__["default"].customRequest(resourcePath, Object.assign({
    method: 'REPORT',
    data: `<?xml version="1.0"?>
			<oc:filter-comments
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns"
				xmlns:nc="http://nextcloud.org/ns"
				xmlns:ocs="http://open-collaboration-services.org/ns">
				<oc:limit>${options.limit ?? DEFAULT_LIMIT}</oc:limit>
				<oc:offset>${options.offset || 0}</oc:offset>
				${datetime}
			</oc:filter-comments>`
  }, options));
  const responseData = await response.text();
  const result = await (0,webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__.parseXML)(responseData);
  const stat = getDirectoryFiles(result, true);
  return (0,webdav_dist_node_response_js__WEBPACK_IMPORTED_MODULE_1__.processResponsePayload)(response, stat, true);
};
// https://github.com/perry-mitchell/webdav-client/blob/8d9694613c978ce7404e26a401c39a41f125f87f/source/operations/directoryContents.ts
const getDirectoryFiles = function (result) {
  let isDetailed = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  // Extract the response items (directory contents)
  const {
    multistatus: {
      response: responseItems
    }
  } = result;
  // Map all items to a consistent output structure (results)
  return responseItems.map(item => {
    // Each item should contain a stat object
    const props = item.propstat.prop;
    return (0,webdav_dist_node_tools_dav_js__WEBPACK_IMPORTED_MODULE_2__.prepareFileFromProps)(props, props.id.toString(), isDetailed);
  });
};

/***/ }),

/***/ "./apps/comments/src/services/NewComment.js":
/*!**************************************************!*\
  !*** ./apps/comments/src/services/NewComment.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./apps/comments/src/services/ReadComments.ts":
/*!****************************************************!*\
  !*** ./apps/comments/src/services/ReadComments.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   markCommentsAsRead: () => (/* binding */ markCommentsAsRead)
/* harmony export */ });
/* harmony import */ var _DavClient_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DavClient.js */ "./apps/comments/src/services/DavClient.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Mark comments older than the date timestamp as read
 *
 * @param resourceType the resource type
 * @param resourceId the resource ID
 * @param date the date object
 */
const markCommentsAsRead = (resourceType, resourceId, date) => {
  const resourcePath = ['', resourceType, resourceId].join('/');
  const readMarker = date.toUTCString();
  return _DavClient_js__WEBPACK_IMPORTED_MODULE_0__["default"].customRequest(resourcePath, {
    method: 'PROPPATCH',
    data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:readMarker>${readMarker}</oc:readMarker>
				</d:prop>
			</d:set>
			</d:propertyupdate>`
  });
};

/***/ }),

/***/ "./apps/comments/src/store/deletedCommentLimbo.js":
/*!********************************************************!*\
  !*** ./apps/comments/src/store/deletedCommentLimbo.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./apps/comments/src/utils/cancelableRequest.js":
/*!******************************************************!*\
  !*** ./apps/comments/src/utils/cancelableRequest.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Creates a cancelable axios 'request object'.
 *
 * @param {Function} request the axios promise request
 * @return {object}
 */
const cancelableRequest = function (request) {
  const controller = new AbortController();
  const signal = controller.signal;

  /**
   * Execute the request
   *
   * @param {string} url the url to send the request to
   * @param {object} [options] optional config for the request
   */
  const fetch = async function (url, options) {
    const response = await request(url, Object.assign({
      signal
    }, options));
    return response;
  };
  return {
    request: fetch,
    abort: () => controller.abort()
  };
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (cancelableRequest);

/***/ }),

/***/ "./apps/comments/src/utils/davUtils.js":
/*!*********************************************!*\
  !*** ./apps/comments/src/utils/davUtils.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getRootPath: () => (/* binding */ getRootPath)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const getRootPath = function () {
  return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateRemoteUrl)('dav/comments');
};


/***/ }),

/***/ "./apps/comments/src/utils/decodeHtmlEntities.js":
/*!*******************************************************!*\
  !*** ./apps/comments/src/utils/decodeHtmlEntities.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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

/***/ "./apps/comments/src/views/Comments.vue":
/*!**********************************************!*\
  !*** ./apps/comments/src/views/Comments.vue ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true */ "./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true");
/* harmony import */ var _Comments_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Comments.vue?vue&type=script&lang=js */ "./apps/comments/src/views/Comments.vue?vue&type=script&lang=js");
/* harmony import */ var _Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true */ "./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Comments_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b0ddc2e8",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/comments/src/views/Comments.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/comments/src/views/Comments.vue?vue&type=script&lang=js":
/*!**********************************************************************!*\
  !*** ./apps/comments/src/views/Comments.vue?vue&type=script&lang=js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comments.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true":
/*!*******************************************************************************************************!*\
  !*** ./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true":
/*!****************************************************************************************!*\
  !*** ./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_template_id_b0ddc2e8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true");


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js":
/*!******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionSeparator */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTime */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_components_NcUserBubble__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcUserBubble */ "./node_modules/@nextcloud/vue/dist/Components/NcUserBubble.mjs");
/* harmony import */ var vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var _mixins_CommentMixin_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../mixins/CommentMixin.js */ "./apps/comments/src/mixins/CommentMixin.js");
/* harmony import */ var pinia__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! pinia */ "./node_modules/pinia/dist/pinia.mjs");
/* harmony import */ var _store_deletedCommentLimbo_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../store/deletedCommentLimbo.js */ "./apps/comments/src/store/deletedCommentLimbo.js");


















// Dynamic loading
const NcRichContenteditable = () => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("node_modules_nextcloud_vue_dist_Components_NcRichContenteditable_mjs")]).then(__webpack_require__.bind(__webpack_require__, /*! @nextcloud/vue/components/NcRichContenteditable */ "./node_modules/@nextcloud/vue/dist/Components/NcRichContenteditable.mjs"));
const NcRichText = () => __webpack_require__.e(/*! import() */ "core-common").then(__webpack_require__.bind(__webpack_require__, /*! @nextcloud/vue/components/NcRichText */ "./node_modules/@nextcloud/vue/dist/Components/NcRichText.mjs"));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Comment',
  components: {
    IconArrowRight: vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    IconClose: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    IconDelete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    IconEdit: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcActionSeparator: _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcDateTime: _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcRichContenteditable,
    NcRichText
  },
  mixins: [_mixins_CommentMixin_js__WEBPACK_IMPORTED_MODULE_14__["default"]],
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
    userData: {
      type: Object,
      default: () => ({})
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
    richContent() {
      const mentions = {};
      let message = this.localMessage;
      Object.keys(this.userData).forEach((user, index) => {
        const key = `mention-${index}`;
        const regex = new RegExp(`@${user}|@"${user}"`, 'g');
        message = message.replace(regex, `{${key}}`);
        mentions[key] = {
          component: _nextcloud_vue_components_NcUserBubble__WEBPACK_IMPORTED_MODULE_9__["default"],
          props: {
            user,
            displayName: this.userData[user].label,
            primary: this.userData[user].primary
          }
        };
      });
      return {
        mentions,
        message
      };
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=script&lang=js":
/*!**************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _vueuse_components__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var vue_material_design_icons_Refresh_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Refresh.vue */ "./node_modules/vue-material-design-icons/Refresh.vue");
/* harmony import */ var vue_material_design_icons_MessageReplyText_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/MessageReplyText.vue */ "./node_modules/vue-material-design-icons/MessageReplyText.vue");
/* harmony import */ var vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/AlertCircleOutline.vue */ "./node_modules/vue-material-design-icons/AlertCircleOutline.vue");
/* harmony import */ var _components_Comment_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../components/Comment.vue */ "./apps/comments/src/components/Comment.vue");
/* harmony import */ var _mixins_CommentView__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/CommentView */ "./apps/comments/src/mixins/CommentView.ts");
/* harmony import */ var _utils_cancelableRequest_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/cancelableRequest.js */ "./apps/comments/src/utils/cancelableRequest.js");
/* harmony import */ var _services_GetComments_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../services/GetComments.ts */ "./apps/comments/src/services/GetComments.ts");
/* harmony import */ var _services_ReadComments_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/ReadComments.ts */ "./apps/comments/src/services/ReadComments.ts");













/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Comments',
  components: {
    Comment: _components_Comment_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcEmptyContent: _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_3__["default"],
    RefreshIcon: vue_material_design_icons_Refresh_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    MessageReplyTextIcon: vue_material_design_icons_MessageReplyText_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    AlertCircleOutlineIcon: vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  directives: {
    elementVisibility: _vueuse_components__WEBPACK_IMPORTED_MODULE_12__.vElementVisibility
  },
  mixins: [_mixins_CommentView__WEBPACK_IMPORTED_MODULE_8__["default"]],
  data() {
    return {
      error: '',
      loading: false,
      done: false,
      currentResourceId: this.resourceId,
      offset: 0,
      comments: [],
      cancelRequest: () => {},
      Comment: _components_Comment_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
      userData: {}
    };
  },
  computed: {
    hasComments() {
      return this.comments.length > 0;
    },
    isFirstLoading() {
      return this.loading && this.offset === 0;
    }
  },
  watch: {
    resourceId() {
      this.currentResourceId = this.resourceId;
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
    async onVisibilityChange(isVisible) {
      if (isVisible) {
        try {
          await (0,_services_ReadComments_ts__WEBPACK_IMPORTED_MODULE_11__.markCommentsAsRead)(this.resourceType, this.currentResourceId, new Date());
        } catch (e) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(e.message || (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('comments', 'Failed to mark comments as read'));
        }
      }
    },
    /**
     * Update current resourceId and fetch new data
     *
     * @param {number} resourceId the current resourceId (fileId...)
     */
    async update(resourceId) {
      this.currentResourceId = resourceId;
      this.resetState();
      this.getComments();
    },
    /**
     * Ran when the bottom of the tab is reached
     */
    onScrollBottomReached() {
      /**
       * Do not fetch more if we:
       * - are showing an error
       * - already fetched everything
       * - are currently loading
       */
      if (this.error || this.done || this.loading) {
        return;
      }
      this.getComments();
    },
    /**
     * Get the existing shares infos
     */
    async getComments() {
      // Cancel any ongoing request
      this.cancelRequest('cancel');
      try {
        this.loading = true;
        this.error = '';

        // Init cancellable request
        const {
          request,
          abort
        } = (0,_utils_cancelableRequest_js__WEBPACK_IMPORTED_MODULE_9__["default"])(_services_GetComments_ts__WEBPACK_IMPORTED_MODULE_10__.getComments);
        this.cancelRequest = abort;

        // Fetch comments
        const {
          data: comments
        } = (await request({
          resourceType: this.resourceType,
          resourceId: this.currentResourceId
        }, {
          offset: this.offset
        })) || {
          data: []
        };
        this.logger.debug(`Processed ${comments.length} comments`, {
          comments
        });

        // We received less than the requested amount,
        // we're done fetching comments
        if (comments.length < _services_GetComments_ts__WEBPACK_IMPORTED_MODULE_10__.DEFAULT_LIMIT) {
          this.done = true;
        }

        // Insert results
        this.comments.push(...comments);

        // Increase offset for next fetch
        this.offset += _services_GetComments_ts__WEBPACK_IMPORTED_MODULE_10__.DEFAULT_LIMIT;
      } catch (error) {
        if (error.message === 'cancel') {
          return;
        }
        this.error = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('comments', 'Unable to load the comments list');
        console.error('Error loading the comments list', error);
      } finally {
        this.loading = false;
      }
    },
    /**
     * Add newly created comment to the list
     *
     * @param {object} comment the new comment
     */
    onNewComment(comment) {
      this.comments.unshift(comment);
    },
    /**
     * Remove deleted comment from the list
     *
     * @param {number} id the deleted comment
     */
    onDelete(id) {
      const index = this.comments.findIndex(comment => comment.props.id === id);
      if (index > -1) {
        this.comments.splice(index, 1);
      } else {
        console.error('Could not find the deleted comment in the list', id);
      }
    },
    /**
     * Reset the current view to its default state
     */
    resetState() {
      this.error = '';
      this.loading = false;
      this.done = false;
      this.offset = 0;
      this.comments = [];
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=template&id=5aee423d&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************/
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
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("comments", "@ for mentions, : for emoji, / for smart picker")) + "\n\t\t\t")])]) : _c("NcRichText", {
    staticClass: "comment__message",
    class: {
      "comment__message--expanded": _vm.expanded
    },
    attrs: {
      text: _vm.richContent.message,
      arguments: _vm.richContent.mentions
    },
    on: {
      click: _vm.onExpand
    }
  })], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=template&id=b0ddc2e8&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("div", {
    directives: [{
      name: "element-visibility",
      rawName: "v-element-visibility",
      value: _vm.onVisibilityChange,
      expression: "onVisibilityChange"
    }],
    staticClass: "comments",
    class: {
      "icon-loading": _vm.isFirstLoading
    }
  }, [_c("Comment", _vm._b({
    staticClass: "comments__writer",
    attrs: {
      "auto-complete": _vm.autoComplete,
      "resource-type": _vm.resourceType,
      editor: true,
      "user-data": _vm.userData,
      "resource-id": _vm.currentResourceId
    },
    on: {
      new: _vm.onNewComment
    }
  }, "Comment", _vm.editorData, false)), _vm._v(" "), !_vm.isFirstLoading ? [!_vm.hasComments && _vm.done ? _c("NcEmptyContent", {
    staticClass: "comments__empty",
    attrs: {
      name: _vm.t("comments", "No comments yet, start the conversation!")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("MessageReplyTextIcon")];
      },
      proxy: true
    }], null, false, 1033639148)
  }) : _c("ul", _vm._l(_vm.comments, function (comment) {
    return _c("Comment", _vm._b({
      key: comment.props.id,
      staticClass: "comments__list",
      attrs: {
        tag: "li",
        "auto-complete": _vm.autoComplete,
        "resource-type": _vm.resourceType,
        message: comment.props.message,
        "resource-id": _vm.currentResourceId,
        "user-data": _vm.genMentionsData(comment.props.mentions)
      },
      on: {
        "update:message": function ($event) {
          return _vm.$set(comment.props, "message", $event);
        },
        delete: _vm.onDelete
      }
    }, "Comment", comment.props, false));
  }), 1), _vm._v(" "), _vm.loading && !_vm.isFirstLoading ? _c("div", {
    staticClass: "comments__info icon-loading"
  }) : _vm.hasComments && _vm.done ? _c("div", {
    staticClass: "comments__info"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("comments", "No more messages")) + "\n\t\t")]) : _vm.error ? [_c("NcEmptyContent", {
    staticClass: "comments__error",
    attrs: {
      name: _vm.error
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("AlertCircleOutlineIcon")];
      },
      proxy: true
    }], null, false, 66050004)
  }), _vm._v(" "), _c("NcButton", {
    staticClass: "comments__retry",
    on: {
      click: _vm.getComments
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("RefreshIcon")];
      },
      proxy: true
    }], null, false, 3924573781)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("comments", "Retry")) + "\n\t\t\t")])] : _vm._e()] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
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
  word-break: normal;
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.comments[data-v-b0ddc2e8] {
  min-height: 100%;
  display: flex;
  flex-direction: column;
}
.comments__empty[data-v-b0ddc2e8], .comments__error[data-v-b0ddc2e8] {
  flex: 1 0;
}
.comments__retry[data-v-b0ddc2e8] {
  margin: 0 auto;
}
.comments__info[data-v-b0ddc2e8] {
  height: 60px;
  color: var(--color-text-maxcontrast);
  text-align: center;
  line-height: 60px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/components/Comment.vue?vue&type=style&index=0&id=5aee423d&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/comments/src/views/Comments.vue?vue&type=style&index=0&id=b0ddc2e8&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Comments_vue_vue_type_style_index_0_id_b0ddc2e8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "MessageReplyTextIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js":
/*!***********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "RefreshIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8":
/*!*******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8 ***!
  \*******************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon message-reply-text-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M18,8H6V6H18V8M18,11H6V9H18V11M18,14H6V12H18V14M22,4A2,2 0 0,0 20,2H4A2,2 0 0,0 2,4V16A2,2 0 0,0 4,18H18L22,22V4Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842":
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842 ***!
  \**********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon refresh-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-material-design-icons/MessageReplyText.vue":
/*!*********************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MessageReplyText.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MessageReplyText.vue?vue&type=template&id=e8e561c8 */ "./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8");
/* harmony import */ var _MessageReplyText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MessageReplyText.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _MessageReplyText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__.render,
  _MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/MessageReplyText.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_MessageReplyText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./MessageReplyText.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_MessageReplyText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8":
/*!***************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8 ***!
  \***************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MessageReplyText_vue_vue_type_template_id_e8e561c8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./MessageReplyText.vue?vue&type=template&id=e8e561c8 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MessageReplyText.vue?vue&type=template&id=e8e561c8");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Refresh.vue":
/*!************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Refresh.vue?vue&type=template&id=10301842 */ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842");
/* harmony import */ var _Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Refresh.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.render,
  _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Refresh.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js":
/*!************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Refresh.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842 ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Refresh.vue?vue&type=template&id=10301842 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842");


/***/ }),

/***/ "?0cc0":
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

/***/ "?3e83":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?4f7e":
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"0a21f85fb5edb886fad0","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"5414d4143400c9b713c3","node_modules_nextcloud_vue_dist_Components_NcRichContenteditable_mjs":"dfc68964914a15bcbd6e","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e":"87f84948225387ac2eec","node_modules_rehype-highlight_index_js":"3c5c32c691780bf457a0"}[chunkId] + "";
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
/******/ 		scriptUrl = scriptUrl.replace(/^blob:/, "").replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
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
/******/ 			"comments-comments-app": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/comments/src/comments-app.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=comments-comments-app.js.map?v=1da451bf1ca67d94c029