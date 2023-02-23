/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/updatenotification/src/init.js":
/*!*********************************************!*\
  !*** ./apps/updatenotification/src/init.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_UpdateNotification__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/UpdateNotification */ "./apps/updatenotification/src/components/UpdateNotification.vue");
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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



vue__WEBPACK_IMPORTED_MODULE_1__["default"].mixin({
  methods: {
    t: function t(app, text, vars, count, options) {
      return OC.L10N.translate(app, text, vars, count, options);
    },
    n: function n(app, textSingular, textPlural, count, vars, options) {
      return OC.L10N.translatePlural(app, textSingular, textPlural, count, vars, options);
    }
  }
});

// eslint-disable-next-line no-new
new vue__WEBPACK_IMPORTED_MODULE_1__["default"]({
  el: '#updatenotification',
  render: function render(h) {
    return h(_components_UpdateNotification__WEBPACK_IMPORTED_MODULE_0__["default"]);
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPopoverMenu.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPopoverMenu.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcMultiselect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcMultiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSettingsSection_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSettingsSection.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSettingsSection.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSettingsSection_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSettingsSection_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }











var logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_10__.getLoggerBuilder)().setApp('updatenotification').detectUser().build();
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UpdateNotification',
  components: {
    NcMultiselect: (_nextcloud_vue_dist_Components_NcMultiselect_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcPopoverMenu: (_nextcloud_vue_dist_Components_NcPopoverMenu_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcSettingsSection: (_nextcloud_vue_dist_Components_NcSettingsSection_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcNoteCard: (_nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4___default())
  },
  directives: {
    ClickOutside: (vue_click_outside__WEBPACK_IMPORTED_MODULE_5___default())
  },
  data: function data() {
    return {
      loadingGroups: false,
      newVersionString: '',
      lastCheckedDate: '',
      isUpdateChecked: false,
      webUpdaterEnabled: true,
      isWebUpdaterRecommended: true,
      updaterEnabled: true,
      versionIsEol: false,
      downloadLink: '',
      isNewVersionAvailable: false,
      hasValidSubscription: false,
      updateServerURL: '',
      changelogURL: '',
      whatsNewData: [],
      currentChannel: '',
      channels: [],
      notifyGroups: '',
      groups: [],
      isDefaultUpdateServerURL: true,
      enableChangeWatcher: false,
      availableAppUpdates: [],
      missingAppUpdates: [],
      appStoreFailed: false,
      appStoreDisabled: false,
      isListFetched: false,
      hideMissingUpdates: false,
      hideAvailableUpdates: true,
      openedWhatsNew: false,
      openedUpdateChannelMenu: false
    };
  },
  computed: {
    newVersionAvailableString: function newVersionAvailableString() {
      return t('updatenotification', 'A new version is available: <strong>{newVersionString}</strong>', {
        newVersionString: this.newVersionString
      });
    },
    noteDelayedStableString: function noteDelayedStableString() {
      return t('updatenotification', 'Note that after a new release the update only shows up after the first minor release or later. We roll out new versions spread out over time to our users and sometimes skip a version when issues are found. Learn more about updates and release channels at {link}').replace('{link}', '<a href="https://nextcloud.com/release-channels/">https://nextcloud.com/release-channels/</a>');
    },
    lastCheckedOnString: function lastCheckedOnString() {
      return t('updatenotification', 'Checked on {lastCheckedDate}', {
        lastCheckedDate: this.lastCheckedDate
      });
    },
    statusText: function statusText() {
      if (!this.isListFetched) {
        return t('updatenotification', 'Checking apps for compatible versions');
      }
      if (this.appStoreDisabled) {
        return t('updatenotification', 'Please make sure your config.php does not set <samp>appstoreenabled</samp> to false.');
      }
      if (this.appStoreFailed) {
        return t('updatenotification', 'Could not connect to the App Store or no updates have been returned at all. Search manually for updates or make sure your server has access to the internet and can connect to the App Store.');
      }
      return this.missingAppUpdates.length === 0 ? t('updatenotification', '<strong>All</strong> apps have a compatible version for this Nextcloud version available.', this) : n('updatenotification', '<strong>%n</strong> app has no compatible version for this Nextcloud version available.', '<strong>%n</strong> apps have no compatible version for this Nextcloud version available.', this.missingAppUpdates.length);
    },
    whatsNew: function whatsNew() {
      if (this.whatsNewData.length === 0) {
        return null;
      }
      var whatsNew = [];
      for (var i in this.whatsNewData) {
        whatsNew[i] = {
          icon: 'icon-checkmark',
          longtext: this.whatsNewData[i]
        };
      }
      if (this.changelogURL) {
        whatsNew.push({
          href: this.changelogURL,
          text: t('updatenotification', 'View changelog'),
          icon: 'icon-link',
          target: '_blank',
          action: ''
        });
      }
      return whatsNew;
    },
    channelList: function channelList() {
      var channelList = [];
      channelList.push({
        text: t('updatenotification', 'Enterprise'),
        longtext: t('updatenotification', 'For enterprise use. Provides always the latest patch level, but will not update to the next major release immediately. That update happens once Nextcloud GmbH has done additional hardening and testing for large-scale and mission-critical deployments. This channel is only available to customers and provides the Nextcloud Enterprise package.'),
        icon: 'icon-star',
        active: this.currentChannel === 'enterprise',
        disabled: !this.hasValidSubscription,
        action: this.changeReleaseChannelToEnterprise
      });
      channelList.push({
        text: t('updatenotification', 'Stable'),
        longtext: t('updatenotification', 'The most recent stable version. It is suited for regular use and will always update to the latest major version.'),
        icon: 'icon-checkmark',
        active: this.currentChannel === 'stable',
        action: this.changeReleaseChannelToStable
      });
      channelList.push({
        text: t('updatenotification', 'Beta'),
        longtext: t('updatenotification', 'A pre-release version only for testing new features, not for production environments.'),
        icon: 'icon-category-customization',
        active: this.currentChannel === 'beta',
        action: this.changeReleaseChannelToBeta
      });
      if (this.isNonDefaultChannel) {
        channelList.push({
          text: this.currentChannel,
          icon: 'icon-rename',
          active: true
        });
      }
      return channelList;
    },
    isNonDefaultChannel: function isNonDefaultChannel() {
      return this.currentChannel !== 'enterprise' && this.currentChannel !== 'stable' && this.currentChannel !== 'beta';
    },
    localizedChannelName: function localizedChannelName() {
      switch (this.currentChannel) {
        case 'enterprise':
          return t('updatenotification', 'Enterprise');
        case 'stable':
          return t('updatenotification', 'Stable');
        case 'beta':
          return t('updatenotification', 'Beta');
        default:
          return this.currentChannel;
      }
    }
  },
  watch: {
    notifyGroups: function notifyGroups(selectedOptions) {
      if (!this.enableChangeWatcher) {
        // The first time is when loading the app
        this.enableChangeWatcher = true;
        return;
      }
      var groups = this.notifyGroups.map(function (group) {
        return group.id;
      });
      OCP.AppConfig.setValue('updatenotification', 'notify_groups', JSON.stringify(groups));
    },
    isNewVersionAvailable: function isNewVersionAvailable() {
      var _this = this;
      if (!this.isNewVersionAvailable) {
        return;
      }
      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/updatenotification/api/v1/applist/{newVersion}', {
        newVersion: this.newVersion
      })).then(function (_ref) {
        var data = _ref.data;
        _this.availableAppUpdates = data.ocs.data.available;
        _this.missingAppUpdates = data.ocs.data.missing;
        _this.isListFetched = true;
        _this.appStoreFailed = false;
      }).catch(function (_ref2) {
        var data = _ref2.data;
        _this.availableAppUpdates = [];
        _this.missingAppUpdates = [];
        _this.appStoreDisabled = data.ocs.data.appstore_disabled;
        _this.isListFetched = true;
        _this.appStoreFailed = true;
      });
    }
  },
  beforeMount: function beforeMount() {
    // Parse server data
    var data = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_7__.loadState)('updatenotification', 'data');
    this.newVersion = data.newVersion;
    this.newVersionString = data.newVersionString;
    this.lastCheckedDate = data.lastChecked;
    this.isUpdateChecked = data.isUpdateChecked;
    this.webUpdaterEnabled = data.webUpdaterEnabled;
    this.isWebUpdaterRecommended = data.isWebUpdaterRecommended;
    this.updaterEnabled = data.updaterEnabled;
    this.downloadLink = data.downloadLink;
    this.isNewVersionAvailable = data.isNewVersionAvailable;
    this.updateServerURL = data.updateServerURL;
    this.currentChannel = data.currentChannel;
    this.channels = data.channels;
    this.notifyGroups = data.notifyGroups;
    this.isDefaultUpdateServerURL = data.isDefaultUpdateServerURL;
    this.versionIsEol = data.versionIsEol;
    this.hasValidSubscription = data.hasValidSubscription;
    if (data.changes && data.changes.changelogURL) {
      this.changelogURL = data.changes.changelogURL;
    }
    if (data.changes && data.changes.whatsNew) {
      if (data.changes.whatsNew.admin) {
        this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.admin);
      }
      this.whatsNewData = this.whatsNewData.concat(data.changes.whatsNew.regular);
    }
  },
  mounted: function mounted() {
    this.searchGroup();
  },
  methods: {
    searchGroup: debounce__WEBPACK_IMPORTED_MODULE_9___default()( /*#__PURE__*/function () {
      var _ref3 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(query) {
        var response;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                this.loadingGroups = true;
                _context.prev = 1;
                _context.next = 4;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('cloud/groups/details'), {
                  search: query,
                  limit: 20,
                  offset: 0
                });
              case 4:
                response = _context.sent;
                this.groups = response.data.ocs.data.groups.sort(function (a, b) {
                  return a.displayname.localeCompare(b.displayname);
                });
                _context.next = 11;
                break;
              case 8:
                _context.prev = 8;
                _context.t0 = _context["catch"](1);
                logger.error('Could not fetch groups', _context.t0);
              case 11:
                _context.prev = 11;
                this.loadingGroups = false;
                return _context.finish(11);
              case 14:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[1, 8, 11, 14]]);
      }));
      return function (_x) {
        return _ref3.apply(this, arguments);
      };
    }(), 500),
    /**
     * Creates a new authentication token and loads the updater URL
     */
    clickUpdaterButton: function clickUpdaterButton() {
      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/apps/updatenotification/credentials')).then(function (_ref4) {
        var data = _ref4.data;
        // create a form to send a proper post request to the updater
        var form = document.createElement('form');
        form.setAttribute('method', 'post');
        form.setAttribute('action', (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.getRootUrl)() + '/updater/');
        var hiddenField = document.createElement('input');
        hiddenField.setAttribute('type', 'hidden');
        hiddenField.setAttribute('name', 'updater-secret-input');
        hiddenField.setAttribute('value', data);
        form.appendChild(hiddenField);
        document.body.appendChild(form);
        form.submit();
      });
    },
    changeReleaseChannelToEnterprise: function changeReleaseChannelToEnterprise() {
      this.changeReleaseChannel('enterprise');
    },
    changeReleaseChannelToStable: function changeReleaseChannelToStable() {
      this.changeReleaseChannel('stable');
    },
    changeReleaseChannelToBeta: function changeReleaseChannelToBeta() {
      this.changeReleaseChannel('beta');
    },
    changeReleaseChannel: function changeReleaseChannel(channel) {
      this.currentChannel = channel;
      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/apps/updatenotification/channel'), {
        channel: this.currentChannel
      }).then(function (_ref5) {
        var data = _ref5.data;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_8__.showSuccess)(data.data.message);
      });
      this.openedUpdateChannelMenu = false;
    },
    toggleUpdateChannelMenu: function toggleUpdateChannelMenu() {
      this.openedUpdateChannelMenu = !this.openedUpdateChannelMenu;
    },
    toggleHideMissingUpdates: function toggleHideMissingUpdates() {
      this.hideMissingUpdates = !this.hideMissingUpdates;
    },
    toggleHideAvailableUpdates: function toggleHideAvailableUpdates() {
      this.hideAvailableUpdates = !this.hideAvailableUpdates;
    },
    toggleMenu: function toggleMenu() {
      this.openedWhatsNew = !this.openedWhatsNew;
    },
    closeUpdateChannelMenu: function closeUpdateChannelMenu() {
      this.openedUpdateChannelMenu = false;
    },
    hideMenu: function hideMenu() {
      this.openedWhatsNew = false;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcSettingsSection", {
    attrs: {
      id: "updatenotification",
      title: _vm.t("updatenotification", "Update")
    }
  }, [_c("div", {
    staticClass: "update"
  }, [_vm.isNewVersionAvailable ? [_vm.versionIsEol ? _c("NcNoteCard", {
    attrs: {
      type: "warning"
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("updatenotification", "The version you are running is not maintained anymore. Please make sure to update to a supported version as soon as possible.")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("p", [_c("span", {
    domProps: {
      innerHTML: _vm._s(_vm.newVersionAvailableString)
    }
  }), _c("br"), _vm._v(" "), !_vm.isListFetched ? _c("span", {
    staticClass: "icon icon-loading-small"
  }) : _vm._e(), _vm._v(" "), _c("span", {
    domProps: {
      innerHTML: _vm._s(_vm.statusText)
    }
  })]), _vm._v(" "), _vm.missingAppUpdates.length ? [_c("h3", {
    on: {
      click: _vm.toggleHideMissingUpdates
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("updatenotification", "Apps missing compatible version")) + "\n\t\t\t\t\t"), !_vm.hideMissingUpdates ? _c("span", {
    staticClass: "icon icon-triangle-n"
  }) : _vm._e(), _vm._v(" "), _vm.hideMissingUpdates ? _c("span", {
    staticClass: "icon icon-triangle-s"
  }) : _vm._e()]), _vm._v(" "), !_vm.hideMissingUpdates ? _c("ul", {
    staticClass: "applist"
  }, _vm._l(_vm.missingAppUpdates, function (app, index) {
    return _c("li", {
      key: index
    }, [_c("a", {
      attrs: {
        href: "https://apps.nextcloud.com/apps/" + app.appId,
        title: _vm.t("settings", "View in store")
      }
    }, [_vm._v(_vm._s(app.appName) + " ↗")])]);
  }), 0) : _vm._e()] : _vm._e(), _vm._v(" "), _vm.availableAppUpdates.length ? [_c("h3", {
    on: {
      click: _vm.toggleHideAvailableUpdates
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("updatenotification", "Apps with compatible version")) + "\n\t\t\t\t\t"), !_vm.hideAvailableUpdates ? _c("span", {
    staticClass: "icon icon-triangle-n"
  }) : _vm._e(), _vm._v(" "), _vm.hideAvailableUpdates ? _c("span", {
    staticClass: "icon icon-triangle-s"
  }) : _vm._e()]), _vm._v(" "), !_vm.hideAvailableUpdates ? _c("ul", {
    staticClass: "applist"
  }, _vm._l(_vm.availableAppUpdates, function (app, index) {
    return _c("li", {
      key: index
    }, [_c("a", {
      attrs: {
        href: "https://apps.nextcloud.com/apps/" + app.appId,
        title: _vm.t("settings", "View in store")
      }
    }, [_vm._v(_vm._s(app.appName) + " ↗")])]);
  }), 0) : _vm._e()] : _vm._e(), _vm._v(" "), !_vm.isWebUpdaterRecommended && _vm.updaterEnabled && _vm.webUpdaterEnabled ? [_c("h3", {
    staticClass: "warning"
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("updatenotification", "Please note that the web updater is not recommended with more than 100 users! Please use the command line updater instead!")) + "\n\t\t\t\t")])] : _vm._e(), _vm._v(" "), _c("div", [_vm.updaterEnabled && _vm.webUpdaterEnabled ? _c("a", {
    staticClass: "button primary",
    attrs: {
      href: "#"
    },
    on: {
      click: _vm.clickUpdaterButton
    }
  }, [_vm._v(_vm._s(_vm.t("updatenotification", "Open updater")))]) : _vm._e(), _vm._v(" "), _vm.downloadLink ? _c("a", {
    staticClass: "button",
    class: {
      hidden: !_vm.updaterEnabled
    },
    attrs: {
      href: _vm.downloadLink
    }
  }, [_vm._v(_vm._s(_vm.t("updatenotification", "Download now")))]) : _vm._e(), _vm._v(" "), _vm.updaterEnabled && !_vm.webUpdaterEnabled ? _c("span", [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("updatenotification", "Please use the command line updater to update.")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.whatsNew ? _c("div", {
    staticClass: "whatsNew"
  }, [_c("div", {
    staticClass: "toggleWhatsNew"
  }, [_c("a", {
    directives: [{
      name: "click-outside",
      rawName: "v-click-outside",
      value: _vm.hideMenu,
      expression: "hideMenu"
    }],
    staticClass: "button",
    on: {
      click: _vm.toggleMenu
    }
  }, [_vm._v(_vm._s(_vm.t("updatenotification", "What's new?")))]), _vm._v(" "), _c("div", {
    staticClass: "popovermenu",
    class: {
      "menu-center": true,
      open: _vm.openedWhatsNew
    }
  }, [_c("NcPopoverMenu", {
    attrs: {
      menu: _vm.whatsNew
    }
  })], 1)])]) : _vm._e()])] : !_vm.isUpdateChecked ? [_vm._v("\n\t\t\t" + _vm._s(_vm.t("updatenotification", "The update check is not yet finished. Please refresh the page.")) + "\n\t\t")] : [_vm._v("\n\t\t\t" + _vm._s(_vm.t("updatenotification", "Your version is up to date.")) + "\n\t\t\t"), _c("span", {
    staticClass: "icon-info svg",
    attrs: {
      title: _vm.lastCheckedOnString,
      "aria-label": _vm.lastCheckedOnString
    }
  })], _vm._v(" "), !_vm.isDefaultUpdateServerURL ? [_c("p", {
    staticClass: "topMargin"
  }, [_c("em", [_vm._v(_vm._s(_vm.t("updatenotification", "A non-default update server is in use to be checked for updates:")) + " "), _c("code", [_vm._v(_vm._s(_vm.updateServerURL))])])])] : _vm._e()], 2), _vm._v(" "), _c("div", [_vm._v("\n\t\t" + _vm._s(_vm.t("updatenotification", "You can change the update channel below which also affects the apps management page. E.g. after switching to the beta channel, beta app updates will be offered to you in the apps management page.")) + "\n\t")]), _vm._v(" "), _c("h3", {
    staticClass: "update-channel-selector"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("updatenotification", "Update channel:")) + "\n\t\t"), _c("div", {
    directives: [{
      name: "click-outside",
      rawName: "v-click-outside",
      value: _vm.closeUpdateChannelMenu,
      expression: "closeUpdateChannelMenu"
    }],
    staticClass: "update-menu"
  }, [_c("span", {
    staticClass: "icon-update-menu",
    on: {
      click: _vm.toggleUpdateChannelMenu
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.localizedChannelName) + "\n\t\t\t\t"), _c("span", {
    staticClass: "icon-triangle-s"
  })]), _vm._v(" "), _c("div", {
    staticClass: "popovermenu menu menu-center",
    class: {
      "show-menu": _vm.openedUpdateChannelMenu
    }
  }, [_c("NcPopoverMenu", {
    attrs: {
      menu: _vm.channelList
    }
  })], 1)])]), _vm._v(" "), _c("span", {
    staticClass: "msg",
    attrs: {
      id: "channel_save_msg"
    }
  }), _c("br"), _vm._v(" "), _c("p", [_c("em", [_vm._v(_vm._s(_vm.t("updatenotification", "You can always update to a newer version. But you can never downgrade to a more stable version.")))]), _c("br"), _vm._v(" "), _c("em", {
    domProps: {
      innerHTML: _vm._s(_vm.noteDelayedStableString)
    }
  })]), _vm._v(" "), _c("p", {
    attrs: {
      id: "oca_updatenotification_groups"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("updatenotification", "Notify members of the following groups about available updates:")) + "\n\t\t"), _c("NcMultiselect", {
    attrs: {
      options: _vm.groups,
      multiple: true,
      searchable: true,
      label: "displayname",
      loading: _vm.loadingGroups,
      "show-no-options": false,
      "close-on-select": false,
      "track-by": "id",
      "tag-width": 75
    },
    on: {
      "search-change": _vm.searchGroup
    },
    model: {
      value: _vm.notifyGroups,
      callback: function callback($$v) {
        _vm.notifyGroups = $$v;
      },
      expression: "notifyGroups"
    }
  }), _c("br"), _vm._v(" "), _vm.currentChannel === "daily" || _vm.currentChannel === "git" ? _c("em", [_vm._v(_vm._s(_vm.t("updatenotification", "Only notifications for app updates are available.")))]) : _vm._e(), _vm._v(" "), _vm.currentChannel === "daily" ? _c("em", [_vm._v(_vm._s(_vm.t("updatenotification", "The selected update channel makes dedicated notifications for the server obsolete.")))]) : _vm._e(), _vm._v(" "), _vm.currentChannel === "git" ? _c("em", [_vm._v(_vm._s(_vm.t("updatenotification", "The selected update channel does not support updates of the server.")))]) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "#updatenotification > *[data-v-82102c34] {\n  max-width: 900px;\n}\n#updatenotification div.update[data-v-82102c34],\n#updatenotification p[data-v-82102c34]:not(.inlineblock) {\n  margin-bottom: 25px;\n}\n#updatenotification h2.inlineblock[data-v-82102c34] {\n  margin-top: 25px;\n}\n#updatenotification h3[data-v-82102c34] {\n  cursor: pointer;\n}\n#updatenotification h3 .icon[data-v-82102c34] {\n  cursor: pointer;\n}\n#updatenotification h3[data-v-82102c34]:first-of-type {\n  margin-top: 0;\n}\n#updatenotification h3.update-channel-selector[data-v-82102c34] {\n  display: inline-block;\n  cursor: inherit;\n}\n#updatenotification .icon[data-v-82102c34] {\n  display: inline-block;\n  margin-bottom: -3px;\n}\n#updatenotification .icon-triangle-s[data-v-82102c34], #updatenotification .icon-triangle-n[data-v-82102c34] {\n  opacity: 0.5;\n}\n#updatenotification .whatsNew[data-v-82102c34] {\n  display: inline-block;\n}\n#updatenotification .toggleWhatsNew[data-v-82102c34] {\n  position: relative;\n}\n#updatenotification .popovermenu[data-v-82102c34] {\n  margin-top: 5px;\n  width: 300px;\n}\n#updatenotification .popovermenu p[data-v-82102c34] {\n  margin-bottom: 0;\n  width: 100%;\n}\n#updatenotification .applist[data-v-82102c34] {\n  margin-bottom: 25px;\n}\n#updatenotification .update-menu[data-v-82102c34] {\n  position: relative;\n  cursor: pointer;\n  margin-left: 3px;\n  display: inline-block;\n}\n#updatenotification .update-menu .icon-update-menu[data-v-82102c34] {\n  cursor: inherit;\n}\n#updatenotification .update-menu .icon-update-menu .icon-triangle-s[data-v-82102c34] {\n  display: inline-block;\n  vertical-align: middle;\n  cursor: inherit;\n  opacity: 1;\n}\n#updatenotification .update-menu .popovermenu[data-v-82102c34] {\n  display: none;\n  top: 28px;\n}\n#updatenotification .update-menu .popovermenu.show-menu[data-v-82102c34] {\n  display: block;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/* override needed to make menu wider */\n#updatenotification .popovermenu {\n  margin-top: 5px;\n  width: 300px;\n}\n#updatenotification .popovermenu p {\n  margin-top: 5px;\n  width: 100%;\n}\n\n/* override needed to replace yellow hover state with a dark one */\n#updatenotification .update-menu .icon-star:hover,\n#updatenotification .update-menu .icon-star:focus {\n  background-image: var(--icon-starred);\n}\n#updatenotification .topMargin {\n  margin-top: 15px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/updatenotification/src/components/UpdateNotification.vue":
/*!***********************************************************************!*\
  !*** ./apps/updatenotification/src/components/UpdateNotification.vue ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true& */ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true&");
/* harmony import */ var _UpdateNotification_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UpdateNotification.vue?vue&type=script&lang=js& */ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js&");
/* harmony import */ var _UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& */ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&");
/* harmony import */ var _UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& */ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;



/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _UpdateNotification_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "82102c34",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/updatenotification/src/components/UpdateNotification.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js&":
/*!************************************************************************************************!*\
  !*** ./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true&":
/*!******************************************************************************************************************!*\
  !*** ./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true& ***!
  \******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_template_id_82102c34_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=template&id=82102c34&scoped=true&");


/***/ }),

/***/ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************!*\
  !*** ./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_0_id_82102c34_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=0&id=82102c34&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UpdateNotification_vue_vue_type_style_index_1_id_82102c34_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/updatenotification/src/components/UpdateNotification.vue?vue&type=style&index=1&id=82102c34&lang=scss&");


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
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
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
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"updatenotification-updatenotification": 0
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
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/updatenotification/src/init.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=updatenotification-updatenotification.js.map?v=4004c3437bbb7293bbaf