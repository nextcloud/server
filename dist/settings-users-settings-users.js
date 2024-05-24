"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["settings-users"],{

/***/ "./apps/settings/src/mixins/UserRowMixin.js":
/*!**************************************************!*\
  !*** ./apps/settings/src/mixins/UserRowMixin.js ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Greta Doci <gretadoci@gmail.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  props: {
    user: {
      type: Object,
      required: true
    },
    settings: {
      type: Object,
      default: () => ({})
    },
    groups: {
      type: Array,
      default: () => []
    },
    subAdminsGroups: {
      type: Array,
      default: () => []
    },
    quotaOptions: {
      type: Array,
      default: () => []
    },
    languages: {
      type: Array,
      required: true
    },
    externalActions: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    /* GROUPS MANAGEMENT */
    userGroups() {
      const userGroups = this.groups.filter(group => this.user.groups.includes(group.id));
      return userGroups;
    },
    userSubAdminsGroups() {
      const userSubAdminsGroups = this.subAdminsGroups.filter(group => this.user.subadmin.includes(group.id));
      return userSubAdminsGroups;
    },
    availableGroups() {
      return this.groups.map(group => {
        // clone object because we don't want
        // to edit the original groups
        const groupClone = Object.assign({}, group);

        // two settings here:
        // 1. user NOT in group but no permission to add
        // 2. user is in group but no permission to remove
        groupClone.$isDisabled = group.canAdd === false && !this.user.groups.includes(group.id) || group.canRemove === false && this.user.groups.includes(group.id);
        return groupClone;
      });
    },
    /* QUOTA MANAGEMENT */
    usedSpace() {
      if (this.user.quota.used) {
        return t('settings', '{size} used', {
          size: OC.Util.humanFileSize(this.user.quota.used)
        });
      }
      return t('settings', '{size} used', {
        size: OC.Util.humanFileSize(0)
      });
    },
    usedQuota() {
      let quota = this.user.quota.quota;
      if (quota > 0) {
        quota = Math.min(100, Math.round(this.user.quota.used / quota * 100));
      } else {
        const usedInGB = this.user.quota.used / (10 * Math.pow(2, 30));
        // asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota
        quota = 95 * (1 - 1 / (usedInGB + 1));
      }
      return isNaN(quota) ? 0 : quota;
    },
    // Mapping saved values to objects
    userQuota() {
      if (this.user.quota.quota >= 0) {
        // if value is valid, let's map the quotaOptions or return custom quota
        const humanQuota = OC.Util.humanFileSize(this.user.quota.quota);
        const userQuota = this.quotaOptions.find(quota => quota.id === humanQuota);
        return userQuota || {
          id: humanQuota,
          label: humanQuota
        };
      } else if (this.user.quota.quota === 'default') {
        // default quota is replaced by the proper value on load
        return this.quotaOptions[0];
      }
      return this.quotaOptions[1]; // unlimited
    },
    /* PASSWORD POLICY? */
    minPasswordLength() {
      return this.$store.getters.getPasswordPolicyMinLength;
    },
    /* LANGUAGE */
    userLanguage() {
      const availableLanguages = this.languages[0].languages.concat(this.languages[1].languages);
      const userLang = availableLanguages.find(lang => lang.code === this.user.language);
      if (typeof userLang !== 'object' && this.user.language !== '') {
        return {
          code: this.user.language,
          name: this.user.language
        };
      } else if (this.user.language === '') {
        return false;
      }
      return userLang;
    },
    /* LAST LOGIN */
    userLastLoginTooltip() {
      if (this.user.lastLogin > 0) {
        return OC.Util.formatDate(this.user.lastLogin);
      }
      return '';
    },
    userLastLogin() {
      if (this.user.lastLogin > 0) {
        return OC.Util.relativeModifiedDate(this.user.lastLogin);
      }
      return t('settings', 'Never');
    }
  }
});

/***/ }),

/***/ "./apps/settings/src/composables/useGroupsNavigation.ts":
/*!**************************************************************!*\
  !*** ./apps/settings/src/composables/useGroupsNavigation.ts ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useFormatGroups: () => (/* binding */ useFormatGroups)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");

/**
 * Format a group to a menu entry
 *
 * @param group the group
 */
function formatGroupMenu(group) {
  if (typeof group === 'undefined') {
    return null;
  }
  const item = {
    id: group.id,
    title: group.name,
    usercount: group.usercount,
    count: Math.max(0, group.usercount - group.disabled)
  };
  return item;
}
const useFormatGroups = groups => {
  /**
   * All non-disabled non-admin groups
   */
  const userGroups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
    const formatted = groups.value
    // filter out disabled and admin
    .filter(group => group.id !== 'disabled' && group.id !== 'admin')
    // format group
    .map(group => formatGroupMenu(group))
    // remove invalid
    .filter(group => group !== null);
    return formatted;
  });
  /**
   * The admin group if found otherwise null
   */
  const adminGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => formatGroupMenu(groups.value.find(group => group.id === 'admin')));
  /**
   * The group of disabled users
   */
  const disabledGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => formatGroupMenu(groups.value.find(group => group.id === 'disabled')));
  return {
    adminGroup,
    disabledGroup,
    userGroups
  };
};

/***/ }),

/***/ "./apps/settings/src/utils/userUtils.ts":
/*!**********************************************!*\
  !*** ./apps/settings/src/utils/userUtils.ts ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultQuota: () => (/* binding */ defaultQuota),
/* harmony export */   isObfuscated: () => (/* binding */ isObfuscated),
/* harmony export */   unlimitedQuota: () => (/* binding */ unlimitedQuota)
/* harmony export */ });
/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
const unlimitedQuota = {
  id: 'none',
  label: t('settings', 'Unlimited')
};
const defaultQuota = {
  id: 'default',
  label: t('settings', 'Default quota')
};
/**
 * Return `true` if the logged in user does not have permissions to view the
 * data of `user`
 * @param user
 * @param user.id
 */
