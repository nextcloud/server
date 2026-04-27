"use strict";
(globalThis["webpackChunknextcloud_ui_legacy"] = globalThis["webpackChunknextcloud_ui_legacy"] || []).push([["settings-users"],{

/***/ "./apps/settings/src/mixins/UserRowMixin.js"
/*!**************************************************!*\
  !*** ./apps/settings/src/mixins/UserRowMixin.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
  setup(props) {
    const {
      formattedFullTime
    } = (0,_nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.useFormatDateTime)(props.user.firstLoginTimestamp * 1000, {
      relativeTime: false,
      format: {
        timeStyle: 'short',
        dateStyle: 'short'
      }
    });
    return {
      formattedFullTime
    };
  },
  computed: {
    showConfig() {
      return this.$store.getters.getShowConfig;
    },
    /* QUOTA MANAGEMENT */
    usedSpace() {
      const quotaUsed = this.user.quota.used > 0 ? this.user.quota.used : 0;
      return t('settings', '{size} used', {
        size: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)(quotaUsed, true)
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
        const humanQuota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)(this.user.quota.quota);
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
    userFirstLogin() {
      if (this.user.firstLoginTimestamp > 0) {
        return this.formattedFullTime;
      }
      if (this.user.firstLoginTimestamp < 0) {
        return t('settings', 'Unknown');
      }
      return t('settings', 'Never');
    },
    /* LAST LOGIN */
    userLastLoginTooltip() {
      if (this.user.lastLoginTimestamp > 0) {
        return OC.Util.formatDate(this.user.lastLoginTimestamp * 1000);
      }
      return '';
    },
    userLastLogin() {
      if (this.user.lastLoginTimestamp > 0) {
        return OC.Util.relativeModifiedDate(this.user.lastLoginTimestamp * 1000);
      }
      return t('settings', 'Never');
    },
    userGroups() {
      const allGroups = this.$store.getters.getGroups;
      return this.user.groups.map(id => allGroups.find(g => g.id === id)).filter(group => group !== undefined);
    },
    userSubAdminGroups() {
      const allGroups = this.$store.getters.getGroups;
      return this.user.subadmin.map(id => allGroups.find(g => g.id === id)).filter(group => group !== undefined);
    }
  }
});

/***/ },

/***/ "./apps/settings/src/composables/useGroupsNavigation.ts"
/*!**************************************************************!*\
  !*** ./apps/settings/src/composables/useGroupsNavigation.ts ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
  return {
    id: group.id,
    title: group.name,
    usercount: group.usercount ?? 0,
    count: Math.max(0, (group.usercount ?? 0) - (group.disabled ?? 0))
  };
}
/**
 *
 * @param groups
 */
function useFormatGroups(groups) {
  /**
   * All non-disabled non-admin groups
   */
  const userGroups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
    const formatted = groups.value
    // filter out disabled and admin
    .filter(group => group.id !== 'disabled' && group.id !== '__nc_internal_recent' && group.id !== 'admin')
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
  /**
   * The group of recent users
   */
  const recentGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => formatGroupMenu(groups.value.find(group => group.id === '__nc_internal_recent')));
  return {
    adminGroup,
    recentGroup,
    disabledGroup,
    userGroups
  };
}

/***/ },

/***/ "./apps/settings/src/service/groups.ts"
/*!*********************************************!*\
  !*** ./apps/settings/src/service/groups.ts ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   loadUserGroups: () => (/* binding */ loadUserGroups),
/* harmony export */   loadUserSubAdminGroups: () => (/* binding */ loadUserSubAdminGroups),
/* harmony export */   searchGroups: () => (/* binding */ searchGroups)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(cancelable_promise__WEBPACK_IMPORTED_MODULE_2__);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 *
 * @param group
 */
function formatGroup(group) {
  return {
    id: group.id,
    name: group.displayname,
    usercount: group.usercount,
    disabled: group.disabled,
    canAdd: group.canAdd,
    canRemove: group.canRemove
  };
}
/**
 * Search groups
 *
 * @param options Options
 * @param options.search Search query
 * @param options.offset Offset
 * @param options.limit Limit
 */
function searchGroups({
  search,
  offset,
  limit
}) {
  const controller = new AbortController();
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_2__.CancelablePromise(async (resolve, reject, onCancel) => {
    onCancel(() => controller.abort());
    try {
      const {
        data
      } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/cloud/groups/details?search={search}&offset={offset}&limit={limit}', {
        search,
        offset,
        limit
      }), {
        signal: controller.signal
      });
      const groups = data.ocs?.data?.groups ?? [];
      const formattedGroups = groups.map(formatGroup);
      resolve(formattedGroups);
    } catch (error) {
      reject(error);
    }
  });
}
/**
 * Load user groups
 *
 * @param options Options
 * @param options.userId User id
 */
async function loadUserGroups({
  userId
}) {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/cloud/users/{userId}/groups/details', {
    userId
  });
  const {
    data
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
  const groups = data.ocs?.data?.groups ?? [];
  const formattedGroups = groups.map(formatGroup);
  return formattedGroups;
}
/**
 * Load user subadmin groups
 *
 * @param options Options
 * @param options.userId User id
 */
async function loadUserSubAdminGroups({
  userId
}) {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/cloud/users/{userId}/subadmins/details', {
    userId
  });
  const {
    data
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
  const groups = data.ocs?.data?.groups ?? [];
  const formattedGroups = groups.map(formatGroup);
  return formattedGroups;
}

/***/ },

/***/ "./apps/settings/src/utils/userUtils.ts"
/*!**********************************************!*\
  !*** ./apps/settings/src/utils/userUtils.ts ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultQuota: () => (/* binding */ defaultQuota),
/* harmony export */   isObfuscated: () => (/* binding */ isObfuscated),
/* harmony export */   unlimitedQuota: () => (/* binding */ unlimitedQuota)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const unlimitedQuota = {
  id: 'none',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Unlimited')
};
const defaultQuota = {
  id: 'default',
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Default quota')
};
/**
 * Return `true` if the logged in user does not have permissions to view the
 * data of `user`
 *
 * @param user The user to check
 * @param user.id Id of the user
 */
