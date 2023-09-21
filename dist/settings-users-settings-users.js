"use strict";
(globalThis["webpackChunknextcloud"] = globalThis["webpackChunknextcloud"] || []).push([["settings-users"],{

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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts&":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts& ***!
  \******************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend({
  name: 'UserListFooter',
  components: {
    NcLoadingIcon: (_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_0___default())
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts&":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts& ***!
  \******************************************************************************************************************************************************************************************************************************************************/
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts&":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts& ***!
  \******************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/check.svg?raw */ "./node_modules/@mdi/svg/svg/check.svg?raw");
/* harmony import */ var _mdi_svg_svg_pencil_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/pencil.svg?raw */ "./node_modules/@mdi/svg/svg/pencil.svg?raw");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_5__.defineComponent)({
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcActions: (_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcIconSvgWrapper: (_nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_2___default())
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'GroupListItem',
  components: {
    AccountGroup: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcActionInput: (_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcCounterBubble: (_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_3___default())
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
      openGroupMenu: false
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
    removeGroup(groupid) {
      // TODO migrate to a vue js confirm dialog component
      OC.dialogs.confirm(t('settings', 'You are about to remove the group {group}. The users will NOT be deleted.', {
        group: groupid
      }), t('settings', 'Please confirm the group removal '), success => {
        if (success) {
          this.$store.dispatch('removeGroup', groupid);
        }
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var vue_virtual_scroller__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-virtual-scroller */ "./node_modules/vue-virtual-scroller/dist/vue-virtual-scroller.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcEmptyContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _Users_NewUserModal_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Users/NewUserModal.vue */ "./apps/settings/src/components/Users/NewUserModal.vue");
/* harmony import */ var _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./Users/UserListFooter.vue */ "./apps/settings/src/components/Users/UserListFooter.vue");
/* harmony import */ var _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Users/UserListHeader.vue */ "./apps/settings/src/components/Users/UserListHeader.vue");
/* harmony import */ var _Users_UserRow_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Users/UserRow.vue */ "./apps/settings/src/components/Users/UserRow.vue");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../logger.js */ "./apps/settings/src/logger.js");
/* harmony import */ var _img_users_svg_raw__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../img/users.svg?raw */ "./apps/settings/img/users.svg?raw");















const newUser = {
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
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserList',
  components: {
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_0__.Fragment,
    NcEmptyContent: (_nextcloud_vue_dist_Components_NcEmptyContent_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcIconSvgWrapper: (_nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcLoadingIcon: (_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NewUserModal: _Users_NewUserModal_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    RecycleScroller: vue_virtual_scroller__WEBPACK_IMPORTED_MODULE_1__.RecycleScroller,
    UserListFooter: _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    UserListHeader: _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    UserRow: _Users_UserRow_vue__WEBPACK_IMPORTED_MODULE_10__["default"]
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
  data() {
    return {
      loading: {
        all: false,
        groups: false,
        users: false
      },
      isInitialLoad: true,
      rowHeight: 55,
      usersSvg: _img_users_svg_raw__WEBPACK_IMPORTED_MODULE_13__,
      searchQuery: '',
      newUser: Object.assign({}, newUser)
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
        '--row-height': `${this.rowHeight}px`
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
    async selectedGroup(val, old) {
      this.isInitialLoad = true;
      // if selected is the disabled group but it's empty
      await this.redirectIfDisabled();
      this.$store.commit('resetUsers');
      await this.loadUsers();
      this.setNewUserDefaultGroup(val);
    },
    filteredUsers(filteredUsers) {
      _logger_js__WEBPACK_IMPORTED_MODULE_12__["default"].debug(`${filteredUsers.length} filtered user(s)`);
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
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.subscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);

    /**
     * If disabled group but empty, redirect
     */
    await this.redirectIfDisabled();
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.unsubscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    async handleMounted() {
      // Add proper semantics to the recycle scroller slots
      const header = this.$refs.scroller.$refs.before;
      const footer = this.$refs.scroller.$refs.after;
      header.classList.add('user-list__header');
      header.setAttribute('role', 'rowgroup');
      footer.classList.add('user-list__footer');
      footer.setAttribute('role', 'rowgroup');
    },
    async handleScrollEnd() {
      await this.loadUsers();
    },
    async loadUsers() {
      this.loading.users = true;
      try {
        await this.$store.dispatch('getUsers', {
          offset: this.usersOffset,
          limit: this.usersLimit,
          group: this.selectedGroup !== 'disabled' ? this.selectedGroup : '',
          search: this.searchQuery
        });
        _logger_js__WEBPACK_IMPORTED_MODULE_12__["default"].debug(`${this.users.length} total user(s) loaded`);
      } catch (error) {
        _logger_js__WEBPACK_IMPORTED_MODULE_12__["default"].error('Failed to load users', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__.showError)('Failed to load users');
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
    async search({
      query
    }) {
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
        // setting new user default group to the current selected one
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcModal.js */ "./node_modules/@nextcloud/vue/dist/Components/NcModal.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__);





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NewUserModal',
  components: {
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcModal: (_nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcPasswordField: (_nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcTextField: (_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4___default())
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
        return t('settings', 'Username will be autogenerated');
      }
      return t('settings', 'Username (required)');
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
        this.$refs.username?.$refs?.inputField?.$refs?.input?.focus?.();
        this.$emit('close');
      } catch (error) {
        this.loading.all = false;
        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          const statuscode = error.response.data.ocs.meta.statuscode;
          if (statuscode === 102) {
            // wrong username
            this.$refs.username?.$refs?.inputField?.$refs?.input?.focus?.();
          } else if (statuscode === 107) {
            // wrong password
            this.$refs.password?.$refs?.inputField?.$refs?.input?.focus?.();
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
    async createGroup({
      name: gid
    }) {
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
        return option.languages.some(({
          name
        }) => name.toLocaleLowerCase().includes(search.toLocaleLowerCase()));
      }
      return (label || '').toLocaleLowerCase().includes(search.toLocaleLowerCase());
    },
    async searchUserManager(query) {
      await this.$store.dispatch('searchUsers', {
        offset: 0,
        limit: 10,
        search: query
      }).then(response => {
        const users = response?.data ? Object.values(response?.data.ocs.data.users) : [];
        if (users.length > 0) {
          this.possibleManagers = users;
        }
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var vue_virtual_scroller__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-virtual-scroller */ "./node_modules/vue-virtual-scroller/dist/vue-virtual-scroller.esm.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcProgressBar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcProgressBar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./UserRowActions.vue */ "./apps/settings/src/components/Users/UserRowActions.vue");
/* harmony import */ var _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../mixins/UserRowMixin.js */ "./apps/settings/src/mixins/UserRowMixin.js");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserRow',
  components: {
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_0__.Fragment,
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcLoadingIcon: (_nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcProgressBar: (_nextcloud_vue_dist_Components_NcProgressBar_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_7___default()),
    NcTextField: (_nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_8___default()),
    UserRowActions: _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_9__["default"]
  },
  mixins: [
  /**
   * Use scoped `idState` instead of `data` which is reused between rows
   *
   * See https://github.com/Akryum/vue-virtual-scroller/tree/v1/packages/vue-virtual-scroller#why-is-this-useful
   */
  (0,vue_virtual_scroller__WEBPACK_IMPORTED_MODULE_1__.IdState)({
    idProp: vm => vm.user.id
  }), _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_10__["default"]],
  props: {
    user: {
      type: Object,
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
  idState() {
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
      editedMail: this.user.email ?? ''
    };
  },
  computed: {
    managerLabel() {
      // TRANSLATORS This string describes a manager in the context of an organization
      return t('settings', 'Set user manager');
    },
    isObfuscated() {
      return (0,_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.isObfuscated)(this.user);
    },
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    isLoadingUser() {
      return this.idState.loading.delete || this.idState.loading.disable || this.idState.loading.wipe;
    },
    isLoadingField() {
      return this.idState.loading.delete || this.idState.loading.disable || this.idState.loading.all;
    },
    uniqueId() {
      return this.user.id + this.idState.rand;
    },
    userGroupsLabels() {
      return this.userGroups.map(group => group.name).join(', ');
    },
    userSubAdminsGroupsLabels() {
      return this.userSubAdminsGroups.map(group => group.name).join(', ');
    },
    usedSpace() {
      if (this.user.quota?.used) {
        return t('settings', '{size} used', {
          size: OC.Util.humanFileSize(this.user.quota?.used)
        });
      }
      return t('settings', '{size} used', {
        size: OC.Util.humanFileSize(0)
      });
    },
    canEdit() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)().uid !== this.user.id || this.settings.isAdmin;
    },
    userQuota() {
      let quota = this.user.quota?.quota;
      if (quota === 'default') {
        quota = this.settings.defaultQuota;
        if (quota !== 'none') {
          // convert to numeric value to match what the server would usually return
          quota = OC.Util.computerFileSize(quota);
        }
      }

      // when the default quota is unlimited, the server returns -3 here, map it to "none"
      if (quota === 'none' || quota === -3) {
        return t('settings', 'Unlimited');
      } else if (quota >= 0) {
        return OC.Util.humanFileSize(quota);
      }
      return OC.Util.humanFileSize(0);
    },
    userActions() {
      const actions = [{
        icon: 'icon-delete',
        text: t('settings', 'Delete user'),
        action: this.deleteUser
      }, {
        icon: 'icon-delete',
        text: t('settings', 'Wipe all devices'),
        action: this.wipeUserDevices
      }, {
        icon: this.user.enabled ? 'icon-close' : 'icon-add',
        text: this.user.enabled ? t('settings', 'Disable user') : t('settings', 'Enable user'),
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
        if (this.idState.selectedQuota !== false) {
          return this.idState.selectedQuota;
        }
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.unlimitedQuota; // unlimited
      },

      set(quota) {
        this.idState.selectedQuota = quota;
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

    // Reset loading state before mounting the component.
    // This is useful when we disable a user as the loading state cannot be properly reset upon promise resolution.
    Object.keys(this.idState.loading).forEach(key => this.idState.loading[key] = false);
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
          this.idState.loading.wipe = true;
          this.idState.loading.all = true;
          this.$store.dispatch('wipeUserDevices', userid).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(t('settings', 'Wiped {userid}\'s devices', {
            userid
          })), {
            timeout: 2000
          }).finally(() => {
            this.idState.loading.wipe = false;
            this.idState.loading.all = false;
          });
        }
      }, true);
    },
    filterManagers(managers) {
      return managers.filter(manager => manager.id !== this.user.id);
    },
    async initManager(userId) {
      await this.$store.dispatch('getUser', userId).then(response => {
        this.idState.currentManager = response?.data.ocs.data;
      });
    },
    async searchInitialUserManager() {
      this.idState.loadingPossibleManagers = true;
      await this.searchUserManager();
      this.idState.loadingPossibleManagers = false;
    },
    async searchUserManager(query) {
      await this.$store.dispatch('searchUsers', {
        offset: 0,
        limit: 10,
        search: query
      }).then(response => {
        const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : [];
        if (users.length > 0) {
          this.idState.possibleManagers = users;
        }
      });
    },
    async updateUserManager(manager) {
      if (manager === null) {
        this.idState.currentManager = '';
      }
      this.idState.loading.manager = true;
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'manager',
          value: this.idState.currentManager ? this.idState.currentManager.id : ''
        });
      } catch (error) {
        // TRANSLATORS This string describes a manager in the context of an organization
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(t('setting', 'Failed to update user manager'));
        console.error(error);
      } finally {
        this.idState.loading.manager = false;
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
          this.idState.loading.delete = true;
          this.idState.loading.all = true;
          return this.$store.dispatch('deleteUser', userid).then(() => {
            this.idState.loading.delete = false;
            this.idState.loading.all = false;
          });
        }
      }, true);
    },
    enableDisableUser() {
      this.idState.loading.delete = true;
      this.idState.loading.all = true;
      const userid = this.user.id;
      const enabled = !this.user.enabled;
      return this.$store.dispatch('enableDisableUser', {
        userid,
        enabled
      }).then(() => {
        this.idState.loading.delete = false;
        this.idState.loading.all = false;
      });
    },
    /**
     * Set user displayName
     *
     * @param {string} displayName The display name
     */
    updateDisplayName() {
      this.idState.loading.displayName = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'displayname',
        value: this.idState.editedDisplayName
      }).then(() => {
        this.idState.loading.displayName = false;
        if (this.idState.editedDisplayName === this.user.displayname) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(t('setting', 'Display name was successfully changed'));
        }
      });
    },
    /**
     * Set user password
     *
     * @param {string} password The email address
     */
    updatePassword() {
      this.idState.loading.password = true;
      if (this.idState.editedPassword.length === 0) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(t('setting', "Password can't be empty"));
        this.idState.loading.password = false;
      } else {
        this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'password',
          value: this.idState.editedPassword
        }).then(() => {
          this.idState.loading.password = false;
          this.idState.editedPassword = '';
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(t('setting', 'Password was successfully changed'));
        });
      }
    },
    /**
     * Set user mailAddress
     *
     * @param {string} mailAddress The email address
     */
    updateEmail() {
      this.idState.loading.mailAddress = true;
      if (this.idState.editedMail === '') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(t('setting', "Email can't be empty"));
        this.idState.loading.mailAddress = false;
        this.idState.editedMail = this.user.email;
      } else {
        this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'email',
          value: this.idState.editedMail
        }).then(() => {
          this.idState.loading.mailAddress = false;
          if (this.idState.editedMail === this.user.email) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(t('setting', 'Email was successfully changed'));
          }
        });
      }
    },
    /**
     * Create a new group and add user to it
     *
     * @param {string} gid Group id
     */
    async createGroup({
      name: gid
    }) {
      this.idState.loading = {
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
        this.idState.loading = {
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
      this.idState.loading.groups = true;
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
        this.idState.loading.groups = false;
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
      this.idState.loading.groups = true;
      const userid = this.user.id;
      const gid = group.id;
      try {
        await this.$store.dispatch('removeUserGroup', {
          userid,
          gid
        });
        this.idState.loading.groups = false;
        // remove user from current list if current list is the removed group
        if (this.$route.params.selectedGroup === gid) {
          this.$store.commit('deleteUser', userid);
        }
      } catch {
        this.idState.loading.groups = false;
      }
    },
    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    async addUserSubAdmin(group) {
      this.idState.loading.subadmins = true;
      const userid = this.user.id;
      const gid = group.id;
      try {
        await this.$store.dispatch('addUserSubAdmin', {
          userid,
          gid
        });
        this.idState.loading.subadmins = false;
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
      this.idState.loading.subadmins = true;
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
        this.idState.loading.subadmins = false;
      }
    },
    /**
     * Dispatch quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {string}
     */
    async setUserQuota(quota = 'none') {
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.unlimitedQuota;
      }
      this.idState.loading.quota = true;
      // ensure we only send the preset id
      quota = quota.id ? quota.id : quota;
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'quota',
          value: quota
        });
      } catch (error) {
        console.error(error);
      } finally {
        this.idState.loading.quota = false;
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
        quota = quota?.id || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = OC.Util.computerFileSize(quota);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_11__.unlimitedQuota;
      } else {
        // unify format output
        quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
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
      this.idState.loading.languages = true;
      // ensure we only send the preset id
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'language',
          value: lang.code
        });
        this.idState.loading.languages = false;
      } catch (error) {
        console.error(error);
      }
      return lang;
    },
    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail() {
      this.idState.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(t('setting', 'Welcome mail sent!'), {
        timeout: 2000
      })).finally(() => {
        this.idState.loading.all = false;
      });
    },
    async toggleEdit() {
      this.idState.editing = !this.idState.editing;
      if (this.idState.editing) {
        await this.$nextTick();
        this.$refs.displayNameField?.$refs?.inputField?.$refs?.input?.focus();
      }
      if (this.idState.editedDisplayName !== this.user.displayname) {
        this.idState.editedDisplayName = this.user.displayname;
      } else if (this.idState.editedMail !== this.user.email) {
        this.idState.editedMail = this.user.email ?? '';
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSettingsDialog.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppSettingsSection.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsSection.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserSettingsDialog',
  components: {
    NcAppSettingsDialog: (_nextcloud_vue_dist_Components_NcAppSettingsDialog_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcAppSettingsSection: (_nextcloud_vue_dist_Components_NcAppSettingsSection_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcCheckboxRadioSwitch: (_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_5___default())
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
        return this.getLocalstorage('showLanguages');
      },
      set(status) {
        this.setLocalStorage('showLanguages', status);
      }
    },
    showLastLogin: {
      get() {
        return this.getLocalstorage('showLastLogin');
      },
      set(status) {
        this.setLocalStorage('showLastLogin', status);
      }
    },
    showUserBackend: {
      get() {
        return this.getLocalstorage('showUserBackend');
      },
      set(status) {
        this.setLocalStorage('showUserBackend', status);
      }
    },
    showStoragePath: {
      get() {
        return this.getLocalstorage('showStoragePath');
      },
      set(status) {
        this.setLocalStorage('showStoragePath', status);
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
        quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__.unlimitedQuota);
      }
      return quotaPreset;
    },
    defaultQuota: {
      get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__.unlimitedQuota; // unlimited
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
          await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/settings/users/preferences/newUser.sendEmail'), {
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
    getLocalstorage(key) {
      // force initialization
      const localConfig = this.$localStorage.get(key);
      // if localstorage is null, fallback to original values
      this.$store.commit('setShowConfig', {
        key,
        value: localConfig !== null ? localConfig === 'true' : this.showConfig[key]
      });
      return this.showConfig[key];
    },
    setLocalStorage(key, status) {
      this.$store.commit('setShowConfig', {
        key,
        value: status
      });
      this.$localStorage.set(key, status);
      return status;
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {object} The validated quota object or unlimited quota if input is invalid
     */
    validateQuota(quota) {
      if (typeof quota === 'object') {
        quota = quota?.id || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = OC.Util.computerFileSize(quota);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__.unlimitedQuota;
      } else {
        // unify format output
        quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
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
    setDefaultQuota(quota = 'none') {
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_6__.unlimitedQuota;
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationCaption.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationNew.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNewItem_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationNewItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNewItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNewItem_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationNewItem_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_AccountOff_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/AccountOff.vue */ "./node_modules/vue-material-design-icons/AccountOff.vue");
/* harmony import */ var vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Cog.vue */ "./node_modules/vue-material-design-icons/Cog.vue");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var vue_material_design_icons_ShieldAccount_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/ShieldAccount.vue */ "./node_modules/vue-material-design-icons/ShieldAccount.vue");
/* harmony import */ var _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../components/GroupListItem.vue */ "./apps/settings/src/components/GroupListItem.vue");
/* harmony import */ var _components_UserList_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../components/UserList.vue */ "./apps/settings/src/components/UserList.vue");
/* harmony import */ var _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../components/Users/UserSettingsDialog.vue */ "./apps/settings/src/components/Users/UserSettingsDialog.vue");



















vue__WEBPACK_IMPORTED_MODULE_18__["default"].use((vue_localstorage__WEBPACK_IMPORTED_MODULE_0___default()));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Users',
  components: {
    AccountGroup: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    AccountOff: vue_material_design_icons_AccountOff_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    Cog: vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_1__.Fragment,
    GroupListItem: _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    NcAppContent: (_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcAppNavigation: (_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcAppNavigationCaption: (_nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcAppNavigationNew: (_nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcAppNavigationNewItem: (_nextcloud_vue_dist_Components_NcAppNavigationNewItem_js__WEBPACK_IMPORTED_MODULE_7___default()),
    NcContent: (_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8___default()),
    NcCounterBubble: (_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_9___default()),
    Plus: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    ShieldAccount: vue_material_design_icons_ShieldAccount_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    UserList: _components_UserList_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    UserSettingsDialog: _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_17__["default"]
  },
  props: {
    selectedGroup: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      // temporary value used for multiselect change
      externalActions: [],
      loadingAddGroup: false,
      isDialogOpen: false
    };
  },
  computed: {
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    selectedGroupDecoded() {
      return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null;
    },
    users() {
      return this.$store.getters.getUsers;
    },
    groups() {
      return this.$store.getters.getGroups;
    },
    usersOffset() {
      return this.$store.getters.getUsersOffset;
    },
    usersLimit() {
      return this.$store.getters.getUsersLimit;
    },
    userCount() {
      return this.$store.getters.getUserCount;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    groupList() {
      const groups = Array.isArray(this.groups) ? this.groups : [];
      return groups
      // filter out disabled and admin
      .filter(group => group.id !== 'disabled' && group.id !== 'admin').map(group => this.formatGroupMenu(group));
    },
    adminGroupMenu() {
      return this.formatGroupMenu(this.groups.find(group => group.id === 'admin'));
    },
    disabledGroupMenu() {
      return this.formatGroupMenu(this.groups.find(group => group.id === 'disabled'));
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
    // init the OCA.Settings.UserList object
    // and add the registerAction method
    Object.assign(OCA, {
      Settings: {
        UserList: {
          registerAction: this.registerAction
        }
      }
    });
  },
  methods: {
    showNewUserMenu() {
      this.$store.commit('setShowConfig', {
        key: 'showNewUserForm',
        value: true
      });
    },
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
    },
    /**
     * Create a new group
     *
     * @param {string} gid The group id
     */
    async createGroup(gid) {
      // group is not valid
      if (gid.trim() === '') {
        return;
      }
      try {
        this.loadingAddGroup = true;
        await this.$store.dispatch('addGroup', gid.trim());
        this.hideAddGroupForm();
        await this.$router.push({
          name: 'group',
          params: {
            selectedGroup: encodeURIComponent(gid.trim())
          }
        });
      } catch {
        this.showAddGroupForm();
      } finally {
        this.loadingAddGroup = false;
      }
    },
    showAddGroupForm() {
      this.$refs.addGroup.newItemActive = true;
      this.$nextTick(() => {
        this.$refs.addGroup.$refs.newItemInput.focusInput();
      });
    },
    hideAddGroupForm() {
      this.$refs.addGroup.newItemActive = false;
      this.$refs.addGroup.newItemValue = '';
    },
    /**
     * Format a group to a menu entry
     *
     * @param {object} group the group
     * @return {object}
     */
    formatGroupMenu(group) {
      const item = {};
      if (typeof group === 'undefined') {
        return {};
      }
      item.id = group.id;
      item.title = group.name;
      item.usercount = group.usercount;

      // users count for all groups
      if (group.usercount - group.disabled > 0) {
        item.count = group.usercount - group.disabled;
      }
      return item;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&":
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202& ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppNavigationItem", {
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
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.count) + "\n\t\t")]) : _vm._e()];
      },
      proxy: true
    }, {
      key: "actions",
      fn: function () {
        return [_vm.id !== "admin" && _vm.id !== "disabled" && _vm.settings.isAdmin ? _c("NcActionInput", {
          ref: "displayNameInput",
          attrs: {
            icon: "icon-edit",
            type: "text",
            value: _vm.name,
            label: _vm.t("settings", "Rename group")
          },
          on: {
            submit: function ($event) {
              return _vm.renameGroup(_vm.id);
            }
          }
        }) : _vm._e(), _vm._v(" "), _vm.id !== "admin" && _vm.id !== "disabled" && _vm.settings.isAdmin ? _c("NcActionButton", {
          attrs: {
            icon: "icon-delete"
          },
          on: {
            click: function ($event) {
              return _vm.removeGroup(_vm.id);
            }
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Remove group")) + "\n\t\t")]) : _vm._e()];
      },
      proxy: true
    }])
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
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
      name: _vm.isInitialLoad && _vm.loading.users ? null : _vm.t("settings", "No users")
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.isInitialLoad && _vm.loading.users ? _c("NcLoadingIcon", {
          attrs: {
            name: _vm.t("settings", "Loading users …"),
            size: 64
          }
        }) : _c("NcIconSvgWrapper", {
          attrs: {
            svg: _vm.usersSvg
          }
        })];
      },
      proxy: true
    }], null, false, 934871631)
  }) : _c("RecycleScroller", {
    ref: "scroller",
    staticClass: "user-list",
    style: _vm.style,
    attrs: {
      items: _vm.filteredUsers,
      "key-field": "id",
      role: "table",
      "list-tag": "tbody",
      "list-class": "user-list__body",
      "item-tag": "tr",
      "item-class": "user-list__row",
      "item-size": _vm.rowHeight
    },
    on: {
      "hook:mounted": _vm.handleMounted,
      "scroll-end": _vm.handleScrollEnd
    },
    scopedSlots: _vm._u([{
      key: "before",
      fn: function () {
        return [_c("caption", {
          staticClass: "hidden-visually"
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "List of users. This list is not fully rendered for performance reasons. The users will be rendered as you navigate through the list.")) + "\n\t\t\t")]), _vm._v(" "), _c("UserListHeader", {
          attrs: {
            "has-obfuscated": _vm.hasObfuscated
          }
        })];
      },
      proxy: true
    }, {
      key: "default",
      fn: function ({
        item: user
      }) {
        return [_c("UserRow", {
          attrs: {
            user: user,
            users: _vm.users,
            settings: _vm.settings,
            "has-obfuscated": _vm.hasObfuscated,
            groups: _vm.groups,
            "sub-admins-groups": _vm.subAdminsGroups,
            "quota-options": _vm.quotaOptions,
            languages: _vm.languages,
            "external-actions": _vm.externalActions
          }
        })];
      }
    }, {
      key: "after",
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
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
      autocorrect: "off",
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
      autocorrect: "off"
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
      autocorrect: "off",
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
      autocorrect: "off",
      required: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.newUser, "mailAddress", $event);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "modal__item"
  }, [!_vm.settings.isAdmin ? _c("NcTextField", {
    class: {
      "icon-loading-small": _vm.loading.groups
    },
    attrs: {
      id: "new-user-groups-input",
      tabindex: "-1",
      value: _vm.newUser.groups,
      required: !_vm.settings.isAdmin
    }
  }) : _vm._e(), _vm._v(" "), _c("label", {
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
      taggable: true
    },
    on: {
      input: _vm.handleGroupInput,
      "option:created": _vm.createGroup
    }
  })], 1), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("div", {
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
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
      scope: "col"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Avatar")) + "\n\t\t")])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--displayname",
    attrs: {
      scope: "col"
    }
  }, [_c("strong", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Display name")) + "\n\t\t")]), _vm._v(" "), _c("span", {
    staticClass: "header__subtitle"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Username")) + "\n\t\t")])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    class: {
      "header__cell--obfuscated": _vm.hasObfuscated
    },
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.passwordLabel))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Email")))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Groups")))])]), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Group admin for")))])]) : _vm._e(), _vm._v(" "), _c("th", {
    staticClass: "header__cell",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Quota")))])]), _vm._v(" "), _vm.showConfig.showLanguages ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Language")))])]) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      scope: "col"
    }
  }, [_vm.showConfig.showUserBackend ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "User backend")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("span", {
    staticClass: "header__subtitle"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Storage location")) + "\n\t\t")]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("th", {
    staticClass: "header__cell",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Last login")))])]) : _vm._e(), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--large",
    attrs: {
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Manager")))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--actions",
    attrs: {
      scope: "col"
    }
  }, [_c("span", {
    staticClass: "hidden-visually"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "User actions")) + "\n\t\t")])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("Fragment", [_c("td", {
    staticClass: "row__cell row__cell--avatar"
  }, [_vm.isLoadingUser ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("settings", "Loading user …"),
      size: 32
    }
  }) : _c("NcAvatar", {
    key: _vm.user.id,
    attrs: {
      "disable-menu": "",
      "show-user-status": false,
      user: _vm.user.id
    }
  })], 1), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--displayname",
    attrs: {
      "data-test": _vm.user.id
    }
  }, [_vm.idState.editing && _vm.user.backendCapabilities.setDisplayName ? [_c("NcTextField", {
    ref: "displayNameField",
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.idState.loading.displayName
    },
    attrs: {
      "data-test": "displayNameField",
      "show-trailing-button": true,
      disabled: _vm.idState.loading.displayName || _vm.isLoadingField,
      label: _vm.t("settings", "Change display name"),
      "trailing-button-icon": "arrowRight",
      value: _vm.idState.editedDisplayName,
      autocapitalize: "off",
      autocomplete: "off",
      autocorrect: "off",
      spellcheck: "false"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.idState, "editedDisplayName", $event);
      },
      "trailing-button-click": _vm.updateDisplayName
    }
  })] : [!_vm.isObfuscated ? _c("strong", {
    attrs: {
      title: _vm.user.displayname?.length > 20 ? _vm.user.displayname : null
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("span", {
    staticClass: "row__subtitle"
  }, [_vm._v(_vm._s(_vm.user.id))])]], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell",
    class: {
      "row__cell--obfuscated": _vm.hasObfuscated
    }
  }, [_vm.idState.editing && _vm.settings.canChangePassword && _vm.user.backendCapabilities.setPassword ? [_c("NcTextField", {
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.idState.loading.password
    },
    attrs: {
      "show-trailing-button": true,
      disabled: _vm.idState.loading.password || _vm.isLoadingField,
      minlength: _vm.minPasswordLength,
      maxlength: "469",
      label: _vm.t("settings", "Add new password"),
      "trailing-button-icon": "arrowRight",
      value: _vm.idState.editedPassword,
      autocapitalize: "off",
      autocomplete: "new-password",
      autocorrect: "off",
      required: "",
      spellcheck: "false",
      type: "password"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.idState, "editedPassword", $event);
      },
      "trailing-button-click": _vm.updatePassword
    }
  })] : _vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "You do not have permissions to see the details of this user")) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell"
  }, [_vm.idState.editing ? [_c("NcTextField", {
    staticClass: "user-row-text-field",
    class: {
      "icon-loading-small": _vm.idState.loading.mailAddress
    },
    attrs: {
      "show-trailing-button": true,
      disabled: _vm.idState.loading.mailAddress || _vm.isLoadingField,
      label: _vm.t("settings", "Add new email address"),
      "trailing-button-icon": "arrowRight",
      value: _vm.idState.editedMail,
      autocapitalize: "off",
      autocomplete: "new-password",
      autocorrect: "off",
      spellcheck: "false",
      type: "email"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.idState, "editedMail", $event);
      },
      "trailing-button-click": _vm.updateEmail
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: _vm.user.email?.length > 20 ? _vm.user.email : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.email) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--large row__cell--multiline"
  }, [_vm.idState.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "groups" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Add user to group")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select-vue",
    attrs: {
      "input-id": "groups" + _vm.uniqueId,
      "close-on-select": false,
      disabled: _vm.isLoadingField,
      loading: _vm.idState.loading.groups,
      multiple: true,
      options: _vm.availableGroups,
      placeholder: _vm.t("settings", "Add user to group"),
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
      title: _vm.userGroupsLabels?.length > 40 ? _vm.userGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userGroupsLabels) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("td", {
    staticClass: "row__cell row__cell--large row__cell--multiline"
  }, [_vm.idState.editing && _vm.settings.isAdmin && _vm.subAdminsGroups.length > 0 ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "subadmins" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set user as admin for")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select-vue",
    attrs: {
      id: "subadmins" + _vm.uniqueId,
      "close-on-select": false,
      disabled: _vm.isLoadingField,
      loading: _vm.idState.loading.subadmins,
      label: "name",
      multiple: true,
      "no-wrap": true,
      options: _vm.subAdminsGroups,
      placeholder: _vm.t("settings", "Set user as admin for"),
      value: _vm.userSubAdminsGroups
    },
    on: {
      "option:deselected": _vm.removeUserSubAdmin,
      "option:selected": options => _vm.addUserSubAdmin(options.at(-1))
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: _vm.userSubAdminsGroupsLabels?.length > 40 ? _vm.userSubAdminsGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userSubAdminsGroupsLabels) + "\n\t\t")]) : _vm._e()], 2) : _vm._e(), _vm._v(" "), _c("td", {
    staticClass: "row__cell"
  }, [_vm.idState.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "quota" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Select user quota")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select-vue",
    attrs: {
      "close-on-select": true,
      "create-option": _vm.validateQuota,
      disabled: _vm.isLoadingField,
      loading: _vm.idState.loading.quota,
      clearable: false,
      "input-id": "quota" + _vm.uniqueId,
      options: _vm.quotaOptions,
      placeholder: _vm.t("settings", "Select user quota"),
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
  })] : !_vm.isObfuscated ? [_c("label", {
    attrs: {
      for: "quota-progress" + _vm.uniqueId
    }
  }, [_vm._v(_vm._s(_vm.userQuota) + " (" + _vm._s(_vm.usedSpace) + ")")]), _vm._v(" "), _c("NcProgressBar", {
    staticClass: "row__progress",
    class: {
      "row__progress--warn": _vm.usedQuota > 80
    },
    attrs: {
      id: "quota-progress" + _vm.uniqueId,
      value: _vm.usedQuota
    }
  })] : _vm._e()], 2), _vm._v(" "), _vm.showConfig.showLanguages ? _c("td", {
    staticClass: "row__cell row__cell--large",
    attrs: {
      "data-test": "language"
    }
  }, [_vm.idState.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "language" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set the language")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select-vue",
    attrs: {
      id: "language" + _vm.uniqueId,
      "allow-empty": false,
      disabled: _vm.isLoadingField,
      loading: _vm.idState.loading.languages,
      clearable: false,
      options: _vm.availableLanguages,
      placeholder: _vm.t("settings", "No language set"),
      value: _vm.userLanguage,
      label: "name"
    },
    on: {
      input: _vm.setUserLanguage
    }
  })] : !_vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.userLanguage.name) + "\n\t\t")]) : _vm._e()], 2) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("td", {
    staticClass: "row__cell row__cell--large"
  }, [!_vm.isObfuscated ? [_vm.showConfig.showUserBackend ? _c("span", [_vm._v(_vm._s(_vm.user.backend))]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("span", {
    staticClass: "row__subtitle",
    attrs: {
      title: _vm.user.storageLocation
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.storageLocation) + "\n\t\t\t")]) : _vm._e()] : _vm._e()], 2) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("td", {
    staticClass: "row__cell",
    attrs: {
      title: _vm.userLastLoginTooltip,
      "data-test": "lastLogin"
    }
  }, [!_vm.isObfuscated ? _c("span", [_vm._v(_vm._s(_vm.userLastLogin))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--large"
  }, [_vm.idState.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "manager" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.managerLabel) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    staticClass: "select-vue",
    attrs: {
      "input-id": "manager" + _vm.uniqueId,
      "close-on-select": true,
      disabled: _vm.isLoadingField,
      loading: _vm.idState.loadingPossibleManagers || _vm.idState.loading.manager,
      label: "displayname",
      options: _vm.idState.possibleManagers,
      placeholder: _vm.managerLabel
    },
    on: {
      open: _vm.searchInitialUserManager,
      search: _vm.searchUserManager,
      "option:selected": _vm.updateUserManager,
      input: _vm.updateUserManager
    },
    model: {
      value: _vm.idState.currentManager,
      callback: function ($$v) {
        _vm.$set(_vm.idState, "currentManager", $$v);
      },
      expression: "idState.currentManager"
    }
  })] : !_vm.isObfuscated ? _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.user.manager) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--actions"
  }, [!_vm.isObfuscated && _vm.canEdit && !_vm.idState.loading.all ? _c("UserRowActions", {
    attrs: {
      actions: _vm.userActions,
      disabled: _vm.isLoadingField,
      edit: _vm.idState.editing
    },
    on: {
      "update:edit": _vm.toggleEdit
    }
  }) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36& ***!
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
  return _c("NcActions", {
    attrs: {
      "aria-label": _vm.t("settings", "Toggle user actions menu"),
      disabled: _vm.disabled,
      inline: 1
    }
  }, [_c("NcActionButton", {
    attrs: {
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
  }, [_vm._v("\n\t\t" + _vm._s(_vm.edit ? _vm.t("settings", "Done") : _vm.t("settings", "Edit")) + "\n\t\t")]), _vm._v(" "), _vm._l(_vm.actions, function ({
    action,
    icon,
    text
  }, index) {
    return _c("NcActionButton", {
      key: index,
      attrs: {
        disabled: _vm.disabled,
        "aria-label": text,
        icon: icon
      },
      on: {
        click: action
      }
    }, [_vm._v("\n\t\t" + _vm._s(text) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
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
      name: _vm.t("settings", "User management settings")
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show user backend")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Send welcome email to new users")) + "\n\t\t")])], 1), _vm._v(" "), _c("NcAppSettingsSection", {
    attrs: {
      id: "default-settings",
      name: _vm.t("settings", "Defaults")
    }
  }, [_c("label", {
    attrs: {
      for: "default-quota-select"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Default quota")))]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "input-id": "default-quota-select",
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

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("Fragment", [_c("NcContent", {
    attrs: {
      "app-name": "settings",
      "navigation-class": {
        "icon-loading": _vm.loadingAddGroup
      }
    }
  }, [_c("NcAppNavigation", {
    scopedSlots: _vm._u([{
      key: "list",
      fn: function () {
        return [_c("NcAppNavigationNewItem", {
          ref: "addGroup",
          attrs: {
            id: "addgroup",
            "edit-placeholder": _vm.t("settings", "Enter group name"),
            editable: true,
            loading: _vm.loadingAddGroup,
            name: _vm.t("settings", "Add group")
          },
          on: {
            click: _vm.showAddGroupForm,
            "new-item": _vm.createGroup
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("Plus", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }])
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "everyone",
            exact: true,
            name: _vm.t("settings", "Active users"),
            to: {
              name: "users"
            }
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
              return [_vm.userCount ? _c("NcCounterBubble", {
                attrs: {
                  type: !_vm.selectedGroupDecoded ? "highlighted" : undefined
                }
              }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.userCount) + "\n\t\t\t\t\t\t")]) : _vm._e()];
            },
            proxy: true
          }])
        }), _vm._v(" "), _vm.settings.isAdmin ? _c("NcAppNavigationItem", {
          attrs: {
            id: "admin",
            exact: true,
            name: _vm.t("settings", "Admins"),
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
              return [_c("ShieldAccount", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }, _vm.adminGroupMenu.count > 0 ? {
            key: "counter",
            fn: function () {
              return [_c("NcCounterBubble", {
                attrs: {
                  type: _vm.selectedGroupDecoded === "admin" ? "highlighted" : undefined
                }
              }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.adminGroupMenu.count) + "\n\t\t\t\t\t\t")])];
            },
            proxy: true
          } : null], null, true)
        }) : _vm._e(), _vm._v(" "), _vm.disabledGroupMenu.usercount > 0 || _vm.disabledGroupMenu.usercount === -1 ? _c("NcAppNavigationItem", {
          attrs: {
            id: "disabled",
            exact: true,
            name: _vm.t("settings", "Disabled users"),
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
              return [_c("AccountOff", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }, _vm.disabledGroupMenu.usercount > 0 ? {
            key: "counter",
            fn: function () {
              return [_c("NcCounterBubble", {
                attrs: {
                  type: _vm.selectedGroupDecoded === "disabled" ? "highlighted" : undefined
                }
              }, [_vm._v("\n\t\t\t\t\t\t\t" + _vm._s(_vm.disabledGroupMenu.usercount) + "\n\t\t\t\t\t\t")])];
            },
            proxy: true
          } : null], null, true)
        }) : _vm._e(), _vm._v(" "), _vm.groupList.length > 0 ? _c("NcAppNavigationCaption", {
          attrs: {
            name: _vm.t("settings", "Groups")
          }
        }) : _vm._e(), _vm._v(" "), _vm._l(_vm.groupList, function (group) {
          return _c("GroupListItem", {
            key: group.id,
            attrs: {
              id: group.id,
              active: _vm.selectedGroupDecoded === group.id,
              name: group.title,
              count: group.count
            }
          });
        })];
      },
      proxy: true
    }, {
      key: "footer",
      fn: function () {
        return [_c("ul", {
          staticClass: "app-navigation-entry__settings"
        }, [_c("NcAppNavigationItem", {
          attrs: {
            name: _vm.t("settings", "User management settings")
          },
          on: {
            click: function ($event) {
              _vm.isDialogOpen = true;
            }
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("Cog", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }])
        })], 1)];
      },
      proxy: true
    }])
  }, [_c("NcAppNavigationNew", {
    attrs: {
      "button-id": "new-user-button",
      text: _vm.t("settings", "New user"),
      "button-class": "icon-add"
    },
    on: {
      click: _vm.showNewUserMenu,
      keyup: [function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        return _vm.showNewUserMenu.apply(null, arguments);
      }, function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "space", 32, $event.key, [" ", "Spacebar"])) return null;
        return _vm.showNewUserMenu.apply(null, arguments);
      }]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("Plus", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  })], 1), _vm._v(" "), _c("NcAppContent", [_c("UserList", {
    attrs: {
      "selected-group": _vm.selectedGroupDecoded,
      "external-actions": _vm.externalActions
    }
  })], 1)], 1), _vm._v(" "), _c("UserSettingsDialog", {
    attrs: {
      open: _vm.isDialogOpen
    },
    on: {
      "update:open": function ($event) {
        _vm.isDialogOpen = $event;
      }
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& ***!
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
}
.user-list[data-v-6cba3aca] {
  --avatar-cell-width: 48px;
  --cell-padding: 7px;
  --cell-width: 200px;
  --cell-min-width: calc(var(--cell-width) - (2 * var(--cell-padding)));
  display: block;
  overflow: auto;
  height: 100%;
}
.user-list[data-v-6cba3aca] .user-list__body {
  display: flex;
  flex-direction: column;
  width: 100%;
  position: relative;
  margin-top: var(--row-height);
}
.user-list[data-v-6cba3aca] .user-list__row {
  position: absolute;
  display: flex;
  height: var(--row-height);
  background-color: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
}
.user-list[data-v-6cba3aca] .user-list__row:hover {
  background-color: var(--color-background-hover);
}
.user-list[data-v-6cba3aca] .user-list__row:hover .row__cell:not(.row__cell--actions) {
  background-color: var(--color-background-hover);
}
.user-list[data-v-6cba3aca] .vue-recycle-scroller__slot.user-list__header, .user-list[data-v-6cba3aca] .vue-recycle-scroller__slot.user-list__footer {
  position: sticky;
}
.user-list[data-v-6cba3aca] .vue-recycle-scroller__slot.user-list__header {
  top: 0;
  z-index: 10;
}
.user-list[data-v-6cba3aca] .vue-recycle-scroller__slot.user-list__footer {
  left: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
  /* fake input for groups validation */
}
.modal__form #new-user-groups-input[data-v-7b45e5ac] {
  position: absolute;
  opacity: 0;
  /* The "hidden" input is behind the NcSelect, so in general it does
  * not receives clicks. However, with Firefox, after the validation
  * fails, it will receive the first click done on it, so its width needs
  * to be set to 0 to prevent that ("pointer-events: none" does not
  * prevent it). */
  width: 0;
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
  position: absolute;
  display: flex;
  height: var(--row-height);
  background-color: var(--color-main-background);
}
.footer__cell[data-v-97a6cb68] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
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
    z-index: 10;
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
.footer__cell--avatar[data-v-97a6cb68] {
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
  width: 300px;
}
.footer__cell--obfuscated[data-v-97a6cb68] {
  width: 400px;
}
.footer__cell--actions[data-v-97a6cb68] {
  position: sticky;
  right: 0;
  z-index: 10;
  display: flex;
  flex-direction: row;
  align-items: center;
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
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
}
.footer__cell--count[data-v-97a6cb68] {
  left: var(--avatar-cell-width);
  width: var(--cell-width);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
  position: absolute;
  display: flex;
  height: var(--row-height);
  background-color: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
}
.header__cell[data-v-55420384] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
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
    z-index: 10;
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
.header__cell--avatar[data-v-55420384] {
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
  width: 300px;
}
.header__cell--obfuscated[data-v-55420384] {
  width: 400px;
}
.header__cell--actions[data-v-55420384] {
  position: sticky;
  right: 0;
  z-index: 10;
  display: flex;
  flex-direction: row;
  align-items: center;
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************/
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
.row__cell[data-v-11563777] {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 0 var(--cell-padding);
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
    z-index: 10;
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
.row__cell--avatar[data-v-11563777] {
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
  width: 300px;
}
.row__cell--obfuscated[data-v-11563777] {
  width: 400px;
}
.row__cell--actions[data-v-11563777] {
  position: sticky;
  right: 0;
  z-index: 10;
  display: flex;
  flex-direction: row;
  align-items: center;
  width: 110px;
  background-color: var(--color-main-background);
  border-left: 1px solid var(--color-border);
}
.row__subtitle[data-v-11563777] {
  color: var(--color-text-maxcontrast);
}
.row__cell[data-v-11563777] .input-field,
.row__cell[data-v-11563777] .input-field__main-wrapper,
.row__cell[data-v-11563777] .input-field__input {
  height: 48px !important;
}
.row__cell[data-v-11563777] .input-field__input:placeholder-shown:not(:focus) + .input-field__label {
  inset-block-start: 16px !important;
}
.row__cell[data-v-11563777] .button-vue--icon-only {
  height: 44px !important;
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

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& ***!
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
___CSS_LOADER_EXPORT___.push([module.id, `label[for=default-quota-select][data-v-3eb7c73e] {
  display: block;
  padding: 4px 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
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
___CSS_LOADER_EXPORT___.push([module.id, `.app-content[data-v-889b7562] {
  display: flex;
  overflow: hidden;
  flex-direction: column;
  max-height: 100%;
}
.app-navigation__list #addgroup[data-v-889b7562] .app-navigation-entry__utils {
  display: none;
}
.app-navigation-entry__settings[data-v-889b7562] {
  height: auto !important;
  flex: 0 0 auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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
/* harmony import */ var _GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./GroupListItem.vue?vue&type=template&id=b3f9b202& */ "./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&");
/* harmony import */ var _GroupListItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./GroupListItem.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _GroupListItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.render,
  _GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
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
/* harmony import */ var _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserList.vue?vue&type=template&id=6cba3aca&scoped=true& */ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&");
/* harmony import */ var _UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserList.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& */ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true& */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true&");
/* harmony import */ var _NewUserModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js&");
/* harmony import */ var _NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& */ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewUserModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true& */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true&");
/* harmony import */ var _UserListFooter_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=script&lang=ts& */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts&");
/* harmony import */ var _UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& */ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserListFooter_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=template&id=55420384&scoped=true& */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true&");
/* harmony import */ var _UserListHeader_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=script&lang=ts& */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts&");
/* harmony import */ var _UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& */ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserListHeader_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRow.vue?vue&type=template&id=11563777&scoped=true& */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true&");
/* harmony import */ var _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRow.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& */ "./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRowActions.vue?vue&type=template&id=34f3ef36& */ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36&");
/* harmony import */ var _UserRowActions_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRowActions.vue?vue&type=script&lang=ts& */ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _UserRowActions_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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
/* harmony import */ var _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true& */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true&");
/* harmony import */ var _UserSettingsDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& */ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserSettingsDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
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

/***/ "./apps/settings/src/views/Users.vue":
/*!*******************************************!*\
  !*** ./apps/settings/src/views/Users.vue ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Users.vue?vue&type=template&id=889b7562&scoped=true& */ "./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&");
/* harmony import */ var _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Users.vue?vue&type=script&lang=js& */ "./apps/settings/src/views/Users.vue?vue&type=script&lang=js&");
/* harmony import */ var _Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "889b7562",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/Users.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts&":
/*!****************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts& ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=script&lang=ts& */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts&":
/*!****************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts& ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=script&lang=ts& */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts&":
/*!****************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts& ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=script&lang=ts& */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js&":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js&":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js&":
/*!********************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!********************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&":
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202& ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=template&id=b3f9b202& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true& ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=template&id=6cba3aca&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true&":
/*!********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true& ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_template_id_7b45e5ac_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=template&id=7b45e5ac&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true&":
/*!**********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true& ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true&":
/*!**********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true& ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=template&id=55420384&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true& ***!
  \***************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=template&id=11563777&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36& ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=template&id=34f3ef36& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true&":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true& ***!
  \**************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true& ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=template&id=889b7562&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&":
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&":
/*!***********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserModal_vue_vue_type_style_index_0_id_7b45e5ac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserModal.vue?vue&type=style&index=0&id=7b45e5ac&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&":
/*!*************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&":
/*!*************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&":
/*!******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/img/users.svg?raw":
/*!*****************************************!*\
  !*** ./apps/settings/img/users.svg?raw ***!
  \*****************************************/
/***/ ((module) => {

module.exports = "<svg width=\"16\" height=\"16\" version=\"1.1\" viewbox=\"0 0 16 16\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"m10 1c-1.75 0-3 1.43-3 2.8 0 1.4 0.1 2.4 0.8 3.5 0.2 0.29 0.5 0.35 0.7 0.6 0.135 0.5 0.24 1 0.1 1.5-0.28 0.1-0.525 0.22-0.8 0.33-0.085-0.15-0.23-0.2-0.47-0.4-0.73-0.44-1.56-0.75-2.33-1.04-0.1-0.37-0.1-0.65 0-1 0.156-0.166 0.37-0.27 0.5-0.43 0.46-0.6 0.5-1.654 0.5-2.37 0-1.06-0.954-1.9-2-1.9-1.17 0-2 1-2 1.9 0 0.93 0.034 1.64 0.5 2.37 0.13 0.2 0.367 0.26 0.5 0.43 0.1 0.33 0.1 0.654 0 1-0.85 0.3-1.6 0.64-2.34 1.04-0.57 0.4-0.52 0.205-0.66 1.53-0.11 1.06 2.335 1.13 4 1.13h0.17c-0.054 0.274-0.1 0.63-0.17 1.3-0.16 1.59 3.5 1.7 6 1.7s6.16-0.1 6-1.7c-0.215-2-0.23-1.71-1-2.3-1.1-0.654-2.45-1.17-3.6-1.6-0.15-0.56-0.04-0.97 0.1-1.5 0.235-0.25 0.5-0.36 0.7-0.6 0.7-0.885 0.8-2.425 0.8-3.5 0-1.6-1.43-2.8-3-2.8z\"/></svg>\n";

/***/ })

}]);
//# sourceMappingURL=settings-users-settings-users.js.map?v=503ca311ce9d7c61af79