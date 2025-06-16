/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/profile/src/main.ts":
/*!**********************************!*\
  !*** ./apps/profile/src/main.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_Profile_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/Profile.vue */ "./apps/profile/src/views/Profile.vue");
/* harmony import */ var _services_ProfileSections_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./services/ProfileSections.js */ "./apps/profile/src/services/ProfileSections.ts");
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
if (!window.OCA) {
  window.OCA = {};
}
if (!window.OCA.Core) {
  window.OCA.Core = {};
}
Object.assign(window.OCA.Core, {
  ProfileSections: new _services_ProfileSections_js__WEBPACK_IMPORTED_MODULE_2__["default"]()
});
const View = vue__WEBPACK_IMPORTED_MODULE_3__["default"].extend(_views_Profile_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
window.addEventListener('DOMContentLoaded', () => {
  new View().$mount('#content');
});

/***/ }),

/***/ "./apps/profile/src/services/ProfileSections.ts":
/*!******************************************************!*\
  !*** ./apps/profile/src/services/ProfileSections.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ProfileSections)
/* harmony export */ });
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class ProfileSections {
  constructor() {
    _defineProperty(this, "_sections", void 0);
    this._sections = [];
  }
  /**
   * @param {registerSectionCallback} section To be called to mount the section to the profile page
   */
  registerSection(section) {
    this._sections.push(section);
  }
  getSections() {
    return this._sections;
  }
}

/***/ }),

/***/ "./apps/profile/src/views/Profile.vue":
/*!********************************************!*\
  !*** ./apps/profile/src/views/Profile.vue ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Profile.vue?vue&type=template&id=1e5d79c8&scoped=true */ "./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true");