function isObfuscated(user) {
  const keys = Object.keys(user);
  return keys.length === 1 && keys.at(0) === 'id';
}

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts"
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/core/index.mjs");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var vue_router_composables__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-router/composables */ "./node_modules/vue-router/composables.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionText */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationCaption__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationCaption */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationList__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationList */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationList.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _GroupListItem_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./GroupListItem.vue */ "./apps/settings/src/components/GroupListItem.vue");
/* harmony import */ var _composables_useGroupsNavigation_ts__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../composables/useGroupsNavigation.ts */ "./apps/settings/src/composables/useGroupsNavigation.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _service_groups_ts__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../service/groups.ts */ "./apps/settings/src/service/groups.ts");
/* harmony import */ var _store_index_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../store/index.js */ "./apps/settings/src/store/index.js");



















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'AppNavigationGroupList',
  setup(__props) {
    const store = (0,_store_index_js__WEBPACK_IMPORTED_MODULE_17__.useStore)();
    const route = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_6__.useRoute)();
    const router = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_6__.useRouter)();
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onBeforeMount)(async () => {
      await loadGroups();
    });
    /** Current active group in the view - this is URL encoded */
    const selectedGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => route.params?.selectedGroup);
    /** Current active group - URL decoded  */
    const selectedGroupDecoded = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => selectedGroup.value ? decodeURIComponent(selectedGroup.value) : null);
    /** Server settings for current user */
    const settings = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => store.getters.getServerData);
    /** True if the current user is a (delegated) admin */
    const isAdminOrDelegatedAdmin = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => settings.value.isAdmin || settings.value.isDelegatedAdmin);
    /** All available groups */
    const groups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      return isAdminOrDelegatedAdmin.value ? store.getters.getSortedGroups : store.getters.getSubAdminGroups;
    });
    /** User groups */
    const {
      userGroups
    } = (0,_composables_useGroupsNavigation_ts__WEBPACK_IMPORTED_MODULE_14__.useFormatGroups)(groups);
    /** True if the 'add-group' dialog is open - needed to be able to close it when the group is created */
    const isAddGroupOpen = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    /** True if the group creation is in progress to show loading spinner and disable adding another one */
    const loadingAddGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    /** Error state for creating a new group */
    const hasAddGroupError = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    /** Name of the group to create (used in the group creation dialog) */
    const newGroupName = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('');
    /** True if groups are loading */
    const loadingGroups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    /** Search offset */
    const offset = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(0);
    /** Search query — shared via Vuex store */
    const groupsSearchQuery = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => store.getters.getSearchQuery);
    const filteredGroups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      if (isAdminOrDelegatedAdmin.value) {
        return userGroups.value;
      }
      const substring = groupsSearchQuery.value.toLowerCase();
      return userGroups.value.filter(group => group.id.toLowerCase().search(substring) !== -1 || group.title.toLowerCase().search(substring) !== -1);
    });
    const groupListItems = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    const lastGroupListItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      return groupListItems.value.findLast(component => component?.$vnode?.key === userGroups.value?.at(-1)?.id) // Order of refs is not guaranteed to match source array order
      ?.$refs?.listItem?.$el;
    });
    const isLastGroupVisible = (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_4__.useElementVisibility)(lastGroupListItem);
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(isLastGroupVisible, async () => {
      if (!isLastGroupVisible.value) {
        return;
      }
      await loadGroups();
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(groupsSearchQuery, async () => {
      store.commit('resetGroups');
      offset.value = 0;
      await loadGroups();
    });
    /** Cancelable promise for search groups request */
    const promise = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    /**
     * Load groups
     */
    async function loadGroups() {
      if (!isAdminOrDelegatedAdmin.value) {
        return;
      }
      if (promise.value) {
        promise.value.cancel();
      }
      loadingGroups.value = true;
      try {
        promise.value = (0,_service_groups_ts__WEBPACK_IMPORTED_MODULE_16__.searchGroups)({
          search: groupsSearchQuery.value,
          offset: offset.value,
          limit: 25
        });
        const groups = await promise.value;
        if (groups.length > 0) {
          offset.value += 25;
        }
        for (const group of groups) {
          store.commit('addGroup', group);
        }
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_15__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('settings', 'Failed to load groups'), {
          error
        });
      }
      promise.value = undefined;
      loadingGroups.value = false;
    }
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
        const newGroupListItem = groupListItems.value.findLast(component => component?.$vnode?.key === groupId);
        newGroupListItem?.$refs?.listItem?.$el?.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest'
        });
        newGroupName.value = '';
      } catch {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('settings', 'Failed to create group'));
      }
      loadingAddGroup.value = false;
    }
    return {
      __sfc: true,
      store,
      route,
      router,
      selectedGroup,
      selectedGroupDecoded,
      settings,
      isAdminOrDelegatedAdmin,
      groups,
      userGroups,
      isAddGroupOpen,
      loadingAddGroup,
      hasAddGroupError,
      newGroupName,
      loadingGroups,
      offset,
      groupsSearchQuery,
      filteredGroups,
      groupListItems,
      lastGroupListItem,
      isLastGroupVisible,
      promise,
      loadGroups,
      createGroup,
      mdiAccountGroupOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiAccountGroupOutline,
      mdiPlus: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiPlus,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t,
      Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_5__.Fragment,
      NcActionInput: _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_7__["default"],
      NcActionText: _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_8__["default"],
      NcAppNavigationCaption: _nextcloud_vue_components_NcAppNavigationCaption__WEBPACK_IMPORTED_MODULE_9__["default"],
      NcAppNavigationList: _nextcloud_vue_components_NcAppNavigationList__WEBPACK_IMPORTED_MODULE_10__["default"],
      NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_11__["default"],
      NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_12__["default"],
      GroupListItem: _GroupListItem_vue__WEBPACK_IMPORTED_MODULE_13__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts"
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_1__["default"].extend({
  name: 'UserListFooter',
  components: {
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_2__["default"]
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
        return this.n('settings', '{userCount} account …', '{userCount} accounts …', this.filteredUsers.length, {
          userCount: this.filteredUsers.length
        });
      }
      return this.n('settings', '{userCount} account', '{userCount} accounts', this.filteredUsers.length, {
        userCount: this.filteredUsers.length
      });
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
    n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translatePlural
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts"
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_1__["default"].extend({
  name: 'UserListHeader',
  computed: {
    showConfig() {
      // @ts-expect-error: allow untyped $store
      return this.$store.getters.getShowConfig;
    },
    settings() {
      // @ts-expect-error: allow untyped $store
      return this.$store.getters.getServerData;
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts"
/*!*****************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/svg/svg/check.svg?raw */ "./node_modules/@mdi/svg/svg/check.svg?raw");
/* harmony import */ var _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/svg/svg/pencil-outline.svg?raw */ "./node_modules/@mdi/svg/svg/pencil-outline.svg?raw");
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! is-svg */ "./node_modules/is-svg/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_3__.defineComponent)({
  components: {
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_6__["default"]
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
      return this.edit ? _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_0__ : _mdi_svg_svg_pencil_outline_svg_raw__WEBPACK_IMPORTED_MODULE_1__;
    },
    /**
     * Enabled user row actions
     */
    enabledActions() {
      return this.actions.filter(action => typeof action.enabled === 'function' ? action.enabled(this.user) : true);
    }
  },
  methods: {
    isSvg: is_svg__WEBPACK_IMPORTED_MODULE_2__["default"],
    /**
     * Toggle edit mode by emitting the update event
     */
    toggleEdit() {
      this.$emit('update:edit', !this.edit);
    }
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts"
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vueuse_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @vueuse/components */ "./node_modules/@vueuse/components/index.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../logger.ts */ "./apps/settings/src/logger.ts");




vue__WEBPACK_IMPORTED_MODULE_2__["default"].directive('elementVisibility', _vueuse_components__WEBPACK_IMPORTED_MODULE_0__.vElementVisibility);
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
        paddingTop: `${this.startIndex * this.itemHeight}px`,
        paddingBottom: isOverScrolled ? 0 : `${hiddenAfterItems * this.itemHeight}px`
      };
    }
  },
  mounted() {
    const root = this.$el;
    const tfoot = this.$refs?.tfoot;
    const thead = this.$refs?.thead;
    this.resizeObserver = new ResizeObserver((0,debounce__WEBPACK_IMPORTED_MODULE_1__["default"])(() => {
      this.headerHeight = thead?.clientHeight ?? 0;
      this.tableHeight = root?.clientHeight ?? 0;
      _logger_ts__WEBPACK_IMPORTED_MODULE_3__["default"].debug('VirtualList resizeObserver updated');
      this.onScroll();
    }, 100));
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts"
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_composables_useHotKey__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/composables/useHotKey */ "./node_modules/@nextcloud/vue/dist/Composables/useHotKey.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var vue_router_composables__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-router/composables */ "./node_modules/vue-router/composables.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigation__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigation */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationItem__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationItem */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationList */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationList.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationNew__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationNew */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCounterBubble__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcCounterBubble */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcInputField__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcInputField */ "./node_modules/@nextcloud/vue/dist/Components/NcInputField.mjs");
/* harmony import */ var _components_AppNavigationGroupList_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../components/AppNavigationGroupList.vue */ "./apps/settings/src/components/AppNavigationGroupList.vue");
/* harmony import */ var _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../components/Users/UserSettingsDialog.vue */ "./apps/settings/src/components/Users/UserSettingsDialog.vue");
/* harmony import */ var _composables_useGroupsNavigation_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../composables/useGroupsNavigation.js */ "./apps/settings/src/composables/useGroupsNavigation.ts");
/* harmony import */ var _store_index_js__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../store/index.js */ "./apps/settings/src/store/index.js");



















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'UserManagementNavigation',
  setup(__props) {
    const route = (0,vue_router_composables__WEBPACK_IMPORTED_MODULE_5__.useRoute)();
    const store = (0,_store_index_js__WEBPACK_IMPORTED_MODULE_17__.useStore)();
    const searchField = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const searchInput = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('');
    const commitSearch = (0,debounce__WEBPACK_IMPORTED_MODULE_4__["default"])(query => {
      store.commit('setSearchQuery', query);
    }, 300);
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(searchInput, value => commitSearch(value));
    function clearSearch() {
      commitSearch.clear();
      searchInput.value = '';
      store.commit('setSearchQuery', '');
    }
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onBeforeUnmount)(() => commitSearch.clear());
    // Intercept Ctrl/Cmd+F to focus the local search. useHotKey ignores the
    // event when an input/textarea is already focused, so a second press falls
    // through to the browser's native find-in-page.
    (0,_nextcloud_vue_composables_useHotKey__WEBPACK_IMPORTED_MODULE_3__.useHotKey)('f', () => searchField.value?.focus(), {
      ctrl: true,
      stop: true,
      prevent: true
    });
    /** State of the 'new-account' dialog */
    const isDialogOpen = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    /** Current active group in the view - this is URL encoded */
    const selectedGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => route.params?.selectedGroup);
    /** Current active group - URL decoded  */
    const selectedGroupDecoded = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => selectedGroup.value ? decodeURIComponent(selectedGroup.value) : null);
    /** Overall user count */
    const userCount = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => store.getters.getUserCount);
    /** All available groups */
    const groups = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => store.getters.getSortedGroups);
    const {
      adminGroup,
      recentGroup,
      disabledGroup
    } = (0,_composables_useGroupsNavigation_js__WEBPACK_IMPORTED_MODULE_16__.useFormatGroups)(groups);
    /** Server settings for current user */
    const settings = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => store.getters.getServerData);
    /** True if the current user is a (delegated) admin */
    const isAdminOrDelegatedAdmin = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => settings.value.isAdmin || settings.value.isDelegatedAdmin);
    /**
     * Open the new-user form dialog
     */
    function showNewUserMenu() {
      store.dispatch('setShowConfig', {
        key: 'showNewUserForm',
        value: true
      });
    }
    return {
      __sfc: true,
      route,
      store,
      searchField,
      searchInput,
      commitSearch,
      clearSearch,
      isDialogOpen,
      selectedGroup,
      selectedGroupDecoded,
      userCount,
      groups,
      adminGroup,
      recentGroup,
      disabledGroup,
      settings,
      isAdminOrDelegatedAdmin,
      showNewUserMenu,
      mdiAccountOffOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiAccountOffOutline,
      mdiAccountOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiAccountOutline,
      mdiClose: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiClose,
      mdiCogOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCogOutline,
      mdiHistory: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiHistory,
      mdiMagnify: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiMagnify,
      mdiPlus: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiPlus,
      mdiShieldAccountOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiShieldAccountOutline,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
      NcAppNavigation: _nextcloud_vue_components_NcAppNavigation__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcAppNavigationItem: _nextcloud_vue_components_NcAppNavigationItem__WEBPACK_IMPORTED_MODULE_7__["default"],
      NcAppNavigationList: _nextcloud_vue_components_NcAppNavigationList__WEBPACK_IMPORTED_MODULE_8__["default"],
      NcAppNavigationNew: _nextcloud_vue_components_NcAppNavigationNew__WEBPACK_IMPORTED_MODULE_9__["default"],
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__["default"],
      NcCounterBubble: _nextcloud_vue_components_NcCounterBubble__WEBPACK_IMPORTED_MODULE_11__["default"],
      NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_12__["default"],
      NcInputField: _nextcloud_vue_components_NcInputField__WEBPACK_IMPORTED_MODULE_13__["default"],
      AppNavigationGroupList: _components_AppNavigationGroupList_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
      UserSettingsDialog: _components_Users_UserSettingsDialog_vue__WEBPACK_IMPORTED_MODULE_15__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppNavigationItem__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppNavigationItem */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCounterBubble__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcCounterBubble */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs");
/* harmony import */ var _nextcloud_vue_components_NcModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcModal */ "./node_modules/@nextcloud/vue/dist/Components/NcModal.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var vue_material_design_icons_AccountGroupOutline_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/AccountGroupOutline.vue */ "./node_modules/vue-material-design-icons/AccountGroupOutline.vue");
/* harmony import */ var vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/PencilOutline.vue */ "./node_modules/vue-material-design-icons/PencilOutline.vue");
/* harmony import */ var vue_material_design_icons_TrashCanOutline_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/TrashCanOutline.vue */ "./node_modules/vue-material-design-icons/TrashCanOutline.vue");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'GroupListItem',
  components: {
    AccountGroup: vue_material_design_icons_AccountGroupOutline_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    Delete: vue_material_design_icons_TrashCanOutline_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_1__.Fragment,
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcActionInput: _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcAppNavigationItem: _nextcloud_vue_components_NcAppNavigationItem__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcCounterBubble: _nextcloud_vue_components_NcCounterBubble__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcModal: _nextcloud_vue_components_NcModal__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_8__["default"],
    Pencil: vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_10__["default"]
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
      } catch {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('settings', 'Failed to delete group "{group}"', {
          group: this.name
        }));
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _Users_NewUserDialog_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Users/NewUserDialog.vue */ "./apps/settings/src/components/Users/NewUserDialog.vue");
/* harmony import */ var _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./Users/UserListFooter.vue */ "./apps/settings/src/components/Users/UserListFooter.vue");
/* harmony import */ var _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Users/UserListHeader.vue */ "./apps/settings/src/components/Users/UserListHeader.vue");
/* harmony import */ var _Users_UserRow_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./Users/UserRow.vue */ "./apps/settings/src/components/Users/UserRow.vue");
/* harmony import */ var _Users_VirtualList_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./Users/VirtualList.vue */ "./apps/settings/src/components/Users/VirtualList.vue");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");














