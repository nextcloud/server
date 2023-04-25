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
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
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
    showConfig: {
      type: Object,
      default: () => ({})
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
  },
  methods: {
    /**
     * Generate avatar url
     *
     * @param {string} user The user name
     * @param {bool} isDarkTheme Whether the avatar should be the dark version
     * @return {string}
     */
    generateAvatar(user, isDarkTheme) {
      if (isDarkTheme) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/avatar/{user}/64/dark?v={version}', {
          user,
          version: oc_userconfig.avatar.version
        });
      } else {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/avatar/{user}/64?v={version}', {
          user,
          version: oc_userconfig.avatar.version
        });
      }
    }
  }
});

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
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCounterBubble.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCounterBubble.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3__);




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'GroupListItem',
  components: {
    NcActionInput: (_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcCounterBubble: (_nextcloud_vue_dist_Components_NcCounterBubble_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  props: {
    id: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    count: {
      type: Number,
      required: false
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
      const self = this;
      // TODO migrate to a vue js confirm dialog component
      OC.dialogs.confirm(t('settings', 'You are about to remove the group {group}. The users will NOT be deleted.', {
        group: groupid
      }), t('settings', 'Please confirm the group removal '), function (success) {
        if (success) {
          self.$store.dispatch('removeGroup', groupid);
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
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-infinite-loading */ "./node_modules/vue-infinite-loading/dist/vue-infinite-loading.js");
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcModal.js */ "./node_modules/@nextcloud/vue/dist/Components/NcModal.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcMultiselect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcMultiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _UserList_UserRow_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./UserList/UserRow.vue */ "./apps/settings/src/components/UserList/UserRow.vue");







const unlimitedQuota = {
  id: 'none',
  label: t('settings', 'Unlimited')
};
const defaultQuota = {
  id: 'default',
  label: t('settings', 'Default quota')
};
const newUser = {
  id: '',
  displayName: '',
  password: '',
  mailAddress: '',
  groups: [],
  subAdminsGroups: [],
  quota: defaultQuota,
  language: {
    code: 'en',
    name: t('settings', 'Default language')
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserList',
  components: {
    NcModal: (_nextcloud_vue_dist_Components_NcModal_js__WEBPACK_IMPORTED_MODULE_2___default()),
    userRow: _UserList_UserRow_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcMultiselect: (_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_4___default()),
    InfiniteLoading: (vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1___default()),
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3___default())
  },
  props: {
    users: {
      type: Array,
      default: () => []
    },
    showConfig: {
      type: Object,
      required: true
    },
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
      unlimitedQuota,
      defaultQuota,
      loading: {
        all: false,
        groups: false
      },
      scrolled: false,
      searchQuery: '',
      newUser: Object.assign({}, newUser)
    };
  },
  computed: {
    settings() {
      return this.$store.getters.getServerData;
    },
    selectedGroupDecoded() {
      return decodeURIComponent(this.selectedGroup);
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
        quotaPreset.unshift(this.unlimitedQuota);
      }
      quotaPreset.unshift(this.defaultQuota);
      return quotaPreset;
    },
    minPasswordLength() {
      return this.$store.getters.getPasswordPolicyMinLength;
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
    },
    isDarkTheme() {
      return window.getComputedStyle(this.$el).getPropertyValue('--background-invert-if-dark') === 'invert(100%)';
    }
  },
  watch: {
    // watch url change and group select
    selectedGroup(val, old) {
      // if selected is the disabled group but it's empty
      this.redirectIfDisabled();
      this.$store.commit('resetUsers');
      this.$refs.infiniteLoading.stateChanger.reset();
      this.setNewUserDefaultGroup(val);
    },
    // make sure the infiniteLoading state is changed if we manually
    // add/remove data from the store
    usersCount(val, old) {
      // deleting the last user, reset the list
      if (val === 0 && old === 1) {
        this.$refs.infiniteLoading.stateChanger.reset();
        // adding the first user, warn the infiniteLoader that
        // the list is not empty anymore (we don't fetch the newly
        // added user as we already have all the info we need)
      } else if (val === 1 && old === 0) {
        this.$refs.infiniteLoading.stateChanger.loaded();
      }
    }
  },
  mounted() {
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
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);

    /**
     * If disabled group but empty, redirect
     */
    this.redirectIfDisabled();
  },
  beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    onScroll(event) {
      this.scrolled = event.target.scrollTo > 0;
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
    infiniteHandler($state) {
      this.$store.dispatch('getUsers', {
        offset: this.usersOffset,
        limit: this.usersLimit,
        group: this.selectedGroup !== 'disabled' ? this.selectedGroup : '',
        search: this.searchQuery
      }).then(usersCount => {
        if (usersCount > 0) {
          $state.loaded();
        }
        if (usersCount < this.usersLimit) {
          $state.complete();
        }
      });
    },
    /* SEARCH */
    search(_ref) {
      let {
        query
      } = _ref;
      this.searchQuery = query;
      this.$store.commit('resetUsers');
      this.$refs.infiniteLoading.stateChanger.reset();
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
        vue__WEBPACK_IMPORTED_MODULE_6__["default"].set(this.newUser.language, 'code', this.settings.defaultLanguage);
      }

      /**
       * In case the user directly loaded the user list within a group
       * the watch won't be triggered. We need to initialize it.
       */
      this.setNewUserDefaultGroup(this.selectedGroup);
      this.loading.all = false;
    },
    createUser() {
      this.loading.all = true;
      this.$store.dispatch('addUser', {
        userid: this.newUser.id,
        password: this.newUser.password,
        displayName: this.newUser.displayName,
        email: this.newUser.mailAddress,
        groups: this.newUser.groups.map(group => group.id),
        subadmin: this.newUser.subAdminsGroups.map(group => group.id),
        quota: this.newUser.quota.id,
        language: this.newUser.language.code
      }).then(() => {
        this.resetForm();
        this.$refs.newusername.focus();
        this.closeModal();
      }).catch(error => {
        this.loading.all = false;
        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          const statuscode = error.response.data.ocs.meta.statuscode;
          if (statuscode === 102) {
            // wrong username
            this.$refs.newusername.focus();
          } else if (statuscode === 107) {
            // wrong password
            this.$refs.newuserpassword.focus();
          }
        }
      });
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
     * Create a new group
     *
     * @param {string} gid Group id
     * @return {Promise}
     */
    createGroup(gid) {
      this.loading.groups = true;
      this.$store.dispatch('addGroup', gid).then(group => {
        this.newUser.groups.push(this.groups.find(group => group.id === gid));
        this.loading.groups = false;
      }).catch(() => {
        this.loading.groups = false;
      });
      return this.$store.getters.getGroups[this.groups.length];
    },
    /**
     * If the selected group is the disabled group but the count is 0
     * redirect to the all users page.
     * we only check for 0 because we don't have the count on ldap
     * and we therefore set the usercount to -1 in this specific case
     */
    redirectIfDisabled() {
      const allGroups = this.$store.getters.getGroups;
      if (this.selectedGroup === 'disabled' && allGroups.findIndex(group => group.id === 'disabled' && group.usercount === 0) > -1) {
        // disabled group is empty, redirection to all users
        this.$router.push({
          name: 'users'
        });
        this.$refs.infiniteLoading.stateChanger.reset();
      }
    },
    closeModal() {
      // eslint-disable-next-line vue/no-mutating-props
      this.showConfig.showNewUserForm = false;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var _UserRowSimple_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRowSimple.vue */ "./apps/settings/src/components/UserList/UserRowSimple.vue");
/* harmony import */ var _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../mixins/UserRowMixin.js */ "./apps/settings/src/mixins/UserRowMixin.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserRow',
  components: {
    UserRowSimple: _UserRowSimple_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcPopoverMenu: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcPopoverMenu,
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActionButton,
    NcMultiselect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcMultiselect
  },
  directives: {
    ClickOutside: (vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default())
  },
  mixins: [_mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
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
    showConfig: {
      type: Object,
      default: () => ({})
    },
    languages: {
      type: Array,
      required: true
    },
    externalActions: {
      type: Array,
      default: () => []
    },
    isDarkTheme: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      rand: parseInt(Math.random() * 1000),
      openedMenu: false,
      feedbackMessage: '',
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
        wipe: false
      }
    };
  },
  computed: {
    /* USER POPOVERMENU ACTIONS */
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
    }
  },
  methods: {
    /* MENU HANDLING */
    toggleMenu() {
      this.openedMenu = !this.openedMenu;
    },
    hideMenu() {
      this.openedMenu = false;
    },
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
          this.$store.dispatch('wipeUserDevices', userid).then(() => {
            this.loading.wipe = false;
            this.loading.all = false;
          });
        }
      }, true);
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
      const displayName = this.$refs.displayName.value;
      this.loading.displayName = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'displayname',
        value: displayName
      }).then(() => {
        this.loading.displayName = false;
        this.$refs.displayName.value = displayName;
      });
    },
    /**
     * Set user password
     *
     * @param {string} password The email address
     */
    updatePassword() {
      const password = this.$refs.password.value;
      this.loading.password = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'password',
        value: password
      }).then(() => {
        this.loading.password = false;
        this.$refs.password.value = ''; // empty & show placeholder
      });
    },

    /**
     * Set user mailAddress
     *
     * @param {string} mailAddress The email address
     */
    updateEmail() {
      const mailAddress = this.$refs.mailAddress.value;
      this.loading.mailAddress = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'email',
        value: mailAddress
      }).then(() => {
        this.loading.mailAddress = false;
        this.$refs.mailAddress.value = mailAddress;
      });
    },
    /**
     * Create a new group and add user to it
     *
     * @param {string} gid Group id
     */
    async createGroup(gid) {
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
      if (group.canAdd === false) {
        return false;
      }
      this.loading.groups = true;
      const userid = this.user.id;
      const gid = group.id;
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
      this.loading.quota = true;
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
        this.loading.quota = false;
      }
      return quota;
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {Promise|boolean}
     */
    validateQuota(quota) {
      // only used for new presets sent through @Tag
      const validQuota = OC.Util.computerFileSize(quota);
      if (validQuota !== null && validQuota >= 0) {
        // unify format output
        return this.setUserQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
      }
      // if no valid do not change
      return false;
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
      } catch (error) {
        console.error(error);
      } finally {
        this.loading.languages = false;
      }
      return lang;
    },
    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail() {
      this.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(success => {
        if (success) {
          // Show feedback to indicate the success
          this.feedbackMessage = t('setting', 'Welcome mail sent!');
          setTimeout(() => {
            this.feedbackMessage = '';
          }, 2000);
        }
        this.loading.all = false;
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPopoverMenu.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPopoverMenu.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../mixins/UserRowMixin.js */ "./apps/settings/src/mixins/UserRowMixin.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'UserRowSimple',
  components: {
    NcPopoverMenu: (_nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcActions: (_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_1___default())
  },
  directives: {
    ClickOutside: (vue_click_outside__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_mixins_UserRowMixin_js__WEBPACK_IMPORTED_MODULE_5__["default"]],
  props: {
    user: {
      type: Object,
      required: true
    },
    loading: {
      type: Object,
      required: true
    },
    showConfig: {
      type: Object,
      required: true
    },
    userActions: {
      type: Array,
      required: true
    },
    openedMenu: {
      type: Boolean,
      required: true
    },
    feedbackMessage: {
      type: String,
      required: true
    },
    subAdminsGroups: {
      type: Array,
      required: true
    },
    settings: {
      type: Object,
      required: true
    },
    isDarkTheme: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    userGroupsLabels() {
      return this.userGroups.map(group => group.name).join(', ');
    },
    userSubAdminsGroupsLabels() {
      return this.userSubAdminsGroups.map(group => group.name).join(', ');
    },
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
    canEdit() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__.getCurrentUser)().uid !== this.user.id || this.settings.isAdmin;
    },
    userQuota() {
      let quota = this.user.quota.quota;
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
    }
  },
  methods: {
    toggleMenu() {
      this.$emit('update:openedMenu', !this.openedMenu);
    },
    hideMenu() {
      this.$emit('update:openedMenu', false);
    },
    toggleEdit() {
      this.$emit('update:editing', true);
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
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigation.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigation.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationCaption.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCaption.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCounter_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationCounter.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationCounter.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationCounter_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationCounter_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationItem.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationNew.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationNew.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSettings_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAppNavigationSettings.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAppNavigationSettings.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAppNavigationSettings_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAppNavigationSettings_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcContent.js */ "./node_modules/@nextcloud/vue/dist/Components/NcContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcMultiselect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcMultiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../components/GroupListItem.vue */ "./apps/settings/src/components/GroupListItem.vue");
/* harmony import */ var _components_UserList_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../components/UserList.vue */ "./apps/settings/src/components/UserList.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");















vue__WEBPACK_IMPORTED_MODULE_14__["default"].use((vue_localstorage__WEBPACK_IMPORTED_MODULE_11___default()));
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Users',
  components: {
    NcAppContent: (_nextcloud_vue_dist_Components_NcAppContent_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcAppNavigation: (_nextcloud_vue_dist_Components_NcAppNavigation_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcAppNavigationCaption: (_nextcloud_vue_dist_Components_NcAppNavigationCaption_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcAppNavigationCounter: (_nextcloud_vue_dist_Components_NcAppNavigationCounter_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcAppNavigationItem: (_nextcloud_vue_dist_Components_NcAppNavigationItem_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcAppNavigationNew: (_nextcloud_vue_dist_Components_NcAppNavigationNew_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcAppNavigationSettings: (_nextcloud_vue_dist_Components_NcAppNavigationSettings_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcContent: (_nextcloud_vue_dist_Components_NcContent_js__WEBPACK_IMPORTED_MODULE_8___default()),
    GroupListItem: _components_GroupListItem_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcMultiselect: (_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_10___default()),
    UserList: _components_UserList_vue__WEBPACK_IMPORTED_MODULE_13__["default"]
  },
  props: {
    selectedGroup: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      // default quota is set to unlimited
      unlimitedQuota: {
        id: 'none',
        label: t('settings', 'Unlimited')
      },
      // temporary value used for multiselect change
      selectedQuota: false,
      externalActions: [],
      loadingAddGroup: false,
      loadingSendMail: false,
      showConfig: {
        showStoragePath: false,
        showUserBackend: false,
        showLastLogin: false,
        showNewUserForm: false,
        showLanguages: false
      }
    };
  },
  computed: {
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
    // Local settings
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
    userCount() {
      return this.$store.getters.getUserCount;
    },
    settings() {
      return this.$store.getters.getServerData;
    },
    // default quota
    quotaOptions() {
      // convert the preset array into objects
      const quotaPreset = this.settings.quotaPreset.reduce((acc, cur) => acc.concat({
        id: cur,
        label: cur
      }), []);
      // add default presets
      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(this.unlimitedQuota);
      }
      return quotaPreset;
    },
    // mapping saved values to objects
    defaultQuota: {
      get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }
        if (this.settings.defaultQuota !== this.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }
        return this.unlimitedQuota; // unlimited
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
          await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_9__.generateUrl)('/settings/users/preferences/newUser.sendEmail'), {
            value: value ? 'yes' : 'no'
          });
        } catch (e) {
          console.error('could not update newUser.sendEmail preference: ' + e.message, e);
        } finally {
          this.loadingSendMail = false;
        }
      }
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
      this.showConfig.showNewUserForm = true;
      if (this.showConfig.showNewUserForm) {
        vue__WEBPACK_IMPORTED_MODULE_14__["default"].nextTick(() => {
          window.newusername.focus();
        });
      }
    },
    getLocalstorage(key) {
      // force initialization
      const localConfig = this.$localStorage.get(key);
      // if localstorage is null, fallback to original values
      this.showConfig[key] = localConfig !== null ? localConfig === 'true' : this.showConfig[key];
      return this.showConfig[key];
    },
    setLocalStorage(key, status) {
      this.showConfig[key] = status;
      this.$localStorage.set(key, status);
      return status;
    },
    /**
     * Dispatch default quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     */
    setDefaultQuota() {
      let quota = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'none';
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
    },
    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {Promise|boolean}
     */
    validateQuota(quota) {
      // only used for new presets sent through @Tag
      const validQuota = OC.Util.computerFileSize(quota);
      if (validQuota === null) {
        return this.setDefaultQuota('none');
      } else {
        // unify format output
        return this.setDefaultQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
      }
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
      this.$refs.addGroup.editingActive = true;
      this.$refs.addGroup.onMenuToggle(false);
      this.$nextTick(() => {
        this.$refs.addGroup.$refs.editingInput.focusInput();
      });
    },
    hideAddGroupForm() {
      this.$refs.addGroup.editingActive = false;
      this.$refs.addGroup.editingValue = '';
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
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcAppNavigationItem", {
    key: _vm.id,
    attrs: {
      exact: true,
      title: _vm.title,
      to: {
        name: "group",
        params: {
          selectedGroup: encodeURIComponent(_vm.id)
        }
      },
      icon: "icon-group",
      loading: _vm.loadingRenameGroup,
      "menu-open": _vm.openGroupMenu
    },
    on: {
      "update:menuOpen": _vm.handleGroupMenuOpen
    },
    scopedSlots: _vm._u([{
      key: "counter",
      fn: function () {
        return [_vm.count ? _c("NcCounterBubble", [_vm._v("\n\t\t\t" + _vm._s(_vm.count) + "\n\t\t")]) : _vm._e()];
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
            value: _vm.title
          },
          on: {
            submit: function ($event) {
              return _vm.renameGroup(_vm.id);
            }
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Rename group")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.id !== "admin" && _vm.id !== "disabled" && _vm.settings.isAdmin ? _c("NcActionButton", {
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
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "user-list-grid",
    attrs: {
      id: "app-content"
    },
    on: {
      "&scroll": function ($event) {
        return _vm.onScroll.apply(null, arguments);
      }
    }
  }, [_vm.showConfig.showNewUserForm ? _c("NcModal", {
    attrs: {
      size: "small"
    },
    on: {
      close: _vm.closeModal
    }
  }, [_c("form", {
    staticClass: "modal__content",
    attrs: {
      id: "new-user",
      disabled: _vm.loading.all
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.createUser.apply(null, arguments);
      }
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("settings", "New user")))]), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.newUser.id,
      expression: "newUser.id"
    }],
    ref: "newusername",
    staticClass: "modal__item",
    attrs: {
      id: "newusername",
      disabled: _vm.settings.newUserGenerateUserID,
      placeholder: _vm.settings.newUserGenerateUserID ? _vm.t("settings", "Will be autogenerated") : _vm.t("settings", "Username"),
      autocapitalize: "none",
      autocomplete: "off",
      autocorrect: "off",
      name: "username",
      pattern: "[a-zA-Z0-9 _\\.@\\-']+",
      required: "",
      type: "text"
    },
    domProps: {
      value: _vm.newUser.id
    },
    on: {
      input: function ($event) {
        if ($event.target.composing) return;
        _vm.$set(_vm.newUser, "id", $event.target.value);
      }
    }
  }), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.newUser.displayName,
      expression: "newUser.displayName"
    }],
    staticClass: "modal__item",
    attrs: {
      id: "newdisplayname",
      placeholder: _vm.t("settings", "Display name"),
      autocapitalize: "none",
      autocomplete: "off",
      autocorrect: "off",
      name: "displayname",
      type: "text"
    },
    domProps: {
      value: _vm.newUser.displayName
    },
    on: {
      input: function ($event) {
        if ($event.target.composing) return;
        _vm.$set(_vm.newUser, "displayName", $event.target.value);
      }
    }
  }), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.newUser.password,
      expression: "newUser.password"
    }],
    ref: "newuserpassword",
    staticClass: "modal__item",
    attrs: {
      id: "newuserpassword",
      minlength: _vm.minPasswordLength,
      maxlength: 469,
      placeholder: _vm.t("settings", "Password"),
      required: _vm.newUser.mailAddress === "",
      autocapitalize: "none",
      autocomplete: "new-password",
      autocorrect: "off",
      name: "password",
      type: "password"
    },
    domProps: {
      value: _vm.newUser.password
    },
    on: {
      input: function ($event) {
        if ($event.target.composing) return;
        _vm.$set(_vm.newUser, "password", $event.target.value);
      }
    }
  }), _vm._v(" "), _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.newUser.mailAddress,
      expression: "newUser.mailAddress"
    }],
    staticClass: "modal__item",
    attrs: {
      id: "newemail",
      placeholder: _vm.t("settings", "Email"),
      required: _vm.newUser.password === "" || _vm.settings.newUserRequireEmail,
      autocapitalize: "none",
      autocomplete: "off",
      autocorrect: "off",
      name: "email",
      type: "email"
    },
    domProps: {
      value: _vm.newUser.mailAddress
    },
    on: {
      input: function ($event) {
        if ($event.target.composing) return;
        _vm.$set(_vm.newUser, "mailAddress", $event.target.value);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "groups modal__item"
  }, [!_vm.settings.isAdmin ? _c("input", {
    class: {
      "icon-loading-small": _vm.loading.groups
    },
    attrs: {
      id: "newgroups",
      required: !_vm.settings.isAdmin,
      tabindex: "-1",
      type: "text"
    },
    domProps: {
      value: _vm.newUser.groups
    }
  }) : _vm._e(), _vm._v(" "), _c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "close-on-select": false,
      disabled: _vm.loading.groups || _vm.loading.all,
      multiple: true,
      options: _vm.canAddGroups,
      placeholder: _vm.t("settings", "Add user to group"),
      "tag-width": 60,
      taggable: true,
      label: "name",
      "tag-placeholder": "create",
      "track-by": "id"
    },
    on: {
      tag: _vm.createGroup
    },
    model: {
      value: _vm.newUser.groups,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "groups", $$v);
      },
      expression: "newUser.groups"
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])])], 1), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("div", {
    staticClass: "subadmins modal__item"
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "close-on-select": false,
      multiple: true,
      options: _vm.subAdminsGroups,
      placeholder: _vm.t("settings", "Set user as admin for"),
      "tag-width": 60,
      label: "name",
      "track-by": "id"
    },
    model: {
      value: _vm.newUser.subAdminsGroups,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "subAdminsGroups", $$v);
      },
      expression: "newUser.subAdminsGroups"
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])])], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "quota modal__item"
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "allow-empty": false,
      options: _vm.quotaOptions,
      placeholder: _vm.t("settings", "Select user quota"),
      taggable: true,
      label: "label",
      "track-by": "id"
    },
    on: {
      tag: _vm.validateQuota
    },
    model: {
      value: _vm.newUser.quota,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "quota", $$v);
      },
      expression: "newUser.quota"
    }
  })], 1), _vm._v(" "), _vm.showConfig.showLanguages ? _c("div", {
    staticClass: "languages modal__item"
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "allow-empty": false,
      options: _vm.languages,
      placeholder: _vm.t("settings", "Default language"),
      "group-label": "label",
      "group-values": "languages",
      label: "name",
      "track-by": "code"
    },
    model: {
      value: _vm.newUser.language,
      callback: function ($$v) {
        _vm.$set(_vm.newUser, "language", $$v);
      },
      expression: "newUser.language"
    }
  })], 1) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("div", {
    staticClass: "storageLocation"
  }) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend ? _c("div", {
    staticClass: "userBackend"
  }) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("div", {
    staticClass: "lastLogin"
  }) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "user-actions"
  }, [_c("NcButton", {
    attrs: {
      id: "newsubmit",
      type: "primary",
      "native-type": "submit",
      value: ""
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Add a new user")) + "\n\t\t\t\t")])], 1)])]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "row",
    class: {
      sticky: _vm.scrolled && !_vm.showConfig.showNewUserForm
    },
    attrs: {
      id: "grid-header"
    }
  }, [_c("div", {
    staticClass: "avatar",
    attrs: {
      id: "headerAvatar"
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "name",
    attrs: {
      id: "headerName"
    }
  }, [_c("div", {
    staticClass: "subtitle"
  }, [_c("strong", [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Display name")) + "\n\t\t\t\t")])]), _vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Username")) + "\n\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "password",
    attrs: {
      id: "headerPassword"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Password")) + "\n\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "mailAddress",
    attrs: {
      id: "headerAddress"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Email")) + "\n\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "groups",
    attrs: {
      id: "headerGroups"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Groups")) + "\n\t\t")]), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("div", {
    staticClass: "subadmins",
    attrs: {
      id: "headerSubAdmins"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Group admin for")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "quota",
    attrs: {
      id: "headerQuota"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Quota")) + "\n\t\t")]), _vm._v(" "), _vm.showConfig.showLanguages ? _c("div", {
    staticClass: "languages",
    attrs: {
      id: "headerLanguages"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Language")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("div", {
    staticClass: "headerUserBackend userBackend"
  }, [_vm.showConfig.showUserBackend ? _c("div", {
    staticClass: "userBackend"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "User backend")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("div", {
    staticClass: "subtitle storageLocation"
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Storage location")) + "\n\t\t\t")]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("div", {
    staticClass: "headerLastLogin lastLogin"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Last login")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "userActions"
  })]), _vm._v(" "), _vm._l(_vm.filteredUsers, function (user) {
    return _c("user-row", {
      key: user.id,
      attrs: {
        "external-actions": _vm.externalActions,
        groups: _vm.groups,
        languages: _vm.languages,
        "quota-options": _vm.quotaOptions,
        settings: _vm.settings,
        "show-config": _vm.showConfig,
        "sub-admins-groups": _vm.subAdminsGroups,
        user: user,
        "is-dark-theme": _vm.isDarkTheme
      }
    });
  }), _vm._v(" "), _c("InfiniteLoading", {
    ref: "infiniteLoading",
    on: {
      infinite: _vm.infiniteHandler
    }
  }, [_c("div", {
    attrs: {
      slot: "spinner"
    },
    slot: "spinner"
  }, [_c("div", {
    staticClass: "users-icon-loading icon-loading"
  })]), _vm._v(" "), _c("div", {
    attrs: {
      slot: "no-more"
    },
    slot: "no-more"
  }, [_c("div", {
    staticClass: "users-list-end"
  })]), _vm._v(" "), _c("div", {
    attrs: {
      slot: "no-results"
    },
    slot: "no-results"
  }, [_c("div", {
    attrs: {
      id: "emptycontent"
    }
  }, [_c("div", {
    staticClass: "icon-contacts-dark"
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No users in here")))])])])])], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return Object.keys(_vm.user).length === 1 ? _c("div", {
    staticClass: "row",
    attrs: {
      "data-id": _vm.user.id
    }
  }, [_c("div", {
    staticClass: "avatar",
    class: {
      "icon-loading-small": _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe
    }
  }, [!_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe ? _c("img", {
    attrs: {
      src: _vm.generateAvatar(_vm.user.id, _vm.isDarkTheme),
      alt: "",
      height: "32",
      width: "32"
    }
  }) : _vm._e()]), _vm._v(" "), _c("div", {
    staticClass: "name"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t")]), _vm._v(" "), _c("div", {
    staticClass: "obfuscated"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "You do not have permissions to see the details of this user")) + "\n\t")])]) : !_vm.editing ? _c("UserRowSimple", {
    class: {
      "row--menu-opened": _vm.openedMenu
    },
    attrs: {
      editing: _vm.editing,
      "feedback-message": _vm.feedbackMessage,
      groups: _vm.groups,
      languages: _vm.languages,
      loading: _vm.loading,
      "opened-menu": _vm.openedMenu,
      settings: _vm.settings,
      "show-config": _vm.showConfig,
      "sub-admins-groups": _vm.subAdminsGroups,
      "user-actions": _vm.userActions,
      user: _vm.user,
      "is-dark-theme": _vm.isDarkTheme
    },
    on: {
      "update:editing": function ($event) {
        _vm.editing = $event;
      },
      "update:openedMenu": function ($event) {
        _vm.openedMenu = $event;
      },
      "update:opened-menu": function ($event) {
        _vm.openedMenu = $event;
      }
    }
  }) : _c("div", {
    staticClass: "row row--editable",
    class: {
      disabled: _vm.loading.delete || _vm.loading.disable,
      "row--menu-opened": _vm.openedMenu
    },
    attrs: {
      "data-id": _vm.user.id
    }
  }, [_c("div", {
    staticClass: "avatar",
    class: {
      "icon-loading-small": _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe
    }
  }, [!_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe ? _c("img", {
    attrs: {
      src: _vm.generateAvatar(_vm.user.id, _vm.isDarkTheme),
      alt: "",
      height: "32",
      width: "32"
    }
  }) : _vm._e()]), _vm._v(" "), _vm.user.backendCapabilities.setDisplayName ? _c("div", {
    staticClass: "displayName"
  }, [_c("form", {
    staticClass: "displayName",
    class: {
      "icon-loading-small": _vm.loading.displayName
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.updateDisplayName.apply(null, arguments);
      }
    }
  }, [_c("input", {
    ref: "displayName",
    attrs: {
      id: "displayName" + _vm.user.id + _vm.rand,
      disabled: _vm.loading.displayName || _vm.loading.all,
      autocapitalize: "off",
      autocomplete: "off",
      autocorrect: "off",
      spellcheck: "false",
      type: "text"
    },
    domProps: {
      value: _vm.user.displayname
    }
  }), _vm._v(" "), _c("input", {
    staticClass: "icon-confirm",
    attrs: {
      type: "submit",
      value: ""
    }
  })])]) : _c("div", {
    staticClass: "name"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t\t"), _c("div", {
    staticClass: "displayName subtitle"
  }, [_c("div", {
    staticClass: "cellText",
    attrs: {
      title: _vm.user.displayname.length > 20 ? _vm.user.displayname : ""
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t\t")])])]), _vm._v(" "), _vm.settings.canChangePassword && _vm.user.backendCapabilities.setPassword ? _c("form", {
    staticClass: "password",
    class: {
      "icon-loading-small": _vm.loading.password
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.updatePassword.apply(null, arguments);
      }
    }
  }, [_c("input", {
    ref: "password",
    attrs: {
      id: "password" + _vm.user.id + _vm.rand,
      disabled: _vm.loading.password || _vm.loading.all,
      minlength: _vm.minPasswordLength,
      maxlength: "469",
      placeholder: _vm.t("settings", "Add new password"),
      autocapitalize: "off",
      autocomplete: "new-password",
      autocorrect: "off",
      required: "",
      spellcheck: "false",
      type: "password",
      value: ""
    }
  }), _vm._v(" "), _c("input", {
    staticClass: "icon-confirm",
    attrs: {
      type: "submit",
      value: ""
    }
  })]) : _c("div"), _vm._v(" "), _c("form", {
    staticClass: "mailAddress",
    class: {
      "icon-loading-small": _vm.loading.mailAddress
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.updateEmail.apply(null, arguments);
      }
    }
  }, [_c("input", {
    ref: "mailAddress",
    attrs: {
      id: "mailAddress" + _vm.user.id + _vm.rand,
      disabled: _vm.loading.mailAddress || _vm.loading.all,
      placeholder: _vm.t("settings", "Add new email address"),
      autocapitalize: "off",
      autocomplete: "new-password",
      autocorrect: "off",
      spellcheck: "false",
      type: "email"
    },
    domProps: {
      value: _vm.user.email
    }
  }), _vm._v(" "), _c("input", {
    staticClass: "icon-confirm",
    attrs: {
      type: "submit",
      value: ""
    }
  })]), _vm._v(" "), _c("div", {
    staticClass: "groups",
    class: {
      "icon-loading-small": _vm.loading.groups
    }
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "close-on-select": false,
      disabled: _vm.loading.groups || _vm.loading.all,
      limit: 2,
      multiple: true,
      options: _vm.availableGroups,
      placeholder: _vm.t("settings", "Add user to group"),
      "tag-width": 60,
      taggable: _vm.settings.isAdmin,
      value: _vm.userGroups,
      label: "name",
      "tag-placeholder": "create",
      "track-by": "id"
    },
    on: {
      remove: _vm.removeUserGroup,
      select: _vm.addUserGroup,
      tag: _vm.createGroup
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])])], 1), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("div", {
    staticClass: "subadmins",
    class: {
      "icon-loading-small": _vm.loading.subadmins
    }
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "close-on-select": false,
      disabled: _vm.loading.subadmins || _vm.loading.all,
      limit: 2,
      multiple: true,
      options: _vm.subAdminsGroups,
      placeholder: _vm.t("settings", "Set user as admin for"),
      "tag-width": 60,
      value: _vm.userSubAdminsGroups,
      label: "name",
      "track-by": "id"
    },
    on: {
      remove: _vm.removeUserSubAdmin,
      select: _vm.addUserSubAdmin
    }
  }, [_c("span", {
    attrs: {
      slot: "noResult"
    },
    slot: "noResult"
  }, [_vm._v(_vm._s(_vm.t("settings", "No results")))])])], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "quota",
    class: {
      "icon-loading-small": _vm.loading.quota
    },
    attrs: {
      title: _vm.usedSpace
    }
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "allow-empty": false,
      disabled: _vm.loading.quota || _vm.loading.all,
      options: _vm.quotaOptions,
      placeholder: _vm.t("settings", "Select user quota"),
      taggable: true,
      value: _vm.userQuota,
      label: "label",
      "tag-placeholder": "create",
      "track-by": "id"
    },
    on: {
      input: _vm.setUserQuota,
      tag: _vm.validateQuota
    }
  })], 1), _vm._v(" "), _vm.showConfig.showLanguages ? _c("div", {
    staticClass: "languages",
    class: {
      "icon-loading-small": _vm.loading.languages
    }
  }, [_c("NcMultiselect", {
    staticClass: "multiselect-vue",
    attrs: {
      "allow-empty": false,
      disabled: _vm.loading.languages || _vm.loading.all,
      options: _vm.languages,
      placeholder: _vm.t("settings", "No language set"),
      value: _vm.userLanguage,
      "group-label": "label",
      "group-values": "languages",
      label: "name",
      "track-by": "code"
    },
    on: {
      input: _vm.setUserLanguage
    }
  })], 1) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath || _vm.showConfig.showUserBackend ? _c("div", {
    staticClass: "storageLocation"
  }) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("div") : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "userActions"
  }, [!_vm.loading.all ? _c("div", {
    staticClass: "toggleUserActions"
  }, [_c("NcActions", [_c("NcActionButton", {
    attrs: {
      icon: "icon-checkmark",
      title: _vm.t("settings", "Done"),
      "aria-label": _vm.t("settings", "Done")
    },
    on: {
      click: function ($event) {
        _vm.editing = false;
      }
    }
  })], 1), _vm._v(" "), _c("div", {
    directives: [{
      name: "click-outside",
      rawName: "v-click-outside",
      value: _vm.hideMenu,
      expression: "hideMenu"
    }],
    staticClass: "userPopoverMenuWrapper"
  }, [_c("button", {
    staticClass: "icon-more",
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.toggleMenu.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "popovermenu",
    class: {
      open: _vm.openedMenu
    }
  }, [_c("NcPopoverMenu", {
    attrs: {
      menu: _vm.userActions
    }
  })], 1)])], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "feedback",
    style: {
      opacity: _vm.feedbackMessage !== "" ? 1 : 0
    }
  }, [_c("div", {
    staticClass: "icon-checkmark"
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.feedbackMessage) + "\n\t\t")])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "row",
    class: {
      disabled: _vm.loading.delete || _vm.loading.disable
    },
    attrs: {
      "data-id": _vm.user.id
    }
  }, [_c("div", {
    staticClass: "avatar",
    class: {
      "icon-loading-small": _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe
    }
  }, [!_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe ? _c("img", {
    attrs: {
      alt: "",
      width: "32",
      height: "32",
      src: _vm.generateAvatar(_vm.user.id, _vm.isDarkTheme)
    }
  }) : _vm._e()]), _vm._v(" "), _c("div", {
    staticClass: "name"
  }, [_c("div", {
    staticClass: "displayName subtitle"
  }, [_c("div", {
    staticClass: "cellText",
    attrs: {
      title: _vm.user.displayname.length > 20 ? _vm.user.displayname : ""
    }
  }, [_c("strong", [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t\t\t")])])]), _vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t")]), _vm._v(" "), _c("div"), _vm._v(" "), _c("div", {
    staticClass: "mailAddress"
  }, [_c("div", {
    staticClass: "cellText",
    attrs: {
      title: _vm.user.email !== null && _vm.user.email.length > 20 ? _vm.user.email : ""
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.email) + "\n\t\t")])]), _vm._v(" "), _c("div", {
    staticClass: "groups"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.userGroupsLabels) + "\n\t")]), _vm._v(" "), _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin ? _c("div", {
    staticClass: "subAdminsGroups"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.userSubAdminsGroupsLabels) + "\n\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "userQuota"
  }, [_c("div", {
    staticClass: "quota"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.userQuota) + " (" + _vm._s(_vm.usedSpace) + ")\n\t\t\t"), _c("progress", {
    staticClass: "quota-user-progress",
    class: {
      warn: _vm.usedQuota > 80
    },
    attrs: {
      max: "100"
    },
    domProps: {
      value: _vm.usedQuota
    }
  })])]), _vm._v(" "), _vm.showConfig.showLanguages ? _c("div", {
    staticClass: "languages"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.userLanguage.name) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath ? _c("div", {
    staticClass: "userBackend"
  }, [_vm.showConfig.showUserBackend ? _c("div", {
    staticClass: "userBackend"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.backend) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.showConfig.showStoragePath ? _c("div", {
    staticClass: "storageLocation subtitle",
    attrs: {
      title: _vm.user.storageLocation
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.user.storageLocation) + "\n\t\t")]) : _vm._e()]) : _vm._e(), _vm._v(" "), _vm.showConfig.showLastLogin ? _c("div", {
    staticClass: "lastLogin",
    attrs: {
      title: _vm.userLastLoginTooltip
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.userLastLogin) + "\n\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "userActions"
  }, [_vm.canEdit && !_vm.loading.all ? _c("div", {
    staticClass: "toggleUserActions"
  }, [_c("NcActions", [_c("NcActionButton", {
    attrs: {
      icon: "icon-rename",
      title: _vm.t("settings", "Edit User"),
      "aria-label": _vm.t("settings", "Edit User")
    },
    on: {
      click: _vm.toggleEdit
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "userPopoverMenuWrapper"
  }, [_c("button", {
    directives: [{
      name: "click-outside",
      rawName: "v-click-outside",
      value: _vm.hideMenu,
      expression: "hideMenu"
    }],
    staticClass: "icon-more",
    attrs: {
      "aria-label": _vm.t("settings", "Toggle user actions menu")
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.toggleMenu.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "popovermenu",
    class: {
      open: _vm.openedMenu
    },
    attrs: {
      "aria-expanded": _vm.openedMenu
    }
  }, [_c("NcPopoverMenu", {
    attrs: {
      menu: _vm.userActions
    }
  })], 1)])], 1) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "feedback",
    style: {
      opacity: _vm.feedbackMessage !== "" ? 1 : 0
    }
  }, [_c("div", {
    staticClass: "icon-checkmark"
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.feedbackMessage) + "\n\t\t")])])]);
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
/* harmony export */   "render": () => (/* binding */ render),
/* harmony export */   "staticRenderFns": () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcContent", {
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
        return [_c("NcAppNavigationItem", {
          ref: "addGroup",
          attrs: {
            id: "addgroup",
            "edit-placeholder": _vm.t("settings", "Enter group name"),
            editable: true,
            loading: _vm.loadingAddGroup,
            title: _vm.t("settings", "Add group"),
            icon: "icon-add"
          },
          on: {
            click: _vm.showAddGroupForm,
            "update:title": _vm.createGroup
          }
        }), _vm._v(" "), _c("NcAppNavigationItem", {
          attrs: {
            id: "everyone",
            exact: true,
            title: _vm.t("settings", "Active users"),
            to: {
              name: "users"
            },
            icon: "icon-contacts-dark"
          }
        }, [_vm.userCount > 0 ? _c("NcAppNavigationCounter", {
          attrs: {
            slot: "counter"
          },
          slot: "counter"
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.userCount) + "\n\t\t\t\t")]) : _vm._e()], 1), _vm._v(" "), _vm.settings.isAdmin ? _c("NcAppNavigationItem", {
          attrs: {
            id: "admin",
            exact: true,
            title: _vm.t("settings", "Admins"),
            to: {
              name: "group",
              params: {
                selectedGroup: "admin"
              }
            },
            icon: "icon-user-admin"
          }
        }, [_vm.adminGroupMenu.count ? _c("NcAppNavigationCounter", {
          attrs: {
            slot: "counter"
          },
          slot: "counter"
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.adminGroupMenu.count) + "\n\t\t\t\t")]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _vm.disabledGroupMenu.usercount > 0 || _vm.disabledGroupMenu.usercount === -1 ? _c("NcAppNavigationItem", {
          attrs: {
            id: "disabled",
            exact: true,
            title: _vm.t("settings", "Disabled users"),
            to: {
              name: "group",
              params: {
                selectedGroup: "disabled"
              }
            },
            icon: "icon-disabled-users"
          }
        }, [_vm.disabledGroupMenu.usercount > 0 ? _c("NcAppNavigationCounter", {
          attrs: {
            slot: "counter"
          },
          slot: "counter"
        }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.disabledGroupMenu.usercount) + "\n\t\t\t\t")]) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _vm.groupList.length > 0 ? _c("NcAppNavigationCaption", {
          attrs: {
            title: _vm.t("settings", "Groups")
          }
        }) : _vm._e(), _vm._v(" "), _vm._l(_vm.groupList, function (group) {
          return _c("GroupListItem", {
            key: group.id,
            attrs: {
              id: group.id,
              title: group.title,
              count: group.count
            }
          });
        })];
      },
      proxy: true
    }, {
      key: "footer",
      fn: function () {
        return [_c("NcAppNavigationSettings", [_c("div", [_c("p", [_vm._v(_vm._s(_vm.t("settings", "Default quota:")))]), _vm._v(" "), _c("NcMultiselect", {
          attrs: {
            value: _vm.defaultQuota,
            options: _vm.quotaOptions,
            "tag-placeholder": "create",
            placeholder: _vm.t("settings", "Select default quota"),
            label: "label",
            "track-by": "id",
            "allow-empty": false,
            taggable: true
          },
          on: {
            tag: _vm.validateQuota,
            input: _vm.setDefaultQuota
          }
        })], 1), _vm._v(" "), _c("div", [_c("input", {
          directives: [{
            name: "model",
            rawName: "v-model",
            value: _vm.showLanguages,
            expression: "showLanguages"
          }],
          staticClass: "checkbox",
          attrs: {
            id: "showLanguages",
            type: "checkbox"
          },
          domProps: {
            checked: Array.isArray(_vm.showLanguages) ? _vm._i(_vm.showLanguages, null) > -1 : _vm.showLanguages
          },
          on: {
            change: function ($event) {
              var $$a = _vm.showLanguages,
                $$el = $event.target,
                $$c = $$el.checked ? true : false;
              if (Array.isArray($$a)) {
                var $$v = null,
                  $$i = _vm._i($$a, $$v);
                if ($$el.checked) {
                  $$i < 0 && (_vm.showLanguages = $$a.concat([$$v]));
                } else {
                  $$i > -1 && (_vm.showLanguages = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
                }
              } else {
                _vm.showLanguages = $$c;
              }
            }
          }
        }), _vm._v(" "), _c("label", {
          attrs: {
            for: "showLanguages"
          }
        }, [_vm._v(_vm._s(_vm.t("settings", "Show Languages")))])]), _vm._v(" "), _c("div", [_c("input", {
          directives: [{
            name: "model",
            rawName: "v-model",
            value: _vm.showLastLogin,
            expression: "showLastLogin"
          }],
          staticClass: "checkbox",
          attrs: {
            id: "showLastLogin",
            type: "checkbox"
          },
          domProps: {
            checked: Array.isArray(_vm.showLastLogin) ? _vm._i(_vm.showLastLogin, null) > -1 : _vm.showLastLogin
          },
          on: {
            change: function ($event) {
              var $$a = _vm.showLastLogin,
                $$el = $event.target,
                $$c = $$el.checked ? true : false;
              if (Array.isArray($$a)) {
                var $$v = null,
                  $$i = _vm._i($$a, $$v);
                if ($$el.checked) {
                  $$i < 0 && (_vm.showLastLogin = $$a.concat([$$v]));
                } else {
                  $$i > -1 && (_vm.showLastLogin = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
                }
              } else {
                _vm.showLastLogin = $$c;
              }
            }
          }
        }), _vm._v(" "), _c("label", {
          attrs: {
            for: "showLastLogin"
          }
        }, [_vm._v(_vm._s(_vm.t("settings", "Show last login")))])]), _vm._v(" "), _c("div", [_c("input", {
          directives: [{
            name: "model",
            rawName: "v-model",
            value: _vm.showUserBackend,
            expression: "showUserBackend"
          }],
          staticClass: "checkbox",
          attrs: {
            id: "showUserBackend",
            type: "checkbox"
          },
          domProps: {
            checked: Array.isArray(_vm.showUserBackend) ? _vm._i(_vm.showUserBackend, null) > -1 : _vm.showUserBackend
          },
          on: {
            change: function ($event) {
              var $$a = _vm.showUserBackend,
                $$el = $event.target,
                $$c = $$el.checked ? true : false;
              if (Array.isArray($$a)) {
                var $$v = null,
                  $$i = _vm._i($$a, $$v);
                if ($$el.checked) {
                  $$i < 0 && (_vm.showUserBackend = $$a.concat([$$v]));
                } else {
                  $$i > -1 && (_vm.showUserBackend = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
                }
              } else {
                _vm.showUserBackend = $$c;
              }
            }
          }
        }), _vm._v(" "), _c("label", {
          attrs: {
            for: "showUserBackend"
          }
        }, [_vm._v(_vm._s(_vm.t("settings", "Show user backend")))])]), _vm._v(" "), _c("div", [_c("input", {
          directives: [{
            name: "model",
            rawName: "v-model",
            value: _vm.showStoragePath,
            expression: "showStoragePath"
          }],
          staticClass: "checkbox",
          attrs: {
            id: "showStoragePath",
            type: "checkbox"
          },
          domProps: {
            checked: Array.isArray(_vm.showStoragePath) ? _vm._i(_vm.showStoragePath, null) > -1 : _vm.showStoragePath
          },
          on: {
            change: function ($event) {
              var $$a = _vm.showStoragePath,
                $$el = $event.target,
                $$c = $$el.checked ? true : false;
              if (Array.isArray($$a)) {
                var $$v = null,
                  $$i = _vm._i($$a, $$v);
                if ($$el.checked) {
                  $$i < 0 && (_vm.showStoragePath = $$a.concat([$$v]));
                } else {
                  $$i > -1 && (_vm.showStoragePath = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
                }
              } else {
                _vm.showStoragePath = $$c;
              }
            }
          }
        }), _vm._v(" "), _c("label", {
          attrs: {
            for: "showStoragePath"
          }
        }, [_vm._v(_vm._s(_vm.t("settings", "Show storage path")))])]), _vm._v(" "), _c("div", [_c("input", {
          directives: [{
            name: "model",
            rawName: "v-model",
            value: _vm.sendWelcomeMail,
            expression: "sendWelcomeMail"
          }],
          staticClass: "checkbox",
          attrs: {
            id: "sendWelcomeMail",
            disabled: _vm.loadingSendMail,
            type: "checkbox"
          },
          domProps: {
            checked: Array.isArray(_vm.sendWelcomeMail) ? _vm._i(_vm.sendWelcomeMail, null) > -1 : _vm.sendWelcomeMail
          },
          on: {
            change: function ($event) {
              var $$a = _vm.sendWelcomeMail,
                $$el = $event.target,
                $$c = $$el.checked ? true : false;
              if (Array.isArray($$a)) {
                var $$v = null,
                  $$i = _vm._i($$a, $$v);
                if ($$el.checked) {
                  $$i < 0 && (_vm.sendWelcomeMail = $$a.concat([$$v]));
                } else {
                  $$i > -1 && (_vm.sendWelcomeMail = $$a.slice(0, $$i).concat($$a.slice($$i + 1)));
                }
              } else {
                _vm.sendWelcomeMail = $$c;
              }
            }
          }
        }), _vm._v(" "), _c("label", {
          attrs: {
            for: "sendWelcomeMail"
          }
        }, [_vm._v(_vm._s(_vm.t("settings", "Send email to new user")))])])])];
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
    }
  })], 1), _vm._v(" "), _c("NcAppContent", [_c("UserList", {
    attrs: {
      users: _vm.users,
      "show-config": _vm.showConfig,
      "selected-group": _vm.selectedGroupDecoded,
      "external-actions": _vm.externalActions
    }
  })], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
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
___CSS_LOADER_EXPORT___.push([module.id, ".row--menu-opened[data-v-77960baa] {\n  z-index: 1 !important;\n}\n.row[data-v-77960baa] .multiselect__single {\n  z-index: auto !important;\n}", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, ".cellText {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n}\n.icon-more {\n  background-color: var(--color-main-background);\n  border: 0;\n}", ""]);
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
___CSS_LOADER_EXPORT___.push([module.id, ".app-navigation__list #addgroup[data-v-889b7562] .app-navigation-entry__utils {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, "\n.modal-wrapper[data-v-6cba3aca] {\n\tmargin: 2vh 0;\n\talign-items: flex-start;\n}\n.modal__content[data-v-6cba3aca] {\n\tdisplay: flex;\n\tpadding: 20px;\n\tflex-direction: column;\n\talign-items: center;\n\ttext-align: center;\n}\n.modal__item[data-v-6cba3aca] {\n\tmargin-bottom: 16px;\n\twidth: 100%;\n}\n.modal__item[data-v-6cba3aca]:not(:focus):not(:active) {\n\tborder-color: var(--color-border-dark);\n}\n.modal__item[data-v-6cba3aca] .multiselect {\n\twidth: 100%;\n}\n.user-actions[data-v-6cba3aca] {\n\tmargin-top: 20px;\n}\n.modal__content[data-v-6cba3aca] .multiselect__single {\n\ttext-align: left;\n\tbox-sizing: border-box;\n}\n.modal__content[data-v-6cba3aca] .multiselect__content-wrapper {\n\tbox-sizing: border-box;\n}\n.row[data-v-6cba3aca] .multiselect__single {\n\tz-index: auto !important;\n}\n\n/* fake input for groups validation */\ninput#newgroups[data-v-6cba3aca] {\n\tposition: absolute;\n\topacity: 0;\n\t/* The \"hidden\" input is behind the Multiselect, so in general it does\n\t * not receives clicks. However, with Firefox, after the validation\n\t * fails, it will receive the first click done on it, so its width needs\n\t * to be set to 0 to prevent that (\"pointer-events: none\" does not\n\t * prevent it). */\n\twidth: 0;\n}\n", ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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
/* harmony import */ var _UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");
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

/***/ "./apps/settings/src/components/UserList/UserRow.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRow.vue?vue&type=template&id=77960baa&scoped=true& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&");
/* harmony import */ var _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRow.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "77960baa",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList/UserRow.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue":
/*!*****************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=template&id=ff154a08& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&");
/* harmony import */ var _UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList/UserRowSimple.vue"
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

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&":
/*!******************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

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
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_GroupListItem_vue_vue_type_template_id_b3f9b202___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
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
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=template&id=6cba3aca&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true& ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=template&id=77960baa&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08& ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=template&id=ff154a08& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true& ***!
  \**************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   "staticRenderFns": () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=template&id=889b7562&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_id_ff154a08_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&id=ff154a08&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");


/***/ })

}]);
//# sourceMappingURL=settings-users-settings-users.js.map?v=e14909af5a7d806d09b2