/* harmony import */ var _Profile_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Profile.vue?vue&type=script&lang=ts */ "./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts");
/* harmony import */ var _Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true */ "./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Profile_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "1e5d79c8",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/profile/src/views/Profile.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts":
/*!********************************************************************!*\
  !*** ./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Profile.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true":
/*!*****************************************************************************************************!*\
  !*** ./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true":
/*!**************************************************************************************!*\
  !*** ./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_template_id_1e5d79c8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Profile.vue?vue&type=template&id=1e5d79c8&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true");


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionLink__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionLink */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppContent__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppContent */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcContent__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcContent */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcRichText__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcRichText */ "./node_modules/@nextcloud/vue/dist/Components/NcRichText.mjs");
/* harmony import */ var vue_material_design_icons_Account_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/Account.vue */ "./node_modules/vue-material-design-icons/Account.vue");
/* harmony import */ var vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/MapMarker.vue */ "./node_modules/vue-material-design-icons/MapMarker.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");


















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_17__.defineComponent)({
  name: 'Profile',
  components: {
    AccountIcon: vue_material_design_icons_Account_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    MapMarkerIcon: vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    NcActionLink: _nextcloud_vue_components_NcActionLink__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcAppContent: _nextcloud_vue_components_NcAppContent__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcContent: _nextcloud_vue_components_NcContent__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcEmptyContent: _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcRichText: _nextcloud_vue_components_NcRichText__WEBPACK_IMPORTED_MODULE_13__["default"],
    PencilIcon: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_16__["default"]
  },
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate
    };
  },
  data() {
    const profileParameters = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('profile', 'profileParameters', {
      userId: null,
      displayname: null,
      address: null,
      organisation: null,
      role: null,
      headline: null,
      biography: null,
      actions: [],
      isUserAvatarVisible: false,
      pronouns: null
    });
    return {
      ...profileParameters,
      status: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_2__.loadState)('profile', 'status', {}),
      sections: window.OCA.Core.ProfileSections.getSections()
    };
  },
  computed: {
    isCurrentUser() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)()?.uid === this.userId;
    },
    allActions() {
      return this.actions;
    },
    primaryAction() {
      if (this.allActions.length) {
        return this.allActions[0];
      }
      return null;
    },
    otherActions() {
      if (this.allActions.length > 1) {
        return this.allActions.slice(1);
      }
      return [];
    },
    settingsUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/user');
    },
    emptyProfileMessage() {
      return this.isCurrentUser ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('profile', 'You have not added any info yet') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('profile', '{user} has not added any info yet', {
        user: this.displayname || this.userId || ''
      });
    }
  },
  mounted() {
    // Set the user's displayname or userId in the page title and preserve the default title of "Nextcloud" at the end
    document.title = `${this.displayname || this.userId} - ${document.title}`;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.subscribe)('user_status:status.updated', this.handleStatusUpdate);
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.unsubscribe)('user_status:status.updated', this.handleStatusUpdate);
  },
  methods: {
    handleStatusUpdate(status) {
      if (this.isCurrentUser && status.userId === this.userId) {
        this.status = status;
      }
    },
    openStatusModal() {
      const statusMenuItem = document.querySelector('.user-status-menu-item');
      // Changing the user status is only enabled if you are the current user
      if (this.isCurrentUser) {
        if (statusMenuItem) {
          statusMenuItem.click();
        } else {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('profile', 'Error opening the user status modal, try hard refreshing the page'));
        }
      }
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=template&id=1e5d79c8&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("NcContent", {
    attrs: {
      "app-name": "profile"
    }
  }, [_c("NcAppContent", [_c("div", {
    staticClass: "profile__header"
  }, [_c("div", {
    staticClass: "profile__header__container"
  }, [_c("div", {
    staticClass: "profile__header__container__placeholder"
  }), _vm._v(" "), _c("div", {
    staticClass: "profile__header__container__displayname"
  }, [_c("h2", [_vm._v(_vm._s(_vm.displayname || _vm.userId))]), _vm._v(" "), _vm.pronouns ? _c("span", [_vm._v("·")]) : _vm._e(), _vm._v(" "), _vm.pronouns ? _c("span", {
    staticClass: "profile__header__container__pronouns"
  }, [_vm._v(_vm._s(_vm.pronouns))]) : _vm._e(), _vm._v(" "), _vm.isCurrentUser ? _c("NcButton", {
    attrs: {
      type: "primary",
      href: _vm.settingsUrl
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("PencilIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 4260349822)
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("profile", "Edit Profile")) + "\n\t\t\t\t\t")]) : _vm._e()], 1), _vm._v(" "), _vm.status.icon || _vm.status.message ? _c("NcButton", {
    attrs: {
      disabled: !_vm.isCurrentUser,
      type: _vm.isCurrentUser ? "tertiary" : "tertiary-no-background"
    },
    on: {
      click: _vm.openStatusModal
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.status.icon) + " " + _vm._s(_vm.status.message) + "\n\t\t\t\t")]) : _vm._e()], 1)]), _vm._v(" "), _c("div", {
    staticClass: "profile__wrapper"
  }, [_c("div", {
    staticClass: "profile__content"
  }, [_c("div", {
    staticClass: "profile__sidebar"
  }, [_c("NcAvatar", {
    staticClass: "avatar",
    class: {
      interactive: _vm.isCurrentUser
    },
    attrs: {
      user: _vm.userId,
      size: 180,
      "show-user-status": true,
      "show-user-status-compact": false,
      "disable-menu": true,
      "disable-tooltip": true,
      "is-no-user": !_vm.isUserAvatarVisible
    },
    nativeOn: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.openStatusModal.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "user-actions"
  }, [_vm.primaryAction ? _c("NcButton", {
    staticClass: "user-actions__primary",
    attrs: {
      type: "primary",
      href: _vm.primaryAction.target,
      icon: _vm.primaryAction.icon,
      target: _vm.primaryAction.id === "phone" ? "_self" : "_blank"
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("img", {
          staticClass: "user-actions__primary__icon",
          attrs: {
            src: _vm.primaryAction.icon,
            alt: ""
          }
        })];
      },
      proxy: true
    }], null, false, 1780240256)
  }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.primaryAction.title) + "\n\t\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcActions", {
    staticClass: "user-actions__other",
    attrs: {
      inline: 4
    }
  }, _vm._l(_vm.otherActions, function (action) {
    return _c("NcActionLink", {
      key: action.id,
      attrs: {
        "close-after-click": true,
        href: action.target,
        target: action.id === "phone" ? "_self" : "_blank"
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c("img", {
            staticClass: "user-actions__other__icon",
            attrs: {
              src: action.icon,
              alt: ""
            }
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t\t\t\t\t\t\t" + _vm._s(action.title) + "\n\t\t\t\t\t\t\t")]);
  }), 1)], 1)], 1), _vm._v(" "), _c("div", {
    staticClass: "profile__blocks"
  }, [_vm.organisation || _vm.role || _vm.address ? _c("div", {
    staticClass: "profile__blocks-details"
  }, [_vm.organisation || _vm.role ? _c("div", {
    staticClass: "detail"
  }, [_c("p", [_vm._v(_vm._s(_vm.organisation) + " "), _vm.organisation && _vm.role ? _c("span", [_vm._v("•")]) : _vm._e(), _vm._v(" " + _vm._s(_vm.role))])]) : _vm._e(), _vm._v(" "), _vm.address ? _c("div", {
    staticClass: "detail"
  }, [_c("p", [_c("MapMarkerIcon", {
    staticClass: "map-icon",
    attrs: {
      size: 16
    }
  }), _vm._v("\n\t\t\t\t\t\t\t\t" + _vm._s(_vm.address) + "\n\t\t\t\t\t\t\t")], 1)]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.headline || _vm.biography || _vm.sections.length > 0 ? [_vm.headline ? _c("h3", {
    staticClass: "profile__blocks-headline"
  }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.headline) + "\n\t\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.biography ? _c("NcRichText", {
    attrs: {
      text: _vm.biography,
      "use-extended-markdown": ""
    }
  }) : _vm._e(), _vm._v(" "), _vm._l(_vm.sections, function (section, index) {
    return _c("div", {
      key: index,
      ref: "section-" + index,
      refInFor: true,
      staticClass: "profile__additionalContent"
    }, [_c(section(_vm.$refs["section-" + index], _vm.userId), {
      tag: "component",
      attrs: {
        "user-id": _vm.userId
      }
    })], 1);
  })] : _c("NcEmptyContent", {
    staticClass: "profile__blocks-empty-info",
    attrs: {
      name: _vm.emptyProfileMessage,
      description: _vm.t("profile", "The headline and about sections will show up here")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("AccountIcon", {
          attrs: {
            size: 60
          }
        })];
      },
      proxy: true
    }])
  })], 2)])])])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `[data-v-1e5d79c8] #app-content-vue {
  background-color: unset;
}
.profile[data-v-1e5d79c8] {
  width: 100%;
  overflow-y: auto;
}
.profile__header[data-v-1e5d79c8] {
  display: flex;
  position: sticky;
  height: 190px;
  top: -40px;
  background-color: var(--color-main-background-blur);
  backdrop-filter: var(--filter-background-blur);
  -webkit-backdrop-filter: var(--filter-background-blur);
}
.profile__header__container[data-v-1e5d79c8] {
  align-self: flex-end;
  width: 100%;
  max-width: 1024px;
  margin: 8px auto;
  row-gap: 8px;
  display: grid;
  grid-template-rows: max-content max-content;
  grid-template-columns: 240px 1fr;
  justify-content: center;
}
.profile__header__container__placeholder[data-v-1e5d79c8] {
  grid-row: 1/3;
}
.profile__header__container__displayname[data-v-1e5d79c8] {
  padding-inline: 16px;
  width: 640px;
  height: 45px;
  margin-block: 125px 0;
  display: flex;
  align-items: center;
  gap: 18px;
}
.profile__header__container__displayname h2[data-v-1e5d79c8] {
  font-size: 30px;
  margin: 0;
}
.profile__header__container__displayname span[data-v-1e5d79c8] {
  font-size: 20px;
}
.profile__sidebar[data-v-1e5d79c8] {
  position: sticky;
  top: 0;
  align-self: flex-start;
  padding-top: 20px;
  min-width: 220px;
  margin-block: -150px 0;
  margin-inline: 0 20px;
}
.profile__sidebar[data-v-1e5d79c8] .avatar.avatardiv {
  text-align: center;
  margin: auto;
  display: block;
  padding: 8px;
}
.profile__sidebar[data-v-1e5d79c8] .avatar.avatardiv.interactive .avatardiv__user-status {
  cursor: pointer;
}
.profile__sidebar[data-v-1e5d79c8] .avatar.avatardiv .avatardiv__user-status {
  inset-inline-end: 14px;
  bottom: 14px;
  width: 34px;
  height: 34px;
  background-size: 28px;
  border: none;
  background-color: var(--color-main-background);
  line-height: 34px;
  font-size: 20px;
}
.profile__wrapper[data-v-1e5d79c8] {
  background-color: var(--color-main-background);
  min-height: 100%;
}
.profile__content[data-v-1e5d79c8] {
  max-width: 1024px;
  margin: 0 auto;
  display: flex;
  width: 100%;
}
.profile__blocks[data-v-1e5d79c8] {
  margin: 18px 0 80px 0;
  display: grid;
  gap: 16px 0;
  width: 640px;
}
.profile__blocks p[data-v-1e5d79c8], .profile__blocks h3[data-v-1e5d79c8] {
  cursor: text;
  overflow-wrap: anywhere;
}
.profile__blocks-details[data-v-1e5d79c8] {
  display: flex;
  flex-direction: column;
  gap: 2px 0;
}
.profile__blocks-details .detail[data-v-1e5d79c8] {
  display: inline-block;
  color: var(--color-text-maxcontrast);
}
.profile__blocks-details .detail p .map-icon[data-v-1e5d79c8] {
  display: inline-block;
  vertical-align: middle;
}
.profile__blocks-headline[data-v-1e5d79c8] {
  margin-inline: 0;
  margin-block: 10px 0;
  font-weight: bold;
  font-size: 20px;
}
@media only screen and (max-width: 1024px) {
.profile__header[data-v-1e5d79c8] {
    height: 250px;
    position: unset;
}
.profile__header__container[data-v-1e5d79c8] {
    grid-template-columns: unset;
    margin-bottom: 110px;
}
.profile__header__container__displayname[data-v-1e5d79c8] {
    margin: 80px 20px 0px 0px !important;
    width: unset;
    text-align: center;
    padding-inline: 12px;
}
.profile__header__container__edit-button[data-v-1e5d79c8] {
    width: fit-content;
    display: block;
    margin: 60px auto;
}
.profile__header__container__status-text[data-v-1e5d79c8] {
    margin: 4px auto;
}
.profile__content[data-v-1e5d79c8] {
    display: block;
}
.profile__content .avatar[data-v-1e5d79c8] {
    margin-top: -110px !important;
}
.profile__blocks[data-v-1e5d79c8] {
    width: unset;
    max-width: 600px;
    margin: 0 auto;
    padding: 20px 50px 50px 50px;
}
.profile__sidebar[data-v-1e5d79c8] {
    margin: unset;
    position: unset;
}
}
.user-actions[data-v-1e5d79c8] {
  display: flex;
  flex-direction: column;
  gap: 8px 0;
  margin-top: 20px;
}
.user-actions__primary[data-v-1e5d79c8] {
  margin: 0 auto;
}
.user-actions__primary__icon[data-v-1e5d79c8] {
  filter: var(--primary-invert-if-dark);
}
.user-actions__other[data-v-1e5d79c8] {
  display: flex;
  justify-content: center;
  gap: 0 4px;
}
.user-actions__other__icon[data-v-1e5d79c8] {
  height: 20px;
  width: 20px;
  object-fit: contain;
  filter: var(--background-invert-if-dark);
  align-self: center;
  margin: 12px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/profile/src/views/Profile.vue?vue&type=style&index=0&id=1e5d79c8&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Profile_vue_vue_type_style_index_0_id_1e5d79c8_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js":
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "MapMarkerIcon",
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

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c":
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c ***!
  \************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
        staticClass: "material-design-icon map-marker-icon",
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
                d: "M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z",
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

/***/ "./node_modules/vue-material-design-icons/MapMarker.vue":
/*!**************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MapMarker.vue ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MapMarker.vue?vue&type=template&id=c80f3d8c */ "./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c");
/* harmony import */ var _MapMarker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MapMarker.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _MapMarker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__.render,
  _MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/MapMarker.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js":
/*!**************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_MapMarker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./MapMarker.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_MapMarker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c":
/*!********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_MapMarker_vue_vue_type_template_id_c80f3d8c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./MapMarker.vue?vue&type=template&id=c80f3d8c */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/MapMarker.vue?vue&type=template&id=c80f3d8c");


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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"0a21f85fb5edb886fad0","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"5414d4143400c9b713c3","node_modules_rehype-highlight_index_js":"3c5c32c691780bf457a0","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e":"87f84948225387ac2eec"}[chunkId] + "";
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
/******/ 			"profile-main": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/profile/src/main.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=profile-main.js.map?v=beb9ceae24c0dfee6cab