const newUser = Object.freeze({
  id: '',
  displayName: '',
  password: '',
  mailAddress: '',
  groups: [],
  manager: '',
  subAdminsGroups: [],
  quota: _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.defaultQuota,
  language: {
    code: 'en',
    name: t('settings', 'Default language')
  }
});
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserList',
  components: {
    Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_3__.Fragment,
    NcEmptyContent: _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_6__["default"],
    NewUserDialog: _Users_NewUserDialog_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    UserListFooter: _Users_UserListFooter_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    UserListHeader: _Users_UserListHeader_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    VirtualList: _Users_VirtualList_vue__WEBPACK_IMPORTED_MODULE_11__["default"]
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
      mdiAccountGroupOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_0__.mdiAccountGroupOutline,
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
      isInitialLoad: true
    };
  },
  computed: {
    searchQuery() {
      return this.$store.getters.getSearchQuery;
    },
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
    users() {
      return this.$store.getters.getUsers;
    },
    filteredUsers() {
      if (this.selectedGroup === 'disabled') {
        return this.users.filter(user => user.enabled === false);
      }
      return this.users.filter(user => user.enabled !== false);
    },
    groups() {
      return this.$store.getters.getSortedGroups.filter(group => group.id !== '__nc_internal_recent' && group.id !== 'disabled');
    },
    quotaOptions() {
      // convert the preset array into objects
      const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({
        id: cur,
        label: cur
      }), []);
      // add default presets
      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.unlimitedQuota);
      }
      quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.defaultQuota);
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
    async searchQuery() {
      this.$store.commit('resetUsers');
      await this.loadUsers();
    },
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
      _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].debug(`${filteredUsers.length} filtered user(s)`);
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
     * If disabled group but empty, redirect
     */
    await this.redirectIfDisabled();
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
        } else if (this.selectedGroup === '__nc_internal_recent') {
          await this.$store.dispatch('getRecentUsers', {
            offset: this.usersOffset,
            limit: this.usersLimit,
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
        _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].debug(`${this.users.length} total user(s) loaded`);
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_12__["default"].error('Failed to load accounts', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)('Failed to load accounts');
      }
      this.loading.users = false;
      this.isInitialLoad = false;
    },
    closeDialog() {
      this.$store.dispatch('setShowConfig', {
        key: 'showNewUserForm',
        value: false
      });
    },
    resetForm() {
      // revert form to original state
      this.newUser = {
        ...newUser
      };

      /**
       * Init default language from server data. The use of this.settings
       * requires a computed variable, which break the v-model binding of the form,
       * this is a much easier solution than getter and setter on a computed var
       */
      if (this.settings.defaultLanguage) {
        vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(this.newUser.language, 'code', this.settings.defaultLanguage);
      }

      /**
       * In case the user directly loaded the user list within a group
       * the watch won't be triggered. We need to initialize it.
       */
      this.setNewUserDefaultGroup(this.selectedGroup);
      this.loading.all = false;
    },
    setNewUserDefaultGroup(value) {
      // Is no value set, but user is a line manager we set their group as this is a requirement for line manager
      if (!value && !this.settings.isAdmin && !this.settings.isDelegatedAdmin) {
        const groups = this.$store.getters.getSubAdminGroups;
        // if there are multiple groups we do not know which to add,
        // so we cannot make the managers life easier by preselecting it.
        if (groups.length === 1) {
          this.newUser.groups = [...groups];
        }
        return;
      }
      if (value) {
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js"
/*!******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcPasswordField */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _service_groups_ts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../service/groups.ts */ "./apps/settings/src/service/groups.ts");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NewUserDialog',
  components: {
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcPasswordField: _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_5__["default"]
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
      managerInputLabel: t('settings', 'Manager'),
      // TRANSLATORS This string describes a manager in the context of an organization
      managerLabel: t('settings', 'Set line manager'),
      // Cancelable promise for search groups request
      promise: null
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
    availableGroups() {
      const groups = this.settings.isAdmin || this.settings.isDelegatedAdmin ? this.$store.getters.getSortedGroups : this.$store.getters.getSubAdminGroups;
      return groups.filter(group => group.id !== '__nc_internal_recent' && group.id !== 'disabled');
    },
    availableSubAdminGroups() {
      return this.availableGroups.filter(group => group.id !== 'admin');
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
  mounted() {
    this.$refs.username?.focus?.();
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
        this.$refs.username?.focus?.();
        this.$emit('closing');
      } catch (error) {
        this.loading.all = false;
        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          const statuscode = error.response.data.ocs.meta.statuscode;
          if (statuscode === 102) {
            // wrong username
            this.$refs.username?.focus?.();
          } else if (statuscode === 107) {
            // wrong password
            this.$refs.password?.focus?.();
          }
        }
      }
    },
    async searchGroups(query, toggleLoading) {
      if (!this.settings.isAdmin && !this.settings.isDelegatedAdmin) {
        // managers cannot search for groups
        return;
      }
      if (this.promise) {
        this.promise.cancel();
      }
      toggleLoading(true);
      try {
        this.promise = (0,_service_groups_ts__WEBPACK_IMPORTED_MODULE_7__.searchGroups)({
          search: query,
          offset: 0,
          limit: 25
        });
        const groups = await this.promise;
        // Populate store from server request
        for (const group of groups) {
          this.$store.commit('addGroup', group);
        }
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_6__["default"].error(t('settings', 'Failed to search groups'), {
          error
        });
      }
      this.promise = null;
      toggleLoading(false);
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
        this.newUser.groups.push({
          id: gid,
          name: gid
        });
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_6__["default"].error(t('settings', 'Failed to create group'), {
          error
        });
      }
      this.loading.groups = false;
    },
    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    async addGroup(group) {
      if (group.isCreating) {
        return;
      }
      if (group.canAdd === false) {
        return;
      }
      this.newUser.groups.push(group);
    },
    /**
     * Remove user from group
     *
     * @param {object} group Group object
     */
    removeGroup(group) {
      if (group.canRemove === false) {
        return;
      }
      this.newUser.groups = this.newUser.groups.filter(g => g.id !== group.id);
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
        quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.formatFileSize)((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.parseFileSize)(quota, true));
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_components_NcProgressBar__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcProgressBar */ "./node_modules/@nextcloud/vue/dist/Components/NcProgressBar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./UserRowActions.vue */ "./apps/settings/src/components/Users/UserRowActions.vue");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../mixins/UserRowMixin.js */ "./apps/settings/src/mixins/UserRowMixin.js");
/* harmony import */ var _service_groups_ts__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../service/groups.ts */ "./apps/settings/src/service/groups.ts");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");














