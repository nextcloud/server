"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_settings_src_components_AppStoreDiscover_PostType_vue"],{

/***/ "./apps/settings/src/components/AppStoreDiscover/common.ts":
/*!*****************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/common.ts ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   commonAppDiscoverProps: () => (/* binding */ commonAppDiscoverProps)
/* harmony export */ });
/* harmony import */ var _constants_AppDiscoverTypes_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../constants/AppDiscoverTypes.ts */ "./apps/settings/src/constants/AppDiscoverTypes.ts");

/**
 * Common Props for all app discover types
 */
const commonAppDiscoverProps = {
  type: {
    type: String,
    required: true,
    validator: v => typeof v === 'string' && _constants_AppDiscoverTypes_ts__WEBPACK_IMPORTED_MODULE_0__.APP_DISCOVER_KNOWN_TYPES.includes(v)
  },
  id: {
    type: String,
    required: true
  },
  date: {
    type: Number,
    required: false,
    default: undefined
  },
  expiryDate: {
    type: Number,
    required: false,
    default: undefined
  },
  headline: {
    type: Object,
    required: false,
    default: () => null
  },
  link: {
    type: String,
    required: false,
    default: () => null
  }
};

/***/ }),

/***/ "./apps/settings/src/composables/useGetLocalizedValue.ts":
/*!***************************************************************!*\
  !*** ./apps/settings/src/composables/useGetLocalizedValue.ts ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useLocalizedValue: () => (/* binding */ useLocalizedValue)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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


/**
 * Helper to get the localized value for the current users language
 * @param dict The dictionary to get the value from
 * @param language The language to use
 */
const getLocalizedValue = (dict, language) => {
  var _ref, _ref2, _dict$language;
  return (_ref = (_ref2 = (_dict$language = dict[language]) !== null && _dict$language !== void 0 ? _dict$language : dict[language.split('_')[0]]) !== null && _ref2 !== void 0 ? _ref2 : dict.en) !== null && _ref !== void 0 ? _ref : null;
};
/**
 * Get the localized value of the dictionary provided
 * @param dict Dictionary
 * @return String or null if invalid dictionary
 */
const useLocalizedValue = dict => {
  /**
   * Language of the current user
   */
  const language = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.getLanguage)();
  return (0,vue__WEBPACK_IMPORTED_MODULE_1__.computed)(() => !(dict !== null && dict !== void 0 && dict.value) ? null : getLocalizedValue(dict.value, language));
};

/***/ }),

/***/ "./apps/settings/src/constants/AppDiscoverTypes.ts":
/*!*********************************************************!*\
  !*** ./apps/settings/src/constants/AppDiscoverTypes.ts ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   APP_DISCOVER_KNOWN_TYPES: () => (/* binding */ APP_DISCOVER_KNOWN_TYPES)
/* harmony export */ });
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
/**
 * Currently known types of app discover section elements
 */
const APP_DISCOVER_KNOWN_TYPES = ['post', 'showcase', 'carousel'];

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts":
/*!*********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts ***!
  \*********************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-router */ "./node_modules/vue-router/dist/vue-router.esm.js");