const isObfuscated = user => {
  const keys = Object.keys(user);
  return keys.length === 1 && keys.at(0) === 'id';
};

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts":
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend({
  name: 'UserListFooter',
  components: {
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    loading: {
      type: Boolean,
      required: true
    },
    filteredUsers: {
      type: Array,
      required: true
    }
  },
  computed: {
    userCount() {
      if (this.loading) {
        return this.n('settings', '{userCount} user …', '{userCount} users …', this.filteredUsers.length, {
          userCount: this.filteredUsers.length
        });
      }
      return this.n('settings', '{userCount} user', '{userCount} users', this.filteredUsers.length, {
        userCount: this.filteredUsers.length
      });
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
    n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translatePlural
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts":
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_1__["default"].extend({
  name: 'UserListHeader',
  props: {
    hasObfuscated: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    showConfig() {
      // @ts-expect-error: allow untyped $store
      return this.$store.getters.getShowConfig;
    },
    settings() {
      // @ts-expect-error: allow untyped $store
      return this.$store.getters.getServerData;
    },
    subAdminsGroups() {
      // @ts-expect-error: allow untyped $store
      return this.$store.getters.getSubadminGroups;
    },
    passwordLabel() {
      if (this.hasObfuscated) {
        // TRANSLATORS This string is for a column header labelling either a password or a message that the current user has insufficient permissions
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Password or insufficient permissions message');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Password');
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts":
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/check.svg?raw */ "./node_modules/@mdi/svg/svg/check.svg?raw");
/* harmony import */ var _mdi_svg_svg_pencil_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/pencil.svg?raw */ "./node_modules/@mdi/svg/svg/pencil.svg?raw");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_5__.defineComponent)({
  components: {
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcActions: _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  props: {
    /**
     * Array of user actions
     */
    actions: {
      type: Array,
      required: true
    },
    /**
     * The state whether the row is currently disabled
     */
    disabled: {
      type: Boolean,
      required: true
    },
    /**
     * The state whether the row is currently edited
     */
    edit: {
      type: Boolean,
      required: true
    },
    /**
     * Target of this actions
     */
    user: {
      type: Object,
      required: true
    }
  },
  computed: {
    /**
     * Current MDI logo to show for edit toggle
     */
    editSvg() {
      return this.edit ? _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_3__ : _mdi_svg_svg_pencil_svg_raw__WEBPACK_IMPORTED_MODULE_4__;
    }
  },
  methods: {
    /**
     * Toggle edit mode by emitting the update event
     */
    toggleEdit() {
      this.$emit('update:edit', !this.edit);
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts":
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _vueuse_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../logger.ts */ "./apps/settings/src/logger.ts");




vue__WEBPACK_IMPORTED_MODULE_2__["default"].directive('elementVisibility', _vueuse_components__WEBPACK_IMPORTED_MODULE_3__.vElementVisibility);
// Items to render before and after the visible area
const bufferItems = 3;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend({
  name: 'VirtualList',
  props: {
    dataComponent: {
      type: [Object, Function],
      required: true
    },
    dataKey: {
      type: String,
      required: true
    },
    dataSources: {
      type: Array,
      required: true
    },
    itemHeight: {
      type: Number,
      required: true
    },
    extraProps: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      bufferItems,
      index: 0,
      headerHeight: 0,
      tableHeight: 0,
      resizeObserver: null
    };
  },
  computed: {
    startIndex() {
      return Math.max(0, this.index - bufferItems);
    },
    shownItems() {
      return Math.ceil((this.tableHeight - this.headerHeight) / this.itemHeight) + bufferItems * 2;
    },
    renderedItems() {
      return this.dataSources.slice(this.startIndex, this.startIndex + this.shownItems);
    },
    tbodyStyle() {
      const isOverScrolled = this.startIndex + this.shownItems > this.dataSources.length;
      const lastIndex = this.dataSources.length - this.startIndex - this.shownItems;
      const hiddenAfterItems = Math.min(this.dataSources.length - this.startIndex, lastIndex);
      return {
        paddingTop: "".concat(this.startIndex * this.itemHeight, "px"),
        paddingBottom: isOverScrolled ? 0 : "".concat(hiddenAfterItems * this.itemHeight, "px")
      };
    }
  },
  mounted() {
    var _this$$refs, _this$$refs2;
    const root = this.$el;
    const tfoot = (_this$$refs = this.$refs) === null || _this$$refs === void 0 ? void 0 : _this$$refs.tfoot;
    const thead = (_this$$refs2 = this.$refs) === null || _this$$refs2 === void 0 ? void 0 : _this$$refs2.thead;
    this.resizeObserver = new ResizeObserver((0,debounce__WEBPACK_IMPORTED_MODULE_0__.debounce)(() => {
      var _thead$clientHeight, _root$clientHeight;
      this.headerHeight = (_thead$clientHeight = thead === null || thead === void 0 ? void 0 : thead.clientHeight) !== null && _thead$clientHeight !== void 0 ? _thead$clientHeight : 0;
      this.tableHeight = (_root$clientHeight = root === null || root === void 0 ? void 0 : root.clientHeight) !== null && _root$clientHeight !== void 0 ? _root$clientHeight : 0;
      _logger_ts__WEBPACK_IMPORTED_MODULE_1__["default"].debug('VirtualList resizeObserver updated');
      this.onScroll();
    }, 100, false));
    this.resizeObserver.observe(root);
    this.resizeObserver.observe(tfoot);
    this.resizeObserver.observe(thead);
    this.$el.addEventListener('scroll', this.onScroll);
  },
  beforeDestroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
  },
  methods: {
    handleFooterVisibility(visible) {
      if (visible) {
        this.$emit('scroll-end');
      }
    },
    onScroll() {
      // Max 0 to prevent negative index
      this.index = Math.max(0, Math.round(this.$el.scrollTop / this.itemHeight));
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts":
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationCaption.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationList_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationList.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationList.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationNew.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../components/GroupListItem.vue */ "./apps/settings/src/components/GroupListItem.vue");
/* harmony import */ var _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../components/Users/UserSettingsDialog.vue */ "./apps/settings/src/components/Users/UserSettingsDialog.vue");
/* harmony import */ var _store__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../store */ "./apps/settings/src/store/index.js");
/* harmony import */ var vue_router_composables__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-router/composables */ "./node_modules/vue-router/composables.mjs");
/* harmony import */ var _composables_useGroupsNavigation__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../composables/useGroupsNavigation */ "./apps/settings/src/composables/useGroupsNavigation.ts");





















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_17__.defineComponent)({
  __name: 'UserManagementNavigation',
  setup(__props) {
    const route = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_18__.useRoute)();
    const router = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_18__.useRouter)();
    const store = (0,_store__WEBPACK_IMPORTED_MODULE_15__.useStore)();
    /** State of the 'new-account' dialog */
    const isDialogOpen = (0,vue__WEBPACK_IMPORTED_MODULE_17__.ref)(false);
    /** Current active group in the view - this is URL encoded */
    const selectedGroup = (0,vue__WEBPACK_IMPORTED_MODULE_17__.computed)(() => {
      var _route$params;
      return (_route$params = route.params) === null || _route$params === void 0 ? void 0 : _route$params.selectedGroup;
    });
    /** Current active group - URL decoded  */
    const selectedGroupDecoded = (0,vue__WEBPACK_IMPORTED_MODULE_17__.computed)(() => selectedGroup.value ? decodeURIComponent(selectedGroup.value) : null);
    /** Overall user count */
    const userCount = (0,vue__WEBPACK_IMPORTED_MODULE_17__.computed)(() => store.getters.getUserCount);
    /** All available groups */
    const groups = (0,vue__WEBPACK_IMPORTED_MODULE_17__.computed)(() => store.getters.getSortedGroups);
    const {
      adminGroup,
      disabledGroup,
      userGroups
    } = (0,_composables_useGroupsNavigation__WEBPACK_IMPORTED_MODULE_16__.useFormatGroups)(groups);
    /** True if the current user is an administrator */
    const isAdmin = (0,vue__WEBPACK_IMPORTED_MODULE_17__.computed)(() => store.getters.getServerData.isAdmin);
    /** True if the 'add-group' dialog is open - needed to be able to close it when the group is created */
    const isAddGroupOpen = (0,vue__WEBPACK_IMPORTED_MODULE_17__.ref)(false);
    /** True if the group creation is in progress to show loading spinner and disable adding another one */
    const loadingAddGroup = (0,vue__WEBPACK_IMPORTED_MODULE_17__.ref)(false);
    /** Error state for creating a new group */
    const hasAddGroupError = (0,vue__WEBPACK_IMPORTED_MODULE_17__.ref)(false);
    /** Name of the group to create (used in the group creation dialog) */
    const newGroupName = (0,vue__WEBPACK_IMPORTED_MODULE_17__.ref)('');
    /**
     * Create a new group
     */
    async function createGroup() {
      hasAddGroupError.value = false;
      const groupId = newGroupName.value.trim();
      if (groupId === '') {
        hasAddGroupError.value = true;
        return;
      }
      isAddGroupOpen.value = false;
      loadingAddGroup.value = true;
      try {
        await store.dispatch('addGroup', groupId);
        await router.push({
          name: 'group',
          params: {
            selectedGroup: encodeURIComponent(groupId)
          }
        });
        newGroupName.value = '';
      } catch {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Failed to create group'));
      }
      loadingAddGroup.value = false;
    }
    /**
     * Open the new-user form dialog
     */
    function showNewUserMenu() {
      store.commit('setShowConfig', {
        key: 'showNewUserForm',
        value: true
      });
    }
    return {
      __sfc: true,
      route,
      router,
      store,
      isDialogOpen,
      selectedGroup,
      selectedGroupDecoded,
      userCount,
      groups,
      adminGroup,
      disabledGroup,
      userGroups,
      isAdmin,
      isAddGroupOpen,
      loadingAddGroup,
      hasAddGroupError,
      newGroupName,
      createGroup,
      showNewUserMenu,
      mdiAccount: _mdi_js__WEBPACK_IMPORTED_MODULE_19__.mdiAccount,
      mdiAccountOff: _mdi_js__WEBPACK_IMPORTED_MODULE_19__.mdiAccountOff,
      mdiCog: _mdi_js__WEBPACK_IMPORTED_MODULE_19__.mdiCog,
      mdiPlus: _mdi_js__WEBPACK_IMPORTED_MODULE_19__.mdiPlus,
      mdiShieldAccount: _mdi_js__WEBPACK_IMPORTED_MODULE_19__.mdiShieldAccount,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
      NcActionInput: _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_2__["default"],
      NcActionText: _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_3__["default"],
      NcAppNavigation: _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcAppNavigationCaption: _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcAppNavigationItem: _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcAppNavigationList: _nextcloud_vue_dist_Components_NcAppNavigationList_js__WEBPACK_IMPORTED_MODULE_7__["default"],
      NcAppNavigationNew: _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_8__["default"],
      NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_9__["default"],
      NcCounterBubble: _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_10__["default"],
      NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_11__["default"],
      NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_12__["default"],
      GroupListItem: _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
      UserSettingsDialog: _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_14__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js":
/*!************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcModal.js */ "./node_modules/@nextcloud/vue/dist/Components/NcModal.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'GroupListItem',
  components: {
    AccountGroup: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    Delete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_0__.Fragment,
    NcActionButton: _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcActionInput: _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcAppNavigationItem: _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcCounterBubble: _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcModal: _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    Pencil: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_10__["default"]
  },
  props: {
    /**
     * If this group is currently selected
     */
    active: {
      type: Boolean,
      required: true
    },
    /**
     * Number of members within this group
     */
    count: {
      type: Number,
      default: null
    },
    /**
     * Identifier of this group
     */
    id: {
      type: String,
      required: true
    },
    /**
     * Name of this group
     */
    name: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loadingRenameGroup: false,
      openGroupMenu: false,
      showRemoveGroupModal: false
    };
  },
  computed: {
    settings() {
      return this.$store.getters.getServerData;
    }
  },
  methods: {
    handleGroupMenuOpen() {
      this.openGroupMenu = true;
    },
    async renameGroup(gid) {
      // check if group id is valid
      if (gid.trim() === '') {
        return;
      }
      const displayName = this.$refs.displayNameInput.$el.querySelector('input[type="text"]').value;

      // check if group name is valid
      if (displayName.trim() === '') {
        return;
      }
      try {
        this.openGroupMenu = false;
        this.loadingRenameGroup = true;
        await this.$store.dispatch('renameGroup', {
          groupid: gid.trim(),
          displayName: displayName.trim()
        });
        this.loadingRenameGroup = false;
      } catch {
        this.openGroupMenu = true;
        this.loadingRenameGroup = false;
      }
    },
    async removeGroup() {
      try {
        await this.$store.dispatch('removeGroup', this.id);
        this.showRemoveGroupModal = false;
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_11__.showError)(t('settings', 'Failed to remove group "{group}"', {
          group: this.name
        }));
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _Users_VirtualList_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Users/VirtualList.vue */ "./apps/settings/src/components/Users/VirtualList.vue");
/* harmony import */ var _Users_NewUserModal_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Users/NewUserModal.vue */ "./apps/settings/src/components/Users/NewUserModal.vue");
/* harmony import */ var _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./Users/UserListFooter.vue */ "./apps/settings/src/components/Users/UserListFooter.vue");
/* harmony import */ var _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Users/UserListHeader.vue */ "./apps/settings/src/components/Users/UserListHeader.vue");
/* harmony import */ var _Users_UserRow_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Users/UserRow.vue */ "./apps/settings/src/components/Users/UserRow.vue");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");















const newUser = Object.freeze({
  id: '',
  displayName: '',
  password: '',
  mailAddress: '',
  groups: [],
  manager: '',
  subAdminsGroups: [],
  quota: _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.defaultQuota,
  language: {
    code: 'en',
    name: t('settings', 'Default language')
  }
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserList',
  components: {
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_2__.Fragment,
    NcEmptyContent: _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NewUserModal: _Users_NewUserModal_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    UserListFooter: _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    UserListHeader: _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    VirtualList: _Users_VirtualList_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  props: {
    selectedGroup: {
      type: String,
      default: null
    },
    externalActions: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    // non reactive properties
    return {
      mdiAccountGroup: _mdi_js__WEBPACK_IMPORTED_MODULE_13__.mdiAccountGroup,
      rowHeight: 55,
      UserRow: _Users_UserRow_vue__WEBPACK_IMPORTED_MODULE_10__["default"]
    };
  },
  data() {
    return {
      loading: {
        all: false,
        groups: false,
        users: false
      },
      newUser: {
        ...newUser
      },
      isInitialLoad: true,
      searchQuery: ''
    };
  },
  computed: {
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    style() {
      return {
        '--row-height': "".concat(this.rowHeight, "px")
      };
    },
    hasObfuscated() {
      return this.filteredUsers.some(user => (0,_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.isObfuscated)(user));
    },
    users() {
      return this.$store.getters.getUsers;
    },
    filteredUsers() {
      if (this.selectedGroup === 'disabled') {
        return this.users.filter(user => user.enabled === false);
      }
      if (!this.settings.isAdmin) {
        // we don't want subadmins to edit themselves
        return this.users.filter(user => user.enabled !== false);
      }
      return this.users.filter(user => user.enabled !== false);
    },
    groups() {
      // data provided php side + remove the disabled group
      return this.$store.getters.getGroups.filter(group => group.id !== 'disabled').sort((a, b) => a.name.localeCompare(b.name));
    },
    subAdminsGroups() {
      // data provided php side
      return this.$store.getters.getSubadminGroups;
    },
    quotaOptions() {
      // convert the preset array into objects
      const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({
        id: cur,
        label: cur
      }), []);
      // add default presets
      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.unlimitedQuota);
      }
      quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.defaultQuota);
      return quotaPreset;
    },
    usersOffset() {
      return this.$store.getters.getUsersOffset;
    },
    usersLimit() {
      return this.$store.getters.getUsersLimit;
    },
    disabledUsersOffset() {
      return this.$store.getters.getDisabledUsersOffset;
    },
    disabledUsersLimit() {
      return this.$store.getters.getDisabledUsersLimit;
    },
    usersCount() {
      return this.users.length;
    },
    /* LANGUAGES */
    languages() {
      return [{
        label: t('settings', 'Common languages'),
        languages: this.settings.languages.commonLanguages
      }, {
        label: t('settings', 'Other languages'),
        languages: this.settings.languages.otherLanguages
      }];
    }
  },
  watch: {
    // watch url change and group select
    async selectedGroup(val) {
      this.isInitialLoad = true;
      // if selected is the disabled group but it's empty
      await this.redirectIfDisabled();
      this.$store.commit('resetUsers');
      await this.loadUsers();
      this.setNewUserDefaultGroup(val);
    },
    filteredUsers(filteredUsers) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].debug("".concat(filteredUsers.length, " filtered user(s)"));
    }
  },
  async created() {
    await this.loadUsers();
  },
  async mounted() {
    if (!this.settings.canChangePassword) {
      OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'));
    }

    /**
     * Reset and init new user form
     */
    this.resetForm();

    /**
     * Register search
     */
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);

    /**
     * If disabled group but empty, redirect
     */
    await this.redirectIfDisabled();
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    async handleScrollEnd() {
      await this.loadUsers();
    },
    async loadUsers() {
      this.loading.users = true;
      try {
        if (this.selectedGroup === 'disabled') {
          await this.$store.dispatch('getDisabledUsers', {
            offset: this.disabledUsersOffset,
            limit: this.disabledUsersLimit,
            search: this.searchQuery
          });
        } else {
          await this.$store.dispatch('getUsers', {
            offset: this.usersOffset,
            limit: this.usersLimit,
            group: this.selectedGroup,
            search: this.searchQuery
          });
        }
        _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].debug("".concat(this.users.length, " total user(s) loaded"));
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].error('Failed to load accounts', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)('Failed to load accounts');
      }
      this.loading.users = false;
      this.isInitialLoad = false;
    },
    closeModal() {
      this.$store.commit('setShowConfig', {
        key: 'showNewUserForm',
        value: false
      });
    },
    async search(_ref) {
      let {
        query
      } = _ref;
      this.searchQuery = query;
      this.$store.commit('resetUsers');
      await this.loadUsers();
    },
    resetSearch() {
      this.search({
        query: ''
      });
    },
    resetForm() {
      // revert form to original state
      this.newUser = Object.assign({}, newUser);

      /**
       * Init default language from server data. The use of this.settings
       * requires a computed variable, which break the v-model binding of the form,
       * this is a much easier solution than getter and setter on a computed var
       */
      if (this.settings.defaultLanguage) {
        vue__WEBPACK_IMPORTED_MODULE_14__["default"].set(this.newUser.language, 'code', this.settings.defaultLanguage);
      }

      /**
       * In case the user directly loaded the user list within a group
       * the watch won't be triggered. We need to initialize it.
       */
      this.setNewUserDefaultGroup(this.selectedGroup);
      this.loading.all = false;
    },
    setNewUserDefaultGroup(value) {
      if (value && value.length > 0) {
        // setting new account default group to the current selected one
        const currentGroup = this.groups.find(group => group.id === value);
        if (currentGroup) {
          this.newUser.groups = [currentGroup];
          return;
        }
      }
      // fallback, empty selected group
      this.newUser.groups = [];
    },
    /**
     * If the selected group is the disabled group but the count is 0
     * redirect to the all users page.
     * we only check for 0 because we don't have the count on ldap
     * and we therefore set the usercount to -1 in this specific case
     */
    async redirectIfDisabled() {
      const allGroups = this.$store.getters.getGroups;
      if (this.selectedGroup === 'disabled' && allGroups.findIndex(group => group.id === 'disabled' && group.usercount === 0) > -1) {
        // disabled group is empty, redirection to all users
        this.$router.push({
          name: 'users'
        });
        await this.loadUsers();
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcModal.js */ "./node_modules/@nextcloud/vue/dist/Components/NcModal.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NewUserModal',
  components: {
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcModal: _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcPasswordField: _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcTextField: _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    loading: {
      type: Object,
      required: true
    },
    newUser: {
      type: Object,
      required: true
    },
    quotaOptions: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      possibleManagers: [],
      // TRANSLATORS This string describes a manager in the context of an organization
      managerLabel: t('settings', 'Set user manager')
    };
  },
  computed: {
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    usernameLabel() {
      if (this.settings.newUserGenerateUserID) {
        return t('settings', 'Account name will be autogenerated');
      }
      return t('settings', 'Account name (required)');
    },
    minPasswordLength() {
      return this.$store.getters.getPasswordPolicyMinLength;
    },
    groups() {
      // data provided php side + remove the disabled group
      return this.$store.getters.getGroups.filter(group => group.id !== 'disabled').sort((a, b) => a.name.localeCompare(b.name));
    },
    subAdminsGroups() {
      // data provided php side
      return this.$store.getters.getSubadminGroups;
    },
    canAddGroups() {
      // disabled if no permission to add new users to group
      return this.groups.map(group => {
        // clone object because we don't want
        // to edit the original groups
        group = Object.assign({}, group);
        group.$isDisabled = group.canAdd === false;
        return group;
      });
    },
    languages() {
      return [{
        name: t('settings', 'Common languages'),
        languages: this.settings.languages.commonLanguages
      }, ...this.settings.languages.commonLanguages, {
        name: t('settings', 'Other languages'),
        languages: this.settings.languages.otherLanguages
      }, ...this.settings.languages.otherLanguages];
    }
  },
  async beforeMount() {
    await this.searchUserManager();
  },
  methods: {
    async createUser() {
      this.loading.all = true;
      try {
        var _this$$refs$username, _this$$refs$username$;
        await this.$store.dispatch('addUser', {
          userid: this.newUser.id,
          password: this.newUser.password,
          displayName: this.newUser.displayName,
          email: this.newUser.mailAddress,
          groups: this.newUser.groups.map(group => group.id),
          subadmin: this.newUser.subAdminsGroups.map(group => group.id),
          quota: this.newUser.quota.id,
          language: this.newUser.language.code,
          manager: this.newUser.manager.id
        });
        this.$emit('reset');
        (_this$$refs$username = this.$refs.username) === null || _this$$refs$username === void 0 || (_this$$refs$username = _this$$refs$username.$refs) === null || _this$$refs$username === void 0 || (_this$$refs$username = _this$$refs$username.inputField) === null || _this$$refs$username === void 0 || (_this$$refs$username = _this$$refs$username.$refs) === null || _this$$refs$username === void 0 || (_this$$refs$username = _this$$refs$username.input) === null || _this$$refs$username === void 0 || (_this$$refs$username$ = _this$$refs$username.focus) === null || _this$$refs$username$ === void 0 || _this$$refs$username$.call(_this$$refs$username);
        this.$emit('close');
      } catch (error) {
        this.loading.all = false;
        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          const statuscode = error.response.data.ocs.meta.statuscode;
          if (statuscode === 102) {
            var _this$$refs$username2, _this$$refs$username3;
            // wrong username
            (_this$$refs$username2 = this.$refs.username) === null || _this$$refs$username2 === void 0 || (_this$$refs$username2 = _this$$refs$username2.$refs) === null || _this$$refs$username2 === void 0 || (_this$$refs$username2 = _this$$refs$username2.inputField) === null || _this$$refs$username2 === void 0 || (_this$$refs$username2 = _this$$refs$username2.$refs) === null || _this$$refs$username2 === void 0 || (_this$$refs$username2 = _this$$refs$username2.input) === null || _this$$refs$username2 === void 0 || (_this$$refs$username3 = _this$$refs$username2.focus) === null || _this$$refs$username3 === void 0 || _this$$refs$username3.call(_this$$refs$username2);
          } else if (statuscode === 107) {
            var _this$$refs$password, _this$$refs$password$;
            // wrong password
            (_this$$refs$password = this.$refs.password) === null || _this$$refs$password === void 0 || (_this$$refs$password = _this$$refs$password.$refs) === null || _this$$refs$password === void 0 || (_this$$refs$password = _this$$refs$password.inputField) === null || _this$$refs$password === void 0 || (_this$$refs$password = _this$$refs$password.$refs) === null || _this$$refs$password === void 0 || (_this$$refs$password = _this$$refs$password.input) === null || _this$$refs$password === void 0 || (_this$$refs$password$ = _this$$refs$password.focus) === null || _this$$refs$password$ === void 0 || _this$$refs$password$.call(_this$$refs$password);
          }
        }
      }
    },
    handleGroupInput(groups) {
      /**
       * Filter out groups with no id to prevent duplicate selected options
       *
       * Created groups are added programmatically by `createGroup()`
       */
      this.newUser.groups = groups.filter(group => Boolean(group.id));
    },
    /**
     * Create a new group
     *
     * @param {any} group Group
     * @param {string} group.name Group id
     */
    async createGroup(_ref) {
      let {
        name: gid
      } = _ref;
      this.loading.groups = true;
      try {
        await this.$store.dispatch('addGroup', gid);
        this.newUser.groups.push(this.groups.find(group => group.id === gid));
        this.loading.groups = false;
      } catch (error) {
        this.loading.groups = false;
      }
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {object}
     */
    validateQuota(quota) {
      // only used for new presets sent through @Tag
      const validQuota = OC.Util.computerFileSize(quota);
      if (validQuota !== null && validQuota >= 0) {
        // unify format output
        quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
        this.newUser.quota = {
          id: quota,
          label: quota
        };
        return this.newUser.quota;
      }
      // Default is unlimited
      this.newUser.quota = this.quotaOptions[0];
      return this.quotaOptions[0];
    },
    languageFilterBy(option, label, search) {
      // Show group header of the language
      if (option.languages) {
        return option.languages.some(_ref2 => {
          let {
            name
          } = _ref2;
          return name.toLocaleLowerCase().includes(search.toLocaleLowerCase());
        });
      }
      return (label || '').toLocaleLowerCase().includes(search.toLocaleLowerCase());
    },
    async searchUserManager(query) {
      await this.$store.dispatch('searchUsers', {
        offset: 0,
        limit: 10,
        search: query
      }).then(response => {
        const users = response !== null && response !== void 0 && response.data ? Object.values(response === null || response === void 0 ? void 0 : response.data.ocs.data.users) : [];
        if (users.length > 0) {
          this.possibleManagers = users;
        }
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js":
/*!************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcProgressBar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcProgressBar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./UserRowActions.vue */ "./apps/settings/src/components/Users/UserRowActions.vue");
/* harmony import */ var _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../mixins/UserRowMixin.js */ "./apps/settings/src/mixins/UserRowMixin.js");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserRow',
  components: {
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcProgressBar: _nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcTextField: _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    UserRowActions: _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  mixins: [_mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_9__["default"]],
  props: {
    user: {
      type: Object,
      required: true
    },
    visible: {
      type: Boolean,
      required: true
    },
    users: {
      type: Array,
      required: true
    },
    hasObfuscated: {
      type: Boolean,
      required: true
    },
    groups: {
      type: Array,
      default: () => []
    },
    subAdminsGroups: {
      type: Array,
      required: true
    },
    quotaOptions: {
      type: Array,
      required: true
    },
    languages: {
      type: Array,
      required: true
    },
    settings: {
      type: Object,
      required: true
    },
    externalActions: {
      type: Array,
      default: () => []
    }
  },
  data() {
    var _this$user$email;
    return {
      selectedQuota: false,
      rand: Math.random().toString(36).substring(2),
      loadingPossibleManagers: false,
      possibleManagers: [],
      currentManager: '',
      editing: false,
      loading: {
        all: false,
        displayName: false,
        password: false,
        mailAddress: false,
        groups: false,
        subadmins: false,
        quota: false,
        delete: false,
        disable: false,
        languages: false,
        wipe: false,
        manager: false
      },
      editedDisplayName: this.user.displayname,
      editedPassword: '',
      editedMail: (_this$user$email = this.user.email) !== null && _this$user$email !== void 0 ? _this$user$email : ''
    };
  },
  computed: {
    managerLabel() {
      // TRANSLATORS This string describes a person's manager in the context of an organization
      return t('settings', 'Set line manager');
    },
    isObfuscated() {
      return (0,_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__.isObfuscated)(this.user);
    },
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    isLoadingUser() {
      return this.loading.delete || this.loading.disable || this.loading.wipe;
    },
    isLoadingField() {
      return this.loading.delete || this.loading.disable || this.loading.all;
    },
    uniqueId() {
      return encodeURIComponent(this.user.id + this.rand);
    },
    userGroupsLabels() {
      return this.userGroups.map(group => group.name).join(', ');
    },
    userSubAdminsGroupsLabels() {
      return this.userSubAdminsGroups.map(group => group.name).join(', ');
    },
    usedSpace() {
      var _this$user$quota;
      if ((_this$user$quota = this.user.quota) !== null && _this$user$quota !== void 0 && _this$user$quota.used) {
        var _this$user$quota2;
        return t('settings', '{size} used', {
          size: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)((_this$user$quota2 = this.user.quota) === null || _this$user$quota2 === void 0 ? void 0 : _this$user$quota2.used)
        });
      }
      return t('settings', '{size} used', {
        size: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)(0)
      });
    },
    canEdit() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid !== this.user.id || this.settings.isAdmin;
    },
    userQuota() {
      var _this$user$quota3;
      let quota = (_this$user$quota3 = this.user.quota) === null || _this$user$quota3 === void 0 ? void 0 : _this$user$quota3.quota;
      if (quota === 'default') {
        quota = this.settings.defaultQuota;
        if (quota !== 'none') {
          // convert to numeric value to match what the server would usually return
          quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota, true);
        }
      }

      // when the default quota is unlimited, the server returns -3 here, map it to "none"
      if (quota === 'none' || quota === -3) {
        return t('settings', 'Unlimited');
      } else if (quota >= 0) {
        return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)(quota);
      }
      return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)(0);
    },
    userActions() {
      const actions = [{
        icon: 'icon-delete',
        text: t('settings', 'Delete account'),
        action: this.deleteUser
      }, {
        icon: 'icon-delete',
        text: t('settings', 'Disconnect all devices and delete local data'),
        action: this.wipeUserDevices
      }, {
        icon: this.user.enabled ? 'icon-close' : 'icon-add',
        text: this.user.enabled ? t('settings', 'Disable account') : t('settings', 'Enable account'),
        action: this.enableDisableUser
      }];
      if (this.user.email !== null && this.user.email !== '') {
        actions.push({
          icon: 'icon-mail',
          text: t('settings', 'Resend welcome email'),
          action: this.sendWelcomeMail
        });
      }
      return actions.concat(this.externalActions);
    },
    // mapping saved values to objects
    editedUserQuota: {
      get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__.unlimitedQuota.id && (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(this.settings.defaultQuota, true) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__.unlimitedQuota; // unlimited
      },
      set(quota) {
        this.selectedQuota = quota;
      }
    },
    availableLanguages() {
      return this.languages[0].languages.concat(this.languages[1].languages);
    }
  },
  async beforeMount() {
    if (this.user.manager) {
      await this.initManager(this.user.manager);
    }
  },
  methods: {
    wipeUserDevices() {
      const userid = this.user.id;
      OC.dialogs.confirmDestructive(t('settings', 'In case of lost device or exiting the organization, this can remotely wipe the Nextcloud data from all devices associated with {userid}. Only works if the devices are connected to the internet.', {
        userid
      }), t('settings', 'Remote wipe of devices'), {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('settings', 'Wipe {userid}\'s devices', {
          userid
        }),
        confirmClasses: 'error',
        cancel: t('settings', 'Cancel')
      }, result => {
        if (result) {
          this.loading.wipe = true;
          this.loading.all = true;
          this.$store.dispatch('wipeUserDevices', userid).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('settings', 'Wiped {userid}\'s devices', {
            userid
          })), {
            timeout: 2000
          }).finally(() => {
            this.loading.wipe = false;
            this.loading.all = false;
          });
        }
      }, true);
    },
    filterManagers(managers) {
      return managers.filter(manager => manager.id !== this.user.id);
    },
    async initManager(userId) {
      await this.$store.dispatch('getUser', userId).then(response => {
        this.currentManager = response === null || response === void 0 ? void 0 : response.data.ocs.data;
      });
    },
    async searchInitialUserManager() {
      this.loadingPossibleManagers = true;
      await this.searchUserManager();
      this.loadingPossibleManagers = false;
    },
    async searchUserManager(query) {
      await this.$store.dispatch('searchUsers', {
        offset: 0,
        limit: 10,
        search: query
      }).then(response => {
        const users = response !== null && response !== void 0 && response.data ? this.filterManagers(Object.values(response === null || response === void 0 ? void 0 : response.data.ocs.data.users)) : [];
        if (users.length > 0) {
          this.possibleManagers = users;
        }
      });
    },
    async updateUserManager(manager) {
      if (manager === null) {
        this.currentManager = '';
      }
      this.loading.manager = true;
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'manager',
          value: this.currentManager ? this.currentManager.id : ''
        });
      } catch (error) {
        // TRANSLATORS This string describes a line manager in the context of an organization
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('setting', 'Failed to update line manager'));
        console.error(error);
      } finally {
        this.loading.manager = false;
      }
    },
    deleteUser() {
      const userid = this.user.id;
      OC.dialogs.confirmDestructive(t('settings', 'Fully delete {userid}\'s account including all their personal files, app data, etc.', {
        userid
      }), t('settings', 'Account deletion'), {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('settings', 'Delete {userid}\'s account', {
          userid
        }),
        confirmClasses: 'error',
        cancel: t('settings', 'Cancel')
      }, result => {
        if (result) {
          this.loading.delete = true;
          this.loading.all = true;
          return this.$store.dispatch('deleteUser', userid).then(() => {
            this.loading.delete = false;
            this.loading.all = false;
          });
        }
      }, true);
    },
    enableDisableUser() {
      this.loading.delete = true;
      this.loading.all = true;
      const userid = this.user.id;
      const enabled = !this.user.enabled;
      return this.$store.dispatch('enableDisableUser', {
        userid,
        enabled
      }).then(() => {
        this.loading.delete = false;
        this.loading.all = false;
      });
    },
    /**
     * Set user displayName
     *
     * @param {string} displayName The display name
     */
    updateDisplayName() {
      this.loading.displayName = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'displayname',
        value: this.editedDisplayName
      }).then(() => {
        this.loading.displayName = false;
        if (this.editedDisplayName === this.user.displayname) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('setting', 'Display name was successfully changed'));
        }
      });
    },
    /**
     * Set user password
     *
     * @param {string} password The email address
     */
    updatePassword() {
      this.loading.password = true;
      if (this.editedPassword.length === 0) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('setting', "Password can't be empty"));
        this.loading.password = false;
      } else {
        this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'password',
          value: this.editedPassword
        }).then(() => {
          this.loading.password = false;
          this.editedPassword = '';
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('setting', 'Password was successfully changed'));
        });
      }
    },
    /**
     * Set user mailAddress
     *
     * @param {string} mailAddress The email address
     */
    updateEmail() {
      this.loading.mailAddress = true;
      if (this.editedMail === '') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('setting', "Email can't be empty"));
        this.loading.mailAddress = false;
        this.editedMail = this.user.email;
      } else {
        this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'email',
          value: this.editedMail
        }).then(() => {
          this.loading.mailAddress = false;
          if (this.editedMail === this.user.email) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('setting', 'Email was successfully changed'));
          }
        });
      }
    },
    /**
     * Create a new group and add user to it
     *
     * @param {string} gid Group id
     */
    async createGroup(_ref) {
      let {
        name: gid
      } = _ref;
      this.loading = {
        groups: true,
        subadmins: true
      };
      try {
        await this.$store.dispatch('addGroup', gid);
        const userid = this.user.id;
        await this.$store.dispatch('addUserGroup', {
          userid,
          gid
        });
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = {
          groups: false,
          subadmins: false
        };
      }
      return this.$store.getters.getGroups[this.groups.length];
    },
    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    async addUserGroup(group) {
      if (group.isCreating) {
        // This is NcSelect's internal value for a new inputted group name
        // Ignore
        return;
      }
      this.loading.groups = true;
      const userid = this.user.id;
      const gid = group.id;
      if (group.canAdd === false) {
        return false;
      }
      try {
        await this.$store.dispatch('addUserGroup', {
          userid,
          gid
        });
      } catch (error) {
        console.error(error);
      } finally {
        this.loading.groups = false;
      }
    },
    /**
     * Remove user from group
     *
     * @param {object} group Group object
     */
    async removeUserGroup(group) {
      if (group.canRemove === false) {
        return false;
      }
      this.loading.groups = true;
      const userid = this.user.id;
      const gid = group.id;
      try {
        await this.$store.dispatch('removeUserGroup', {
          userid,
          gid
        });
        this.loading.groups = false;
        // remove user from current list if current list is the removed group
        if (this.$route.params.selectedGroup === gid) {
          this.$store.commit('deleteUser', userid);
        }
      } catch {
        this.loading.groups = false;
      }
    },
    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    async addUserSubAdmin(group) {
      this.loading.subadmins = true;
      const userid = this.user.id;
      const gid = group.id;
      try {
        await this.$store.dispatch('addUserSubAdmin', {
          userid,
          gid
        });
        this.loading.subadmins = false;
      } catch (error) {
        console.error(error);
      }
    },
    /**
     * Remove user from group
     *
     * @param {object} group Group object
     */
    async removeUserSubAdmin(group) {
      this.loading.subadmins = true;
      const userid = this.user.id;
      const gid = group.id;
      try {
        await this.$store.dispatch('removeUserSubAdmin', {
          userid,
          gid
        });
      } catch (error) {
        console.error(error);
      } finally {
        this.loading.subadmins = false;
      }
    },
    /**
     * Dispatch quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {string}
     */
    async setUserQuota() {
      let quota = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'none';
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__.unlimitedQuota;
      }
      this.loading.quota = true;

      // ensure we only send the preset id
      quota = quota.id ? quota.id : quota;
      try {
        // If human readable format, convert to raw float format
        // Else just send the raw string
        const value = ((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota, true) || quota).toString();
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'quota',
          value
        });
      } catch (error) {
        console.error(error);
      } finally {
        this.loading.quota = false;
      }
      return quota;
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {object} The validated quota object or unlimited quota if input is invalid
     */
    validateQuota(quota) {
      if (typeof quota === 'object') {
        var _quota;
        quota = ((_quota = quota) === null || _quota === void 0 ? void 0 : _quota.id) || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota, true);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_10__.unlimitedQuota;
      } else {
        // unify format output
        quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota, true));
        return {
          id: quota,
          label: quota
        };
      }
    },
    /**
     * Dispatch language set request
     *
     * @param {object} lang language object {code:'en', name:'English'}
     * @return {object}
     */
    async setUserLanguage(lang) {
      this.loading.languages = true;
      // ensure we only send the preset id
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'language',
          value: lang.code
        });
        this.loading.languages = false;
      } catch (error) {
        console.error(error);
      }
      return lang;
    },
    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail() {
      this.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('setting', 'Welcome mail sent!'), {
        timeout: 2000
      })).finally(() => {
        this.loading.all = false;
      });
    },
    async toggleEdit() {
      this.editing = !this.editing;
      if (this.editing) {
        var _this$$refs$displayNa;
        await this.$nextTick();
        (_this$$refs$displayNa = this.$refs.displayNameField) === null || _this$$refs$displayNa === void 0 || (_this$$refs$displayNa = _this$$refs$displayNa.$refs) === null || _this$$refs$displayNa === void 0 || (_this$$refs$displayNa = _this$$refs$displayNa.inputField) === null || _this$$refs$displayNa === void 0 || (_this$$refs$displayNa = _this$$refs$displayNa.$refs) === null || _this$$refs$displayNa === void 0 || (_this$$refs$displayNa = _this$$refs$displayNa.input) === null || _this$$refs$displayNa === void 0 || _this$$refs$displayNa.focus();
      }
      if (this.editedDisplayName !== this.user.displayname) {
        this.editedDisplayName = this.user.displayname;
      } else if (this.editedMail !== this.user.email) {
        var _this$user$email2;
        this.editedMail = (_this$user$email2 = this.user.email) !== null && _this$user$email2 !== void 0 ? _this$user$email2 : '';
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js":
/*!***********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSettingsDialog.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSettingsSection.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsSection.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../constants/GroupManagement.ts */ "./apps/settings/src/constants/GroupManagement.ts");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserSettingsDialog',
  components: {
    NcAppSettingsDialog: _nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcAppSettingsSection: _nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcSelect: _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7__["default"]
  },
  props: {
    open: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      selectedQuota: false,
      loadingSendMail: false
    };
  },
  computed: {
    groupSorting: {
      get() {
        return this.$store.getters.getGroupSorting === _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_8__.GroupSorting.GroupName ? 'name' : 'member-count';
      },
      set(sorting) {
        this.$store.commit('setGroupSorting', sorting === 'name' ? _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_8__.GroupSorting.GroupName : _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_8__.GroupSorting.UserCount);
      }
    },
    /**
     * Admin has configured `sort_groups_by_name` in the system config
     */
    isGroupSortingEnforced() {
      return this.$store.getters.getServerData.forceSortGroupByName;
    },
    isModalOpen: {
      get() {
        return this.open;
      },
      set(open) {
        this.$emit('update:open', open);
      }
    },
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    showLanguages: {
      get() {
        return this.showConfig.showLanguages;
      },
      set(status) {
        this.setShowConfig('showLanguages', status);
      }
    },
    showLastLogin: {
      get() {
        return this.showConfig.showLastLogin;
      },
      set(status) {
        this.setShowConfig('showLastLogin', status);
      }
    },
    showUserBackend: {
      get() {
        return this.showConfig.showUserBackend;
      },
      set(status) {
        this.setShowConfig('showUserBackend', status);
      }
    },
    showStoragePath: {
      get() {
        return this.showConfig.showStoragePath;
      },
      set(status) {
        this.setShowConfig('showStoragePath', status);
      }
    },
    quotaOptions() {
      // convert the preset array into objects
      const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({
        id: cur,
        label: cur
      }), []);
      // add default presets
      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__.unlimitedQuota);
      }
      return quotaPreset;
    },
    defaultQuota: {
      get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__.unlimitedQuota; // unlimited
      },
      set(quota) {
        this.selectedQuota = quota;
      }
    },
    sendWelcomeMail: {
      get() {
        return this.settings.newUserSendEmail;
      },
      async set(value) {
        try {
          this.loadingSendMail = true;
          this.$store.commit('setServerData', {
            ...this.settings,
            newUserSendEmail: value
          });
          await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/settings/users/preferences/newUser.sendEmail'), {
            value: value ? 'yes' : 'no'
          });
        } catch (e) {
          console.error('could not update newUser.sendEmail preference: ' + e.message, e);
        } finally {
          this.loadingSendMail = false;
        }
      }
    }
  },
  methods: {
    setShowConfig(key, status) {
      this.$store.commit('setShowConfig', {
        key,
        value: status
      });
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {object} The validated quota object or unlimited quota if input is invalid
     */
    validateQuota(quota) {
      if (typeof quota === 'object') {
        var _quota;
        quota = ((_quota = quota) === null || _quota === void 0 ? void 0 : _quota.id) || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__.unlimitedQuota;
      } else {
        // unify format output
        quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota));
        return {
          id: quota,
          label: quota
        };
      }
    },
    /**
     * Dispatch default quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     */
    setDefaultQuota() {
      let quota = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'none';
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_9__.unlimitedQuota;
      }
      this.$store.dispatch('setAppConfig', {
        app: 'files',
        key: 'default_quota',
        // ensure we only send the preset id
        value: quota.id ? quota.id : quota
      }).then(() => {
        if (typeof quota !== 'object') {
          quota = {
            id: quota,
            label: quota
          };
        }
        this.defaultQuota = quota;
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.mjs");
/* harmony import */ var _components_UserList_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/UserList.vue */ "./apps/settings/src/components/UserList.vue");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_3__.defineComponent)({
  name: 'UserManagement',
  components: {
    NcAppContent: _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    UserList: _components_UserList_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data() {
    return {
      // temporary value used for multiselect change
      externalActions: []
    };
  },
  computed: {
    pageHeading() {
      var _matchHeading$this$se;
      if (this.selectedGroupDecoded === null) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Active accounts');
      }
      const matchHeading = {
        admin: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Admins'),
        disabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Disabled accounts')
      };
      return (_matchHeading$this$se = matchHeading[this.selectedGroupDecoded]) !== null && _matchHeading$this$se !== void 0 ? _matchHeading$this$se : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Account group: {group}', {
        group: this.selectedGroupDecoded
      });
    },
    selectedGroup() {
      return this.$route.params.selectedGroup;
    },
    selectedGroupDecoded() {
      return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null;
    }
  },
  beforeMount() {
    this.$store.commit('initGroups', {
      groups: this.$store.getters.getServerData.groups,
      orderBy: this.$store.getters.getServerData.sortGroups,
      userCount: this.$store.getters.getServerData.userCount
    });
    this.$store.dispatch('getPasswordPolicyMinLength');
  },
  created() {
    var _window$OCA, _window$OCA$Settings, _window$OCA$Settings$;
    // init the OCA.Settings.UserList object
    window.OCA = (_window$OCA = window.OCA) !== null && _window$OCA !== void 0 ? _window$OCA : {};
    window.OCA.Settings = (_window$OCA$Settings = window.OCA.Settings) !== null && _window$OCA$Settings !== void 0 ? _window$OCA$Settings : {};
    window.OCA.Settings.UserList = (_window$OCA$Settings$ = window.OCA.Settings.UserList) !== null && _window$OCA$Settings$ !== void 0 ? _window$OCA$Settings$ : {};
    // and add the registerAction method
    window.OCA.Settings.UserList.registerAction = this.registerAction;
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
    /**
     * Register a new action for the user menu
     *
     * @param {string} icon the icon class
     * @param {string} text the text to display
     * @param {Function} action the function to run
     * @return {Array}
     */
    registerAction(icon, text, action) {
      this.externalActions.push({
        icon,
        text,
        action
      });
      return this.externalActions;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("Fragment", [_vm.showRemoveGroupModal ? _c("NcModal", {
    on: {
      close: function ($event) {
        _vm.showRemoveGroupModal = false;
      }
    }
  }, [_c("div", {
    staticClass: "modal__content"
  }, [_c("h2", {
    staticClass: "modal__header"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Please confirm the group removal")) + "\n\t\t\t")]), _vm._v(" "), _c("NcNoteCard", {
    attrs: {
      type: "warning",
      "show-alert": ""
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", 'You are about to remove the group "{group}". The accounts will NOT be deleted.', {
    group: _vm.name
  })) + "\n\t\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "modal__button-row"
  }, [_c("NcButton", {
    attrs: {
      type: "secondary"
    },
    on: {
      click: function ($event) {
        _vm.showRemoveGroupModal = false;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Cancel")) + "\n\t\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      type: "primary"
    },
    on: {
      click: _vm.removeGroup
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Confirm")) + "\n\t\t\t\t")])], 1)], 1)]) : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
    key: _vm.id,
    attrs: {
      exact: true,
      name: _vm.name,
      to: {
        name: "group",
        params: {
          selectedGroup: encodeURIComponent(_vm.id)
        }
      },
      loading: _vm.loadingRenameGroup,
      "menu-open": _vm.openGroupMenu
    },
    on: {
      "update:menuOpen": _vm.handleGroupMenuOpen
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("AccountGroup", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }, {
      key: "counter",
      fn: function () {
        return [_vm.count ? _c("NcCounterBubble", {
          attrs: {
            type: _vm.active ? "highlighted" : undefined
          }
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.count) + "\n\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }, {
      key: "actions",
      fn: function () {
        return [_vm.id !== "admin" && _vm.id !== "disabled" && _vm.settings.isAdmin ? _c("NcActionInput", {
          ref: "displayNameInput",
          attrs: {
            "trailing-button-label": _vm.t("settings", "Submit"),
            type: "text",
            value: _vm.name,
            label: _vm.t("settings", "Rename group")
          },
          on: {
            submit: function ($event) {
              return _vm.renameGroup(_vm.id);
            }
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("Pencil", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 580569589)
        }) : _vm._e(), _vm._v(" "), _vm.id !== "admin" && _vm.id !== "disabled" && _vm.settings.isAdmin ? _c("NcActionButton", {
          on: {
            click: function ($event) {
              _vm.showRemoveGroupModal = true;
            }
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("Delete", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 2705356561)
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Remove group")) + "\n\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }])
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("Fragment", [_vm.showConfig.showNewUserForm ? _c("NewUserModal", {
    attrs: {
      loading: _vm.loading,
      "new-user": _vm.newUser,
      "quota-options": _vm.quotaOptions
    },
    on: {
      reset: _vm.resetForm,
      close: _vm.closeModal
    }
  }) : _vm._e(), _vm._v(" "), _vm.filteredUsers.length === 0 ? _c("NcEmptyContent", {
    staticClass: "empty",
    attrs: {
      name: _vm.isInitialLoad && _vm.loading.users ? null : _vm.t("settings", "No accounts")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.isInitialLoad && _vm.loading.users ? _c("NcLoadingIcon", {
          attrs: {
            name: _vm.t("settings", "Loading accounts …"),
            size: 64
          }
        }) : _c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiAccountGroup,
            size: 64
          }
        })];
      },
      proxy: true
    }], null, false, 226056511)
  }) : _c("VirtualList", {
    style: _vm.style,
    attrs: {
      "data-component": _vm.UserRow,
      "data-sources": _vm.filteredUsers,
      "data-key": "id",
      "data-cy-user-list": "",
      "item-height": _vm.rowHeight,
      "extra-props": {
        users: _vm.users,
        settings: _vm.settings,
        hasObfuscated: _vm.hasObfuscated,
        groups: _vm.groups,
        subAdminsGroups: _vm.subAdminsGroups,
        quotaOptions: _vm.quotaOptions,
        languages: _vm.languages,
        externalActions: _vm.externalActions
      }
    },
    on: {
      "scroll-end": _vm.handleScrollEnd
    },
    scopedSlots: _vm._u([{
      key: "before",
      fn: function () {
        return [_c("caption", {
          staticClass: "hidden-visually"
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "List of accounts. This list is not fully rendered for performance reasons. The accounts will be rendered as you navigate through the list.")) + "\n\t\t\t")])];
      },
      proxy: true
    }, {
      key: "header",
      fn: function () {
        return [_c("UserListHeader", {
          attrs: {
            "has-obfuscated": _vm.hasObfuscated
          }
        })];
      },
      proxy: true
    }, {
      key: "footer",
      fn: function () {
        return [_c("UserListFooter", {
          attrs: {
            loading: _vm.loading.users,
            "filtered-users": _vm.filteredUsers
          }
        })];
      },
      proxy: true
    }])
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcModal", _vm._g({
    staticClass: "modal",
    attrs: {
      size: "small"
    }
  }, _vm.$listeners), [_c("form", {
    staticClass: "modal__form",
    attrs: {
      "data-test": "form",
      disabled: _vm.loading.all
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.createUser.apply(null, arguments);
      }
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("settings", "New user")))]), _vm._v(" "), _c("NcTextField", {
    ref: "username",
    staticClass: "modal__item",
    attrs: {
      "data-test": "username",
      value: _vm.newUser.id,
      disabled: _vm.settings.newUserGenerateUserID,
      label: _vm.usernameLabel,
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false",
      pattern: "[a-zA-Z0-9 _\\.@\\-']+",
      required: ""
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.newUser, "id", $event);
      }
    }
  }), _vm._v(" "), _c("NcTextField", {
    staticClass: "modal__item",
    attrs: {
      "data-test": "displayName",
      value: _vm.newUser.displayName,
      label: _vm.t("settings", "Display name"),
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.newUser, "displayName", $event);
      }
    }
  }), _vm._v(" "), !_vm.settings.newUserRequireEmail ? _c("span", {
    staticClass: "modal__hint",
    attrs: {
      id: "password-email-hint"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Either password or email is required")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("NcPasswordField", {
    ref: "password",
    staticClass: "modal__item",
    attrs: {
      "data-test": "password",
      value: _vm.newUser.password,
      minlength: _vm.minPasswordLength,
      maxlength: 469,
      "aria-describedby": "password-email-hint",
      label: _vm.newUser.mailAddress === "" ? _vm.t("settings", "Password (required)") : _vm.t("settings", "Password"),
      autocapitalize: "none",
      autocomplete: "new-password",
      spellcheck: "false",
      required: _vm.newUser.mailAddress === ""
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.newUser, "password", $event);
      }
    }
  }), _vm._v(" "), _c("NcTextField", {
    staticClass: "modal__item",
    attrs: {
      "data-test": "email",
      type: "email",
      value: _vm.newUser.mailAddress,
      "aria-describedby": "password-email-hint",
      label: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail ? _vm.t("settings", "Email (required)") : _vm.t("settings", "Email"),
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false",
      required: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.newUser, "mailAddress", $event);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "modal__item"
  }, [_c("label", {
    staticClass: "modal__label",
    attrs: {
      for: "new-user-groups"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(!_vm.settings.isAdmin ? _vm.t("settings", "Groups (required)") : _vm.t("settings", "Groups")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "modal__select",
    attrs: {
      "input-id": "new-user-groups",
      placeholder: _vm.t("settings", "Set user groups"),
      disabled: _vm.loading.groups || _vm.loading.all,
      options: _vm.canAddGroups,
      value: _vm.newUser.groups,
      label: "name",
      "close-on-select": false,
      multiple: true,
      taggable: true,
      required: !_vm.settings.isAdmin
    },
    on: {
      input: _vm.handleGroupInput,
      "option:created": _vm.createGroup
    }
  })], 1), _vm._v(" "), _vm.subAdminsGroups.length > 0 ? _c("div", {
    staticClass: "modal__item"
  }, [_c("label", {
    staticClass: "modal__label",
    attrs: {
      for: "new-user-sub-admin"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Administered groups")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "modal__select",
    attrs: {
      "input-id": "new-user-sub-admin",
      placeholder: _vm.t("settings", "Set user as admin for …"),
      options: _vm.subAdminsGroups,
      "close-on-select": false,
      multiple: true,
      label: "name"
    },
    model: {
      value: _vm.newUser.subAdminsGroups,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "subAdminsGroups", $$v);
      },
      expression: "newUser.subAdminsGroups"
    }
  })], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "modal__item"
  }, [_c("label", {
    staticClass: "modal__label",
    attrs: {
      for: "new-user-quota"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Quota")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "modal__select",
    attrs: {
      "input-id": "new-user-quota",
      placeholder: _vm.t("settings", "Set user quota"),
      options: _vm.quotaOptions,
      clearable: false,
      taggable: true,
      "create-option": _vm.validateQuota
    },
    model: {
      value: _vm.newUser.quota,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "quota", $$v);
      },
      expression: "newUser.quota"
    }
  })], 1), _vm._v(" "), _vm.showConfig.showLanguages ? _c("div", {
    staticClass: "modal__item"
  }, [_c("label", {
    staticClass: "modal__label",
    attrs: {
      for: "new-user-language"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Language")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "modal__select",
    attrs: {
      "input-id": "new-user-language",
      placeholder: _vm.t("settings", "Set default language"),
      clearable: false,
      selectable: option => !option.languages,
      "filter-by": _vm.languageFilterBy,
      options: _vm.languages,
      label: "name"
    },
    model: {
      value: _vm.newUser.language,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "language", $$v);
      },
      expression: "newUser.language"
    }
  })], 1) : _vm._e(), _vm._v(" "), _c("div", {
    class: ["modal__item managers", {
      "icon-loading-small": _vm.loading.manager
    }]
  }, [_c("label", {
    staticClass: "modal__label",
    attrs: {
      for: "new-user-manager"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Manager")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "modal__select",
    attrs: {
      "input-id": "new-user-manager",
      placeholder: _vm.managerLabel,
      options: _vm.possibleManagers,
      "user-select": true,
      label: "displayname"
    },
    on: {
      search: _vm.searchUserManager
    },
    model: {
      value: _vm.newUser.manager,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "manager", $$v);
      },
      expression: "newUser.manager"
    }
  })], 1), _vm._v(" "), _c("NcButton", {
    staticClass: "modal__submit",
    attrs: {
      "data-test": "submit",
      type: "primary",
      "native-type": "submit"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Add new user")) + "\n\t\t")])], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("tr", {
    staticClass: "footer"
  }, [_c("th", {
    attrs: {
      scope: "row"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v(_vm._s(_vm.t("settings", "Total rows summary")))])]), _vm._v(" "), _c("td", {
    staticClass: "footer__cell footer__cell--loading"
  }, [_vm.loading ? _c("NcLoadingIcon", {
    attrs: {
      title: _vm.t("settings", "Loading users …"),
      size: 32
    }
  }) : _vm._e()], 1), _vm._v(" "), _c("td", {
    staticClass: "footer__cell footer__cell--count footer__cell--multiline"
  }, [_c("span", {
    attrs: {
      "aria-describedby": "user-count-desc"
    }
  }, [_vm._v(_vm._s(_vm.userCount))]), _vm._v(" "), _c("span", {
    staticClass: "hidden-visually",
    attrs: {
      id: "user-count-desc"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Scroll to load more rows")) + "\n\t\t")])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("tr", {
    staticClass: "header"
  }, [_c("th", {
    staticClass: "header__cell header__cell--avatar",
    attrs: {
      "data-cy-user-list-header-avatar": "",
      scope: "col"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Avatar")) + "\n\t\t")])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--displayname",
    attrs: {
      "data-cy-user-list-header-displayname": "",
      scope: "col"
    }
  }, [_c("strong", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Display name")) + "\n\t\t")])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--username",
    attrs: {
      "data-cy-user-list-header-username": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Account name")) + "\n\t\t")])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    class: {
      "header__cell--obfuscated": _vm.hasObfuscated
    },
    attrs: {
      "data-cy-user-list-header-password": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.passwordLabel))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    attrs: {
      "data-cy-user-list-header-email": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Email")))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      "data-cy-user-list-header-groups": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Groups")))])]), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      "data-cy-user-list-header-subadmins": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Group admin for")))])]) : _vm._e(), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    attrs: {
      "data-cy-user-list-header-quota": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Quota")))])]), _vm._v(" "), _vm.showConfig.showLanguages ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      "data-cy-user-list-header-languages": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Language")))])]) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      "data-cy-user-list-header-storage-location": "",
      scope: "col"
    }
  }, [_vm.showConfig.showUserBackend ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Account backend")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("span", {
    staticClass: "header__subtitle"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Storage location")) + "\n\t\t")]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("th", {
    staticClass: "header__cell",
    attrs: {
      "data-cy-user-list-header-last-login": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Last login")))])]) : _vm._e(), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--large header__cell--fill",
    attrs: {
      "data-cy-user-list-header-manager": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Manager")))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--actions",
    attrs: {
      "data-cy-user-list-header-actions": "",
      scope: "col"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "User actions")) + "\n\t\t")])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm$user$displayname, _vm$user$email, _vm$userGroupsLabels, _vm$userSubAdminsGrou;
  var _vm = this,
    _c = _vm._self._c;
  return _c("tr", {
    staticClass: "user-list__row",
    attrs: {
      "data-cy-user-row": _vm.user.id
    }
  }, [_c("td", {
    staticClass: "row__cell row__cell--avatar",
    attrs: {
      "data-cy-user-list-cell-avatar": ""
    }
  }, [_vm.isLoadingUser ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("settings", "Loading account …"),
      size: 32
    }
  }) : _vm.visible ? _c("NcAvatar", {
    attrs: {
      "disable-menu": "",
      "show-user-status": false,
      user: _vm.user.id
    }
  }) : _vm._e()], 1), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--displayname",
    attrs: {
      "data-cy-user-list-cell-displayname": ""
    }
  }, [_vm.editing && _vm.user.backendCapabilities.setDisplayName ? [_c("NcTextField", {
    ref: "displayNameField",
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.loading.displayName
    },
    attrs: {
      "data-cy-user-list-input-displayname": "",
      "data-loading": _vm.loading.displayName || undefined,
      "trailing-button-label": _vm.t("settings", "Submit"),
      "show-trailing-button": true,
      disabled: _vm.loading.displayName || _vm.isLoadingField,
      label: _vm.t("settings", "Change display name"),
      "trailing-button-icon": "arrowRight",
      value: _vm.editedDisplayName,
      autocapitalize: "off",
      autocomplete: "off",
      spellcheck: "false"
    },
    on: {
      "update:value": function ($event) {
        _vm.editedDisplayName = $event;
      },
      "trailing-button-click": _vm.updateDisplayName
    }
  })] : !_vm.isObfuscated ? _c("strong", {
    attrs: {
      title: ((_vm$user$displayname = _vm.user.displayname) === null || _vm$user$displayname === void 0 ? void 0 : _vm$user$displayname.length) > 20 ? _vm.user.displayname : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--username",
    attrs: {
      "data-cy-user-list-cell-username": ""
    }
  }, [_c("span", {
    staticClass: "row__subtitle"
  }, [_vm._v(_vm._s(_vm.user.id))])]), _vm._v(" "), _c("td", {
    staticClass: "row__cell",
    class: {
      "row__cell--obfuscated": _vm.hasObfuscated
    },
    attrs: {
      "data-cy-user-list-cell-password": ""
    }
  }, [_vm.editing && _vm.settings.canChangePassword && _vm.user.backendCapabilities.setPassword ? [_c("NcTextField", {
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.loading.password
    },
    attrs: {
      "data-cy-user-list-input-password": "",
      "data-loading": _vm.loading.password || undefined,
      "trailing-button-label": _vm.t("settings", "Submit"),
      "show-trailing-button": true,
      disabled: _vm.loading.password || _vm.isLoadingField,
      minlength: _vm.minPasswordLength,
      maxlength: "469",
      label: _vm.t("settings", "Set new password"),
      "trailing-button-icon": "arrowRight",
      value: _vm.editedPassword,
      autocapitalize: "off",
      autocomplete: "new-password",
      required: "",
      spellcheck: "false",
      type: "password"
    },
    on: {
      "update:value": function ($event) {
        _vm.editedPassword = $event;
      },
      "trailing-button-click": _vm.updatePassword
    }
  })] : _vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "You do not have permissions to see the details of this account")) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell",
    attrs: {
      "data-cy-user-list-cell-email": ""
    }
  }, [_vm.editing ? [_c("NcTextField", {
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.loading.mailAddress
    },
    attrs: {
      "data-cy-user-list-input-email": "",
      "data-loading": _vm.loading.mailAddress || undefined,
      "show-trailing-button": true,
      "trailing-button-label": _vm.t("settings", "Submit"),
      label: _vm.t("settings", "Set new email address"),
      disabled: _vm.loading.mailAddress || _vm.isLoadingField,
      "trailing-button-icon": "arrowRight",
      value: _vm.editedMail,
      autocapitalize: "off",
      autocomplete: "email",
      spellcheck: "false",
      type: "email"
    },
    on: {
      "update:value": function ($event) {
        _vm.editedMail = $event;
      },
      "trailing-button-click": _vm.updateEmail
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: ((_vm$user$email = _vm.user.email) === null || _vm$user$email === void 0 ? void 0 : _vm$user$email.length) > 20 ? _vm.user.email : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.email) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--large row__cell--multiline",
    attrs: {
      "data-cy-user-list-cell-groups": ""
    }
  }, [_vm.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "groups" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Add user to group")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "data-cy-user-list-input-groups": "",
      "data-loading": _vm.loading.groups || undefined,
      "input-id": "groups" + _vm.uniqueId,
      "close-on-select": false,
      disabled: _vm.isLoadingField,
      loading: _vm.loading.groups,
      multiple: true,
      "append-to-body": false,
      options: _vm.availableGroups,
      placeholder: _vm.t("settings", "Add account to group"),
      taggable: _vm.settings.isAdmin,
      value: _vm.userGroups,
      label: "name",
      "no-wrap": true,
      "create-option": value => ({
        name: value,
        isCreating: true
      })
    },
    on: {
      "option:created": _vm.createGroup,
      "option:selected": options => _vm.addUserGroup(options.at(-1)),
      "option:deselected": _vm.removeUserGroup
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: ((_vm$userGroupsLabels = _vm.userGroupsLabels) === null || _vm$userGroupsLabels === void 0 ? void 0 : _vm$userGroupsLabels.length) > 40 ? _vm.userGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userGroupsLabels) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("td", {
    staticClass: "row__cell row__cell--large row__cell--multiline",
    attrs: {
      "data-cy-user-list-cell-subadmins": ""
    }
  }, [_vm.editing && _vm.settings.isAdmin && _vm.subAdminsGroups.length > 0 ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "subadmins" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set account as admin for")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "data-cy-user-list-input-subadmins": "",
      "data-loading": _vm.loading.subadmins || undefined,
      "input-id": "subadmins" + _vm.uniqueId,
      "close-on-select": false,
      disabled: _vm.isLoadingField,
      loading: _vm.loading.subadmins,
      label: "name",
      "append-to-body": false,
      multiple: true,
      "no-wrap": true,
      options: _vm.subAdminsGroups,
      placeholder: _vm.t("settings", "Set account as admin for"),
      value: _vm.userSubAdminsGroups
    },
    on: {
      "option:deselected": _vm.removeUserSubAdmin,
      "option:selected": options => _vm.addUserSubAdmin(options.at(-1))
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: ((_vm$userSubAdminsGrou = _vm.userSubAdminsGroupsLabels) === null || _vm$userSubAdminsGrou === void 0 ? void 0 : _vm$userSubAdminsGrou.length) > 40 ? _vm.userSubAdminsGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userSubAdminsGroupsLabels) + "\n\t\t")]) : _vm._e()], 2) : _vm._e(), _vm._v(" "), _c("td", {
    staticClass: "row__cell",
    attrs: {
      "data-cy-user-list-cell-quota": ""
    }
  }, [_vm.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "quota" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Select account quota")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "close-on-select": true,
      "create-option": _vm.validateQuota,
      "data-cy-user-list-input-quota": "",
      "data-loading": _vm.loading.quota || undefined,
      disabled: _vm.isLoadingField,
      loading: _vm.loading.quota,
      "append-to-body": false,
      clearable: false,
      "input-id": "quota" + _vm.uniqueId,
      options: _vm.quotaOptions,
      placeholder: _vm.t("settings", "Select account quota"),
      taggable: true
    },
    on: {
      "option:selected": _vm.setUserQuota
    },
    model: {
      value: _vm.editedUserQuota,
      callback: function ($$v) {
        _vm.editedUserQuota = $$v;
      },
      expression: "editedUserQuota"
    }
  })] : !_vm.isObfuscated ? [_c("span", {
    attrs: {
      id: "quota-progress" + _vm.uniqueId
    }
  }, [_vm._v(_vm._s(_vm.userQuota) + " (" + _vm._s(_vm.usedSpace) + ")")]), _vm._v(" "), _c("NcProgressBar", {
    staticClass: "row__progress",
    class: {
      "row__progress--warn": _vm.usedQuota > 80
    },
    attrs: {
      "aria-labelledby": "quota-progress" + _vm.uniqueId,
      value: _vm.usedQuota
    }
  })] : _vm._e()], 2), _vm._v(" "), _vm.showConfig.showLanguages ? _c("td", {
    staticClass: "row__cell row__cell--large",
    attrs: {
      "data-cy-user-list-cell-language": ""
    }
  }, [_vm.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "language" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set the language")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      id: "language" + _vm.uniqueId,
      "data-cy-user-list-input-language": "",
      "data-loading": _vm.loading.languages || undefined,
      "allow-empty": false,
      disabled: _vm.isLoadingField,
      loading: _vm.loading.languages,
      clearable: false,
      "append-to-body": false,
      options: _vm.availableLanguages,
      placeholder: _vm.t("settings", "No language set"),
      value: _vm.userLanguage,
      label: "name"
    },
    on: {
      input: _vm.setUserLanguage
    }
  })] : !_vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.userLanguage.name) + "\n\t\t")]) : _vm._e()], 2) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("td", {
    staticClass: "row__cell row__cell--large",
    attrs: {
      "data-cy-user-list-cell-storage-location": ""
    }
  }, [!_vm.isObfuscated ? [_vm.showConfig.showUserBackend ? _c("span", [_vm._v(_vm._s(_vm.user.backend))]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("span", {
    staticClass: "row__subtitle",
    attrs: {
      title: _vm.user.storageLocation
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.storageLocation) + "\n\t\t\t")]) : _vm._e()] : _vm._e()], 2) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("td", {
    staticClass: "row__cell",
    attrs: {
      title: _vm.userLastLoginTooltip,
      "data-cy-user-list-cell-last-login": ""
    }
  }, [!_vm.isObfuscated ? _c("span", [_vm._v(_vm._s(_vm.userLastLogin))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--large row__cell--fill",
    attrs: {
      "data-cy-user-list-cell-manager": ""
    }
  }, [_vm.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "manager" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.managerLabel) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select--fill",
    attrs: {
      "data-cy-user-list-input-manager": "",
      "data-loading": _vm.loading.manager || undefined,
      "input-id": "manager" + _vm.uniqueId,
      "close-on-select": true,
      disabled: _vm.isLoadingField,
      "append-to-body": false,
      loading: _vm.loadingPossibleManagers || _vm.loading.manager,
      label: "displayname",
      options: _vm.possibleManagers,
      placeholder: _vm.managerLabel
    },
    on: {
      open: _vm.searchInitialUserManager,
      search: _vm.searchUserManager,
      "option:selected": _vm.updateUserManager
    },
    model: {
      value: _vm.currentManager,
      callback: function ($$v) {
        _vm.currentManager = $$v;
      },
      expression: "currentManager"
    }
  })] : !_vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.user.manager) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--actions",
    attrs: {
      "data-cy-user-list-cell-actions": ""
    }
  }, [_vm.visible && !_vm.isObfuscated && _vm.canEdit && !_vm.loading.all ? _c("UserRowActions", {
    attrs: {
      actions: _vm.userActions,
      disabled: _vm.isLoadingField,
      edit: _vm.editing,
      user: _vm.user
    },
    on: {
      "update:edit": _vm.toggleEdit
    }
  }) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36 ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcActions", {
    attrs: {
      "aria-label": _vm.t("settings", "Toggle account actions menu"),
      disabled: _vm.disabled,
      inline: 1
    }
  }, [_c("NcActionButton", {
    attrs: {
      "data-cy-user-list-action-toggle-edit": "".concat(_vm.edit),
      disabled: _vm.disabled
    },
    on: {
      click: _vm.toggleEdit
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          key: _vm.editSvg,
          attrs: {
            svg: _vm.editSvg,
            "aria-hidden": "true"
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t" + _vm._s(_vm.edit ? _vm.t("settings", "Done") : _vm.t("settings", "Edit")) + "\n\t\t")]), _vm._v(" "), _vm._l(_vm.actions, function (_ref, index) {
    let {
      action,
      icon,
      text
    } = _ref;
    return _c("NcActionButton", {
      key: index,
      attrs: {
        disabled: _vm.disabled,
        "aria-label": text,
        icon: icon
      },
      on: {
        click: event => action(event, {
          ..._vm.user
        })
      }
    }, [_vm._v("\n\t\t" + _vm._s(text) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppSettingsDialog", {
    attrs: {
      open: _vm.isModalOpen,
      "show-navigation": true,
      name: _vm.t("settings", "Account management settings")
    },
    on: {
      "update:open": function ($event) {
        _vm.isModalOpen = $event;
      }
    }
  }, [_c("NcAppSettingsSection", {
    attrs: {
      id: "visibility-settings",
      name: _vm.t("settings", "Visibility")
    }
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showLanguages",
      checked: _vm.showLanguages
    },
    on: {
      "update:checked": function ($event) {
        _vm.showLanguages = $event;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show language")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showUserBackend",
      checked: _vm.showUserBackend
    },
    on: {
      "update:checked": function ($event) {
        _vm.showUserBackend = $event;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show account backend")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showStoragePath",
      checked: _vm.showStoragePath
    },
    on: {
      "update:checked": function ($event) {
        _vm.showStoragePath = $event;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show storage path")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showLastLogin",
      checked: _vm.showLastLogin
    },
    on: {
      "update:checked": function ($event) {
        _vm.showLastLogin = $event;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show last login")) + "\n\t\t")])], 1), _vm._v(" "), _c("NcAppSettingsSection", {
    attrs: {
      id: "groups-sorting",
      name: _vm.t("settings", "Sorting")
    }
  }, [_vm.isGroupSortingEnforced ? _c("NcNoteCard", {
    attrs: {
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "The system config enforces sorting the groups by name. This also disables showing the member count.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("fieldset", [_c("legend", [_vm._v(_vm._s(_vm.t("settings", "Group list sorting")))]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "radio",
      checked: _vm.groupSorting,
      "data-test": "sortGroupsByMemberCount",
      disabled: _vm.isGroupSortingEnforced,
      name: "group-sorting-mode",
      value: "member-count"
    },
    on: {
      "update:checked": function ($event) {
        _vm.groupSorting = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "By member count")) + "\n\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "radio",
      checked: _vm.groupSorting,
      "data-test": "sortGroupsByName",
      disabled: _vm.isGroupSortingEnforced,
      name: "group-sorting-mode",
      value: "name"
    },
    on: {
      "update:checked": function ($event) {
        _vm.groupSorting = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "By name")) + "\n\t\t\t")])], 1)], 1), _vm._v(" "), _c("NcAppSettingsSection", {
    attrs: {
      id: "email-settings",
      name: _vm.t("settings", "Send email")
    }
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "sendWelcomeMail",
      checked: _vm.sendWelcomeMail,
      disabled: _vm.loadingSendMail
    },
    on: {
      "update:checked": function ($event) {
        _vm.sendWelcomeMail = $event;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Send welcome email to new accounts")) + "\n\t\t")])], 1), _vm._v(" "), _c("NcAppSettingsSection", {
    attrs: {
      id: "default-settings",
      name: _vm.t("settings", "Defaults")
    }
  }, [_c("NcSelect", {
    attrs: {
      "input-label": _vm.t("settings", "Default quota"),
      placement: "top",
      taggable: true,
      options: _vm.quotaOptions,
      "create-option": _vm.validateQuota,
      placeholder: _vm.t("settings", "Select default quota"),
      clearable: false
    },
    on: {
      "option:selected": _vm.setDefaultQuota
    },
    model: {
      value: _vm.defaultQuota,
      callback: function ($$v) {
        _vm.defaultQuota = $$v;
      },
      expression: "defaultQuota"
    }
  })], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("table", {
    staticClass: "user-list"
  }, [_vm._t("before"), _vm._v(" "), _c("thead", {
    ref: "thead",
    staticClass: "user-list__header",
    attrs: {
      role: "rowgroup"
    }
  }, [_vm._t("header")], 2), _vm._v(" "), _c("tbody", {
    staticClass: "user-list__body",
    style: _vm.tbodyStyle
  }, _vm._l(_vm.renderedItems, function (item, i) {
    return _c(_vm.dataComponent, _vm._b({
      key: item[_vm.dataKey],
      tag: "component",
      attrs: {
        user: item,
        visible: (i >= _vm.bufferItems || _vm.index <= _vm.bufferItems) && i < _vm.shownItems - _vm.bufferItems
      }
    }, "component", _vm.extraProps, false));
  }), 1), _vm._v(" "), _c("tfoot", {
    directives: [{
      name: "element-visibility",
      rawName: "v-element-visibility",
      value: _vm.handleFooterVisibility,
      expression: "handleFooterVisibility"
    }],
    ref: "tfoot",
    staticClass: "user-list__footer",
    attrs: {
      role: "rowgroup"
    }
  }, [_vm._t("footer")], 2)], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcAppContent", {
    attrs: {
      "page-heading": _vm.pageHeading
    }
  }, [_c("UserList", {
    attrs: {
      "selected-group": _vm.selectedGroupDecoded,
      "external-actions": _vm.externalActions
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c(_setup.NcAppNavigation, {
    attrs: {
      "aria-label": _setup.t("settings", "Account management")
    },
    scopedSlots: _vm._u([{
      key: "footer",
      fn: function () {
        return [_c(_setup.NcButton, {
          staticClass: "account-management__settings-toggle",
          attrs: {
            type: "tertiary"
          },
          on: {
            click: function ($event) {
              _setup.isDialogOpen = true;
            }
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.mdiCog
                }
              })];
            },
            proxy: true
          }])
        }, [_vm._v("\n\t\t\t" + _vm._s(_setup.t("settings", "Account management settings")) + "\n\t\t")]), _vm._v(" "), _c(_setup.UserSettingsDialog, {
          attrs: {
            open: _setup.isDialogOpen
          },
          on: {
            "update:open": function ($event) {
              _setup.isDialogOpen = $event;
            }
          }
        })];
      },
      proxy: true
    }])
  }, [_c(_setup.NcAppNavigationNew, {
    attrs: {
      "button-id": "new-user-button",
      text: _setup.t("settings", "New account")
    },
    on: {
      click: _setup.showNewUserMenu,
      keyup: [function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        return _setup.showNewUserMenu.apply(null, arguments);
      }, function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "space", 32, $event.key, [" ", "Spacebar"])) return null;
        return _setup.showNewUserMenu.apply(null, arguments);
      }]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiPlus
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _c(_setup.NcAppNavigationList, {
    staticClass: "account-management__system-list",
    attrs: {
      "data-cy-users-settings-navigation-groups": "system"
    }
  }, [_c(_setup.NcAppNavigationItem, {
    attrs: {
      id: "everyone",
      exact: true,
      name: _setup.t("settings", "Active accounts"),
      to: {
        name: "users"
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiAccount
          }
        })];
      },
      proxy: true
    }, {
      key: "counter",
      fn: function () {
        return [_setup.userCount ? _c(_setup.NcCounterBubble, {
          attrs: {
            type: !_setup.selectedGroupDecoded ? "highlighted" : undefined
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_setup.userCount) + "\n\t\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }])
  }), _vm._v(" "), _setup.isAdmin ? _c(_setup.NcAppNavigationItem, {
    attrs: {
      id: "admin",
      exact: true,
      name: _setup.t("settings", "Admins"),
      to: {
        name: "group",
        params: {
          selectedGroup: "admin"
        }
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiShieldAccount
          }
        })];
      },
      proxy: true
    }, {
      key: "counter",
      fn: function () {
        return [_setup.adminGroup && _setup.adminGroup.count > 0 ? _c(_setup.NcCounterBubble, {
          attrs: {
            type: _setup.selectedGroupDecoded === "admin" ? "highlighted" : undefined
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_setup.adminGroup.count) + "\n\t\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }], null, false, 2218088905)
  }) : _vm._e(), _vm._v(" "), _setup.disabledGroup && (_setup.disabledGroup.usercount > 0 || _setup.disabledGroup.usercount === -1) ? _c(_setup.NcAppNavigationItem, {
    attrs: {
      id: "disabled",
      exact: true,
      name: _setup.t("settings", "Disabled accounts"),
      to: {
        name: "group",
        params: {
          selectedGroup: "disabled"
        }
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiAccountOff
          }
        })];
      },
      proxy: true
    }, _setup.disabledGroup.usercount > 0 ? {
      key: "counter",
      fn: function () {
        return [_c(_setup.NcCounterBubble, {
          attrs: {
            type: _setup.selectedGroupDecoded === "disabled" ? "highlighted" : undefined
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_setup.disabledGroup.usercount) + "\n\t\t\t\t")])];
      },
      proxy: true
    } : null], null, true)
  }) : _vm._e()], 1), _vm._v(" "), _c(_setup.NcAppNavigationCaption, {
    attrs: {
      name: _setup.t("settings", "Groups"),
      disabled: _setup.loadingAddGroup,
      "aria-label": _setup.loadingAddGroup ? _setup.t("settings", "Creating group…") : _setup.t("settings", "Create group"),
      "force-menu": "",
      "is-heading": "",
      open: _setup.isAddGroupOpen
    },
    on: {
      "update:open": function ($event) {
        _setup.isAddGroupOpen = $event;
      }
    },
    scopedSlots: _vm._u([{
      key: "actionsTriggerIcon",
      fn: function () {
        return [_setup.loadingAddGroup ? _c(_setup.NcLoadingIcon) : _c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiPlus
          }
        })];
      },
      proxy: true
    }, {
      key: "actions",
      fn: function () {
        return [_c(_setup.NcActionText, {
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("AccountGroup", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }])
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_setup.t("settings", "Create group")) + "\n\t\t\t")]), _vm._v(" "), _c(_setup.NcActionInput, {
          attrs: {
            label: _setup.t("settings", "Group name"),
            "data-cy-users-settings-new-group-name": "",
            "label-outside": false,
            disabled: _setup.loadingAddGroup,
            value: _setup.newGroupName,
            error: _setup.hasAddGroupError,
            "helper-text": _setup.hasAddGroupError ? _setup.t("settings", "Please enter a valid group name") : ""
          },
          on: {
            "update:value": function ($event) {
              _setup.newGroupName = $event;
            },
            submit: _setup.createGroup
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _c(_setup.NcAppNavigationList, {
    staticClass: "account-management__group-list",
    attrs: {
      "data-cy-users-settings-navigation-groups": "custom"
    }
  }, _vm._l(_setup.userGroups, function (group) {
    return _c(_setup.GroupListItem, {
      key: group.id,
      attrs: {
        id: group.id,
        active: _setup.selectedGroupDecoded === group.id,
        name: group.title,
        count: group.count
      }
    });
  }), 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.modal__header[data-v-b3f9b202] {
  margin: 0;
}
.modal__content[data-v-b3f9b202] {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  gap: 4px 0;
}
.modal__button-row[data-v-b3f9b202] {
  display: flex;
  width: 100%;
  justify-content: space-between;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
.empty[data-v-6cba3aca] .icon-vue {
  width: 64px;
  height: 64px;
}
.empty[data-v-6cba3aca] .icon-vue svg {
  max-width: 64px;
  max-height: 64px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.modal__form[data-v-7b45e5ac] {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  gap: 4px 0;
}
.modal__item[data-v-7b45e5ac] {
  width: 100%;
}
.modal__item[data-v-7b45e5ac]:not(:focus):not(:active) {
  border-color: var(--color-border-dark);
}
.modal__hint[data-v-7b45e5ac] {
  color: var(--color-text-maxcontrast);
  margin-top: 8px;
  align-self: flex-start;
}
.modal__label[data-v-7b45e5ac] {
  display: block;
  padding: 4px 0;
}
.modal__select[data-v-7b45e5ac] {
  width: 100%;
}
.modal__submit[data-v-7b45e5ac] {
  margin-top: 20px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
.footer[data-v-97a6cb68] {
  position: relative;
  display: flex;
  min-width: 100%;
  width: fit-content;
  height: var(--row-height);
  background-color: var(--color-main-background);
}
.footer__cell[data-v-97a6cb68] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
  min-width: var(--cell-width);
  width: var(--cell-width);
  color: var(--color-main-text);
}
.footer__cell strong[data-v-97a6cb68],
.footer__cell span[data-v-97a6cb68],
.footer__cell label[data-v-97a6cb68] {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow-wrap: anywhere;
}
@media (min-width: 670px) {
.footer__cell[data-v-97a6cb68] { /* Show one &--large column between stickied columns */
}
.footer__cell--avatar[data-v-97a6cb68], .footer__cell--displayname[data-v-97a6cb68] {
    position: sticky;
    z-index: var(--sticky-column-z-index);
    background-color: var(--color-main-background);
}
.footer__cell--avatar[data-v-97a6cb68] {
    left: 0;
}
.footer__cell--displayname[data-v-97a6cb68] {
    left: var(--avatar-cell-width);
    border-right: 1px solid var(--color-border);
}
}
.footer__cell--username[data-v-97a6cb68] {
  padding-left: calc(var(--default-grid-baseline) * 3);
}
.footer__cell--avatar[data-v-97a6cb68] {
  min-width: var(--avatar-cell-width);
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
  user-select: none;
}
.footer__cell--multiline span[data-v-97a6cb68] {
  line-height: 1.3em;
  white-space: unset;
}
@supports (-webkit-line-clamp: 2) {
.footer__cell--multiline span[data-v-97a6cb68] {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
}
.footer__cell--large[data-v-97a6cb68] {
  min-width: var(--cell-width-large);
  width: var(--cell-width-large);
}
.footer__cell--obfuscated[data-v-97a6cb68] {
  min-width: 400px;
  width: 400px;
}
.footer__cell--fill[data-v-97a6cb68] {
  min-width: var(--cell-width-large);
  width: 100%;
}
.footer__cell--actions[data-v-97a6cb68] {
  position: sticky;
  right: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-left: 1px solid var(--color-border);
}
.footer__subtitle[data-v-97a6cb68] {
  color: var(--color-text-maxcontrast);
}
.footer__cell[data-v-97a6cb68] {
  position: sticky;
  color: var(--color-text-maxcontrast);
}
.footer__cell--loading[data-v-97a6cb68] {
  left: 0;
  min-width: var(--avatar-cell-width);
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
}
.footer__cell--count[data-v-97a6cb68] {
  left: var(--avatar-cell-width);
  min-width: var(--cell-width);
  width: var(--cell-width);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
.header[data-v-55420384] {
  position: relative;
  display: flex;
  min-width: 100%;
  width: fit-content;
  height: var(--row-height);
  background-color: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
}
.header__cell[data-v-55420384] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
  min-width: var(--cell-width);
  width: var(--cell-width);
  color: var(--color-main-text);
}
.header__cell strong[data-v-55420384],
.header__cell span[data-v-55420384],
.header__cell label[data-v-55420384] {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow-wrap: anywhere;
}
@media (min-width: 670px) {
.header__cell[data-v-55420384] { /* Show one &--large column between stickied columns */
}
.header__cell--avatar[data-v-55420384], .header__cell--displayname[data-v-55420384] {
    position: sticky;
    z-index: var(--sticky-column-z-index);
    background-color: var(--color-main-background);
}
.header__cell--avatar[data-v-55420384] {
    left: 0;
}
.header__cell--displayname[data-v-55420384] {
    left: var(--avatar-cell-width);
    border-right: 1px solid var(--color-border);
}
}
.header__cell--username[data-v-55420384] {
  padding-left: calc(var(--default-grid-baseline) * 3);
}
.header__cell--avatar[data-v-55420384] {
  min-width: var(--avatar-cell-width);
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
  user-select: none;
}
.header__cell--multiline span[data-v-55420384] {
  line-height: 1.3em;
  white-space: unset;
}
@supports (-webkit-line-clamp: 2) {
.header__cell--multiline span[data-v-55420384] {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
}
.header__cell--large[data-v-55420384] {
  min-width: var(--cell-width-large);
  width: var(--cell-width-large);
}
.header__cell--obfuscated[data-v-55420384] {
  min-width: 400px;
  width: 400px;
}
.header__cell--fill[data-v-55420384] {
  min-width: var(--cell-width-large);
  width: 100%;
}
.header__cell--actions[data-v-55420384] {
  position: sticky;
  right: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-left: 1px solid var(--color-border);
}
.header__subtitle[data-v-55420384] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
.user-list__row[data-v-11563777] {
  position: relative;
  display: flex;
  min-width: 100%;
  width: fit-content;
  height: var(--row-height);
  background-color: var(--color-main-background);
}
.user-list__row[data-v-11563777]:hover {
  background-color: var(--color-background-hover);
}
.user-list__row:hover .row__cell[data-v-11563777]:not(.row__cell--actions) {
  background-color: var(--color-background-hover);
}
.user-list__row .select--fill[data-v-11563777] {
  max-width: calc(var(--cell-width-large) - 2 * var(--cell-padding));
}
.row__cell[data-v-11563777] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
  min-width: var(--cell-width);
  width: var(--cell-width);
  color: var(--color-main-text);
}
.row__cell strong[data-v-11563777],
.row__cell span[data-v-11563777],
.row__cell label[data-v-11563777] {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow-wrap: anywhere;
}
@media (min-width: 670px) {
.row__cell[data-v-11563777] { /* Show one &--large column between stickied columns */
}
.row__cell--avatar[data-v-11563777], .row__cell--displayname[data-v-11563777] {
    position: sticky;
    z-index: var(--sticky-column-z-index);
    background-color: var(--color-main-background);
}
.row__cell--avatar[data-v-11563777] {
    left: 0;
}
.row__cell--displayname[data-v-11563777] {
    left: var(--avatar-cell-width);
    border-right: 1px solid var(--color-border);
}
}
.row__cell--username[data-v-11563777] {
  padding-left: calc(var(--default-grid-baseline) * 3);
}
.row__cell--avatar[data-v-11563777] {
  min-width: var(--avatar-cell-width);
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
  user-select: none;
}
.row__cell--multiline span[data-v-11563777] {
  line-height: 1.3em;
  white-space: unset;
}
@supports (-webkit-line-clamp: 2) {
.row__cell--multiline span[data-v-11563777] {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
}
.row__cell--large[data-v-11563777] {
  min-width: var(--cell-width-large);
  width: var(--cell-width-large);
}
.row__cell--obfuscated[data-v-11563777] {
  min-width: 400px;
  width: 400px;
}
.row__cell--fill[data-v-11563777] {
  min-width: var(--cell-width-large);
  width: 100%;
}
.row__cell--actions[data-v-11563777] {
  position: sticky;
  right: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-left: 1px solid var(--color-border);
}
.row__subtitle[data-v-11563777] {
  color: var(--color-text-maxcontrast);
}
.row__cell[data-v-11563777] {
  border-bottom: 1px solid var(--color-border);
}
.row__cell[data-v-11563777] .v-select.select {
  min-width: var(--cell-min-width);
}
.row__progress[data-v-11563777] {
  margin-top: 4px;
}
.row__progress--warn[data-v-11563777]::-moz-progress-bar {
  background: var(--color-warning) !important;
}
.row__progress--warn[data-v-11563777]::-webkit-progress-value {
  background: var(--color-warning) !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `fieldset[data-v-3eb7c73e] {
  font-weight: bold;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.user-list[data-v-51adeab1] {
  --avatar-cell-width: 48px;
  --cell-padding: 7px;
  --cell-width: 200px;
  --cell-width-large: 300px;
  --cell-min-width: calc(var(--cell-width) - (2 * var(--cell-padding)));
  --sticky-column-z-index: calc(var(--vs-dropdown-z-index) + 1);
  display: block;
  overflow: auto;
  height: 100%;
}
.user-list__header[data-v-51adeab1], .user-list__footer[data-v-51adeab1] {
  position: sticky;
  display: block;
}
.user-list__header[data-v-51adeab1] {
  top: 0;
  z-index: calc(var(--sticky-column-z-index) + 1);
}
.user-list__footer[data-v-51adeab1] {
  left: 0;
}
.user-list__body[data-v-51adeab1] {
  display: flex;
  flex-direction: column;
  width: 100%;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-content[data-v-4821d392] {
  display: flex;
  overflow: hidden;
  flex-direction: column;
  max-height: 100%;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `.account-management__system-list[data-v-b127f0aa] {
  height: auto !important;
  overflow: visible !important;
}
.account-management__group-list[data-v-b127f0aa] {
  height: 100% !important;
}
.account-management__settings-toggle[data-v-b127f0aa] {
  margin-bottom: 12px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue":
/*!********************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true */ "./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true");
/* harmony import */ var _GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./GroupListItem.vue?vue&type=script&lang=js */ "./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js");
/* harmony import */ var _GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true */ "./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b3f9b202",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/GroupListItem.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/UserList.vue":
/*!***************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserList.vue?vue&type=template&id=6cba3aca&scoped=true */ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true");
/* harmony import */ var _UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserList.vue?vue&type=script&lang=js */ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js");
/* harmony import */ var _UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true */ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6cba3aca",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue":
/*!*************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true");
/* harmony import */ var _NewUserModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=script&lang=js */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js");
/* harmony import */ var _NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewUserModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7b45e5ac",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/NewUserModal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue":
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true");
/* harmony import */ var _UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts");
/* harmony import */ var _UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "97a6cb68",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/UserListFooter.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue":
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=template&id=55420384&scoped=true */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true");
/* harmony import */ var _UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts");
/* harmony import */ var _UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "55420384",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/UserListHeader.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue":
/*!********************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRow.vue?vue&type=template&id=11563777&scoped=true */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true");
/* harmony import */ var _UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRow.vue?vue&type=script&lang=js */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js");
/* harmony import */ var _UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "11563777",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/UserRow.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRowActions.vue":
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRowActions.vue?vue&type=template&id=34f3ef36 */ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36");
/* harmony import */ var _UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRowActions.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/UserRowActions.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue":
/*!*******************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true");
/* harmony import */ var _UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=script&lang=js */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js");
/* harmony import */ var _UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3eb7c73e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/UserSettingsDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/VirtualList.vue":
/*!************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./VirtualList.vue?vue&type=template&id=51adeab1&scoped=true */ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true");
/* harmony import */ var _VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./VirtualList.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts");
/* harmony import */ var _VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true */ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "51adeab1",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/Users/VirtualList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/UserManagement.vue":
/*!****************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserManagement.vue?vue&type=template&id=4821d392&scoped=true */ "./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true");
/* harmony import */ var _UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserManagement.vue?vue&type=script&lang=js */ "./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js");
/* harmony import */ var _UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true */ "./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "4821d392",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/UserManagement.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/UserManagementNavigation.vue":
/*!**************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue ***!
  \**************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true */ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true");
/* harmony import */ var _UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss */ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b127f0aa",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/UserManagementNavigation.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts":
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts":
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts":
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts":
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \*************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js":
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js":
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=script&lang=js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js":
/*!*************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js":
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js":
/*!*******************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true":
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true":
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=template&id=6cba3aca&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true":
/*!*******************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true ***!
  \*********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true ***!
  \*********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=template&id=55420384&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true":
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=template&id=11563777&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36":
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36 ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=template&id=34f3ef36 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true":
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=template&id=51adeab1&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=template&id=4821d392&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true":
/*!********************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true":
/*!*****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \*****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true":
/*!**********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true":
/*!************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true":
/*!************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true":
/*!*****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \*****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss":
/*!****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true":
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss":
/*!***********************************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \***********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss");


/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e":
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e":
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \***************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/check.svg?raw":
/*!*************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/check.svg?raw ***!
  \*************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-check\" viewBox=\"0 0 24 24\"><path d=\"M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/pencil.svg?raw":
/*!**************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/pencil.svg?raw ***!
  \**************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-pencil\" viewBox=\"0 0 24 24\"><path d=\"M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs":
/*!********************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs ***!
  \********************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppNavigationCaption_DI7SIPdI_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppNavigationCaption_DI7SIPdI_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppNavigationCaption-DI7SIPdI.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppNavigationCaption-DI7SIPdI.mjs");




/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs":
/*!*****************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSettingsDialog_BLOgrVCz_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSettingsDialog_BLOgrVCz_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSettingsDialog-BLOgrVCz.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSettingsDialog-BLOgrVCz.mjs");




/***/ }),

/***/ "./node_modules/vue-router/composables.mjs":
/*!*************************************************!*\
  !*** ./node_modules/vue-router/composables.mjs ***!
  \*************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isSameRouteLocationParams: () => (/* binding */ isSameRouteLocationParams),
/* harmony export */   onBeforeRouteLeave: () => (/* binding */ onBeforeRouteLeave),
/* harmony export */   onBeforeRouteUpdate: () => (/* binding */ onBeforeRouteUpdate),
/* harmony export */   useLink: () => (/* binding */ useLink),
/* harmony export */   useRoute: () => (/* binding */ useRoute),
/* harmony export */   useRouter: () => (/* binding */ useRouter)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/*!
  * vue-router v3.6.5
  * (c) 2022 Evan You
  * @license MIT
  */


// dev only warn if no current instance

function throwNoCurrentInstance (method) {
  if (!(0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)()) {
    throw new Error(
      ("[vue-router]: Missing current instance. " + method + "() must be called inside <script setup> or setup().")
    )
  }
}

function useRouter () {
  if (true) {
    throwNoCurrentInstance('useRouter');
  }

  return (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)().proxy.$root.$router
}

function useRoute () {
  if (true) {
    throwNoCurrentInstance('useRoute');
  }

  var root = (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)().proxy.$root;
  if (!root._$route) {
    var route = (0,vue__WEBPACK_IMPORTED_MODULE_0__.effectScope)(true).run(function () { return (0,vue__WEBPACK_IMPORTED_MODULE_0__.shallowReactive)(Object.assign({}, root.$router.currentRoute)); }
    );
    root._$route = route;

    root.$router.afterEach(function (to) {
      Object.assign(route, to);
    });
  }

  return root._$route
}

function onBeforeRouteUpdate (guard) {
  if (true) {
    throwNoCurrentInstance('onBeforeRouteUpdate');
  }

  return useFilteredGuard(guard, isUpdateNavigation)
}
function isUpdateNavigation (to, from, depth) {
  var toMatched = to.matched;
  var fromMatched = from.matched;
  return (
    toMatched.length >= depth &&
    toMatched
      .slice(0, depth + 1)
      .every(function (record, i) { return record === fromMatched[i]; })
  )
}

function isLeaveNavigation (to, from, depth) {
  var toMatched = to.matched;
  var fromMatched = from.matched;
  return toMatched.length < depth || toMatched[depth] !== fromMatched[depth]
}

function onBeforeRouteLeave (guard) {
  if (true) {
    throwNoCurrentInstance('onBeforeRouteLeave');
  }

  return useFilteredGuard(guard, isLeaveNavigation)
}

var noop = function () {};
function useFilteredGuard (guard, fn) {
  var instance = (0,vue__WEBPACK_IMPORTED_MODULE_0__.getCurrentInstance)();
  var router = useRouter();

  var target = instance.proxy;
  // find the nearest RouterView to know the depth
  while (
    target &&
    target.$vnode &&
    target.$vnode.data &&
    target.$vnode.data.routerViewDepth == null
  ) {
    target = target.$parent;
  }

  var depth =
    target && target.$vnode && target.$vnode.data
      ? target.$vnode.data.routerViewDepth
      : null;

  if (depth != null) {
    var removeGuard = router.beforeEach(function (to, from, next) {
      return fn(to, from, depth) ? guard(to, from, next) : next()
    });

    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onUnmounted)(removeGuard);
    return removeGuard
  }

  return noop
}

/*  */

function guardEvent (e) {
  // don't redirect with control keys
  if (e.metaKey || e.altKey || e.ctrlKey || e.shiftKey) { return }
  // don't redirect when preventDefault called
  if (e.defaultPrevented) { return }
  // don't redirect on right click
  if (e.button !== undefined && e.button !== 0) { return }
  // don't redirect if `target="_blank"`
  if (e.currentTarget && e.currentTarget.getAttribute) {
    var target = e.currentTarget.getAttribute('target');
    if (/\b_blank\b/i.test(target)) { return }
  }
  // this may be a Weex event which doesn't have this method
  if (e.preventDefault) {
    e.preventDefault();
  }
  return true
}

function includesParams (outer, inner) {
  var loop = function ( key ) {
    var innerValue = inner[key];
    var outerValue = outer[key];
    if (typeof innerValue === 'string') {
      if (innerValue !== outerValue) { return { v: false } }
    } else {
      if (
        !Array.isArray(outerValue) ||
        outerValue.length !== innerValue.length ||
        innerValue.some(function (value, i) { return value !== outerValue[i]; })
      ) {
        return { v: false }
      }
    }
  };

  for (var key in inner) {
    var returned = loop( key );

    if ( returned ) return returned.v;
  }

  return true
}

// helpers from vue router 4

function isSameRouteLocationParamsValue (a, b) {
  return Array.isArray(a)
    ? isEquivalentArray(a, b)
    : Array.isArray(b)
      ? isEquivalentArray(b, a)
      : a === b
}

function isEquivalentArray (a, b) {
  return Array.isArray(b)
    ? a.length === b.length && a.every(function (value, i) { return value === b[i]; })
    : a.length === 1 && a[0] === b
}

function isSameRouteLocationParams (a, b) {
  if (Object.keys(a).length !== Object.keys(b).length) { return false }

  for (var key in a) {
    if (!isSameRouteLocationParamsValue(a[key], b[key])) { return false }
  }

  return true
}

function useLink (props) {
  if (true) {
    throwNoCurrentInstance('useLink');
  }

  var router = useRouter();
  var currentRoute = useRoute();

  var resolvedRoute = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return router.resolve((0,vue__WEBPACK_IMPORTED_MODULE_0__.unref)(props.to), currentRoute); });

  var activeRecordIndex = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () {
    var route = resolvedRoute.value.route;
    var matched = route.matched;
    var length = matched.length;
    var routeMatched = matched[length - 1];
    var currentMatched = currentRoute.matched;
    if (!routeMatched || !currentMatched.length) { return -1 }
    var index = currentMatched.indexOf(routeMatched);
    if (index > -1) { return index }
    // possible parent record
    var parentRecord = currentMatched[currentMatched.length - 2];

    return (
      // we are dealing with nested routes
      length > 1 &&
        // if the parent and matched route have the same path, this link is
        // referring to the empty child. Or we currently are on a different
        // child of the same parent
        parentRecord && parentRecord === routeMatched.parent
    )
  });

  var isActive = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(
    function () { return activeRecordIndex.value > -1 &&
      includesParams(currentRoute.params, resolvedRoute.value.route.params); }
  );
  var isExactActive = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(
    function () { return activeRecordIndex.value > -1 &&
      activeRecordIndex.value === currentRoute.matched.length - 1 &&
      isSameRouteLocationParams(currentRoute.params, resolvedRoute.value.route.params); }
  );

  var navigate = function (e) {
    var href = resolvedRoute.value.route;
    if (guardEvent(e)) {
      return props.replace
        ? router.replace(href)
        : router.push(href)
    }
    return Promise.resolve()
  };

  return {
    href: (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return resolvedRoute.value.href; }),
    route: (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(function () { return resolvedRoute.value.route; }),
    isExactActive: isExactActive,
    isActive: isActive,
    navigate: navigate
  }
}




/***/ })

}]);
//# sourceMappingURL=settings-users-settings-users.js.map?v=36e68a196d738502b264