const productName = window.OC.theme.productName;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserRow',
  components: {
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcProgressBar: _nextcloud_vue_components_NcProgressBar__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_8__["default"],
    UserRowActions: _UserRowActions_vue__WEBPACK_IMPORTED_MODULE_9__["default"]
  },
  mixins: [_mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_11__["default"]],
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
        mailAddress: false,
        groups: false,
        groupsDetails: false,
        subAdminGroupsDetails: false,
        subadmins: false,
        quota: false,
        delete: false,
        disable: false,
        languages: false,
        wipe: false,
        manager: false
      },
      editedDisplayName: this.user.displayname,
      editedMail: this.user.email ?? '',
      // Cancelable promise for search groups request
      promise: null
    };
  },
  computed: {
    managerLabel() {
      // TRANSLATORS This string describes a person's manager in the context of an organization
      return t('settings', 'Set line manager');
    },
    isObfuscated() {
      return (0,_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.isObfuscated)(this.user);
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
    availableGroups() {
      const groups = this.settings.isAdmin || this.settings.isDelegatedAdmin ? this.$store.getters.getSortedGroups : this.$store.getters.getSubAdminGroups;
      return groups.filter(group => group.id !== '__nc_internal_recent' && group.id !== 'disabled');
    },
    availableSubAdminGroups() {
      return this.availableGroups.filter(group => group.id !== 'admin');
    },
    userGroupsLabels() {
      return this.userGroups.map(group => {
        // Try to match with more extensive group data
        const availableGroup = this.availableGroups.find(g => g.id === group.id);
        return availableGroup?.name ?? group.name ?? group.id;
      }).join(', ');
    },
    userSubAdminGroupsLabels() {
      return this.userSubAdminGroups.map(group => {
        // Try to match with more extensive group data
        const availableGroup = this.availableSubAdminGroups.find(g => g.id === group.id);
        return availableGroup?.name ?? group.name ?? group.id;
      }).join(', ');
    },
    usedSpace() {
      if (this.user.quota?.used) {
        return t('settings', '{size} used', {
          size: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.formatFileSize)(this.user.quota?.used)
        });
      }
      return t('settings', '{size} used', {
        size: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.formatFileSize)(0)
      });
    },
    canEdit() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid !== this.user.id || this.settings.isAdmin || this.settings.isDelegatedAdmin;
    },
    userQuota() {
      let quota = this.user.quota?.quota;
      if (quota === 'default') {
        quota = this.settings.defaultQuota;
        if (quota !== 'none') {
          // convert to numeric value to match what the server would usually return
          quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(quota, true);
        }
      }

      // when the default quota is unlimited, the server returns -3 here, map it to "none"
      if (quota === 'none' || quota === -3) {
        return t('settings', 'Unlimited');
      } else if (quota >= 0) {
        return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.formatFileSize)(quota);
      }
      return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.formatFileSize)(0);
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
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.unlimitedQuota.id && (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(this.settings.defaultQuota, true) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.unlimitedQuota; // unlimited
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
    async wipeUserDevices() {
      const userid = this.user.id;
      await (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
      OC.dialogs.confirmDestructive(t('settings', 'In case of lost device or exiting the organization, this can remotely wipe the {productName} data from all devices associated with {userid}. Only works if the devices are connected to the internet.', {
        userid,
        productName
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
          this.$store.dispatch('wipeUserDevices', userid).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('settings', 'Wiped {userid}\'s devices', {
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
        this.currentManager = response?.data.ocs.data;
      });
    },
    async searchInitialUserManager() {
      this.loadingPossibleManagers = true;
      await this.searchUserManager();
      this.loadingPossibleManagers = false;
    },
    async loadGroupsDetails() {
      this.loading.groups = true;
      this.loading.groupsDetails = true;
      try {
        const groups = await (0,_service_groups_ts__WEBPACK_IMPORTED_MODULE_12__.loadUserGroups)({
          userId: this.user.id
        });
        // Populate store from server request
        for (const group of groups) {
          this.$store.commit('addGroup', group);
        }
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(t('settings', 'Failed to load groups with details'), {
          error
        });
      }
      this.loading.groups = false;
      this.loading.groupsDetails = false;
    },
    async loadSubAdminGroupsDetails() {
      this.loading.subadmins = true;
      this.loading.subAdminGroupsDetails = true;
      try {
        const groups = await (0,_service_groups_ts__WEBPACK_IMPORTED_MODULE_12__.loadUserSubAdminGroups)({
          userId: this.user.id
        });
        // Populate store from server request
        for (const group of groups) {
          this.$store.commit('addGroup', group);
        }
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(t('settings', 'Failed to load sub admin groups with details'), {
          error
        });
      }
      this.loading.subadmins = false;
      this.loading.subAdminGroupsDetails = false;
    },
    async searchGroups(query, toggleLoading) {
      if (query === '') {
        return; // Prevent unexpected search behaviour e.g. on option:created
      }
      if (this.promise) {
        this.promise.cancel();
      }
      toggleLoading(true);
      try {
        this.promise = await (0,_service_groups_ts__WEBPACK_IMPORTED_MODULE_12__.searchGroups)({
          search: query,
          offset: 0,
          limit: 25
        });
        const groups = await this.promise;
        // Populate store from server request
        for (const group of groups) {
          this.$store.commit('addGroup', group);
        }
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(t('settings', 'Failed to search groups'), {
          error
        });
      }
      this.promise = null;
      toggleLoading(false);
    },
    async searchUserManager(query) {
      await this.$store.dispatch('searchUsers', {
        offset: 0,
        limit: 10,
        search: query
      }).then(response => {
        const users = response?.data ? this.filterManagers(Object.values(response?.data.ocs.data.users)) : [];
        if (users.length > 0) {
          this.possibleManagers = users;
        }
      });
    },
    async updateUserManager() {
      this.loading.manager = true;

      // Store the current manager before making changes
      const previousManager = this.user.manager;
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'manager',
          value: this.currentManager ? this.currentManager.id : ''
        });
      } catch (error) {
        // TRANSLATORS This string describes a line manager in the context of an organization
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('settings', 'Failed to update line manager'));
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error('Failed to update manager:', {
          error
        });

        // Revert to the previous manager in the UI on error
        this.currentManager = previousManager;
      } finally {
        this.loading.manager = false;
      }
    },
    async deleteUser() {
      const userid = this.user.id;
      await (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
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
     */
    async updateDisplayName() {
      this.loading.displayName = true;
      try {
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'displayname',
          value: this.editedDisplayName
        });
        if (this.editedDisplayName === this.user.displayname) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('settings', 'Display name was successfully changed'));
        }
      } finally {
        this.loading.displayName = false;
      }
    },
    /**
     * Set user mailAddress
     */
    async updateEmail() {
      this.loading.mailAddress = true;
      if (this.editedMail === '') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('settings', "Email can't be empty"));
        this.loading.mailAddress = false;
        this.editedMail = this.user.email;
      } else {
        try {
          await this.$store.dispatch('setUserData', {
            userid: this.user.id,
            key: 'email',
            value: this.editedMail
          });
          if (this.editedMail === this.user.email) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('settings', 'Email was successfully changed'));
          }
        } finally {
          this.loading.mailAddress = false;
        }
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
      this.loading.groups = true;
      try {
        await this.$store.dispatch('addGroup', gid);
        const userid = this.user.id;
        await this.$store.dispatch('addUserGroup', {
          userid,
          gid
        });
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(t('settings', 'Failed to create group'), {
          error
        });
      }
      this.loading.groups = false;
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
      const userid = this.user.id;
      const gid = group.id;
      if (group.canAdd === false) {
        return;
      }
      this.loading.groups = true;
      try {
        await this.$store.dispatch('addUserGroup', {
          userid,
          gid
        });
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
      }
      this.loading.groups = false;
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
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
      }
      this.loading.subadmins = false;
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
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
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
    async setUserQuota(quota = 'none') {
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.unlimitedQuota;
      }
      this.loading.quota = true;

      // ensure we only send the preset id
      quota = quota.id ? quota.id : quota;
      try {
        // If human readable format, convert to raw float format
        // Else just send the raw string
        const value = ((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(quota, true) || quota).toString();
        await this.$store.dispatch('setUserData', {
          userid: this.user.id,
          key: 'quota',
          value
        });
      } catch (error) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
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
        quota = quota?.id || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(quota, true);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_13__.unlimitedQuota;
      } else {
        // unify format output
        quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.formatFileSize)((0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.parseFileSize)(quota, true));
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
        _logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error(error);
      }
      return lang;
    },
    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail() {
      this.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(() => (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('settings', 'Welcome mail sent!'), {
        timeout: 2000
      })).finally(() => {
        this.loading.all = false;
      });
    },
    async toggleEdit() {
      this.editing = !this.editing;
      if (this.editing) {
        await this.$nextTick();
        this.$refs.displayNameField?.$refs?.inputField?.$refs?.input?.focus();
        this.loadGroupsDetails();
        this.loadSubAdminGroupsDetails();
      }
      if (this.editedDisplayName !== this.user.displayname) {
        this.editedDisplayName = this.user.displayname;
      } else if (this.editedMail !== this.user.email) {
        this.editedMail = this.user.email ?? '';
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_composables_useHotKey__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/composables/useHotKey */ "./node_modules/@nextcloud/vue/dist/Composables/useHotKey.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcAppSettingsDialog__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppSettingsDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppSettingsSection__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppSettingsSection */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsSection.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAppSettingsShortcutsSection__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppSettingsShortcutsSection */ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsShortcutsSection.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_components_NcHotkey__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcHotkey */ "./node_modules/@nextcloud/vue/dist/Components/NcHotkey.mjs");
/* harmony import */ var _nextcloud_vue_components_NcHotkeyList__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcHotkeyList */ "./node_modules/@nextcloud/vue/dist/Components/NcHotkeyList.mjs");
/* harmony import */ var _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcNoteCard */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../constants/GroupManagement.ts */ "./apps/settings/src/constants/GroupManagement.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../logger.ts */ "./apps/settings/src/logger.ts");
/* harmony import */ var _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../../utils/userUtils.ts */ "./apps/settings/src/utils/userUtils.ts");
















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserSettingsDialog',
  components: {
    NcAppSettingsDialog: _nextcloud_vue_components_NcAppSettingsDialog__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcAppSettingsSection: _nextcloud_vue_components_NcAppSettingsSection__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcAppSettingsShortcutsSection: _nextcloud_vue_components_NcAppSettingsShortcutsSection__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcHotkey: _nextcloud_vue_components_NcHotkey__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcHotkeyList: _nextcloud_vue_components_NcHotkeyList__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcNoteCard: _nextcloud_vue_components_NcNoteCard__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_12__["default"]
  },
  props: {
    open: {
      type: Boolean,
      required: true
    }
  },
  emits: ['update:open'],
  setup(_, {
    emit
  }) {
    // ? opens the settings dialog on the keyboard shortcuts section
    (0,_nextcloud_vue_composables_useHotKey__WEBPACK_IMPORTED_MODULE_3__.useHotKey)('?', async () => {
      emit('update:open', true);
      await (0,vue__WEBPACK_IMPORTED_MODULE_4__.nextTick)();
      document.getElementById('settings-section_keyboard-shortcuts')?.scrollIntoView({
        behavior: 'smooth',
        inline: 'nearest'
      });
    }, {
      stop: true,
      prevent: true
    });
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
        return this.$store.getters.getGroupSorting === _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_13__.GroupSorting.GroupName ? 'name' : 'member-count';
      },
      set(sorting) {
        this.$store.commit('setGroupSorting', sorting === 'name' ? _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_13__.GroupSorting.GroupName : _constants_GroupManagement_ts__WEBPACK_IMPORTED_MODULE_13__.GroupSorting.UserCount);
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
    showFirstLogin: {
      get() {
        return this.showConfig.showFirstLogin;
      },
      set(status) {
        this.setShowConfig('showFirstLogin', status);
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
        quotaPreset.unshift(_utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__.unlimitedQuota);
      }
      return quotaPreset;
    },
    defaultQuota: {
      get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }
        if (this.settings.defaultQuota !== _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__.unlimitedQuota; // unlimited
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
          await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/settings/users/preferences/newUser.sendEmail'), {
            value: value ? 'yes' : 'no'
          });
        } catch (error) {
          _logger_ts__WEBPACK_IMPORTED_MODULE_14__["default"].error('Could not update newUser.sendEmail preference', {
            error
          });
        } finally {
          this.loadingSendMail = false;
        }
      }
    }
  },
  methods: {
    /**
     * Check if a quota matches the current search.
     * This is a custom filter function to allow to map "1GB" to the label "1 GB" (ignoring whitespaces).
     *
     * @param {object} option The quota to check
     * @param {string} label The label of the quota
     * @param {string} search The search string
     */
    filterQuotas(option, label, search) {
      const searchValue = search.toLocaleLowerCase().replaceAll(/\s/g, '');
      return (label || '').toLocaleLowerCase().replaceAll(/\s/g, '').indexOf(searchValue) > -1;
    },
    setShowConfig(key, status) {
      this.$store.dispatch('setShowConfig', {
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
        quota = quota?.id || quota.label;
      }
      // only used for new presets sent through @Tag
      const validQuota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.parseFileSize)(quota, true);
      if (validQuota === null) {
        return _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__.unlimitedQuota;
      }
      // unify format output
      quota = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.formatFileSize)(validQuota);
      return {
        id: quota,
        label: quota
      };
    },
    /**
     * Dispatch default quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     */
    setDefaultQuota(quota = 'none') {
      // Make sure correct label is set for unlimited quota
      if (quota === 'none') {
        quota = _utils_userUtils_ts__WEBPACK_IMPORTED_MODULE_15__.unlimitedQuota;
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcAppContent__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcAppContent */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.mjs");
/* harmony import */ var _components_UserList_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/UserList.vue */ "./apps/settings/src/components/UserList.vue");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_2__.defineComponent)({
  name: 'UserManagement',
  components: {
    NcAppContent: _nextcloud_vue_components_NcAppContent__WEBPACK_IMPORTED_MODULE_3__["default"],
    UserList: _components_UserList_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  data() {
    return {
      // temporary value used for multiselect change
      externalActions: []
    };
  },
  computed: {
    pageHeading() {
      if (this.selectedGroupDecoded === null) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'All accounts');
      }
      const matchHeading = {
        admin: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Admins'),
        disabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Disabled accounts')
      };
      return matchHeading[this.selectedGroupDecoded] ?? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('settings', 'Account group: {group}', {
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
    this.$store.dispatch('getPasswordPolicyMinLength');
  },
  created() {
    // init the OCA.Settings.UserList object
    window.OCA = window.OCA ?? {};
    window.OCA.Settings = window.OCA.Settings ?? {};
    window.OCA.Settings.UserList = window.OCA.Settings.UserList ?? {};
    // and add the registerAction method
    window.OCA.Settings.UserList.registerAction = this.registerAction;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('settings:user-management:loaded');
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
    /**
     * Register a new action for the user menu
     *
     * @param {string} icon the icon class
     * @param {string} text the text to display
     * @param {Function} action the function to run
     * @param {(user: Record<string, unknown>) => boolean} enabled return true if the action is enabled for the user
     * @return {Array}
     */
    registerAction(icon, text, action, enabled) {
      this.externalActions.push({
        icon,
        text,
        action,
        enabled
      });
      return this.externalActions;
    }
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae"
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.Fragment, [_c(_setup.NcAppNavigationCaption, {
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
    scopedSlots: _vm._u([_setup.isAdminOrDelegatedAdmin ? {
      key: "actionsTriggerIcon",
      fn: function () {
        return [_setup.loadingAddGroup ? _c(_setup.NcLoadingIcon) : _c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiPlus
          }
        })];
      },
      proxy: true
    } : null, _setup.isAdminOrDelegatedAdmin ? {
      key: "actions",
      fn: function () {
        return [_c(_setup.NcActionText, {
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.mdiAccountGroupOutline
                }
              })];
            },
            proxy: true
          }], null, false, 4071362859)
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_setup.t("settings", "Create group")) + "\n\t\t\t")]), _vm._v(" "), _c(_setup.NcActionInput, {
          attrs: {
            label: _setup.t("settings", "Group name"),
            "data-cy-users-settings-new-group-name": "",
            "label-outside": false,
            disabled: _setup.loadingAddGroup,
            error: _setup.hasAddGroupError,
            "helper-text": _setup.hasAddGroupError ? _setup.t("settings", "Please enter a valid group name") : ""
          },
          on: {
            submit: _setup.createGroup
          },
          model: {
            value: _setup.newGroupName,
            callback: function ($$v) {
              _setup.newGroupName = $$v;
            },
            expression: "newGroupName"
          }
        })];
      },
      proxy: true
    } : null], null, true)
  }), _vm._v(" "), _c("p", {
    staticClass: "hidden-visually",
    attrs: {
      id: "group-list-desc"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_setup.t("settings", "List of groups. This list is not fully populated for performance reasons. The groups will be loaded as you navigate or search through the list.")) + "\n\t")]), _vm._v(" "), _c(_setup.NcAppNavigationList, {
    staticClass: "account-management__group-list",
    attrs: {
      "aria-describedby": "group-list-desc",
      "data-cy-users-settings-navigation-groups": "custom"
    }
  }, [_vm._l(_setup.filteredGroups, function (group) {
    return _c(_setup.GroupListItem, {
      key: group.id,
      ref: "groupListItems",
      refInFor: true,
      attrs: {
        id: group.id,
        active: _setup.selectedGroupDecoded === group.id,
        name: group.title,
        count: group.count
      }
    });
  }), _vm._v(" "), _setup.loadingGroups ? _c("div", {
    attrs: {
      role: "note"
    }
  }, [_c(_setup.NcLoadingIcon, {
    attrs: {
      name: _setup.t("settings", "Loading groups…")
    }
  })], 1) : _vm._e()], 2)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", 'You are about to delete the group "{group}". The accounts will NOT be deleted.', {
    group: _vm.name
  })) + "\n\t\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "modal__button-row"
  }, [_c("NcButton", {
    attrs: {
      variant: "secondary"
    },
    on: {
      click: function ($event) {
        _vm.showRemoveGroupModal = false;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Cancel")) + "\n\t\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      variant: "primary"
    },
    on: {
      click: _vm.removeGroup
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Confirm")) + "\n\t\t\t\t")])], 1)], 1)]) : _vm._e(), _vm._v(" "), _c("NcAppNavigationItem", {
    key: _vm.id,
    ref: "listItem",
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
        return [_vm.id !== "admin" && _vm.id !== "disabled" && (_vm.settings.isAdmin || _vm.settings.isDelegatedAdmin) ? _c("NcActionInput", {
          ref: "displayNameInput",
          attrs: {
            "trailing-button-label": _vm.t("settings", "Submit"),
            type: "text",
            "model-value": _vm.name,
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
        }) : _vm._e(), _vm._v(" "), _vm.id !== "admin" && _vm.id !== "disabled" && (_vm.settings.isAdmin || _vm.settings.isDelegatedAdmin) ? _c("NcActionButton", {
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
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Delete group")) + "\n\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }])
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("Fragment", [_vm.showConfig.showNewUserForm ? _c("NewUserDialog", {
    attrs: {
      loading: _vm.loading,
      "new-user": _vm.newUser,
      "quota-options": _vm.quotaOptions
    },
    on: {
      reset: _vm.resetForm,
      closing: _vm.closeDialog
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
            name: _vm.t("settings", "Loading accounts …"),
            size: 64
          }
        }) : _c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiAccountGroupOutline,
            size: 64
          }
        })];
      },
      proxy: true
    }], null, false, 1085698719)
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
        return [_c("UserListHeader")];
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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcDialog", _vm._g({
    staticClass: "dialog",
    attrs: {
      size: "small",
      name: _vm.t("settings", "New account"),
      "out-transition": ""
    },
    scopedSlots: _vm._u([{
      key: "actions",
      fn: function () {
        return [_c("NcButton", {
          staticClass: "dialog__submit",
          attrs: {
            "data-test": "submit",
            form: "new-user-form",
            variant: "primary",
            type: "submit"
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Add new account")) + "\n\t\t")])];
      },
      proxy: true
    }])
  }, _vm.$listeners), [_c("form", {
    staticClass: "dialog__form",
    attrs: {
      id: "new-user-form",
      "data-test": "form",
      disabled: _vm.loading.all
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.createUser.apply(null, arguments);
      }
    }
  }, [_c("NcTextField", {
    ref: "username",
    staticClass: "dialog__item",
    attrs: {
      "data-test": "username",
      disabled: _vm.settings.newUserGenerateUserID,
      label: _vm.usernameLabel,
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false",
      pattern: "[a-zA-Z0-9 _\\.@\\-']+",
      required: ""
    },
    model: {
      value: _vm.newUser.id,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "id", $$v);
      },
      expression: "newUser.id"
    }
  }), _vm._v(" "), _c("NcTextField", {
    staticClass: "dialog__item",
    attrs: {
      "data-test": "displayName",
      label: _vm.t("settings", "Display name"),
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false"
    },
    model: {
      value: _vm.newUser.displayName,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "displayName", $$v);
      },
      expression: "newUser.displayName"
    }
  }), _vm._v(" "), !_vm.settings.newUserRequireEmail ? _c("span", {
    staticClass: "dialog__hint",
    attrs: {
      id: "password-email-hint"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Either password or email is required")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("NcPasswordField", {
    ref: "password",
    staticClass: "dialog__item",
    attrs: {
      "data-test": "password",
      minlength: _vm.minPasswordLength,
      maxlength: 469,
      "aria-describedby": "password-email-hint",
      label: _vm.newUser.mailAddress === "" ? _vm.t("settings", "Password (required)") : _vm.t("settings", "Password"),
      autocapitalize: "none",
      autocomplete: "new-password",
      spellcheck: "false",
      required: _vm.newUser.mailAddress === ""
    },
    model: {
      value: _vm.newUser.password,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "password", $$v);
      },
      expression: "newUser.password"
    }
  }), _vm._v(" "), _c("NcTextField", {
    staticClass: "dialog__item",
    attrs: {
      "data-test": "email",
      type: "email",
      "aria-describedby": "password-email-hint",
      label: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail ? _vm.t("settings", "Email (required)") : _vm.t("settings", "Email"),
      autocapitalize: "none",
      autocomplete: "off",
      spellcheck: "false",
      required: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail
    },
    model: {
      value: _vm.newUser.mailAddress,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "mailAddress", $$v);
      },
      expression: "newUser.mailAddress"
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "dialog__item"
  }, [_c("NcSelect", {
    staticClass: "dialog__select",
    attrs: {
      "data-test": "groups",
      "input-label": !_vm.settings.isAdmin && !_vm.settings.isDelegatedAdmin ? _vm.t("settings", "Member of the following groups (required)") : _vm.t("settings", "Member of the following groups"),
      placeholder: _vm.t("settings", "Set account groups"),
      disabled: _vm.loading.groups || _vm.loading.all,
      options: _vm.availableGroups,
      "model-value": _vm.newUser.groups,
      label: "name",
      "keep-open": "",
      multiple: true,
      taggable: _vm.settings.isAdmin || _vm.settings.isDelegatedAdmin,
      required: !_vm.settings.isAdmin && !_vm.settings.isDelegatedAdmin,
      "create-option": value => ({
        id: value,
        name: value,
        isCreating: true
      })
    },
    on: {
      search: _vm.searchGroups,
      "option:created": _vm.createGroup,
      "option:deselected": _vm.removeGroup,
      "option:selected": options => _vm.addGroup(options.at(-1))
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "dialog__item"
  }, [_c("NcSelect", {
    staticClass: "dialog__select",
    attrs: {
      "input-label": _vm.t("settings", "Admin of the following groups"),
      placeholder: _vm.t("settings", "Set account as admin for …"),
      disabled: _vm.loading.groups || _vm.loading.all,
      options: _vm.availableSubAdminGroups,
      "keep-open": "",
      multiple: true,
      label: "name"
    },
    on: {
      search: _vm.searchGroups
    },
    model: {
      value: _vm.newUser.subAdminsGroups,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "subAdminsGroups", $$v);
      },
      expression: "newUser.subAdminsGroups"
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "dialog__item"
  }, [_c("NcSelect", {
    staticClass: "dialog__select",
    attrs: {
      "input-label": _vm.t("settings", "Quota"),
      placeholder: _vm.t("settings", "Set account quota"),
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
    staticClass: "dialog__item"
  }, [_c("NcSelect", {
    staticClass: "dialog__select",
    attrs: {
      "input-label": _vm.t("settings", "Language"),
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
    staticClass: "dialog__item dialog__managers",
    class: [{
      "icon-loading-small": _vm.loading.manager
    }]
  }, [_c("NcSelect", {
    staticClass: "dialog__select",
    attrs: {
      "input-label": _vm.managerInputLabel,
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
  })], 1)], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      title: _vm.t("settings", "Loading accounts …"),
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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    attrs: {
      "data-cy-user-list-header-email": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Email")))])]), _vm._v(" "), _c("th", {
    staticClass: "header__cell header__cell--groups",
    attrs: {
      "data-cy-user-list-header-groups": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "Groups")))])]), _vm._v(" "), _vm.settings.isAdmin || _vm.settings.isDelegatedAdmin ? _c("th", {
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Storage location")) + "\n\t\t")]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showFirstLogin ? _c("th", {
    staticClass: "header__cell",
    attrs: {
      "data-cy-user-list-header-first-login": "",
      scope: "col"
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.t("settings", "First login")))])]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("th", {
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Account actions")) + "\n\t\t")])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
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
      name: _vm.t("settings", "Loading account …"),
      size: 32
    }
  }) : _vm.visible ? _c("NcAvatar", {
    attrs: {
      "disable-menu": "",
      "hide-status": "",
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
      "trailing-button-icon": "arrowEnd",
      autocapitalize: "off",
      autocomplete: "off",
      spellcheck: "false"
    },
    on: {
      "trailing-button-click": _vm.updateDisplayName
    },
    model: {
      value: _vm.editedDisplayName,
      callback: function ($$v) {
        _vm.editedDisplayName = $$v;
      },
      expression: "editedDisplayName"
    }
  })] : !_vm.isObfuscated ? _c("strong", {
    attrs: {
      title: _vm.user.displayname?.length > 20 ? _vm.user.displayname : null
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
      "trailing-button-icon": "arrowEnd",
      autocapitalize: "off",
      autocomplete: "email",
      spellcheck: "false",
      type: "email"
    },
    on: {
      "trailing-button-click": _vm.updateEmail
    },
    model: {
      value: _vm.editedMail,
      callback: function ($$v) {
        _vm.editedMail = $$v;
      },
      expression: "editedMail"
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: _vm.user.email?.length > 20 ? _vm.user.email : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.email) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _c("td", {
    staticClass: "row__cell row__cell--groups row__cell--multiline",
    attrs: {
      "data-cy-user-list-cell-groups": ""
    }
  }, [_vm.editing ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "groups" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Add account to group")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "data-cy-user-list-input-groups": "",
      "data-loading": _vm.loading.groups || undefined,
      "input-id": "groups" + _vm.uniqueId,
      "keep-open": "",
      disabled: _vm.isLoadingField || _vm.loading.groupsDetails,
      loading: _vm.loading.groups,
      multiple: true,
      "append-to-body": false,
      options: _vm.availableGroups,
      placeholder: _vm.t("settings", "Add account to group"),
      taggable: _vm.settings.isAdmin || _vm.settings.isDelegatedAdmin,
      "model-value": _vm.userGroups,
      label: "name",
      "no-wrap": true,
      "create-option": value => ({
        id: value,
        name: value,
        isCreating: true
      })
    },
    on: {
      search: _vm.searchGroups,
      "option:created": _vm.createGroup,
      "option:selected": options => _vm.addUserGroup(options.at(-1)),
      "option:deselected": _vm.removeUserGroup
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: _vm.userGroupsLabels?.length > 40 ? _vm.userGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userGroupsLabels) + "\n\t\t")]) : _vm._e()], 2), _vm._v(" "), _vm.settings.isAdmin || _vm.settings.isDelegatedAdmin ? _c("td", {
    staticClass: "row__cell row__cell--large row__cell--multiline",
    attrs: {
      "data-cy-user-list-cell-subadmins": ""
    }
  }, [_vm.editing && (_vm.settings.isAdmin || _vm.settings.isDelegatedAdmin) ? [_c("label", {
    staticClass: "hidden-visually",
    attrs: {
      for: "subadmins" + _vm.uniqueId
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set account as admin for")) + "\n\t\t\t")]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "data-cy-user-list-input-subadmins": "",
      "data-loading": _vm.loading.subadmins || undefined,
      "input-id": "subadmins" + _vm.uniqueId,
      "keep-open": "",
      disabled: _vm.isLoadingField || _vm.loading.subAdminGroupsDetails,
      loading: _vm.loading.subadmins,
      label: "name",
      "append-to-body": false,
      multiple: true,
      "no-wrap": true,
      options: _vm.availableSubAdminGroups,
      placeholder: _vm.t("settings", "Set account as admin for"),
      "model-value": _vm.userSubAdminGroups
    },
    on: {
      search: _vm.searchGroups,
      "option:deselected": _vm.removeUserSubAdmin,
      "option:selected": options => _vm.addUserSubAdmin(options.at(-1))
    }
  })] : !_vm.isObfuscated ? _c("span", {
    attrs: {
      title: _vm.userSubAdminGroupsLabels?.length > 40 ? _vm.userSubAdminGroupsLabels : null
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userSubAdminGroupsLabels) + "\n\t\t")]) : _vm._e()], 2) : _vm._e(), _vm._v(" "), _c("td", {
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
      "model-value": _vm.userLanguage,
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
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.storageLocation) + "\n\t\t\t")]) : _vm._e()] : _vm._e()], 2) : _vm._e(), _vm._v(" "), _vm.showConfig.showFirstLogin ? _c("td", {
    staticClass: "row__cell",
    attrs: {
      "data-cy-user-list-cell-first-login": ""
    }
  }, [!_vm.isObfuscated ? _c("span", [_vm._v(_vm._s(_vm.userFirstLogin))]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("td", {
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
      disabled: _vm.isLoadingField,
      loading: _vm.loadingPossibleManagers || _vm.loading.manager,
      options: _vm.possibleManagers,
      placeholder: _vm.managerLabel,
      label: "displayname",
      filterable: false,
      "internal-search": false,
      clearable: true
    },
    on: {
      open: _vm.searchInitialUserManager,
      search: _vm.searchUserManager,
      "update:model-value": _vm.updateUserManager
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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36"
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36 ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      "data-cy-user-list-action-toggle-edit": `${_vm.edit}`,
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
  }, [_vm._v("\n\t\t" + _vm._s(_vm.edit ? _vm.t("settings", "Done") : _vm.t("settings", "Edit")) + "\n\t\t")]), _vm._v(" "), _vm._l(_vm.enabledActions, function ({
    action,
    icon,
    text
  }, index) {
    return _c("NcActionButton", {
      key: index,
      attrs: {
        disabled: _vm.disabled,
        "aria-label": text,
        icon: icon,
        "close-after-click": ""
      },
      on: {
        click: event => action(event, {
          ..._vm.user
        })
      },
      scopedSlots: _vm._u([_vm.isSvg(icon) ? {
        key: "icon",
        fn: function () {
          return [_c("NcIconSvgWrapper", {
            attrs: {
              svg: icon,
              "aria-hidden": "true"
            }
          })];
        },
        proxy: true
      } : null], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(text) + "\n\t\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      "data-test": "showLanguages"
    },
    model: {
      value: _vm.showLanguages,
      callback: function ($$v) {
        _vm.showLanguages = $$v;
      },
      expression: "showLanguages"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show language")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showUserBackend"
    },
    model: {
      value: _vm.showUserBackend,
      callback: function ($$v) {
        _vm.showUserBackend = $$v;
      },
      expression: "showUserBackend"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show account backend")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showStoragePath"
    },
    model: {
      value: _vm.showStoragePath,
      callback: function ($$v) {
        _vm.showStoragePath = $$v;
      },
      expression: "showStoragePath"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show storage path")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showFirstLogin"
    },
    model: {
      value: _vm.showFirstLogin,
      callback: function ($$v) {
        _vm.showFirstLogin = $$v;
      },
      expression: "showFirstLogin"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show first login")) + "\n\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      "data-test": "showLastLogin"
    },
    model: {
      value: _vm.showLastLogin,
      callback: function ($$v) {
        _vm.showLastLogin = $$v;
      },
      expression: "showLastLogin"
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
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "The system config enforces sorting the groups by name. This also disables showing the member count.")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("fieldset", [_c("legend", [_vm._v(_vm._s(_vm.t("settings", "Group list sorting")))]), _vm._v(" "), _c("NcNoteCard", {
    staticClass: "dialog__note",
    attrs: {
      type: "info",
      text: _vm.t("settings", "Sorting only applies to the currently loaded groups for performance reasons. Groups will be loaded as you navigate or search through the list.")
    }
  }), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "radio",
      "data-test": "sortGroupsByMemberCount",
      disabled: _vm.isGroupSortingEnforced,
      name: "group-sorting-mode",
      value: "member-count"
    },
    model: {
      value: _vm.groupSorting,
      callback: function ($$v) {
        _vm.groupSorting = $$v;
      },
      expression: "groupSorting"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "By member count")) + "\n\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "radio",
      "data-test": "sortGroupsByName",
      disabled: _vm.isGroupSortingEnforced,
      name: "group-sorting-mode",
      value: "name"
    },
    model: {
      value: _vm.groupSorting,
      callback: function ($$v) {
        _vm.groupSorting = $$v;
      },
      expression: "groupSorting"
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
      disabled: _vm.loadingSendMail
    },
    model: {
      value: _vm.sendWelcomeMail,
      callback: function ($$v) {
        _vm.sendWelcomeMail = $$v;
      },
      expression: "sendWelcomeMail"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Send welcome email to new accounts")) + "\n\t\t")])], 1), _vm._v(" "), _c("NcAppSettingsSection", {
    attrs: {
      id: "default-settings",
      name: _vm.t("settings", "Defaults")
    }
  }, [_c("NcSelect", {
    attrs: {
      clearable: false,
      "create-option": _vm.validateQuota,
      "filter-by": _vm.filterQuotas,
      "input-label": _vm.t("settings", "Default quota"),
      options: _vm.quotaOptions,
      placement: "top",
      placeholder: _vm.t("settings", "Select default quota"),
      taggable: ""
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
  })], 1), _vm._v(" "), _c("NcAppSettingsShortcutsSection", [_c("NcHotkeyList", {
    attrs: {
      label: _vm.t("settings", "Search")
    }
  }, [_c("NcHotkey", {
    attrs: {
      label: _vm.t("settings", "Focus search"),
      hotkey: "Control F"
    }
  })], 1), _vm._v(" "), _c("NcHotkeyList", {
    attrs: {
      label: _vm.t("settings", "Help")
    }
  }, [_c("NcHotkey", {
    attrs: {
      label: _vm.t("settings", "Show those shortcuts"),
      hotkey: "?"
    }
  })], 1)], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    staticClass: "account-management__navigation",
    attrs: {
      "aria-label": _setup.t("settings", "Account management")
    },
    scopedSlots: _vm._u([{
      key: "footer",
      fn: function () {
        return [_c(_setup.NcButton, {
          staticClass: "account-management__settings-toggle",
          attrs: {
            variant: "tertiary"
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
                  path: _setup.mdiCogOutline
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
  }), _vm._v(" "), _c("div", {
    staticClass: "account-management__search",
    attrs: {
      role: "search",
      "aria-label": _setup.t("settings", "Search accounts and groups")
    }
  }, [_c(_setup.NcInputField, {
    ref: "searchField",
    attrs: {
      label: _setup.t("settings", "Search accounts and groups…"),
      "show-trailing-button": _setup.searchInput !== "",
      trailingButtonLabel: _setup.t("settings", "Clear search")
    },
    on: {
      "trailing-button-click": _setup.clearSearch
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiMagnify
          }
        })];
      },
      proxy: true
    }, {
      key: "trailing-button-icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiClose
          }
        })];
      },
      proxy: true
    }]),
    model: {
      value: _setup.searchInput,
      callback: function ($$v) {
        _setup.searchInput = $$v;
      },
      expression: "searchInput"
    }
  })], 1), _vm._v(" "), _c(_setup.NcAppNavigationList, {
    staticClass: "account-management__system-list",
    attrs: {
      "data-cy-users-settings-navigation-groups": "system"
    }
  }, [_c(_setup.NcAppNavigationItem, {
    attrs: {
      id: "everyone",
      exact: true,
      name: _setup.t("settings", "All accounts"),
      to: {
        name: "users"
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiAccountOutline
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
  }), _vm._v(" "), _setup.settings.isAdmin ? _c(_setup.NcAppNavigationItem, {
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
            path: _setup.mdiShieldAccountOutline
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
    }], null, false, 3779933833)
  }) : _vm._e(), _vm._v(" "), _setup.isAdminOrDelegatedAdmin ? _c(_setup.NcAppNavigationItem, {
    attrs: {
      id: "recent",
      exact: true,
      name: _setup.t("settings", "Recently active"),
      to: {
        name: "group",
        params: {
          selectedGroup: "__nc_internal_recent"
        }
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiHistory
          }
        })];
      },
      proxy: true
    }, {
      key: "counter",
      fn: function () {
        return [_setup.recentGroup?.usercount ? _c(_setup.NcCounterBubble, {
          attrs: {
            type: _setup.selectedGroupDecoded === "__nc_internal_recent" ? "highlighted" : undefined
          }
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_setup.recentGroup.usercount) + "\n\t\t\t\t")]) : _vm._e()];
      },
      proxy: true
    }], null, false, 2299424282)
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
            path: _setup.mdiAccountOffOutline
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
  }) : _vm._e()], 1), _vm._v(" "), _c(_setup.AppNavigationGroupList)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