const knownRoutes = Object.fromEntries(Object.entries((0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('core', 'apps')).map(_ref => {
  var _v$app;
  let [k, v] = _ref;
  return [(_v$app = v.app) !== null && _v$app !== void 0 ? _v$app : k, v.href];
}));
/**
 * This component either shows a native link to the installed app or external size - or a router link to the appstore page of the app if not installed
 */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  name: 'AppLink',
  components: {
    RouterLink: vue_router__WEBPACK_IMPORTED_MODULE_3__.RouterLink
  },
  props: {
    href: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      routerProps: undefined,
      linkProps: undefined
    };
  },
  watch: {
    href: {
      immediate: true,
      handler() {
        var _this$$route$params$c, _this$$route$params;
        const match = this.href.match(/^app:\/\/([^/]+)(\/.+)?$/);
        this.routerProps = undefined;
        this.linkProps = undefined;
        // not an app url
        if (match === null) {
          this.linkProps = {
            href: this.href,
            target: '_blank',
            rel: 'noreferrer noopener'
          };
          return;
        }
        const appId = match[1];
        // Check if specific route was requested
        if (match[2]) {
          // we do no know anything about app internal path so we only allow generic app paths
          this.linkProps = {
            href: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)("/apps/".concat(appId).concat(match[2]))
          };
          return;
        }
        // If we know any route for that app we open it
        if (appId in knownRoutes) {
          this.linkProps = {
            href: knownRoutes[appId]
          };
          return;
        }
        // Fallback to show the app store entry
        this.routerProps = {
          to: {
            name: 'apps-details',
            params: {
              category: (_this$$route$params$c = (_this$$route$params = this.$route.params) === null || _this$$route$params === void 0 ? void 0 : _this$$route$params.category) !== null && _this$$route$params$c !== void 0 ? _this$$route$params$c : 'discover',
              id: appId
            }
          }
        };
      }
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/core/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _common__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./common */ "./apps/settings/src/components/AppStoreDiscover/common.ts");
/* harmony import */ var _composables_useGetLocalizedValue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../composables/useGetLocalizedValue */ "./apps/settings/src/composables/useGetLocalizedValue.ts");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _AppLink_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AppLink.vue */ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_5__.defineComponent)({
  components: {
    AppLink: _AppLink_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    ..._common__WEBPACK_IMPORTED_MODULE_1__.commonAppDiscoverProps,
    text: {
      type: Object,
      required: false,
      default: () => null
    },
    media: {
      type: Object,
      required: false,
      default: () => null
    },
    inline: {
      type: Boolean,
      required: false,
      default: false
    },
    domId: {
      type: String,
      required: false,
      default: null
    }
  },
  setup(props) {
    const translatedHeadline = (0,_composables_useGetLocalizedValue__WEBPACK_IMPORTED_MODULE_2__.useLocalizedValue)((0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => props.headline));
    const translatedText = (0,_composables_useGetLocalizedValue__WEBPACK_IMPORTED_MODULE_2__.useLocalizedValue)((0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => props.text));
    const localizedMedia = (0,_composables_useGetLocalizedValue__WEBPACK_IMPORTED_MODULE_2__.useLocalizedValue)((0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => {
      var _props$media;
      return (_props$media = props.media) === null || _props$media === void 0 ? void 0 : _props$media.content;
    }));
    const mediaSources = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => localizedMedia.value !== null ? [localizedMedia.value.src].flat() : undefined);
    const mediaAlt = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => {
      var _localizedMedia$value, _localizedMedia$value2;
      return (_localizedMedia$value = (_localizedMedia$value2 = localizedMedia.value) === null || _localizedMedia$value2 === void 0 ? void 0 : _localizedMedia$value2.alt) !== null && _localizedMedia$value !== void 0 ? _localizedMedia$value : '';
    });
    const isImage = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => {
      var _mediaSources$value;
      return (mediaSources === null || mediaSources === void 0 || (_mediaSources$value = mediaSources.value) === null || _mediaSources$value === void 0 ? void 0 : _mediaSources$value[0].mime.startsWith('image/')) === true;
    });
    /**
     * Is the media is shown full width
     */
    const isFullWidth = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => !translatedHeadline.value && !translatedText.value);
    /**
     * Link on the media
     * Fallback to post link to prevent link inside link (which is invalid HTML)
     */
    const mediaLink = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => {
      var _localizedMedia$value3, _localizedMedia$value4;
      return (_localizedMedia$value3 = (_localizedMedia$value4 = localizedMedia.value) === null || _localizedMedia$value4 === void 0 ? void 0 : _localizedMedia$value4.link) !== null && _localizedMedia$value3 !== void 0 ? _localizedMedia$value3 : props.link;
    });
    const hasPlaybackEnded = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)(false);
    const showPlayVideo = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => {
      var _localizedMedia$value5;
      return ((_localizedMedia$value5 = localizedMedia.value) === null || _localizedMedia$value5 === void 0 ? void 0 : _localizedMedia$value5.link) && hasPlaybackEnded.value;
    });
    /**
     * The content is sized / styles are applied based on the container width
     * To make it responsive even for inline usage and when opening / closing the sidebar / navigation
     */
    const container = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)();
    const {
      width: containerWidth
    } = (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_6__.useElementSize)(container);
    const isSmallWidth = (0,vue__WEBPACK_IMPORTED_MODULE_5__.computed)(() => containerWidth.value < 600);
    /**
     * Generate URL for cached media to prevent user can be tracked
     * @param url The URL to resolve
     */
    const generatePrivacyUrl = url => url.startsWith('/') ? url : (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/settings/api/apps/media?fileName={fileName}', {
      fileName: url
    });
    const mediaElement = (0,vue__WEBPACK_IMPORTED_MODULE_5__.ref)();
    const mediaIsVisible = (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_6__.useElementVisibility)(mediaElement, {
      threshold: 0.3
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_5__.watchEffect)(() => {
      // Only if media is video
      if (!isImage.value && mediaElement.value) {
        const video = mediaElement.value;
        if (mediaIsVisible.value) {
          // Ensure video is muted - otherwise .play() will be blocked by browsers
          video.muted = true;
          // If visible start playback
          video.play();
        } else {
          // If not visible pause the playback
          video.pause();
          // If the animation has ended reset
          if (video.ended) {
            video.currentTime = 0;
            hasPlaybackEnded.value = false;
          }
        }
      }
    });
    return {
      mdiPlayCircleOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiPlayCircleOutline,
      container,
      translatedText,
      translatedHeadline,
      mediaElement,
      mediaSources,
      mediaAlt,
      mediaLink,
      hasPlaybackEnded,
      showPlayVideo,
      isFullWidth,
      isSmallWidth,
      isImage,
      generatePrivacyUrl
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896":
/*!**********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896 ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************/
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
  return _vm.linkProps ? _c("a", _vm._b({}, "a", _vm.linkProps, false), [_vm._t("default")], 2) : _vm.routerProps ? _c("RouterLink", _vm._b({}, "RouterLink", _vm.routerProps, false), [_vm._t("default")], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm$media, _vm$media2;
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("article", {
    ref: "container",
    staticClass: "app-discover-post",
    class: {
      "app-discover-post--reverse": _vm.media && _vm.media.alignment === "start",
      "app-discover-post--small": _vm.isSmallWidth
    },
    attrs: {
      id: _vm.domId
    }
  }, [_vm.headline || _vm.text ? _c(_vm.link ? "AppLink" : "div", {
    tag: "component",
    staticClass: "app-discover-post__text",
    attrs: {
      href: _vm.link
    }
  }, [_c(_vm.inline ? "h4" : "h3", {
    tag: "component"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.translatedHeadline) + "\n\t\t")]), _vm._v(" "), _c("p", [_vm._v(_vm._s(_vm.translatedText))])], 1) : _vm._e(), _vm._v(" "), _vm.mediaSources ? _c(_vm.mediaLink ? "AppLink" : "div", {
    tag: "component",
    staticClass: "app-discover-post__media",
    class: {
      "app-discover-post__media--fullwidth": _vm.isFullWidth,
      "app-discover-post__media--start": ((_vm$media = _vm.media) === null || _vm$media === void 0 ? void 0 : _vm$media.alignment) === "start",
      "app-discover-post__media--end": ((_vm$media2 = _vm.media) === null || _vm$media2 === void 0 ? void 0 : _vm$media2.alignment) === "end"
    },
    attrs: {
      href: _vm.mediaLink
    }
  }, [_c(_vm.isImage ? "picture" : "video", {
    ref: "mediaElement",
    tag: "component",
    staticClass: "app-discover-post__media-element",
    attrs: {
      muted: !_vm.isImage,
      playsinline: !_vm.isImage,
      preload: !_vm.isImage && "auto"
    },
    on: {
      ended: function ($event) {
        _vm.hasPlaybackEnded = true;
      }
    }
  }, [_vm._l(_vm.mediaSources, function (source) {
    return _c("source", {
      key: source.src,
      attrs: {
        src: _vm.isImage ? undefined : _vm.generatePrivacyUrl(source.src),
        srcset: _vm.isImage ? _vm.generatePrivacyUrl(source.src) : undefined,
        type: source.mime
      }
    });
  }), _vm._v(" "), _vm.isImage ? _c("img", {
    attrs: {
      src: _vm.generatePrivacyUrl(_vm.mediaSources[0].src),
      alt: _vm.mediaAlt
    }
  }) : _vm._e()], 2), _vm._v(" "), _c("div", {
    staticClass: "app-discover-post__play-icon-wrapper"
  }, [!_vm.isImage && _vm.showPlayVideo ? _c("NcIconSvgWrapper", {
    staticClass: "app-discover-post__play-icon",
    attrs: {
      path: _vm.mdiPlayCircleOutline,
      size: 92
    }
  }) : _vm._e()], 1)], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.app-discover-post[data-v-687237a2] {
  max-height: 300px;
  width: 100%;
  background-color: var(--color-primary-element-light);
  border-radius: var(--border-radius-rounded);
  display: flex;
  flex-direction: row;
  justify-content: start;
}
.app-discover-post--reverse[data-v-687237a2] {
  flex-direction: row-reverse;
}
.app-discover-post h3[data-v-687237a2], .app-discover-post h4[data-v-687237a2] {
  font-size: 24px;
  font-weight: 600;
  margin-block: 0 1em;
}
.app-discover-post__text[data-v-687237a2] {
  display: block;
  width: 100%;
  padding: var(--border-radius-rounded);
  overflow-y: scroll;
}
.app-discover-post:has(.app-discover-post__media) .app-discover-post__text[data-v-687237a2] {
  padding-block-end: 0;
}
.app-discover-post__media[data-v-687237a2] {
  display: block;
  overflow: hidden;
  max-width: 450px;
  border-radius: var(--border-radius-rounded);
}
.app-discover-post__media--fullwidth[data-v-687237a2] {
  max-width: unset;
  max-height: unset;
}
.app-discover-post__media--end[data-v-687237a2] {
  border-end-start-radius: 0;
  border-start-start-radius: 0;
}
.app-discover-post__media--start[data-v-687237a2] {
  border-end-end-radius: 0;
  border-start-end-radius: 0;
}
.app-discover-post__media img[data-v-687237a2], .app-discover-post__media-element[data-v-687237a2] {
  height: 100%;
  width: 100%;
  object-fit: cover;
  object-position: center;
}
.app-discover-post__play-icon[data-v-687237a2] {
  position: absolute;
  top: -46px;
  right: -46px;
}
.app-discover-post__play-icon-wrapper[data-v-687237a2] {
  position: relative;
  top: -50%;
  left: -50%;
}
.app-discover-post--small.app-discover-post[data-v-687237a2] {
  flex-direction: column;
  max-height: 500px;
}
.app-discover-post--small.app-discover-post--reverse[data-v-687237a2] {
  flex-direction: column-reverse;
}
.app-discover-post--small .app-discover-post__text[data-v-687237a2] {
  flex: 1 1 50%;
}
.app-discover-post--small .app-discover-post__media[data-v-687237a2] {
  min-width: 100%;
}
.app-discover-post--small .app-discover-post__media--end[data-v-687237a2] {
  border-radius: var(--border-radius-rounded);
  border-start-end-radius: 0;
  border-start-start-radius: 0;
}
.app-discover-post--small .app-discover-post__media--start[data-v-687237a2] {
  border-radius: var(--border-radius-rounded);
  border-end-end-radius: 0;
  border-end-start-radius: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue":
/*!*******************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppLink.vue ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppLink.vue?vue&type=template&id=63ee4896 */ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896");
/* harmony import */ var _AppLink_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppLink.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AppLink_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreDiscover/AppLink.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/PostType.vue":
/*!********************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/PostType.vue ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PostType.vue?vue&type=template&id=687237a2&scoped=true */ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true");
/* harmony import */ var _PostType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PostType.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts");
/* harmony import */ var _PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PostType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "687237a2",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreDiscover/PostType.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts":
/*!*******************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLink_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLink.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLink_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts":
/*!********************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PostType.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896":
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896 ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppLink_vue_vue_type_template_id_63ee4896__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppLink.vue?vue&type=template&id=63ee4896 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/AppLink.vue?vue&type=template&id=63ee4896");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true ***!
  \**************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_template_id_687237a2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PostType.vue?vue&type=template&id=687237a2&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=template&id=687237a2&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PostType_vue_vue_type_style_index_0_id_687237a2_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/PostType.vue?vue&type=style&index=0&id=687237a2&scoped=true&lang=scss");


/***/ })

}]);
//# sourceMappingURL=apps_settings_src_components_AppStoreDiscover_PostType_vue-apps_settings_src_components_AppStoreDiscover_PostType_vue.js.map?v=bd0718820d5bbad8c4ed