___CSS_LOADER_EXPORT___.push([module.id, `.dialog__form[data-v-e111cbca] {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 8px;
  gap: 4px 0;
}
.dialog__item[data-v-e111cbca] {
  width: 100%;
}
.dialog__item[data-v-e111cbca]:not(:focus):not(:active) {
  border-color: var(--color-border-dark);
}
.dialog__hint[data-v-e111cbca] {
  color: var(--color-text-maxcontrast);
  margin-top: 8px;
  align-self: flex-start;
}
.dialog__label[data-v-e111cbca] {
  display: block;
  padding: 4px 0;
}
.dialog__select[data-v-e111cbca] {
  width: 100%;
}
.dialog__managers[data-v-e111cbca] {
  margin-bottom: 12px;
}
.dialog__submit[data-v-e111cbca] {
  margin-top: 4px;
  margin-bottom: 8px;
}
.dialog[data-v-e111cbca] .dialog__actions {
  margin: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
    inset-inline-start: 0;
}
.footer__cell--displayname[data-v-97a6cb68] {
    inset-inline-start: var(--avatar-cell-width);
    border-inline-end: 1px solid var(--color-border);
}
}
.footer__cell--username[data-v-97a6cb68] {
  padding-inline-start: calc(var(--default-grid-baseline) * 3);
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
.footer__cell--groups[data-v-97a6cb68] {
  min-width: var(--cell-width-groups);
  width: var(--cell-width-groups);
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
  inset-inline-end: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-inline-start: 1px solid var(--color-border);
}
.footer__subtitle[data-v-97a6cb68] {
  color: var(--color-text-maxcontrast);
}
.footer__cell[data-v-97a6cb68] {
  position: sticky;
  color: var(--color-text-maxcontrast);
}
.footer__cell--loading[data-v-97a6cb68] {
  inset-inline-start: 0;
  min-width: var(--avatar-cell-width);
  width: var(--avatar-cell-width);
  align-items: center;
  padding: 0;
}
.footer__cell--count[data-v-97a6cb68] {
  inset-inline-start: var(--avatar-cell-width);
  min-width: var(--cell-width);
  width: var(--cell-width);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.header[data-v-55420384] {
  border-bottom: 1px solid var(--color-border);
  position: relative;
  display: flex;
  min-width: 100%;
  width: fit-content;
  height: var(--row-height);
  background-color: var(--color-main-background);
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
    inset-inline-start: 0;
}
.header__cell--displayname[data-v-55420384] {
    inset-inline-start: var(--avatar-cell-width);
    border-inline-end: 1px solid var(--color-border);
}
}
.header__cell--username[data-v-55420384] {
  padding-inline-start: calc(var(--default-grid-baseline) * 3);
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
.header__cell--groups[data-v-55420384] {
  min-width: var(--cell-width-groups);
  width: var(--cell-width-groups);
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
  inset-inline-end: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-inline-start: 1px solid var(--color-border);
}
.header__subtitle[data-v-55420384] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
    inset-inline-start: 0;
}
.row__cell--displayname[data-v-11563777] {
    inset-inline-start: var(--avatar-cell-width);
    border-inline-end: 1px solid var(--color-border);
}
}
.row__cell--username[data-v-11563777] {
  padding-inline-start: calc(var(--default-grid-baseline) * 3);
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
.row__cell--groups[data-v-11563777] {
  min-width: var(--cell-width-groups);
  width: var(--cell-width-groups);
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
  inset-inline-end: 0;
  z-index: var(--sticky-column-z-index);
  display: flex;
  flex-direction: row;
  align-items: center;
  min-width: 110px;
  width: 110px;
  background-color: var(--color-main-background);
  border-inline-start: 1px solid var(--color-border);
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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
___CSS_LOADER_EXPORT___.push([module.id, `.dialog__note[data-v-3eb7c73e] {
  font-weight: normal;
}
fieldset[data-v-3eb7c73e] {
  font-weight: bold;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  --cell-width-groups: 380px;
  --cell-min-width: calc(var(--cell-width) - (2 * var(--cell-padding)));
  --sticky-column-z-index: calc(var(--vs-dropdown-z-index) + 1);
  display: block;
  overflow: auto;
  height: 100%;
  will-change: scroll-position;
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
  inset-inline-start: 0;
}
.user-list__body[data-v-51adeab1] {
  display: flex;
  flex-direction: column;
  width: 100%;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
___CSS_LOADER_EXPORT___.push([module.id, `.account-management__navigation[data-v-b127f0aa] .app-navigation__body {
  will-change: scroll-position;
}
.account-management__search[data-v-b127f0aa] {
  padding-block: var(--default-grid-baseline, 4px);
  padding-inline: var(--app-navigation-padding, 8px);
}
.account-management__system-list[data-v-b127f0aa] {
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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./apps/settings/src/components/AppNavigationGroupList.vue"
/*!*****************************************************************!*\
  !*** ./apps/settings/src/components/AppNavigationGroupList.vue ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AppNavigationGroupList.vue?vue&type=template&id=c4c336ae */ "./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae");
/* harmony import */ var _AppNavigationGroupList_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts */ "./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AppNavigationGroupList_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__.render,
  _AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/AppNavigationGroupList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/GroupListItem.vue"
/*!********************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/GroupListItem.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/UserList.vue"
/*!***************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/UserList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/NewUserDialog.vue"
/*!**************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserDialog.vue ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true */ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true");
/* harmony import */ var _NewUserDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewUserDialog.vue?vue&type=script&lang=js */ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js");
/* harmony import */ var _NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true */ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewUserDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "e111cbca",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/NewUserDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/UserListFooter.vue"
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/UserListFooter.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/UserListHeader.vue"
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/UserListHeader.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/UserRow.vue"
/*!********************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/UserRow.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/UserRowActions.vue"
/*!***************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/UserRowActions.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue"
/*!*******************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/UserSettingsDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/components/Users/VirtualList.vue"
/*!************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/components/Users/VirtualList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/views/UserManagement.vue"
/*!****************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/views/UserManagement.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/settings/src/views/UserManagementNavigation.vue"
/*!**************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/settings/src/views/UserManagementNavigation.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-material-design-icons/TrashCanOutline.vue"
/*!********************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrashCanOutline.vue ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TrashCanOutline.vue?vue&type=template&id=f4b8d5a4 */ "./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4");
/* harmony import */ var _TrashCanOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TrashCanOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _TrashCanOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__.render,
  _TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/TrashCanOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TrashCanOutlineIcon",
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


/***/ },

/***/ "./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts"
/*!****************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppNavigationGroupList_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_AppNavigationGroupList_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts"
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts"
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts"
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts"
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts"
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js"
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js"
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=script&lang=js ***!
  \***************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js"
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserDialog.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js"
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js"
/*!*******************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js"
/*!****************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae"
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AppNavigationGroupList_vue_vue_type_template_id_c4c336ae__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AppNavigationGroupList.vue?vue&type=template&id=c4c336ae */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppNavigationGroupList.vue?vue&type=template&id=c4c336ae");


/***/ },

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true"
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=template&id=b3f9b202&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true"
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=template&id=6cba3aca&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true"
/*!********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true ***!
  \********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_template_id_e111cbca_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=template&id=e111cbca&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true"
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true ***!
  \*********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_template_id_97a6cb68_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=template&id=97a6cb68&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true"
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true ***!
  \*********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_template_id_55420384_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=template&id=55420384&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=template&id=55420384&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true"
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_11563777_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=template&id=11563777&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=template&id=11563777&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36"
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36 ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowActions_vue_vue_type_template_id_34f3ef36__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowActions.vue?vue&type=template&id=34f3ef36 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRowActions.vue?vue&type=template&id=34f3ef36");


/***/ },

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true"
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true ***!
  \*************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_template_id_3eb7c73e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=template&id=3eb7c73e&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true"
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_template_id_51adeab1_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=template&id=51adeab1&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=template&id=51adeab1&scoped=true");


/***/ },

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true"
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_template_id_4821d392_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=template&id=4821d392&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=template&id=4821d392&scoped=true");


/***/ },

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true"
/*!********************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true ***!
  \********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_template_id_b127f0aa_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=template&id=b127f0aa&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true"
/*!*****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true ***!
  \*****************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_style_index_0_id_b3f9b202_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/GroupListItem.vue?vue&type=style&index=0&id=b3f9b202&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true"
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true"
/*!***********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewUserDialog_vue_vue_type_style_index_0_id_e111cbca_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/NewUserDialog.vue?vue&type=style&index=0&id=e111cbca&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true"
/*!************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true ***!
  \************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListFooter_vue_vue_type_style_index_0_id_97a6cb68_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListFooter.vue?vue&type=style&index=0&id=97a6cb68&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true"
/*!************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true ***!
  \************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserListHeader_vue_vue_type_style_index_0_id_55420384_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserListHeader.vue?vue&type=style&index=0&id=55420384&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true"
/*!*****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true ***!
  \*****************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_11563777_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserRow.vue?vue&type=style&index=0&id=11563777&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss"
/*!****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss ***!
  \****************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserSettingsDialog_vue_vue_type_style_index_0_id_3eb7c73e_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/UserSettingsDialog.vue?vue&type=style&index=0&id=3eb7c73e&scoped=true&lang=scss");


/***/ },

/***/ "./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true"
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VirtualList_vue_vue_type_style_index_0_id_51adeab1_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/Users/VirtualList.vue?vue&type=style&index=0&id=51adeab1&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true"
/*!*************************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true ***!
  \*************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagement_vue_vue_type_style_index_0_id_4821d392_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagement.vue?vue&type=style&index=0&id=4821d392&lang=scss&scoped=true");


/***/ },

/***/ "./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss"
/*!***********************************************************************************************************************!*\
  !*** ./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss ***!
  \***********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserManagementNavigation_vue_vue_type_style_index_0_id_b127f0aa_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/UserManagementNavigation.vue?vue&type=style&index=0&id=b127f0aa&scoped=true&lang=scss");


/***/ },

/***/ "./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js"
/*!********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_TrashCanOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./TrashCanOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_TrashCanOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4"
/*!**************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4 ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrashCanOutline_vue_vue_type_template_id_f4b8d5a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./TrashCanOutline.vue?vue&type=template&id=f4b8d5a4 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4");


/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4"
/*!******************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrashCanOutline.vue?vue&type=template&id=f4b8d5a4 ***!
  \******************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
        staticClass: "material-design-icon trash-can-outline-icon",
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
                d: "M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z",
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



/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "./node_modules/@mdi/svg/svg/pencil-outline.svg?raw"
/*!**********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/pencil-outline.svg?raw ***!
  \**********************************************************/
(module) {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-pencil-outline\" viewBox=\"0 0 24 24\"><path d=\"M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z\" /></svg>";

/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs"
/*!********************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.mjs ***!
  \********************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppNavigationCaption_CQaKs_Zf_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppNavigationCaption_CQaKs_Zf_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppNavigationCaption-CQaKs-Zf.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppNavigationCaption-CQaKs-Zf.mjs");


//# sourceMappingURL=NcAppNavigationCaption.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.mjs"
/*!****************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.mjs ***!
  \****************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppNavigationNew_t3Rkrwjh_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppNavigationNew_t3Rkrwjh_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppNavigationNew-t3Rkrwjh.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppNavigationNew-t3Rkrwjh.mjs");


//# sourceMappingURL=NcAppNavigationNew.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs"
/*!*****************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsDialog.mjs ***!
  \*****************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSettingsDialog_zwmLtX7z_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSettingsDialog_zwmLtX7z_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSettingsDialog-zwmLtX7z.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSettingsDialog-zwmLtX7z.mjs");


//# sourceMappingURL=NcAppSettingsDialog.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsSection.mjs"
/*!******************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsSection.mjs ***!
  \******************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSettingsSection_BjQllLEA_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSettingsSection_BjQllLEA_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSettingsSection-BjQllLEA.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSettingsSection-BjQllLEA.mjs");


//# sourceMappingURL=NcAppSettingsSection.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsShortcutsSection.mjs"
/*!***************************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcAppSettingsShortcutsSection.mjs ***!
  \***************************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcAppSettingsShortcutsSection_CiLZpXkV_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcAppSettingsShortcutsSection_CiLZpXkV_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcAppSettingsShortcutsSection-CiLZpXkV.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcAppSettingsShortcutsSection-CiLZpXkV.mjs");


//# sourceMappingURL=NcAppSettingsShortcutsSection.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs"
/*!*************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.mjs ***!
  \*************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcCounterBubble_oxV8oMlX_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcCounterBubble_oxV8oMlX_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcCounterBubble-oxV8oMlX.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcCounterBubble-oxV8oMlX.mjs");


//# sourceMappingURL=NcCounterBubble.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcHotkey.mjs"
/*!******************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcHotkey.mjs ***!
  \******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcHotkey_Cv2Je6eF_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcHotkey_Cv2Je6eF_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcHotkey-Cv2Je6eF.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcHotkey-Cv2Je6eF.mjs");


//# sourceMappingURL=NcHotkey.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcHotkeyList.mjs"
/*!**********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcHotkeyList.mjs ***!
  \**********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcHotkeyList_CrAcIN70_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcHotkeyList_CrAcIN70_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcHotkeyList-CrAcIN70.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcHotkeyList-CrAcIN70.mjs");


//# sourceMappingURL=NcHotkeyList.mjs.map


/***/ }

}]);
//# sourceMappingURL=settings-users-settings-users.js.map?v=c23f193552914eef4c20