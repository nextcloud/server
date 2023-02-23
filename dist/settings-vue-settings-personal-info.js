/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/settings/src/constants/AccountPropertyConstants.js":
/*!*****************************************************************!*\
  !*** ./apps/settings/src/constants/AccountPropertyConstants.js ***!
  \*****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "ACCOUNT_PROPERTY_ENUM": function() { return /* binding */ ACCOUNT_PROPERTY_ENUM; },
/* harmony export */   "ACCOUNT_PROPERTY_READABLE_ENUM": function() { return /* binding */ ACCOUNT_PROPERTY_READABLE_ENUM; },
/* harmony export */   "ACCOUNT_SETTING_PROPERTY_ENUM": function() { return /* binding */ ACCOUNT_SETTING_PROPERTY_ENUM; },
/* harmony export */   "ACCOUNT_SETTING_PROPERTY_READABLE_ENUM": function() { return /* binding */ ACCOUNT_SETTING_PROPERTY_READABLE_ENUM; },
/* harmony export */   "DEFAULT_ADDITIONAL_EMAIL_SCOPE": function() { return /* binding */ DEFAULT_ADDITIONAL_EMAIL_SCOPE; },
/* harmony export */   "NAME_READABLE_ENUM": function() { return /* binding */ NAME_READABLE_ENUM; },
/* harmony export */   "PROFILE_READABLE_ENUM": function() { return /* binding */ PROFILE_READABLE_ENUM; },
/* harmony export */   "PROPERTY_READABLE_KEYS_ENUM": function() { return /* binding */ PROPERTY_READABLE_KEYS_ENUM; },
/* harmony export */   "PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM": function() { return /* binding */ PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM; },
/* harmony export */   "SCOPE_ENUM": function() { return /* binding */ SCOPE_ENUM; },
/* harmony export */   "SCOPE_PROPERTY_ENUM": function() { return /* binding */ SCOPE_PROPERTY_ENUM; },
/* harmony export */   "SCOPE_SUFFIX": function() { return /* binding */ SCOPE_SUFFIX; },
/* harmony export */   "UNPUBLISHED_READABLE_PROPERTIES": function() { return /* binding */ UNPUBLISHED_READABLE_PROPERTIES; },
/* harmony export */   "VALIDATE_EMAIL_REGEX": function() { return /* binding */ VALIDATE_EMAIL_REGEX; },
/* harmony export */   "VERIFICATION_ENUM": function() { return /* binding */ VERIFICATION_ENUM; }
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
var _Object$freeze, _Object$freeze2, _Object$freeze3, _Object$freeze4;
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * SYNC to be kept in sync with `lib/public/Accounts/IAccountManager.php`
 */



/** Enum of account properties */
var ACCOUNT_PROPERTY_ENUM = Object.freeze({
  ADDRESS: 'address',
  AVATAR: 'avatar',
  BIOGRAPHY: 'biography',
  DISPLAYNAME: 'displayname',
  EMAIL_COLLECTION: 'additional_mail',
  EMAIL: 'email',
  HEADLINE: 'headline',
  NOTIFICATION_EMAIL: 'notify_email',
  FEDIVERSE: 'fediverse',
  ORGANISATION: 'organisation',
  PHONE: 'phone',
  PROFILE_ENABLED: 'profile_enabled',
  ROLE: 'role',
  TWITTER: 'twitter',
  WEBSITE: 'website'
});

/** Enum of account properties to human readable account property names */
var ACCOUNT_PROPERTY_READABLE_ENUM = Object.freeze({
  ADDRESS: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Location'),
  AVATAR: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Profile picture'),
  BIOGRAPHY: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'About'),
  DISPLAYNAME: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Full name'),
  EMAIL_COLLECTION: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Additional email'),
  EMAIL: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Email'),
  HEADLINE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Headline'),
  ORGANISATION: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Organisation'),
  PHONE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Phone number'),
  PROFILE_ENABLED: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Profile'),
  ROLE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Role'),
  TWITTER: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Twitter'),
  FEDIVERSE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Fediverse (e.g. Mastodon)'),
  WEBSITE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Website')
});
var NAME_READABLE_ENUM = Object.freeze((_Object$freeze = {}, _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.ADDRESS, ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.AVATAR, ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.BIOGRAPHY, ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.DISPLAYNAME, ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.EMAIL, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.HEADLINE, ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.ORGANISATION, ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.PHONE, ACCOUNT_PROPERTY_READABLE_ENUM.PHONE), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.ROLE, ACCOUNT_PROPERTY_READABLE_ENUM.ROLE), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.TWITTER, ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.FEDIVERSE, ACCOUNT_PROPERTY_READABLE_ENUM.FEDIVERSE), _defineProperty(_Object$freeze, ACCOUNT_PROPERTY_ENUM.WEBSITE, ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE), _Object$freeze));

/** Enum of profile specific sections to human readable names */
var PROFILE_READABLE_ENUM = Object.freeze({
  PROFILE_VISIBILITY: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Profile visibility')
});

/** Enum of readable account properties to account property keys used by the server */
var PROPERTY_READABLE_KEYS_ENUM = Object.freeze((_Object$freeze2 = {}, _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS, ACCOUNT_PROPERTY_ENUM.ADDRESS), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR, ACCOUNT_PROPERTY_ENUM.AVATAR), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY, ACCOUNT_PROPERTY_ENUM.BIOGRAPHY), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME, ACCOUNT_PROPERTY_ENUM.DISPLAYNAME), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION, ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL, ACCOUNT_PROPERTY_ENUM.EMAIL), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE, ACCOUNT_PROPERTY_ENUM.HEADLINE), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION, ACCOUNT_PROPERTY_ENUM.ORGANISATION), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.PHONE, ACCOUNT_PROPERTY_ENUM.PHONE), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED, ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.ROLE, ACCOUNT_PROPERTY_ENUM.ROLE), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER, ACCOUNT_PROPERTY_ENUM.TWITTER), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.FEDIVERSE, ACCOUNT_PROPERTY_ENUM.FEDIVERSE), _defineProperty(_Object$freeze2, ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE, ACCOUNT_PROPERTY_ENUM.WEBSITE), _Object$freeze2));

/**
 * Enum of account setting properties
 *
 * Account setting properties unlike account properties do not support scopes*
 */
var ACCOUNT_SETTING_PROPERTY_ENUM = Object.freeze({
  LANGUAGE: 'language',
  LOCALE: 'locale'
});

/** Enum of account setting properties to human readable setting properties */
var ACCOUNT_SETTING_PROPERTY_READABLE_ENUM = Object.freeze({
  LANGUAGE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Language'),
  LOCALE: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Locale')
});

/** Enum of scopes */
var SCOPE_ENUM = Object.freeze({
  PRIVATE: 'v2-private',
  LOCAL: 'v2-local',
  FEDERATED: 'v2-federated',
  PUBLISHED: 'v2-published'
});

/** Enum of readable account properties to supported scopes */
var PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM = Object.freeze((_Object$freeze3 = {}, _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.ADDRESS, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.AVATAR, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME, [SCOPE_ENUM.LOCAL]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL_COLLECTION, [SCOPE_ENUM.LOCAL]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL, [SCOPE_ENUM.LOCAL]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.PHONE, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.ROLE, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.TWITTER, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.FEDIVERSE, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _defineProperty(_Object$freeze3, ACCOUNT_PROPERTY_READABLE_ENUM.WEBSITE, [SCOPE_ENUM.LOCAL, SCOPE_ENUM.PRIVATE]), _Object$freeze3));

/** List of readable account properties which aren't published to the lookup server */
var UNPUBLISHED_READABLE_PROPERTIES = Object.freeze([ACCOUNT_PROPERTY_READABLE_ENUM.BIOGRAPHY, ACCOUNT_PROPERTY_READABLE_ENUM.HEADLINE, ACCOUNT_PROPERTY_READABLE_ENUM.ORGANISATION, ACCOUNT_PROPERTY_READABLE_ENUM.ROLE]);

/** Scope suffix */
var SCOPE_SUFFIX = 'Scope';

/**
 * Enum of scope names to properties
 *
 * Used for federation control*
 */
var SCOPE_PROPERTY_ENUM = Object.freeze((_Object$freeze4 = {}, _defineProperty(_Object$freeze4, SCOPE_ENUM.PRIVATE, {
  name: SCOPE_ENUM.PRIVATE,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Private'),
  tooltip: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Only visible to people matched via phone number integration through Talk on mobile'),
  tooltipDisabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Not available as this property is required for core functionality including file sharing and calendar invitations'),
  iconClass: 'icon-phone'
}), _defineProperty(_Object$freeze4, SCOPE_ENUM.LOCAL, {
  name: SCOPE_ENUM.LOCAL,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Local'),
  tooltip: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Only visible to people on this instance and guests'),
  // tooltipDisabled is not required here as this scope is supported by all account properties
  iconClass: 'icon-password'
}), _defineProperty(_Object$freeze4, SCOPE_ENUM.FEDERATED, {
  name: SCOPE_ENUM.FEDERATED,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Federated'),
  tooltip: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Only synchronize to trusted servers'),
  tooltipDisabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Not available as federation has been disabled for your account, contact your system administrator if you have any questions'),
  iconClass: 'icon-contacts-dark'
}), _defineProperty(_Object$freeze4, SCOPE_ENUM.PUBLISHED, {
  name: SCOPE_ENUM.PUBLISHED,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Published'),
  tooltip: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Synchronize to trusted servers and the global and public address book'),
  tooltipDisabled: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('settings', 'Not available as publishing user specific data to the lookup server is not allowed, contact your system administrator if you have any questions'),
  iconClass: 'icon-link'
}), _Object$freeze4));

/** Default additional email scope */
var DEFAULT_ADDITIONAL_EMAIL_SCOPE = SCOPE_ENUM.LOCAL;

/** Enum of verification constants, according to IAccountManager */
var VERIFICATION_ENUM = Object.freeze({
  NOT_VERIFIED: 0,
  VERIFICATION_IN_PROGRESS: 1,
  VERIFIED: 2
});

/**
 * Email validation regex
 *
 * Sourced from https://github.com/mpyw/FILTER_VALIDATE_EMAIL.js/blob/71e62ca48841d2246a1b531e7e84f5a01f15e615/src/regexp/ascii.ts*
 */
// eslint-disable-next-line no-control-regex
var VALIDATE_EMAIL_REGEX = /^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/i;

/***/ }),

/***/ "./apps/settings/src/constants/ProfileConstants.js":
/*!*********************************************************!*\
  !*** ./apps/settings/src/constants/ProfileConstants.js ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "VISIBILITY_ENUM": function() { return /* binding */ VISIBILITY_ENUM; },
/* harmony export */   "VISIBILITY_PROPERTY_ENUM": function() { return /* binding */ VISIBILITY_PROPERTY_ENUM; }
/* harmony export */ });
var _Object$freeze;
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
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

/*
 * SYNC to be kept in sync with `core/Db/ProfileConfig.php`
 */

/** Enum of profile visibility constants */
var VISIBILITY_ENUM = Object.freeze({
  SHOW: 'show',
  SHOW_USERS_ONLY: 'show_users_only',
  HIDE: 'hide'
});

/**
 * Enum of profile visibility constants to properties
 */
var VISIBILITY_PROPERTY_ENUM = Object.freeze((_Object$freeze = {}, _defineProperty(_Object$freeze, VISIBILITY_ENUM.SHOW, {
  name: VISIBILITY_ENUM.SHOW,
  label: t('settings', 'Show to everyone')
}), _defineProperty(_Object$freeze, VISIBILITY_ENUM.SHOW_USERS_ONLY, {
  name: VISIBILITY_ENUM.SHOW_USERS_ONLY,
  label: t('settings', 'Show to logged in users only')
}), _defineProperty(_Object$freeze, VISIBILITY_ENUM.HIDE, {
  name: VISIBILITY_ENUM.HIDE,
  label: t('settings', 'Hide')
}), _Object$freeze));

/***/ }),

/***/ "./apps/settings/src/logger.js":
/*!*************************************!*\
  !*** ./apps/settings/src/logger.js ***!
  \*************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.js");
/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
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


/* harmony default export */ __webpack_exports__["default"] = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('settings').detectUser().build());

/***/ }),

/***/ "./apps/settings/src/main-personal-info.js":
/*!*************************************************!*\
  !*** ./apps/settings/src/main-personal-info.js ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs_dist_index_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs/dist/index.css */ "./node_modules/@nextcloud/dialogs/dist/index.css");
/* harmony import */ var _components_PersonalInfo_AvatarSection_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/PersonalInfo/AvatarSection.vue */ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue");
/* harmony import */ var _components_PersonalInfo_DetailsSection_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/PersonalInfo/DetailsSection.vue */ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue");
/* harmony import */ var _components_PersonalInfo_DisplayNameSection_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/PersonalInfo/DisplayNameSection.vue */ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue");
/* harmony import */ var _components_PersonalInfo_EmailSection_EmailSection_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/PersonalInfo/EmailSection/EmailSection.vue */ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue");
/* harmony import */ var _components_PersonalInfo_PhoneSection_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./components/PersonalInfo/PhoneSection.vue */ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue");
/* harmony import */ var _components_PersonalInfo_LocationSection_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/PersonalInfo/LocationSection.vue */ "./apps/settings/src/components/PersonalInfo/LocationSection.vue");
/* harmony import */ var _components_PersonalInfo_WebsiteSection_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./components/PersonalInfo/WebsiteSection.vue */ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue");
/* harmony import */ var _components_PersonalInfo_TwitterSection_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./components/PersonalInfo/TwitterSection.vue */ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue");
/* harmony import */ var _components_PersonalInfo_FediverseSection_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./components/PersonalInfo/FediverseSection.vue */ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue");
/* harmony import */ var _components_PersonalInfo_LanguageSection_LanguageSection_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./components/PersonalInfo/LanguageSection/LanguageSection.vue */ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue");
/* harmony import */ var _components_PersonalInfo_LocaleSection_LocaleSection_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./components/PersonalInfo/LocaleSection/LocaleSection.vue */ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue");
/* harmony import */ var _components_PersonalInfo_ProfileSection_ProfileSection_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./components/PersonalInfo/ProfileSection/ProfileSection.vue */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue");
/* harmony import */ var _components_PersonalInfo_OrganisationSection_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./components/PersonalInfo/OrganisationSection.vue */ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue");
/* harmony import */ var _components_PersonalInfo_RoleSection_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./components/PersonalInfo/RoleSection.vue */ "./apps/settings/src/components/PersonalInfo/RoleSection.vue");
/* harmony import */ var _components_PersonalInfo_HeadlineSection_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./components/PersonalInfo/HeadlineSection.vue */ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue");
/* harmony import */ var _components_PersonalInfo_BiographySection_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./components/PersonalInfo/BiographySection.vue */ "./apps/settings/src/components/PersonalInfo/BiographySection.vue");
/* harmony import */ var _components_PersonalInfo_ProfileVisibilitySection_ProfileVisibilitySection_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue");
/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */























__webpack_require__.nc = btoa((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getRequestToken)());
var profileEnabledGlobally = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'profileEnabledGlobally', true);
vue__WEBPACK_IMPORTED_MODULE_21__["default"].mixin({
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate
  }
});
var AvatarView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_AvatarSection_vue__WEBPACK_IMPORTED_MODULE_4__["default"]);
var DetailsView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_DetailsSection_vue__WEBPACK_IMPORTED_MODULE_5__["default"]);
var DisplayNameView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_DisplayNameSection_vue__WEBPACK_IMPORTED_MODULE_6__["default"]);
var EmailView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_EmailSection_EmailSection_vue__WEBPACK_IMPORTED_MODULE_7__["default"]);
var PhoneView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_PhoneSection_vue__WEBPACK_IMPORTED_MODULE_8__["default"]);
var LocationView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_LocationSection_vue__WEBPACK_IMPORTED_MODULE_9__["default"]);
var WebsiteView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_WebsiteSection_vue__WEBPACK_IMPORTED_MODULE_10__["default"]);
var TwitterView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_TwitterSection_vue__WEBPACK_IMPORTED_MODULE_11__["default"]);
var FediverseView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_FediverseSection_vue__WEBPACK_IMPORTED_MODULE_12__["default"]);
var LanguageView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_LanguageSection_LanguageSection_vue__WEBPACK_IMPORTED_MODULE_13__["default"]);
var LocaleView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_LocaleSection_LocaleSection_vue__WEBPACK_IMPORTED_MODULE_14__["default"]);
new AvatarView().$mount('#vue-avatar-section');
new DetailsView().$mount('#vue-details-section');
new DisplayNameView().$mount('#vue-displayname-section');
new EmailView().$mount('#vue-email-section');
new PhoneView().$mount('#vue-phone-section');
new LocationView().$mount('#vue-location-section');
new WebsiteView().$mount('#vue-website-section');
new TwitterView().$mount('#vue-twitter-section');
new FediverseView().$mount('#vue-fediverse-section');
new LanguageView().$mount('#vue-language-section');
new LocaleView().$mount('#vue-locale-section');
if (profileEnabledGlobally) {
  var ProfileView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_ProfileSection_ProfileSection_vue__WEBPACK_IMPORTED_MODULE_15__["default"]);
  var OrganisationView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_OrganisationSection_vue__WEBPACK_IMPORTED_MODULE_16__["default"]);
  var RoleView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_RoleSection_vue__WEBPACK_IMPORTED_MODULE_17__["default"]);
  var HeadlineView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_HeadlineSection_vue__WEBPACK_IMPORTED_MODULE_18__["default"]);
  var BiographyView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_BiographySection_vue__WEBPACK_IMPORTED_MODULE_19__["default"]);
  var ProfileVisibilityView = vue__WEBPACK_IMPORTED_MODULE_21__["default"].extend(_components_PersonalInfo_ProfileVisibilitySection_ProfileVisibilitySection_vue__WEBPACK_IMPORTED_MODULE_20__["default"]);
  new ProfileView().$mount('#vue-profile-section');
  new OrganisationView().$mount('#vue-organisation-section');
  new RoleView().$mount('#vue-role-section');
  new HeadlineView().$mount('#vue-headline-section');
  new BiographyView().$mount('#vue-biography-section');
  new ProfileVisibilityView().$mount('#vue-profile-visibility-section');
}

/***/ }),

/***/ "./apps/settings/src/service/PersonalInfo/EmailService.js":
/*!****************************************************************!*\
  !*** ./apps/settings/src/service/PersonalInfo/EmailService.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "removeAdditionalEmail": function() { return /* binding */ removeAdditionalEmail; },
/* harmony export */   "saveAdditionalEmail": function() { return /* binding */ saveAdditionalEmail; },
/* harmony export */   "saveAdditionalEmailScope": function() { return /* binding */ saveAdditionalEmailScope; },
/* harmony export */   "saveNotificationEmail": function() { return /* binding */ saveNotificationEmail; },
/* harmony export */   "savePrimaryEmail": function() { return /* binding */ savePrimaryEmail; },
/* harmony export */   "savePrimaryEmailScope": function() { return /* binding */ savePrimaryEmailScope; },
/* harmony export */   "updateAdditionalEmail": function() { return /* binding */ updateAdditionalEmail; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/main.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
/* harmony import */ var _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */








/**
 * Save the primary email of the user
 *
 * @param {string} email the primary email
 * @return {object}
 */
var savePrimaryEmail = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(email) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL,
              value: email
            });
          case 6:
            res = _context.sent;
            return _context.abrupt("return", res.data);
          case 8:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function savePrimaryEmail(_x) {
    return _ref.apply(this, arguments);
  };
}();

/**
 * Save an additional email of the user
 *
 * Will be appended to the user's additional emails*
 *
 * @param {string} email the additional email
 * @return {object}
 */
var saveAdditionalEmail = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(email) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context2.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context2.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION,
              value: email
            });
          case 6:
            res = _context2.sent;
            return _context2.abrupt("return", res.data);
          case 8:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return function saveAdditionalEmail(_x2) {
    return _ref2.apply(this, arguments);
  };
}();

/**
 * Save the notification email of the user
 *
 * @param {string} email the notification email
 * @return {object}
 */
var saveNotificationEmail = /*#__PURE__*/function () {
  var _ref3 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3(email) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee3$(_context3) {
      while (1) {
        switch (_context3.prev = _context3.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context3.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context3.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.NOTIFICATION_EMAIL,
              value: email
            });
          case 6:
            res = _context3.sent;
            return _context3.abrupt("return", res.data);
          case 8:
          case "end":
            return _context3.stop();
        }
      }
    }, _callee3);
  }));
  return function saveNotificationEmail(_x3) {
    return _ref3.apply(this, arguments);
  };
}();

/**
 * Remove an additional email of the user
 *
 * @param {string} email the additional email
 * @return {object}
 */
var removeAdditionalEmail = /*#__PURE__*/function () {
  var _ref4 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4(email) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee4$(_context4) {
      while (1) {
        switch (_context4.prev = _context4.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}/{collection}', {
              userId: userId,
              collection: _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION
            });
            _context4.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context4.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: email,
              value: ''
            });
          case 6:
            res = _context4.sent;
            return _context4.abrupt("return", res.data);
          case 8:
          case "end":
            return _context4.stop();
        }
      }
    }, _callee4);
  }));
  return function removeAdditionalEmail(_x4) {
    return _ref4.apply(this, arguments);
  };
}();

/**
 * Update an additional email of the user
 *
 * @param {string} prevEmail the additional email to be updated
 * @param {string} newEmail the new additional email
 * @return {object}
 */
var updateAdditionalEmail = /*#__PURE__*/function () {
  var _ref5 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5(prevEmail, newEmail) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee5$(_context5) {
      while (1) {
        switch (_context5.prev = _context5.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}/{collection}', {
              userId: userId,
              collection: _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION
            });
            _context5.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context5.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: prevEmail,
              value: newEmail
            });
          case 6:
            res = _context5.sent;
            return _context5.abrupt("return", res.data);
          case 8:
          case "end":
            return _context5.stop();
        }
      }
    }, _callee5);
  }));
  return function updateAdditionalEmail(_x5, _x6) {
    return _ref5.apply(this, arguments);
  };
}();

/**
 * Save the federation scope for the primary email of the user
 *
 * @param {string} scope the federation scope
 * @return {object}
 */
var savePrimaryEmailScope = /*#__PURE__*/function () {
  var _ref6 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6(scope) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee6$(_context6) {
      while (1) {
        switch (_context6.prev = _context6.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context6.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context6.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: "".concat(_constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL).concat(_constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.SCOPE_SUFFIX),
              value: scope
            });
          case 6:
            res = _context6.sent;
            return _context6.abrupt("return", res.data);
          case 8:
          case "end":
            return _context6.stop();
        }
      }
    }, _callee6);
  }));
  return function savePrimaryEmailScope(_x7) {
    return _ref6.apply(this, arguments);
  };
}();

/**
 * Save the federation scope for the additional email of the user
 *
 * @param {string} email the additional email
 * @param {string} scope the federation scope
 * @return {object}
 */
var saveAdditionalEmailScope = /*#__PURE__*/function () {
  var _ref7 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee7(email, scope) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee7$(_context7) {
      while (1) {
        switch (_context7.prev = _context7.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}/{collectionScope}', {
              userId: userId,
              collectionScope: "".concat(_constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION).concat(_constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.SCOPE_SUFFIX)
            });
            _context7.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context7.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: email,
              value: scope
            });
          case 6:
            res = _context7.sent;
            return _context7.abrupt("return", res.data);
          case 8:
          case "end":
            return _context7.stop();
        }
      }
    }, _callee7);
  }));
  return function saveAdditionalEmailScope(_x8, _x9) {
    return _ref7.apply(this, arguments);
  };
}();

/***/ }),

/***/ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js":
/*!***********************************************************************!*\
  !*** ./apps/settings/src/service/PersonalInfo/PersonalInfoService.js ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "savePrimaryAccountProperty": function() { return /* binding */ savePrimaryAccountProperty; },
/* harmony export */   "savePrimaryAccountPropertyScope": function() { return /* binding */ savePrimaryAccountPropertyScope; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/main.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
/* harmony import */ var _constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */








/**
 * Save the primary account property value for the user
 *
 * @param {string} accountProperty the account property
 * @param {string|boolean} value the primary value
 * @return {object}
 */
var savePrimaryAccountProperty = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(accountProperty, value) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            // TODO allow boolean values on backend route handler
            // Convert boolean to string for compatibility
            if (typeof value === 'boolean') {
              value = value ? '1' : '0';
            }
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context.next = 5;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 5:
            _context.next = 7;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: accountProperty,
              value: value
            });
          case 7:
            res = _context.sent;
            return _context.abrupt("return", res.data);
          case 9:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function savePrimaryAccountProperty(_x, _x2) {
    return _ref.apply(this, arguments);
  };
}();

/**
 * Save the federation scope of the primary account property for the user
 *
 * @param {string} accountProperty the account property
 * @param {string} scope the federation scope
 * @return {object}
 */
var savePrimaryAccountPropertyScope = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(accountProperty, scope) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('cloud/users/{userId}', {
              userId: userId
            });
            _context2.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context2.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              key: "".concat(accountProperty).concat(_constants_AccountPropertyConstants__WEBPACK_IMPORTED_MODULE_5__.SCOPE_SUFFIX),
              value: scope
            });
          case 6:
            res = _context2.sent;
            return _context2.abrupt("return", res.data);
          case 8:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return function savePrimaryAccountPropertyScope(_x3, _x4) {
    return _ref2.apply(this, arguments);
  };
}();

/***/ }),

/***/ "./apps/settings/src/service/ProfileService.js":
/*!*****************************************************!*\
  !*** ./apps/settings/src/service/ProfileService.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "saveProfileDefault": function() { return /* binding */ saveProfileDefault; },
/* harmony export */   "saveProfileParameterVisibility": function() { return /* binding */ saveProfileParameterVisibility; }
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/main.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
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







/**
 * Save the visibility of the profile parameter
 *
 * @param {string} paramId the profile parameter ID
 * @param {string} visibility the visibility
 * @return {object}
 */
var saveProfileParameterVisibility = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(paramId, visibility) {
    var userId, url, res;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            userId = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('/profile/{userId}', {
              userId: userId
            });
            _context.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
              paramId: paramId,
              visibility: visibility
            });
          case 6:
            res = _context.sent;
            return _context.abrupt("return", res.data);
          case 8:
          case "end":
            return _context.stop();
        }
      }
    }, _callee);
  }));
  return function saveProfileParameterVisibility(_x, _x2) {
    return _ref.apply(this, arguments);
  };
}();

/**
 * Save profile default
 *
 * @param {boolean} isEnabled the default
 * @return {object}
 */
var saveProfileDefault = /*#__PURE__*/function () {
  var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(isEnabled) {
    var url, res;
    return regeneratorRuntime.wrap(function _callee2$(_context2) {
      while (1) {
        switch (_context2.prev = _context2.next) {
          case 0:
            // Convert to string for compatibility
            isEnabled = isEnabled ? '1' : '0';
            url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
              appId: 'settings',
              key: 'profile_enabled_by_default'
            });
            _context2.next = 4;
            return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_3__.confirmPassword)();
          case 4:
            _context2.next = 6;
            return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
              value: isEnabled
            });
          case 6:
            res = _context2.sent;
            return _context2.abrupt("return", res.data);
          case 8:
          case "end":
            return _context2.stop();
        }
      }
    }, _callee2);
  }));
  return function saveProfileDefault(_x3) {
    return _ref2.apply(this, arguments);
  };
}();

/***/ }),

/***/ "./apps/settings/src/utils/validate.js":
/*!*********************************************!*\
  !*** ./apps/settings/src/utils/validate.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "validateBoolean": function() { return /* binding */ validateBoolean; },
/* harmony export */   "validateEmail": function() { return /* binding */ validateEmail; },
/* harmony export */   "validateLanguage": function() { return /* binding */ validateLanguage; },
/* harmony export */   "validateLocale": function() { return /* binding */ validateLocale; },
/* harmony export */   "validateUrl": function() { return /* binding */ validateUrl; }
/* harmony export */ });
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * Frontend validators, less strict than backend validators
 *
 * TODO add nice validation errors for Profile page settings modal
 */



/**
 * Validate the email input
 *
 * Compliant with PHP core FILTER_VALIDATE_EMAIL validator*
 *
 * Reference implementation https://github.com/mpyw/FILTER_VALIDATE_EMAIL.js/blob/71e62ca48841d2246a1b531e7e84f5a01f15e615/src/index.ts*
 *
 * @param {string} input the input
 * @return {boolean}
 */
function validateEmail(input) {
  return typeof input === 'string' && _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_0__.VALIDATE_EMAIL_REGEX.test(input) && input.slice(-1) !== '\n' && input.length <= 320 && encodeURIComponent(input).replace(/%../g, 'x').length <= 320;
}

/**
 * Validate the URL input
 *
 * @param {string} input the input
 * @return {boolean}
 */
function validateUrl(input) {
  try {
    // eslint-disable-next-line no-new
    new URL(input);
    return true;
  } catch (e) {
    return false;
  }
}

/**
 * Validate the language input
 *
 * @param {object} input the input
 * @return {boolean}
 */
function validateLanguage(input) {
  return input.code !== '' && input.name !== '' && input.name !== undefined;
}

/**
 * Validate the locale input
 *
 * @param {object} input the input
 * @return {boolean}
 */
function validateLocale(input) {
  return input.code !== '' && input.name !== '' && input.name !== undefined;
}

/**
 * Validate boolean input
 *
 * @param {boolean} input the input
 * @return {boolean}
 */
function validateBoolean(input) {
  return typeof input === 'boolean';
}

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var vue_cropperjs__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-cropperjs */ "./node_modules/vue-cropperjs/dist/VueCropper.js");
/* harmony import */ var cropperjs_dist_cropper_css__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! cropperjs/dist/cropper.css */ "./node_modules/cropperjs/dist/cropper.css");
/* harmony import */ var vue_material_design_icons_Upload__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Upload */ "./node_modules/vue-material-design-icons/Upload.vue");
/* harmony import */ var vue_material_design_icons_Folder__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Folder */ "./node_modules/vue-material-design-icons/Folder.vue");
/* harmony import */ var vue_material_design_icons_Delete__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Delete */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }









// eslint-disable-next-line node/no-extraneous-import






var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'personalInfoParameters', {}),
  avatar = _loadState.avatar;
var _loadState2 = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'accountParameters', {}),
  avatarChangeSupported = _loadState2.avatarChangeSupported;
var VALID_MIME_TYPES = ['image/png', 'image/jpeg'];
var picker = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.getFilePickerBuilder)(t('settings', 'Choose your profile picture')).setMultiSelect(false).setMimeTypeFilter(VALID_MIME_TYPES).setModal(true).setType(1).allowDirectories(false).build();
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AvatarSection',
  components: {
    Delete: vue_material_design_icons_Delete__WEBPACK_IMPORTED_MODULE_12__["default"],
    Folder: vue_material_design_icons_Folder__WEBPACK_IMPORTED_MODULE_11__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_6___default()),
    NcButton: (_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_7___default()),
    Upload: vue_material_design_icons_Upload__WEBPACK_IMPORTED_MODULE_10__["default"],
    VueCropper: vue_cropperjs__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  data: function data() {
    return {
      avatar: _objectSpread(_objectSpread({}, avatar), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_14__.NAME_READABLE_ENUM[avatar.name]
      }),
      avatarChangeSupported: avatarChangeSupported,
      showCropper: false,
      loading: false,
      userId: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__.getCurrentUser)().uid,
      displayName: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_3__.getCurrentUser)().displayName,
      version: oc_userconfig.avatar.version,
      isGenerated: oc_userconfig.avatar.generated,
      validMimeTypes: VALID_MIME_TYPES,
      cropperOptions: {
        aspectRatio: 1 / 1,
        viewMode: 1,
        guides: false,
        center: false,
        highlight: false,
        autoCropArea: 1,
        minContainerWidth: 300,
        minContainerHeight: 300
      }
    };
  },
  computed: {
    inputId: function inputId() {
      return "account-property-".concat(this.avatar.name);
    }
  },
  created: function created() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.subscribe)('settings:display-name:updated', this.handleDisplayNameUpdate);
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.unsubscribe)('settings:display-name:updated', this.handleDisplayNameUpdate);
  },
  methods: {
    activateLocalFilePicker: function activateLocalFilePicker() {
      // Set to null so that selecting the same file will trigger the change event
      this.$refs.input.value = null;
      this.$refs.input.click();
    },
    onChange: function onChange(e) {
      var _this = this;
      this.loading = true;
      var file = e.target.files[0];
      if (!this.validMimeTypes.includes(file.type)) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Please select a valid png or jpg file'));
        this.cancel();
        return;
      }
      var reader = new FileReader();
      reader.onload = function (e) {
        _this.$refs.cropper.replace(e.target.result);
        _this.showCropper = true;
      };
      reader.readAsDataURL(file);
    },
    openFilePicker: function openFilePicker() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var path, _yield$axios$post, data, tempAvatar;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _context.next = 2;
                return picker.pick();
              case 2:
                path = _context.sent;
                _this2.loading = true;
                _context.prev = 4;
                _context.next = 7;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/avatar'), {
                  path: path
                });
              case 7:
                _yield$axios$post = _context.sent;
                data = _yield$axios$post.data;
                if (data.status === 'success') {
                  _this2.handleAvatarUpdate(false);
                } else if (data.data === 'notsquare') {
                  tempAvatar = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/avatar/tmp') + '?requesttoken=' + encodeURIComponent(OC.requestToken) + '#' + Math.floor(Math.random() * 1000);
                  _this2.$refs.cropper.replace(tempAvatar);
                  _this2.showCropper = true;
                } else {
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(data.data.message);
                  _this2.cancel();
                }
                _context.next = 16;
                break;
              case 12:
                _context.prev = 12;
                _context.t0 = _context["catch"](4);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Error setting profile picture'));
                _this2.cancel();
              case 16:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[4, 12]]);
      }))();
    },
    saveAvatar: function saveAvatar() {
      var _this3 = this;
      this.showCropper = false;
      this.loading = true;
      this.$refs.cropper.getCroppedCanvas().toBlob( /*#__PURE__*/function () {
        var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(blob) {
          var formData;
          return regeneratorRuntime.wrap(function _callee2$(_context2) {
            while (1) {
              switch (_context2.prev = _context2.next) {
                case 0:
                  if (!(blob === null)) {
                    _context2.next = 4;
                    break;
                  }
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Error cropping profile picture'));
                  _this3.cancel();
                  return _context2.abrupt("return");
                case 4:
                  formData = new FormData();
                  formData.append('files[]', blob);
                  _context2.prev = 6;
                  _context2.next = 9;
                  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/avatar'), formData);
                case 9:
                  _this3.handleAvatarUpdate(false);
                  _context2.next = 16;
                  break;
                case 12:
                  _context2.prev = 12;
                  _context2.t0 = _context2["catch"](6);
                  (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Error saving profile picture'));
                  _this3.handleAvatarUpdate(_this3.isGenerated);
                case 16:
                case "end":
                  return _context2.stop();
              }
            }
          }, _callee2, null, [[6, 12]]);
        }));
        return function (_x) {
          return _ref.apply(this, arguments);
        };
      }());
    },
    removeAvatar: function removeAvatar() {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _this4.loading = true;
                _context3.prev = 1;
                _context3.next = 4;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"]["delete"]((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/avatar'));
              case 4:
                _this4.handleAvatarUpdate(true);
                _context3.next = 11;
                break;
              case 7:
                _context3.prev = 7;
                _context3.t0 = _context3["catch"](1);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(t('settings', 'Error removing profile picture'));
                _this4.handleAvatarUpdate(_this4.isGenerated);
              case 11:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[1, 7]]);
      }))();
    },
    cancel: function cancel() {
      this.showCropper = false;
      this.loading = false;
    },
    handleAvatarUpdate: function handleAvatarUpdate(isGenerated) {
      // Update the avatar version so that avatar update handlers refresh correctly
      this.version = oc_userconfig.avatar.version = Date.now();
      this.isGenerated = oc_userconfig.avatar.generated = isGenerated;
      this.loading = false;
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_5__.emit)('settings:avatar:updated', oc_userconfig.avatar.version);
    },
    handleDisplayNameUpdate: function handleDisplayNameUpdate() {
      this.version = oc_userconfig.avatar.version;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  biography = _loadState.biography;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'BiographySection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      biography: _objectSpread(_objectSpread({}, biography), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[biography.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcProgressBar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcProgressBar */ "./node_modules/@nextcloud/vue/dist/Components/NcProgressBar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcProgressBar__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcProgressBar__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue_material_design_icons_Account__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Account */ "./node_modules/vue-material-design-icons/Account.vue");
/* harmony import */ var vue_material_design_icons_CircleSlice3__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/CircleSlice3 */ "./node_modules/vue-material-design-icons/CircleSlice3.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");






/** SYNC to be kept in sync with `lib/public/Files/FileInfo.php` */
var SPACE_UNLIMITED = -3;
var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  groups = _loadState.groups,
  quota = _loadState.quota,
  totalSpace = _loadState.totalSpace,
  usage = _loadState.usage,
  usageRelative = _loadState.usageRelative;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'DetailsSection',
  components: {
    Account: vue_material_design_icons_Account__WEBPACK_IMPORTED_MODULE_2__["default"],
    CircleSlice: vue_material_design_icons_CircleSlice3__WEBPACK_IMPORTED_MODULE_3__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcProgressBar: (_nextcloud_vue_dist_Components_NcProgressBar__WEBPACK_IMPORTED_MODULE_1___default())
  },
  data: function data() {
    return {
      groups: groups,
      usageRelative: usageRelative
    };
  },
  computed: {
    quotaText: function quotaText() {
      if (quota === SPACE_UNLIMITED) {
        return t('settings', 'You are using <strong>{usage}</strong>', {
          usage: usage
        });
      }
      return t('settings', 'You are using <strong>{usage}</strong> of <strong>{totalSpace}</strong> (<strong>{usageRelative}%</strong>)', {
        usage: usage,
        totalSpace: totalSpace,
        usageRelative: usageRelative
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }




var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  displayName = _loadState.displayName;
var _loadState2 = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'accountParameters', {}),
  displayNameChangeSupported = _loadState2.displayNameChangeSupported;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'DisplayNameSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data: function data() {
    return {
      displayName: _objectSpread(_objectSpread({}, displayName), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.NAME_READABLE_ENUM[displayName.name]
      }),
      displayNameChangeSupported: displayNameChangeSupported
    };
  },
  methods: {
    onValidate: function onValidate(value) {
      return value !== '';
    },
    onSave: function onSave(value) {
      if (oc_userconfig.avatar.generated) {
        // Update the avatar version so that avatar update handlers refresh correctly
        oc_userconfig.avatar.version = Date.now();
      }
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('settings:display-name:updated', value);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/AlertCircleOutline.vue */ "./node_modules/vue-material-design-icons/AlertCircleOutline.vue");
/* harmony import */ var vue_material_design_icons_AlertOctagon_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/AlertOctagon.vue */ "./node_modules/vue-material-design-icons/AlertOctagon.vue");
/* harmony import */ var vue_material_design_icons_Check__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Check */ "./node_modules/vue-material-design-icons/Check.vue");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _shared_FederationControl_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../shared/FederationControl.vue */ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../../service/PersonalInfo/EmailService.js */ "./apps/settings/src/service/PersonalInfo/EmailService.js");
/* harmony import */ var _utils_validate_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../../utils/validate.js */ "./apps/settings/src/utils/validate.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }











/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Email',
  components: {
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActionButton,
    AlertCircle: vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    AlertOctagon: vue_material_design_icons_AlertOctagon_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    Check: vue_material_design_icons_Check__WEBPACK_IMPORTED_MODULE_3__["default"],
    FederationControl: _shared_FederationControl_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  props: {
    email: {
      type: String,
      required: true
    },
    index: {
      type: Number,
      default: 0
    },
    primary: {
      type: Boolean,
      default: false
    },
    scope: {
      type: String,
      required: true
    },
    activeNotificationEmail: {
      type: String,
      default: ''
    },
    localVerificationState: {
      type: Number,
      default: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_8__.VERIFICATION_ENUM.NOT_VERIFIED
    }
  },
  data: function data() {
    return {
      propertyReadable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_8__.ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
      initialEmail: this.email,
      localScope: this.scope,
      saveAdditionalEmailScope: _service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.saveAdditionalEmailScope,
      helperText: null,
      showCheckmarkIcon: false,
      showErrorIcon: false
    };
  },
  computed: {
    deleteDisabled: function deleteDisabled() {
      if (this.primary) {
        // Disable for empty primary email as there is nothing to delete
        // OR when initialEmail (reflects server state) and email (current input) are not the same
        return this.email === '' || this.initialEmail !== this.email;
      } else if (this.initialEmail !== '') {
        return this.initialEmail !== this.email;
      }
      return false;
    },
    deleteEmailLabel: function deleteEmailLabel() {
      if (this.primary) {
        return t('settings', 'Remove primary email');
      }
      return t('settings', 'Delete email');
    },
    setNotificationMailDisabled: function setNotificationMailDisabled() {
      return !this.primary && this.localVerificationState !== _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_8__.VERIFICATION_ENUM.VERIFIED;
    },
    setNotificationMailLabel: function setNotificationMailLabel() {
      if (this.isNotificationEmail) {
        return t('settings', 'Unset as primary email');
      } else if (!this.primary && this.localVerificationState !== _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_8__.VERIFICATION_ENUM.VERIFIED) {
        return t('settings', 'This address is not confirmed');
      }
      return t('settings', 'Set as primary email');
    },
    federationDisabled: function federationDisabled() {
      return !this.initialEmail;
    },
    inputId: function inputId() {
      if (this.primary) {
        return 'email';
      }
      return "email-".concat(this.index);
    },
    inputPlaceholder: function inputPlaceholder() {
      if (this.primary) {
        return t('settings', 'Your email address');
      }
      return t('settings', 'Additional email address {index}', {
        index: this.index + 1
      });
    },
    isNotificationEmail: function isNotificationEmail() {
      return this.email && this.email === this.activeNotificationEmail || this.primary && this.activeNotificationEmail === '';
    }
  },
  mounted: function mounted() {
    var _this = this;
    if (!this.primary && this.initialEmail === '') {
      // $nextTick is needed here, otherwise it may not always work https://stackoverflow.com/questions/51922767/autofocus-input-on-mount-vue-ios/63485725#63485725
      this.$nextTick(function () {
        var _this$$refs$email;
        return (_this$$refs$email = _this.$refs.email) === null || _this$$refs$email === void 0 ? void 0 : _this$$refs$email.focus();
      });
    }
  },
  methods: {
    onEmailChange: function onEmailChange(e) {
      this.$emit('update:email', e.target.value);
      this.debounceEmailChange(e.target.value.trim());
    },
    debounceEmailChange: debounce__WEBPACK_IMPORTED_MODULE_5___default()( /*#__PURE__*/function () {
      var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(email) {
        var _this$$refs$email2;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                this.helperText = null;
                if (!((_this$$refs$email2 = this.$refs.email) !== null && _this$$refs$email2 !== void 0 && _this$$refs$email2.validationMessage)) {
                  _context.next = 4;
                  break;
                }
                this.helperText = this.$refs.email.validationMessage;
                return _context.abrupt("return");
              case 4:
                if (!((0,_utils_validate_js__WEBPACK_IMPORTED_MODULE_10__.validateEmail)(email) || email === '')) {
                  _context.next = 18;
                  break;
                }
                if (!this.primary) {
                  _context.next = 10;
                  break;
                }
                _context.next = 8;
                return this.updatePrimaryEmail(email);
              case 8:
                _context.next = 18;
                break;
              case 10:
                if (!email) {
                  _context.next = 18;
                  break;
                }
                if (!(this.initialEmail === '')) {
                  _context.next = 16;
                  break;
                }
                _context.next = 14;
                return this.addAdditionalEmail(email);
              case 14:
                _context.next = 18;
                break;
              case 16:
                _context.next = 18;
                return this.updateAdditionalEmail(email);
              case 18:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));
      return function (_x) {
        return _ref.apply(this, arguments);
      };
    }(), 500),
    deleteEmail: function deleteEmail() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                if (!_this2.primary) {
                  _context2.next = 6;
                  break;
                }
                _this2.$emit('update:email', '');
                _context2.next = 4;
                return _this2.updatePrimaryEmail('');
              case 4:
                _context2.next = 8;
                break;
              case 6:
                _context2.next = 8;
                return _this2.deleteAdditionalEmail();
              case 8:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2);
      }))();
    },
    updatePrimaryEmail: function updatePrimaryEmail(email) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _context3.prev = 0;
                _context3.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.savePrimaryEmail)(email);
              case 3:
                responseData = _context3.sent;
                _this3.handleResponse({
                  email: email,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _context3.next = 10;
                break;
              case 7:
                _context3.prev = 7;
                _context3.t0 = _context3["catch"](0);
                if (email === '') {
                  _this3.handleResponse({
                    errorMessage: t('settings', 'Unable to delete primary email address'),
                    error: _context3.t0
                  });
                } else {
                  _this3.handleResponse({
                    errorMessage: t('settings', 'Unable to update primary email address'),
                    error: _context3.t0
                  });
                }
              case 10:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[0, 7]]);
      }))();
    },
    addAdditionalEmail: function addAdditionalEmail(email) {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
        var _responseData$ocs2, _responseData$ocs2$me, responseData;
        return regeneratorRuntime.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                _context4.prev = 0;
                _context4.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.saveAdditionalEmail)(email);
              case 3:
                responseData = _context4.sent;
                _this4.handleResponse({
                  email: email,
                  status: (_responseData$ocs2 = responseData.ocs) === null || _responseData$ocs2 === void 0 ? void 0 : (_responseData$ocs2$me = _responseData$ocs2.meta) === null || _responseData$ocs2$me === void 0 ? void 0 : _responseData$ocs2$me.status
                });
                _context4.next = 10;
                break;
              case 7:
                _context4.prev = 7;
                _context4.t0 = _context4["catch"](0);
                _this4.handleResponse({
                  errorMessage: t('settings', 'Unable to add additional email address'),
                  error: _context4.t0
                });
              case 10:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4, null, [[0, 7]]);
      }))();
    },
    setNotificationMail: function setNotificationMail() {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5() {
        var _responseData$ocs3, _responseData$ocs3$me, newNotificationMailValue, responseData;
        return regeneratorRuntime.wrap(function _callee5$(_context5) {
          while (1) {
            switch (_context5.prev = _context5.next) {
              case 0:
                _context5.prev = 0;
                newNotificationMailValue = _this5.primary || _this5.isNotificationEmail ? '' : _this5.initialEmail;
                _context5.next = 4;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.saveNotificationEmail)(newNotificationMailValue);
              case 4:
                responseData = _context5.sent;
                _this5.handleResponse({
                  notificationEmail: newNotificationMailValue,
                  status: (_responseData$ocs3 = responseData.ocs) === null || _responseData$ocs3 === void 0 ? void 0 : (_responseData$ocs3$me = _responseData$ocs3.meta) === null || _responseData$ocs3$me === void 0 ? void 0 : _responseData$ocs3$me.status
                });
                _context5.next = 11;
                break;
              case 8:
                _context5.prev = 8;
                _context5.t0 = _context5["catch"](0);
                _this5.handleResponse({
                  errorMessage: 'Unable to choose this email for notifications',
                  error: _context5.t0
                });
              case 11:
              case "end":
                return _context5.stop();
            }
          }
        }, _callee5, null, [[0, 8]]);
      }))();
    },
    updateAdditionalEmail: function updateAdditionalEmail(email) {
      var _this6 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6() {
        var _responseData$ocs4, _responseData$ocs4$me, responseData;
        return regeneratorRuntime.wrap(function _callee6$(_context6) {
          while (1) {
            switch (_context6.prev = _context6.next) {
              case 0:
                _context6.prev = 0;
                _context6.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.updateAdditionalEmail)(_this6.initialEmail, email);
              case 3:
                responseData = _context6.sent;
                _this6.handleResponse({
                  email: email,
                  status: (_responseData$ocs4 = responseData.ocs) === null || _responseData$ocs4 === void 0 ? void 0 : (_responseData$ocs4$me = _responseData$ocs4.meta) === null || _responseData$ocs4$me === void 0 ? void 0 : _responseData$ocs4$me.status
                });
                _context6.next = 10;
                break;
              case 7:
                _context6.prev = 7;
                _context6.t0 = _context6["catch"](0);
                _this6.handleResponse({
                  errorMessage: t('settings', 'Unable to update additional email address'),
                  error: _context6.t0
                });
              case 10:
              case "end":
                return _context6.stop();
            }
          }
        }, _callee6, null, [[0, 7]]);
      }))();
    },
    deleteAdditionalEmail: function deleteAdditionalEmail() {
      var _this7 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee7() {
        var _responseData$ocs5, _responseData$ocs5$me, responseData;
        return regeneratorRuntime.wrap(function _callee7$(_context7) {
          while (1) {
            switch (_context7.prev = _context7.next) {
              case 0:
                _context7.prev = 0;
                _context7.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_9__.removeAdditionalEmail)(_this7.initialEmail);
              case 3:
                responseData = _context7.sent;
                _this7.handleDeleteAdditionalEmail((_responseData$ocs5 = responseData.ocs) === null || _responseData$ocs5 === void 0 ? void 0 : (_responseData$ocs5$me = _responseData$ocs5.meta) === null || _responseData$ocs5$me === void 0 ? void 0 : _responseData$ocs5$me.status);
                _context7.next = 10;
                break;
              case 7:
                _context7.prev = 7;
                _context7.t0 = _context7["catch"](0);
                _this7.handleResponse({
                  errorMessage: t('settings', 'Unable to delete additional email address'),
                  error: _context7.t0
                });
              case 10:
              case "end":
                return _context7.stop();
            }
          }
        }, _callee7, null, [[0, 7]]);
      }))();
    },
    handleDeleteAdditionalEmail: function handleDeleteAdditionalEmail(status) {
      if (status === 'ok') {
        this.$emit('delete-additional-email');
      } else {
        this.handleResponse({
          errorMessage: t('settings', 'Unable to delete additional email address')
        });
      }
    },
    handleResponse: function handleResponse(_ref2) {
      var _this8 = this;
      var email = _ref2.email,
        notificationEmail = _ref2.notificationEmail,
        status = _ref2.status,
        errorMessage = _ref2.errorMessage,
        error = _ref2.error;
      if (status === 'ok') {
        // Ensure that local state reflects server state
        if (email) {
          this.initialEmail = email;
        } else if (notificationEmail !== undefined) {
          this.$emit('update:notification-email', notificationEmail);
        }
        this.showCheckmarkIcon = true;
        setTimeout(function () {
          _this8.showCheckmarkIcon = false;
        }, 2000);
      } else {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].error(errorMessage, error);
        this.showErrorIcon = true;
        setTimeout(function () {
          _this8.showErrorIcon = false;
        }, 2000);
      }
    },
    onScopeChange: function onScopeChange(scope) {
      this.$emit('update:scope', scope);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _Email_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Email.vue */ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../service/PersonalInfo/EmailService.js */ "./apps/settings/src/service/PersonalInfo/EmailService.js");
/* harmony import */ var _utils_validate_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../utils/validate.js */ "./apps/settings/src/utils/validate.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }








var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  _loadState$emailMap = _loadState.emailMap,
  additionalEmails = _loadState$emailMap.additionalEmails,
  primaryEmail = _loadState$emailMap.primaryEmail,
  notificationEmail = _loadState$emailMap.notificationEmail;
var _loadState2 = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'accountParameters', {}),
  displayNameChangeSupported = _loadState2.displayNameChangeSupported;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'EmailSection',
  components: {
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Email: _Email_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data: function data() {
    var _this = this;
    return {
      accountProperty: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.ACCOUNT_PROPERTY_READABLE_ENUM.EMAIL,
      additionalEmails: additionalEmails.map(function (properties) {
        return _objectSpread(_objectSpread({}, properties), {}, {
          key: _this.generateUniqueKey()
        });
      }),
      displayNameChangeSupported: displayNameChangeSupported,
      primaryEmail: _objectSpread(_objectSpread({}, primaryEmail), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.NAME_READABLE_ENUM[primaryEmail.name]
      }),
      savePrimaryEmailScope: _service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_5__.savePrimaryEmailScope,
      notificationEmail: notificationEmail
    };
  },
  computed: {
    firstAdditionalEmail: function firstAdditionalEmail() {
      if (this.additionalEmails.length) {
        return this.additionalEmails[0].value;
      }
      return null;
    },
    inputId: function inputId() {
      return "account-property-".concat(this.primaryEmail.name);
    },
    isValidSection: function isValidSection() {
      return (0,_utils_validate_js__WEBPACK_IMPORTED_MODULE_6__.validateEmail)(this.primaryEmail.value) && this.additionalEmails.map(function (_ref) {
        var value = _ref.value;
        return value;
      }).every(_utils_validate_js__WEBPACK_IMPORTED_MODULE_6__.validateEmail);
    },
    primaryEmailValue: {
      get: function get() {
        return this.primaryEmail.value;
      },
      set: function set(value) {
        this.primaryEmail.value = value;
      }
    }
  },
  methods: {
    onAddAdditionalEmail: function onAddAdditionalEmail() {
      if (this.isValidSection) {
        this.additionalEmails.push({
          value: '',
          scope: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.DEFAULT_ADDITIONAL_EMAIL_SCOPE,
          key: this.generateUniqueKey()
        });
      }
    },
    onDeleteAdditionalEmail: function onDeleteAdditionalEmail(index) {
      this.$delete(this.additionalEmails, index);
    },
    onUpdateEmail: function onUpdateEmail() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var deletedEmail;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                if (!(_this2.primaryEmailValue === '' && _this2.firstAdditionalEmail)) {
                  _context.next = 7;
                  break;
                }
                deletedEmail = _this2.firstAdditionalEmail;
                _context.next = 4;
                return _this2.deleteFirstAdditionalEmail();
              case 4:
                _this2.primaryEmailValue = deletedEmail;
                _context.next = 7;
                return _this2.updatePrimaryEmail();
              case 7:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    },
    onUpdateNotificationEmail: function onUpdateNotificationEmail(email) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this3.notificationEmail = email;
              case 1:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2);
      }))();
    },
    updatePrimaryEmail: function updatePrimaryEmail() {
      var _this4 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _context3.prev = 0;
                _context3.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_5__.savePrimaryEmail)(_this4.primaryEmailValue);
              case 3:
                responseData = _context3.sent;
                _this4.handleResponse((_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status);
                _context3.next = 10;
                break;
              case 7:
                _context3.prev = 7;
                _context3.t0 = _context3["catch"](0);
                _this4.handleResponse('error', t('settings', 'Unable to update primary email address'), _context3.t0);
              case 10:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[0, 7]]);
      }))();
    },
    deleteFirstAdditionalEmail: function deleteFirstAdditionalEmail() {
      var _this5 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
        var _responseData$ocs2, _responseData$ocs2$me, responseData;
        return regeneratorRuntime.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                _context4.prev = 0;
                _context4.next = 3;
                return (0,_service_PersonalInfo_EmailService_js__WEBPACK_IMPORTED_MODULE_5__.removeAdditionalEmail)(_this5.firstAdditionalEmail);
              case 3:
                responseData = _context4.sent;
                _this5.handleDeleteFirstAdditionalEmail((_responseData$ocs2 = responseData.ocs) === null || _responseData$ocs2 === void 0 ? void 0 : (_responseData$ocs2$me = _responseData$ocs2.meta) === null || _responseData$ocs2$me === void 0 ? void 0 : _responseData$ocs2$me.status);
                _context4.next = 10;
                break;
              case 7:
                _context4.prev = 7;
                _context4.t0 = _context4["catch"](0);
                _this5.handleResponse('error', t('settings', 'Unable to delete additional email address'), _context4.t0);
              case 10:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4, null, [[0, 7]]);
      }))();
    },
    handleDeleteFirstAdditionalEmail: function handleDeleteFirstAdditionalEmail(status) {
      if (status === 'ok') {
        this.$delete(this.additionalEmails, 0);
      } else {
        this.handleResponse('error', t('settings', 'Unable to delete additional email address'), {});
      }
    },
    handleResponse: function handleResponse(status, errorMessage, error) {
      if (status !== 'ok') {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].error(errorMessage, error);
      }
    },
    generateUniqueKey: function generateUniqueKey() {
      return Math.random().toString(36).substring(2);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  fediverse = _loadState.fediverse;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'FediverseSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      fediverse: _objectSpread(_objectSpread({}, fediverse), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[fediverse.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  headline = _loadState.headline;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'HeadlineSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      headline: _objectSpread(_objectSpread({}, headline), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[headline.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../service/PersonalInfo/PersonalInfoService.js */ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js");
/* harmony import */ var _utils_validate_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../utils/validate.js */ "./apps/settings/src/utils/validate.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }





/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Language',
  props: {
    inputId: {
      type: String,
      default: null
    },
    commonLanguages: {
      type: Array,
      required: true
    },
    otherLanguages: {
      type: Array,
      required: true
    },
    language: {
      type: Object,
      required: true
    }
  },
  data: function data() {
    return {
      initialLanguage: this.language
    };
  },
  computed: {
    allLanguages: function allLanguages() {
      return Object.freeze([].concat(_toConsumableArray(this.commonLanguages), _toConsumableArray(this.otherLanguages)).reduce(function (acc, _ref) {
        var code = _ref.code,
          name = _ref.name;
        return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, code, name));
      }, {}));
    }
  },
  methods: {
    onLanguageChange: function onLanguageChange(e) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var language;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                language = _this.constructLanguage(e.target.value);
                _this.$emit('update:language', language);
                if (!(0,_utils_validate_js__WEBPACK_IMPORTED_MODULE_3__.validateLanguage)(language)) {
                  _context.next = 5;
                  break;
                }
                _context.next = 5;
                return _this.updateLanguage(language);
              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    },
    updateLanguage: function updateLanguage(language) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return (0,_service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_2__.savePrimaryAccountProperty)(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_1__.ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE, language.code);
              case 3:
                responseData = _context2.sent;
                _this2.handleResponse({
                  language: language,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _this2.reloadPage();
                _context2.next = 11;
                break;
              case 8:
                _context2.prev = 8;
                _context2.t0 = _context2["catch"](0);
                _this2.handleResponse({
                  errorMessage: t('settings', 'Unable to update language'),
                  error: _context2.t0
                });
              case 11:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 8]]);
      }))();
    },
    constructLanguage: function constructLanguage(languageCode) {
      return {
        code: languageCode,
        name: this.allLanguages[languageCode]
      };
    },
    handleResponse: function handleResponse(_ref2) {
      var language = _ref2.language,
        status = _ref2.status,
        errorMessage = _ref2.errorMessage,
        error = _ref2.error;
      if (status === 'ok') {
        // Ensure that local state reflects server state
        this.initialLanguage = language;
      } else {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_4__["default"].error(errorMessage, error);
      }
    },
    reloadPage: function reloadPage() {
      location.reload();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _Language_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Language.vue */ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");




var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  _loadState$languageMa = _loadState.languageMap,
  activeLanguage = _loadState$languageMa.activeLanguage,
  commonLanguages = _loadState$languageMa.commonLanguages,
  otherLanguages = _loadState$languageMa.otherLanguages;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LanguageSection',
  components: {
    Language: _Language_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data: function data() {
    return {
      propertyReadable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LANGUAGE,
      commonLanguages: commonLanguages,
      otherLanguages: otherLanguages,
      language: activeLanguage
    };
  },
  computed: {
    inputId: function inputId() {
      return "account-setting-".concat(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE);
    },
    isEditable: function isEditable() {
      return Boolean(this.language);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue_material_design_icons_Web__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Web */ "./node_modules/vue-material-design-icons/Web.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../service/PersonalInfo/PersonalInfoService.js */ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js");
/* harmony import */ var _utils_validate_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../utils/validate.js */ "./apps/settings/src/utils/validate.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }







/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Locale',
  components: {
    Web: vue_material_design_icons_Web__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  props: {
    inputId: {
      type: String,
      default: null
    },
    locale: {
      type: Object,
      required: true
    },
    localesForLanguage: {
      type: Array,
      required: true
    },
    otherLocales: {
      type: Array,
      required: true
    }
  },
  data: function data() {
    return {
      initialLocale: this.locale,
      example: {
        date: _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()().format('L'),
        time: _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()().format('LTS'),
        firstDayOfWeek: window.dayNames[window.firstDay]
      }
    };
  },
  computed: {
    allLocales: function allLocales() {
      return Object.freeze([].concat(_toConsumableArray(this.localesForLanguage), _toConsumableArray(this.otherLocales)).reduce(function (acc, _ref) {
        var code = _ref.code,
          name = _ref.name;
        return _objectSpread(_objectSpread({}, acc), {}, _defineProperty({}, code, name));
      }, {}));
    }
  },
  created: function created() {
    setInterval(this.refreshExample, 1000);
  },
  methods: {
    onLocaleChange: function onLocaleChange(e) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var locale;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                locale = _this.constructLocale(e.target.value);
                _this.$emit('update:locale', locale);
                if (!(0,_utils_validate_js__WEBPACK_IMPORTED_MODULE_5__.validateLocale)(locale)) {
                  _context.next = 5;
                  break;
                }
                _context.next = 5;
                return _this.updateLocale(locale);
              case 5:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    },
    updateLocale: function updateLocale(locale) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return (0,_service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_4__.savePrimaryAccountProperty)(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE, locale.code);
              case 3:
                responseData = _context2.sent;
                _this2.handleResponse({
                  locale: locale,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _this2.reloadPage();
                _context2.next = 11;
                break;
              case 8:
                _context2.prev = 8;
                _context2.t0 = _context2["catch"](0);
                _this2.handleResponse({
                  errorMessage: t('settings', 'Unable to update locale'),
                  error: _context2.t0
                });
              case 11:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 8]]);
      }))();
    },
    constructLocale: function constructLocale(localeCode) {
      return {
        code: localeCode,
        name: this.allLocales[localeCode]
      };
    },
    handleResponse: function handleResponse(_ref2) {
      var locale = _ref2.locale,
        status = _ref2.status,
        errorMessage = _ref2.errorMessage,
        error = _ref2.error;
      if (status === 'ok') {
        this.initialLocale = locale;
      } else {
        this.$emit('update:locale', this.initialLocale);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_6__["default"].error(errorMessage, error);
      }
    },
    refreshExample: function refreshExample() {
      this.example = {
        date: _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()().format('L'),
        time: _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()().format('LTS'),
        firstDayOfWeek: window.dayNames[window.firstDay]
      };
    },
    reloadPage: function reloadPage() {
      location.reload();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _Locale_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Locale.vue */ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");




var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  _loadState$localeMap = _loadState.localeMap,
  activeLocale = _loadState$localeMap.activeLocale,
  localesForLanguage = _loadState$localeMap.localesForLanguage,
  otherLocales = _loadState$localeMap.otherLocales;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LocaleSection',
  components: {
    Locale: _Locale_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  data: function data() {
    return {
      propertyReadable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LOCALE,
      localesForLanguage: localesForLanguage,
      otherLocales: otherLocales,
      locale: activeLocale
    };
  },
  computed: {
    inputId: function inputId() {
      return "account-setting-".concat(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE);
    },
    isEditable: function isEditable() {
      return Boolean(this.locale);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  location = _loadState.location;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'LocationSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      location: _objectSpread(_objectSpread({}, location), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[location.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  organisation = _loadState.organisation;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'OrganisationSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      organisation: _objectSpread(_objectSpread({}, organisation), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[organisation.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var libphonenumber_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! libphonenumber-js */ "./node_modules/libphonenumber-js/min/exports/isValidPhoneNumber.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }




var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  defaultPhoneRegion = _loadState.defaultPhoneRegion,
  phone = _loadState.phone;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'PhoneSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      phone: _objectSpread(_objectSpread({}, phone), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[phone.name]
      })
    };
  },
  methods: {
    onValidate: function onValidate(value) {
      if (defaultPhoneRegion) {
        return (0,libphonenumber_js__WEBPACK_IMPORTED_MODULE_3__.isValidPhoneNumber)(value, defaultPhoneRegion);
      }
      return (0,libphonenumber_js__WEBPACK_IMPORTED_MODULE_3__.isValidPhoneNumber)(value);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_material_design_icons_ChevronDown__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown */ "./node_modules/vue-material-design-icons/ChevronDown.vue");

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'EditProfileAnchorLink',
  components: {
    ChevronDownIcon: vue_material_design_icons_ChevronDown__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    profileEnabled: {
      type: Boolean,
      required: true
    }
  },
  computed: {
    disabled: function disabled() {
      return !this.profileEnabled;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../service/PersonalInfo/PersonalInfoService.js */ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__);
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }






/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ProfileCheckbox',
  components: {
    NcCheckboxRadioSwitch: (_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5___default())
  },
  props: {
    profileEnabled: {
      type: Boolean,
      required: true
    }
  },
  data: function data() {
    return {
      isProfileEnabled: this.profileEnabled,
      loading: false
    };
  },
  methods: {
    saveEnableProfile: function saveEnableProfile() {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this.loading = true;
                _context.prev = 1;
                _context.next = 4;
                return (0,_service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_2__.savePrimaryAccountProperty)(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, _this.isProfileEnabled);
              case 4:
                responseData = _context.sent;
                _this.handleResponse({
                  isProfileEnabled: _this.isProfileEnabled,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _context.next = 11;
                break;
              case 8:
                _context.prev = 8;
                _context.t0 = _context["catch"](1);
                _this.handleResponse({
                  errorMessage: t('settings', 'Unable to update profile enabled state'),
                  error: _context.t0
                });
              case 11:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[1, 8]]);
      }))();
    },
    handleResponse: function handleResponse(_ref) {
      var isProfileEnabled = _ref.isProfileEnabled,
        status = _ref.status,
        errorMessage = _ref.errorMessage,
        error = _ref.error;
      if (status === 'ok') {
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('settings:profile-enabled:updated', isProfileEnabled);
      } else {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_4__["default"].error(errorMessage, error);
      }
      this.loading = false;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_2__);



/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ProfilePreviewCard',
  components: {
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar__WEBPACK_IMPORTED_MODULE_2___default())
  },
  props: {
    displayName: {
      type: String,
      required: true
    },
    organisation: {
      type: String,
      required: true
    },
    profileEnabled: {
      type: Boolean,
      required: true
    },
    userId: {
      type: String,
      required: true
    }
  },
  computed: {
    disabled: function disabled() {
      return !this.profileEnabled;
    },
    profilePageLink: function profilePageLink() {
      if (this.profileEnabled) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/u/{userId}', {
          userId: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid
        });
      }
      // Since an anchor element is used rather than a button for better UX,
      // this hack removes href if the profile is disabled so that disabling pointer-events is not needed to prevent a click from opening a page
      // and to allow the hover event (which disabling pointer-events wouldn't allow) for styling
      return null;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _EditProfileAnchorLink_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./EditProfileAnchorLink.vue */ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _ProfileCheckbox_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ProfileCheckbox.vue */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue");
/* harmony import */ var _ProfilePreviewCard_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ProfilePreviewCard.vue */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");







var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  organisation = _loadState.organisation.value,
  displayName = _loadState.displayName.value,
  profileEnabled = _loadState.profileEnabled,
  userId = _loadState.userId;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ProfileSection',
  components: {
    EditProfileAnchorLink: _EditProfileAnchorLink_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    ProfileCheckbox: _ProfileCheckbox_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    ProfilePreviewCard: _ProfilePreviewCard_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  data: function data() {
    return {
      propertyReadable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_6__.ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED,
      organisation: organisation,
      displayName: displayName,
      profileEnabled: profileEnabled,
      userId: userId
    };
  },
  mounted: function mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('settings:display-name:updated', this.handleDisplayNameUpdate);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('settings:organisation:updated', this.handleOrganisationUpdate);
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('settings:display-name:updated', this.handleDisplayNameUpdate);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('settings:organisation:updated', this.handleOrganisationUpdate);
  },
  methods: {
    handleDisplayNameUpdate: function handleDisplayNameUpdate(displayName) {
      this.displayName = displayName;
    },
    handleOrganisationUpdate: function handleOrganisationUpdate(organisation) {
      this.organisation = organisation;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _VisibilityDropdown_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./VisibilityDropdown.vue */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0) { ; } } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i.return && (_r = _i.return(), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }





var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'profileParameters', {}),
  profileConfig = _loadState.profileConfig;
var _loadState2 = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', false),
  profileEnabled = _loadState2.profileEnabled;
var compareParams = function compareParams(a, b) {
  if (a.appId === b.appId || a.appId !== 'core' && b.appId !== 'core') {
    return a.displayId.localeCompare(b.displayId);
  } else if (a.appId === 'core') {
    return 1;
  } else {
    return -1;
  }
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ProfileVisibilitySection',
  components: {
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    VisibilityDropdown: _VisibilityDropdown_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  data: function data() {
    return {
      heading: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.PROFILE_READABLE_ENUM.PROFILE_VISIBILITY,
      profileEnabled: profileEnabled,
      visibilityParams: Object.entries(profileConfig).map(function (_ref) {
        var _ref2 = _slicedToArray(_ref, 2),
          paramId = _ref2[0],
          _ref2$ = _ref2[1],
          appId = _ref2$.appId,
          displayId = _ref2$.displayId,
          visibility = _ref2$.visibility;
        return {
          id: paramId,
          appId: appId,
          displayId: displayId,
          visibility: visibility
        };
      }).sort(compareParams),
      // TODO remove this when not used once the settings layout is updated
      marginLeft: window.matchMedia('(min-width: 1600px)').matches ? window.getComputedStyle(document.getElementById('vue-avatar-section')).getPropertyValue('width').trim() : '0px'
    };
  },
  computed: {
    disabled: function disabled() {
      return !this.profileEnabled;
    },
    rows: function rows() {
      return Math.ceil(this.visibilityParams.length / 2);
    }
  },
  mounted: function mounted() {
    var _this = this;
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.subscribe)('settings:profile-enabled:updated', this.handleProfileEnabledUpdate);
    // TODO remove this when not used once the settings layout is updated
    window.onresize = function () {
      _this.marginLeft = window.matchMedia('(min-width: 1600px)').matches ? window.getComputedStyle(document.getElementById('vue-avatar-section')).getPropertyValue('width').trim() : '0px';
    };
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.unsubscribe)('settings:profile-enabled:updated', this.handleProfileEnabledUpdate);
  },
  methods: {
    handleProfileEnabledUpdate: function handleProfileEnabledUpdate(profileEnabled) {
      this.profileEnabled = profileEnabled;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcMultiselect */ "./node_modules/@nextcloud/vue/dist/Components/NcMultiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcMultiselect__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcMultiselect__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _service_ProfileService_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../service/ProfileService.js */ "./apps/settings/src/service/ProfileService.js");
/* harmony import */ var _constants_ProfileConstants_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../constants/ProfileConstants.js */ "./apps/settings/src/constants/ProfileConstants.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }







var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'personalInfoParameters', false),
  profileEnabled = _loadState.profileEnabled;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'VisibilityDropdown',
  components: {
    NcMultiselect: (_nextcloud_vue_dist_Components_NcMultiselect__WEBPACK_IMPORTED_MODULE_3___default())
  },
  props: {
    paramId: {
      type: String,
      required: true
    },
    displayId: {
      type: String,
      required: true
    },
    visibility: {
      type: String,
      required: true
    }
  },
  data: function data() {
    return {
      initialVisibility: this.visibility,
      profileEnabled: profileEnabled
    };
  },
  computed: {
    disabled: function disabled() {
      return !this.profileEnabled;
    },
    inputId: function inputId() {
      return "profile-visibility-".concat(this.paramId);
    },
    visibilityObject: function visibilityObject() {
      return _constants_ProfileConstants_js__WEBPACK_IMPORTED_MODULE_5__.VISIBILITY_PROPERTY_ENUM[this.visibility];
    },
    visibilityOptions: function visibilityOptions() {
      return Object.values(_constants_ProfileConstants_js__WEBPACK_IMPORTED_MODULE_5__.VISIBILITY_PROPERTY_ENUM);
    }
  },
  mounted: function mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.subscribe)('settings:profile-enabled:updated', this.handleProfileEnabledUpdate);
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.unsubscribe)('settings:profile-enabled:updated', this.handleProfileEnabledUpdate);
  },
  methods: {
    onVisibilityChange: function onVisibilityChange(visibilityObject) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var visibility;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                if (!(visibilityObject !== null)) {
                  _context.next = 6;
                  break;
                }
                visibility = visibilityObject.name;
                _this.$emit('update:visibility', visibility);
                if (!(visibility !== '')) {
                  _context.next = 6;
                  break;
                }
                _context.next = 6;
                return _this.updateVisibility(visibility);
              case 6:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    },
    updateVisibility: function updateVisibility(visibility) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return (0,_service_ProfileService_js__WEBPACK_IMPORTED_MODULE_4__.saveProfileParameterVisibility)(_this2.paramId, visibility);
              case 3:
                responseData = _context2.sent;
                _this2.handleResponse({
                  visibility: visibility,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _context2.next = 10;
                break;
              case 7:
                _context2.prev = 7;
                _context2.t0 = _context2["catch"](0);
                _this2.handleResponse({
                  errorMessage: t('settings', 'Unable to update visibility of {displayId}', {
                    displayId: _this2.displayId
                  }),
                  error: _context2.t0
                });
              case 10:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 7]]);
      }))();
    },
    handleResponse: function handleResponse(_ref) {
      var visibility = _ref.visibility,
        status = _ref.status,
        errorMessage = _ref.errorMessage,
        error = _ref.error;
      if (status === 'ok') {
        // Ensure that local state reflects server state
        this.initialVisibility = visibility;
      } else {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_6__["default"].error(errorMessage, error);
      }
    },
    handleProfileEnabledUpdate: function handleProfileEnabledUpdate(profileEnabled) {
      this.profileEnabled = profileEnabled;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  role = _loadState.role;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'RoleSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      role: _objectSpread(_objectSpread({}, role), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[role.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }



var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  twitter = _loadState.twitter;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'TwitterSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      twitter: _objectSpread(_objectSpread({}, twitter), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[twitter.name]
      })
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./shared/AccountPropertySection.vue */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _utils_validate_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../utils/validate.js */ "./apps/settings/src/utils/validate.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }




var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'personalInfoParameters', {}),
  website = _loadState.website;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'WebsiteSection',
  components: {
    AccountPropertySection: _shared_AccountPropertySection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  data: function data() {
    return {
      website: _objectSpread(_objectSpread({}, website), {}, {
        readable: _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_2__.NAME_READABLE_ENUM[website.name]
      })
    };
  },
  methods: {
    onValidate: function onValidate(value) {
      return (0,_utils_validate_js__WEBPACK_IMPORTED_MODULE_3__.validateUrl)(value);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/AlertCircleOutline.vue */ "./node_modules/vue-material-design-icons/AlertCircleOutline.vue");
/* harmony import */ var vue_material_design_icons_AlertOctagon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/AlertOctagon */ "./node_modules/vue-material-design-icons/AlertOctagon.vue");
/* harmony import */ var vue_material_design_icons_Check__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Check */ "./node_modules/vue-material-design-icons/Check.vue");
/* harmony import */ var _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../shared/HeaderBar.vue */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue");
/* harmony import */ var _service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../service/PersonalInfo/PersonalInfoService.js */ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }








/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AccountPropertySection',
  components: {
    AlertCircle: vue_material_design_icons_AlertCircleOutline_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    AlertOctagon: vue_material_design_icons_AlertOctagon__WEBPACK_IMPORTED_MODULE_3__["default"],
    Check: vue_material_design_icons_Check__WEBPACK_IMPORTED_MODULE_4__["default"],
    HeaderBar: _shared_HeaderBar_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    name: {
      type: String,
      required: true
    },
    value: {
      type: String,
      required: true
    },
    scope: {
      type: String,
      required: true
    },
    readable: {
      type: String,
      required: true
    },
    placeholder: {
      type: String,
      required: true
    },
    type: {
      type: String,
      default: 'text'
    },
    isEditable: {
      type: Boolean,
      default: true
    },
    multiLine: {
      type: Boolean,
      default: false
    },
    onValidate: {
      type: Function,
      default: null
    },
    onSave: {
      type: Function,
      default: null
    }
  },
  data: function data() {
    return {
      initialValue: this.value,
      helperText: null,
      showCheckmarkIcon: false,
      showErrorIcon: false
    };
  },
  computed: {
    inputId: function inputId() {
      return "account-property-".concat(this.name);
    }
  },
  methods: {
    onPropertyChange: function onPropertyChange(e) {
      this.$emit('update:value', e.target.value);
      this.debouncePropertyChange(e.target.value.trim());
    },
    debouncePropertyChange: debounce__WEBPACK_IMPORTED_MODULE_0___default()( /*#__PURE__*/function () {
      var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(value) {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                this.helperText = null;
                if (!(this.$refs.input && this.$refs.input.validationMessage)) {
                  _context.next = 4;
                  break;
                }
                this.helperText = this.$refs.input.validationMessage;
                return _context.abrupt("return");
              case 4:
                if (!(this.onValidate && !this.onValidate(value))) {
                  _context.next = 6;
                  break;
                }
                return _context.abrupt("return");
              case 6:
                _context.next = 8;
                return this.updateProperty(value);
              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this);
      }));
      return function (_x) {
        return _ref.apply(this, arguments);
      };
    }(), 500),
    updateProperty: function updateProperty(value) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return (0,_service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_6__.savePrimaryAccountProperty)(_this.name, value);
              case 3:
                responseData = _context2.sent;
                _this.handleResponse({
                  value: value,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _context2.next = 10;
                break;
              case 7:
                _context2.prev = 7;
                _context2.t0 = _context2["catch"](0);
                _this.handleResponse({
                  errorMessage: t('settings', 'Unable to update {property}', {
                    property: _this.readable.toLocaleLowerCase()
                  }),
                  error: _context2.t0
                });
              case 10:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 7]]);
      }))();
    },
    handleResponse: function handleResponse(_ref2) {
      var _this2 = this;
      var value = _ref2.value,
        status = _ref2.status,
        errorMessage = _ref2.errorMessage,
        error = _ref2.error;
      if (status === 'ok') {
        this.initialValue = value;
        if (this.onSave) {
          this.onSave(value);
        }
        this.showCheckmarkIcon = true;
        setTimeout(function () {
          _this2.showCheckmarkIcon = false;
        }, 2000);
      } else {
        this.$emit('update:value', this.initialValue);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_7__["default"].error(errorMessage, error);
        this.showErrorIcon = true;
        setTimeout(function () {
          _this2.showErrorIcon = false;
        }, 2000);
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _FederationControlAction_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FederationControlAction.vue */ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");
/* harmony import */ var _service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../../service/PersonalInfo/PersonalInfoService.js */ "./apps/settings/src/service/PersonalInfo/PersonalInfoService.js");
/* harmony import */ var _logger_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../logger.js */ "./apps/settings/src/logger.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }







var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('settings', 'accountParameters', {}),
  federationEnabled = _loadState.federationEnabled,
  lookupServerUploadEnabled = _loadState.lookupServerUploadEnabled;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'FederationControl',
  components: {
    NcActions: (_nextcloud_vue_dist_Components_NcActions__WEBPACK_IMPORTED_MODULE_0___default()),
    FederationControlAction: _FederationControlAction_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    readable: {
      type: String,
      required: true,
      validator: function validator(value) {
        return Object.values(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.ACCOUNT_PROPERTY_READABLE_ENUM).includes(value) || Object.values(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.ACCOUNT_SETTING_PROPERTY_READABLE_ENUM).includes(value) || value === _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.PROFILE_READABLE_ENUM.PROFILE_VISIBILITY;
      }
    },
    additional: {
      type: Boolean,
      default: false
    },
    additionalValue: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    },
    handleAdditionalScopeChange: {
      type: Function,
      default: null
    },
    scope: {
      type: String,
      required: true
    }
  },
  data: function data() {
    return {
      readableLowerCase: this.readable.toLocaleLowerCase(),
      initialScope: this.scope
    };
  },
  computed: {
    ariaLabel: function ariaLabel() {
      return t('settings', 'Change scope level of {property}, current scope is {scope}', {
        property: this.readableLowerCase,
        scope: this.scopeDisplayNameLowerCase
      });
    },
    scopeDisplayNameLowerCase: function scopeDisplayNameLowerCase() {
      return _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.SCOPE_PROPERTY_ENUM[this.scope].displayName.toLocaleLowerCase();
    },
    scopeIcon: function scopeIcon() {
      return _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.SCOPE_PROPERTY_ENUM[this.scope].iconClass;
    },
    federationScopes: function federationScopes() {
      return Object.values(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.SCOPE_PROPERTY_ENUM);
    },
    supportedScopes: function supportedScopes() {
      var scopes = _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM[this.readable];
      if (_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.UNPUBLISHED_READABLE_PROPERTIES.includes(this.readable)) {
        return scopes;
      }
      if (federationEnabled) {
        scopes.push(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.SCOPE_ENUM.FEDERATED);
      }
      if (lookupServerUploadEnabled) {
        scopes.push(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.SCOPE_ENUM.PUBLISHED);
      }
      return scopes;
    }
  },
  methods: {
    changeScope: function changeScope(scope) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this.$emit('update:scope', scope);
                if (_this.additional) {
                  _context.next = 6;
                  break;
                }
                _context.next = 4;
                return _this.updatePrimaryScope(scope);
              case 4:
                _context.next = 8;
                break;
              case 6:
                _context.next = 8;
                return _this.updateAdditionalScope(scope);
              case 8:
              case "end":
                return _context.stop();
            }
          }
        }, _callee);
      }))();
    },
    updatePrimaryScope: function updatePrimaryScope(scope) {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var _responseData$ocs, _responseData$ocs$met, responseData;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _context2.next = 3;
                return (0,_service_PersonalInfo_PersonalInfoService_js__WEBPACK_IMPORTED_MODULE_5__.savePrimaryAccountPropertyScope)(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_4__.PROPERTY_READABLE_KEYS_ENUM[_this2.readable], scope);
              case 3:
                responseData = _context2.sent;
                _this2.handleResponse({
                  scope: scope,
                  status: (_responseData$ocs = responseData.ocs) === null || _responseData$ocs === void 0 ? void 0 : (_responseData$ocs$met = _responseData$ocs.meta) === null || _responseData$ocs$met === void 0 ? void 0 : _responseData$ocs$met.status
                });
                _context2.next = 10;
                break;
              case 7:
                _context2.prev = 7;
                _context2.t0 = _context2["catch"](0);
                _this2.handleResponse({
                  errorMessage: t('settings', 'Unable to update federation scope of the primary {property}', {
                    property: _this2.readableLowerCase
                  }),
                  error: _context2.t0
                });
              case 10:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 7]]);
      }))();
    },
    updateAdditionalScope: function updateAdditionalScope(scope) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var _responseData$ocs2, _responseData$ocs2$me, responseData;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _context3.prev = 0;
                _context3.next = 3;
                return _this3.handleAdditionalScopeChange(_this3.additionalValue, scope);
              case 3:
                responseData = _context3.sent;
                _this3.handleResponse({
                  scope: scope,
                  status: (_responseData$ocs2 = responseData.ocs) === null || _responseData$ocs2 === void 0 ? void 0 : (_responseData$ocs2$me = _responseData$ocs2.meta) === null || _responseData$ocs2$me === void 0 ? void 0 : _responseData$ocs2$me.status
                });
                _context3.next = 10;
                break;
              case 7:
                _context3.prev = 7;
                _context3.t0 = _context3["catch"](0);
                _this3.handleResponse({
                  errorMessage: t('settings', 'Unable to update federation scope of additional {property}', {
                    property: _this3.readableLowerCase
                  }),
                  error: _context3.t0
                });
              case 10:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[0, 7]]);
      }))();
    },
    handleResponse: function handleResponse(_ref) {
      var scope = _ref.scope,
        status = _ref.status,
        errorMessage = _ref.errorMessage,
        error = _ref.error;
      if (status === 'ok') {
        this.initialScope = scope;
      } else {
        this.$emit('update:scope', this.initialScope);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(errorMessage);
        _logger_js__WEBPACK_IMPORTED_MODULE_6__["default"].error(errorMessage, error);
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'FederationControlAction',
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    activeScope: {
      type: String,
      required: true
    },
    displayName: {
      type: String,
      required: true
    },
    handleScopeChange: {
      type: Function,
      default: function _default() {}
    },
    iconClass: {
      type: String,
      required: true
    },
    isSupportedScope: {
      type: Boolean,
      required: true
    },
    name: {
      type: String,
      required: true
    },
    tooltipDisabled: {
      type: String,
      default: ''
    },
    tooltip: {
      type: String,
      required: true
    }
  },
  methods: {
    updateScope: function updateScope() {
      this.handleScopeChange(this.name);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue_material_design_icons_Plus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/Plus */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var _FederationControl_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FederationControl.vue */ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue");
/* harmony import */ var _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../constants/AccountPropertyConstants.js */ "./apps/settings/src/constants/AccountPropertyConstants.js");




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'HeaderBar',
  components: {
    FederationControl: _FederationControl_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcButton: (_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_0___default()),
    Plus: vue_material_design_icons_Plus__WEBPACK_IMPORTED_MODULE_1__["default"]
  },
  props: {
    scope: {
      type: String,
      default: null
    },
    readable: {
      type: String,
      required: true
    },
    inputId: {
      type: String,
      default: null
    },
    isEditable: {
      type: Boolean,
      default: true
    },
    isMultiValueSupported: {
      type: Boolean,
      default: false
    },
    isValidSection: {
      type: Boolean,
      default: true
    }
  },
  data: function data() {
    return {
      localScope: this.scope
    };
  },
  computed: {
    isProfileProperty: function isProfileProperty() {
      return this.readable === _constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED;
    },
    isSettingProperty: function isSettingProperty() {
      return !Object.values(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.ACCOUNT_PROPERTY_READABLE_ENUM).includes(this.readable) && !Object.values(_constants_AccountPropertyConstants_js__WEBPACK_IMPORTED_MODULE_3__.PROFILE_READABLE_ENUM).includes(this.readable);
    }
  },
  methods: {
    onAddAdditional: function onAddAdditional() {
      this.$emit('add-additional');
    },
    onScopeChange: function onScopeChange(scope) {
      this.$emit('update:scope', scope);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", {
    attrs: {
      id: "vue-avatar-section"
    }
  }, [_c("HeaderBar", {
    attrs: {
      "input-id": _vm.avatarChangeSupported ? _vm.inputId : null,
      readable: _vm.avatar.readable,
      scope: _vm.avatar.scope
    },
    on: {
      "update:scope": function updateScope($event) {
        return _vm.$set(_vm.avatar, "scope", $event);
      }
    }
  }), _vm._v(" "), !_vm.showCropper ? _c("div", {
    staticClass: "avatar__container"
  }, [_c("div", {
    staticClass: "avatar__preview"
  }, [!_vm.loading ? _c("NcAvatar", {
    key: _vm.version,
    attrs: {
      user: _vm.userId,
      "aria-label": _vm.t("settings", "Your profile picture"),
      "disabled-menu": true,
      "disabled-tooltip": true,
      "show-user-status": false,
      size: 180
    }
  }) : _c("div", {
    staticClass: "icon-loading"
  })], 1), _vm._v(" "), _vm.avatarChangeSupported ? [_c("div", {
    staticClass: "avatar__buttons"
  }, [_c("NcButton", {
    attrs: {
      "aria-label": _vm.t("settings", "Upload profile picture")
    },
    on: {
      click: _vm.activateLocalFilePicker
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Upload", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1329850251)
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("settings", "Choose profile picture from files")
    },
    on: {
      click: _vm.openFilePicker
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Folder", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 4270628382)
  }), _vm._v(" "), !_vm.isGenerated ? _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("settings", "Remove profile picture")
    },
    on: {
      click: _vm.removeAvatar
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Delete", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2705356561)
  }) : _vm._e()], 1), _vm._v(" "), _c("span", [_vm._v(_vm._s(_vm.t("settings", "png or jpg, max. 20 MB")))]), _vm._v(" "), _c("input", {
    ref: "input",
    attrs: {
      id: _vm.inputId,
      type: "file",
      accept: _vm.validMimeTypes.join(",")
    },
    on: {
      change: _vm.onChange
    }
  })] : _c("span", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Picture provided by original account")) + "\n\t\t")])], 2) : _vm._e(), _vm._v(" "), _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.showCropper,
      expression: "showCropper"
    }],
    staticClass: "avatar__container"
  }, [_c("VueCropper", _vm._b({
    ref: "cropper",
    staticClass: "avatar__cropper"
  }, "VueCropper", _vm.cropperOptions, false)), _vm._v(" "), _c("div", {
    staticClass: "avatar__cropper-buttons"
  }, [_c("NcButton", {
    on: {
      click: _vm.cancel
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Cancel")) + "\n\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      type: "primary"
    },
    on: {
      click: _vm.saveAvatar
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Set as profile picture")) + "\n\t\t\t")])], 1), _vm._v(" "), _c("span", [_vm._v(_vm._s(_vm.t("settings", "Please note that it can take up to 24 hours for your profile picture to be updated everywhere.")))])], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your biography"),
      "multi-line": true
    }
  }, "AccountPropertySection", _vm.biography, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      readable: _vm.t("settings", "Details")
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "details"
  }, [_c("div", {
    staticClass: "details__groups"
  }, [_c("Account", {
    attrs: {
      size: 20
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "details__groups-info"
  }, [_c("p", [_vm._v(_vm._s(_vm.t("settings", "You are a member of the following groups:")))]), _vm._v(" "), _c("p", {
    staticClass: "details__groups-list"
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.groups.join(", ")) + "\n\t\t\t\t")])])], 1), _vm._v(" "), _c("div", {
    staticClass: "details__quota"
  }, [_c("CircleSlice", {
    attrs: {
      size: 20
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "details__quota-info"
  }, [_c("p", {
    staticClass: "details__quota-text",
    domProps: {
      innerHTML: _vm._s(_vm.quotaText)
    }
  }), _vm._v(" "), _c("NcProgressBar", {
    attrs: {
      size: "medium",
      value: _vm.usageRelative,
      error: _vm.usageRelative > 80
    }
  })], 1)], 1)])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your full name"),
      "is-editable": _vm.displayNameChangeSupported,
      "on-validate": _vm.onValidate,
      "on-save": _vm.onSave
    }
  }, "AccountPropertySection", _vm.displayName, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", [_c("div", {
    staticClass: "email"
  }, [_c("input", {
    ref: "email",
    attrs: {
      id: _vm.inputId,
      type: "email",
      placeholder: _vm.inputPlaceholder,
      "aria-describedby": _vm.helperText ? "".concat(_vm.inputId, "-helper-text") : "",
      autocapitalize: "none",
      autocomplete: "on",
      autocorrect: "off"
    },
    domProps: {
      value: _vm.email
    },
    on: {
      input: _vm.onEmailChange
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "email__actions-container"
  }, [_c("transition", {
    attrs: {
      name: "fade"
    }
  }, [_vm.showCheckmarkIcon ? _c("Check", {
    attrs: {
      size: 20
    }
  }) : _vm.showErrorIcon ? _c("AlertOctagon", {
    attrs: {
      size: 20
    }
  }) : _vm._e()], 1), _vm._v(" "), !_vm.primary ? [_c("FederationControl", {
    attrs: {
      readable: _vm.propertyReadable,
      additional: true,
      "additional-value": _vm.email,
      disabled: _vm.federationDisabled,
      "handle-additional-scope-change": _vm.saveAdditionalEmailScope,
      scope: _vm.localScope
    },
    on: {
      "update:scope": [function ($event) {
        _vm.localScope = $event;
      }, _vm.onScopeChange]
    }
  })] : _vm._e(), _vm._v(" "), _c("NcActions", {
    staticClass: "email__actions",
    attrs: {
      "aria-label": _vm.t("settings", "Email options"),
      "force-menu": true
    }
  }, [_c("NcActionButton", {
    attrs: {
      "aria-label": _vm.deleteEmailLabel,
      "close-after-click": true,
      disabled: _vm.deleteDisabled,
      icon: "icon-delete"
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.deleteEmail.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.deleteEmailLabel) + "\n\t\t\t\t")]), _vm._v(" "), !_vm.primary || !_vm.isNotificationEmail ? _c("NcActionButton", {
    attrs: {
      "aria-label": _vm.setNotificationMailLabel,
      "close-after-click": true,
      disabled: _vm.setNotificationMailDisabled,
      icon: "icon-favorite"
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.setNotificationMail.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.setNotificationMailLabel) + "\n\t\t\t\t")]) : _vm._e()], 1)], 2)]), _vm._v(" "), _vm.helperText ? _c("p", {
    staticClass: "email__helper-text-message email__helper-text-message--error",
    attrs: {
      id: "".concat(_vm.inputId, "-helper-text")
    }
  }, [_c("AlertCircle", {
    staticClass: "email__helper-text-message__icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t" + _vm._s(_vm.helperText) + "\n\t")], 1) : _vm._e(), _vm._v(" "), _vm.isNotificationEmail ? _c("em", [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "Primary email for password reset and notifications")) + "\n\t")]) : _vm._e()]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      "input-id": _vm.inputId,
      readable: _vm.primaryEmail.readable,
      "handle-scope-change": _vm.savePrimaryEmailScope,
      "is-editable": true,
      "is-multi-value-supported": true,
      "is-valid-section": _vm.isValidSection,
      scope: _vm.primaryEmail.scope
    },
    on: {
      "update:scope": function updateScope($event) {
        return _vm.$set(_vm.primaryEmail, "scope", $event);
      },
      "add-additional": _vm.onAddAdditionalEmail
    }
  }), _vm._v(" "), _vm.displayNameChangeSupported ? [_c("Email", {
    attrs: {
      primary: true,
      scope: _vm.primaryEmail.scope,
      email: _vm.primaryEmail.value,
      "active-notification-email": _vm.notificationEmail
    },
    on: {
      "update:scope": function updateScope($event) {
        return _vm.$set(_vm.primaryEmail, "scope", $event);
      },
      "update:email": [function ($event) {
        return _vm.$set(_vm.primaryEmail, "value", $event);
      }, _vm.onUpdateEmail],
      "update:activeNotificationEmail": function updateActiveNotificationEmail($event) {
        _vm.notificationEmail = $event;
      },
      "update:active-notification-email": function updateActiveNotificationEmail($event) {
        _vm.notificationEmail = $event;
      },
      "update:notification-email": _vm.onUpdateNotificationEmail
    }
  })] : _c("span", [_vm._v("\n\t\t" + _vm._s(_vm.primaryEmail.value || _vm.t("settings", "No email address set")) + "\n\t")]), _vm._v(" "), _vm.additionalEmails.length ? [_c("em", {
    staticClass: "additional-emails-label"
  }, [_vm._v(_vm._s(_vm.t("settings", "Additional emails")))]), _vm._v(" "), _vm._l(_vm.additionalEmails, function (additionalEmail, index) {
    return _c("Email", {
      key: additionalEmail.key,
      attrs: {
        index: index,
        scope: additionalEmail.scope,
        email: additionalEmail.value,
        "local-verification-state": parseInt(additionalEmail.locallyVerified, 10),
        "active-notification-email": _vm.notificationEmail
      },
      on: {
        "update:scope": function updateScope($event) {
          return _vm.$set(additionalEmail, "scope", $event);
        },
        "update:email": [function ($event) {
          return _vm.$set(additionalEmail, "value", $event);
        }, _vm.onUpdateEmail],
        "update:activeNotificationEmail": function updateActiveNotificationEmail($event) {
          _vm.notificationEmail = $event;
        },
        "update:active-notification-email": function updateActiveNotificationEmail($event) {
          _vm.notificationEmail = $event;
        },
        "update:notification-email": _vm.onUpdateNotificationEmail,
        "delete-additional-email": function deleteAdditionalEmail($event) {
          return _vm.onDeleteAdditionalEmail(index);
        }
      }
    });
  })] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your handle")
    }
  }, "AccountPropertySection", _vm.fediverse, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your headline")
    }
  }, "AccountPropertySection", _vm.headline, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "language"
  }, [_c("select", {
    attrs: {
      id: _vm.inputId,
      placeholder: _vm.t("settings", "Language")
    },
    on: {
      change: _vm.onLanguageChange
    }
  }, [_vm._l(_vm.commonLanguages, function (commonLanguage) {
    return _c("option", {
      key: commonLanguage.code,
      domProps: {
        selected: _vm.language.code === commonLanguage.code,
        value: commonLanguage.code
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(commonLanguage.name) + "\n\t\t")]);
  }), _vm._v(" "), _c("option", {
    attrs: {
      disabled: ""
    }
  }, [_vm._v("\n\t\t\t──────────\n\t\t")]), _vm._v(" "), _vm._l(_vm.otherLanguages, function (otherLanguage) {
    return _c("option", {
      key: otherLanguage.code,
      domProps: {
        selected: _vm.language.code === otherLanguage.code,
        value: otherLanguage.code
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(otherLanguage.name) + "\n\t\t")]);
  })], 2), _vm._v(" "), _c("a", {
    attrs: {
      href: "https://www.transifex.com/nextcloud/nextcloud/",
      target: "_blank",
      rel: "noreferrer noopener"
    }
  }, [_c("em", [_vm._v(_vm._s(_vm.t("settings", "Help translate")))])])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      "input-id": _vm.inputId,
      readable: _vm.propertyReadable
    }
  }), _vm._v(" "), _vm.isEditable ? [_c("Language", {
    attrs: {
      "input-id": _vm.inputId,
      "common-languages": _vm.commonLanguages,
      "other-languages": _vm.otherLanguages,
      language: _vm.language
    },
    on: {
      "update:language": function updateLanguage($event) {
        _vm.language = $event;
      }
    }
  })] : _c("span", [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "No language set")) + "\n\t")])], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "locale"
  }, [_c("select", {
    attrs: {
      id: _vm.inputId,
      placeholder: _vm.t("settings", "Locale")
    },
    on: {
      change: _vm.onLocaleChange
    }
  }, [_vm._l(_vm.localesForLanguage, function (currentLocale) {
    return _c("option", {
      key: currentLocale.code,
      domProps: {
        selected: _vm.locale.code === currentLocale.code,
        value: currentLocale.code
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(currentLocale.name) + "\n\t\t")]);
  }), _vm._v(" "), _c("option", {
    attrs: {
      disabled: ""
    }
  }, [_vm._v("\n\t\t\t──────────\n\t\t")]), _vm._v(" "), _vm._l(_vm.otherLocales, function (currentLocale) {
    return _c("option", {
      key: currentLocale.code,
      domProps: {
        selected: _vm.locale.code === currentLocale.code,
        value: currentLocale.code
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(currentLocale.name) + "\n\t\t")]);
  })], 2), _vm._v(" "), _c("div", {
    staticClass: "example"
  }, [_c("Web", {
    attrs: {
      size: 20
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "example__text"
  }, [_c("p", [_c("span", [_vm._v(_vm._s(_vm.example.date))]), _vm._v(" "), _c("span", [_vm._v(_vm._s(_vm.example.time))])]), _vm._v(" "), _c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Week starts on {firstDayOfWeek}", {
    firstDayOfWeek: _vm.example.firstDayOfWeek
  })) + "\n\t\t\t")])])], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      "input-id": _vm.inputId,
      readable: _vm.propertyReadable
    }
  }), _vm._v(" "), _vm.isEditable ? [_c("Locale", {
    attrs: {
      "input-id": _vm.inputId,
      "locales-for-language": _vm.localesForLanguage,
      "other-locales": _vm.otherLocales,
      locale: _vm.locale
    },
    on: {
      "update:locale": function updateLocale($event) {
        _vm.locale = $event;
      }
    }
  })] : _c("span", [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "No locale set")) + "\n\t")])], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your location")
    }
  }, "AccountPropertySection", _vm.location, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your organisation")
    }
  }, "AccountPropertySection", _vm.organisation, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2&":
/*!************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your phone number"),
      type: "tel",
      "on-validate": _vm.onValidate
    }
  }, "AccountPropertySection", _vm.phone, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("a", _vm._g({
    class: {
      disabled: _vm.disabled
    },
    attrs: {
      href: "#profile-visibility"
    }
  }, _vm.$listeners), [_c("ChevronDownIcon", {
    staticClass: "anchor-icon",
    attrs: {
      size: 22
    }
  }), _vm._v("\n\t" + _vm._s(_vm.t("settings", "Edit your Profile visibility")) + "\n")], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "checkbox-container"
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      checked: _vm.isProfileEnabled,
      loading: _vm.loading
    },
    on: {
      "update:checked": [function ($event) {
        _vm.isProfileEnabled = $event;
      }, _vm.saveEnableProfile]
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "Enable Profile")) + "\n\t")])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("a", {
    staticClass: "preview-card",
    class: {
      disabled: _vm.disabled
    },
    attrs: {
      href: _vm.profilePageLink
    }
  }, [_c("NcAvatar", {
    staticClass: "preview-card__avatar",
    attrs: {
      user: _vm.userId,
      size: 48,
      "show-user-status": true,
      "show-user-status-compact": false,
      "disable-menu": true,
      "disable-tooltip": true
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "preview-card__header"
  }, [_c("span", [_vm._v(_vm._s(_vm.displayName))])]), _vm._v(" "), _c("div", {
    staticClass: "preview-card__footer"
  }, [_c("span", [_vm._v(_vm._s(_vm.organisation))])])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      readable: _vm.propertyReadable
    }
  }), _vm._v(" "), _c("ProfileCheckbox", {
    attrs: {
      "profile-enabled": _vm.profileEnabled
    },
    on: {
      "update:profileEnabled": function updateProfileEnabled($event) {
        _vm.profileEnabled = $event;
      },
      "update:profile-enabled": function updateProfileEnabled($event) {
        _vm.profileEnabled = $event;
      }
    }
  }), _vm._v(" "), _c("ProfilePreviewCard", {
    attrs: {
      organisation: _vm.organisation,
      "display-name": _vm.displayName,
      "profile-enabled": _vm.profileEnabled,
      "user-id": _vm.userId
    }
  }), _vm._v(" "), _c("EditProfileAnchorLink", {
    attrs: {
      "profile-enabled": _vm.profileEnabled
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", {
    style: {
      marginLeft: _vm.marginLeft
    },
    attrs: {
      id: "profile-visibility"
    }
  }, [_c("HeaderBar", {
    attrs: {
      readable: _vm.heading
    }
  }), _vm._v(" "), _c("em", {
    class: {
      disabled: _vm.disabled
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", 'The more restrictive setting of either visibility or scope is respected on your Profile. For example, if visibility is set to "Show to everyone" and scope is set to "Private", "Private" is respected.')) + "\n\t")]), _vm._v(" "), _c("div", {
    staticClass: "visibility-dropdowns",
    style: {
      gridTemplateRows: "repeat(".concat(_vm.rows, ", 44px)")
    }
  }, _vm._l(_vm.visibilityParams, function (param) {
    return _c("VisibilityDropdown", {
      key: param.id,
      attrs: {
        "param-id": param.id,
        "display-id": param.displayId,
        visibility: param.visibility
      },
      on: {
        "update:visibility": function updateVisibility($event) {
          return _vm.$set(param, "visibility", $event);
        }
      }
    });
  }), 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "visibility-container",
    class: {
      disabled: _vm.disabled
    }
  }, [_c("label", {
    attrs: {
      for: _vm.inputId
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "{displayId}", {
    displayId: _vm.displayId
  })) + "\n\t")]), _vm._v(" "), _c("NcMultiselect", {
    staticClass: "visibility-container__multiselect",
    attrs: {
      id: _vm.inputId,
      options: _vm.visibilityOptions,
      "track-by": "name",
      label: "label",
      value: _vm.visibilityObject
    },
    on: {
      change: _vm.onVisibilityChange
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your role")
    }
  }, "AccountPropertySection", _vm.role, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your Twitter handle")
    }
  }, "AccountPropertySection", _vm.twitter, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("AccountPropertySection", _vm._b({
    attrs: {
      placeholder: _vm.t("settings", "Your website"),
      type: "url",
      "on-validate": _vm.onValidate
    }
  }, "AccountPropertySection", _vm.website, false, true));
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("HeaderBar", {
    attrs: {
      scope: _vm.scope,
      readable: _vm.readable,
      "input-id": _vm.inputId,
      "is-editable": _vm.isEditable
    },
    on: {
      "update:scope": function updateScope($event) {
        _vm.scope = $event;
      },
      "update:readable": function updateReadable($event) {
        _vm.readable = $event;
      }
    }
  }), _vm._v(" "), _vm.isEditable ? _c("div", {
    staticClass: "property"
  }, [_vm.multiLine ? _c("textarea", {
    attrs: {
      id: _vm.inputId,
      placeholder: _vm.placeholder,
      rows: "8",
      autocapitalize: "none",
      autocomplete: "off",
      autocorrect: "off"
    },
    domProps: {
      value: _vm.value
    },
    on: {
      input: _vm.onPropertyChange
    }
  }) : _c("input", {
    ref: "input",
    attrs: {
      id: _vm.inputId,
      placeholder: _vm.placeholder,
      type: _vm.type,
      "aria-describedby": _vm.helperText ? "".concat(_vm.name, "-helper-text") : "",
      autocapitalize: "none",
      autocomplete: "on",
      autocorrect: "off"
    },
    domProps: {
      value: _vm.value
    },
    on: {
      input: _vm.onPropertyChange
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "property__actions-container"
  }, [_c("transition", {
    attrs: {
      name: "fade"
    }
  }, [_vm.showCheckmarkIcon ? _c("Check", {
    attrs: {
      size: 20
    }
  }) : _vm.showErrorIcon ? _c("AlertOctagon", {
    attrs: {
      size: 20
    }
  }) : _vm._e()], 1)], 1)]) : _c("span", [_vm._v("\n\t\t" + _vm._s(_vm.value || _vm.t("settings", "No {property} set", {
    property: _vm.readable.toLocaleLowerCase()
  })) + "\n\t")]), _vm._v(" "), _vm.helperText ? _c("p", {
    staticClass: "property__helper-text-message property__helper-text-message--error",
    attrs: {
      id: "".concat(_vm.name, "-helper-text")
    }
  }, [_c("AlertCircle", {
    staticClass: "property__helper-text-message__icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t" + _vm._s(_vm.helperText) + "\n\t")], 1) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcActions", {
    class: {
      "federation-actions": !_vm.additional,
      "federation-actions--additional": _vm.additional
    },
    attrs: {
      "aria-label": _vm.ariaLabel,
      "default-icon": _vm.scopeIcon,
      disabled: _vm.disabled
    }
  }, _vm._l(_vm.federationScopes, function (federationScope) {
    return _c("FederationControlAction", {
      key: federationScope.name,
      attrs: {
        "active-scope": _vm.scope,
        "display-name": federationScope.displayName,
        "handle-scope-change": _vm.changeScope,
        "icon-class": federationScope.iconClass,
        "is-supported-scope": _vm.supportedScopes.includes(federationScope.name),
        name: federationScope.name,
        "tooltip-disabled": federationScope.tooltipDisabled,
        tooltip: federationScope.tooltip,
        "aria-label": federationScope.tooltip
      }
    });
  }), 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcActionButton", {
    staticClass: "federation-actions__btn",
    class: {
      "federation-actions__btn--active": _vm.activeScope === _vm.name
    },
    attrs: {
      "aria-label": _vm.isSupportedScope ? _vm.tooltip : _vm.tooltipDisabled,
      "close-after-click": true,
      disabled: !_vm.isSupportedScope,
      icon: _vm.iconClass,
      title: _vm.isSupportedScope ? _vm.tooltip : _vm.tooltipDisabled
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.updateScope.apply(null, arguments);
      }
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("h3", {
    class: {
      "setting-property": _vm.isSettingProperty,
      "profile-property": _vm.isProfileProperty
    }
  }, [_c("label", {
    attrs: {
      for: _vm.inputId
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.readable) + "\n\t")]), _vm._v(" "), _vm.scope ? [_c("FederationControl", {
    staticClass: "federation-control",
    attrs: {
      readable: _vm.readable,
      scope: _vm.localScope
    },
    on: {
      "update:scope": [function ($event) {
        _vm.localScope = $event;
      }, _vm.onScopeChange]
    }
  })] : _vm._e(), _vm._v(" "), _vm.isEditable && _vm.isMultiValueSupported ? [_c("NcButton", {
    attrs: {
      type: "tertiary",
      disabled: !_vm.isValidSection,
      "aria-label": _vm.t("settings", "Add additional email")
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.onAddAdditional.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Plus", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 32235154)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Add")) + "\n\t\t")])] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-5ab4ff28] {\n  grid-row: 1/3;\n}\n.avatar__container[data-v-5ab4ff28] {\n  margin: 0 auto;\n  display: flex;\n  flex-direction: column;\n  justify-content: center;\n  align-items: center;\n  gap: 16px 0;\n  width: 300px;\n}\n.avatar__container span[data-v-5ab4ff28] {\n  color: var(--color-text-lighter);\n}\n.avatar__preview[data-v-5ab4ff28] {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  width: 180px;\n  height: 180px;\n}\n.avatar__buttons[data-v-5ab4ff28] {\n  display: flex;\n  gap: 0 10px;\n}\n.avatar__cropper[data-v-5ab4ff28] {\n  width: 300px;\n  height: 300px;\n  overflow: hidden;\n}\n.avatar__cropper-buttons[data-v-5ab4ff28] {\n  width: 100%;\n  display: flex;\n  justify-content: space-between;\n}\n.avatar__cropper[data-v-5ab4ff28] .cropper-view-box {\n  border-radius: 50%;\n}\ninput[type=file][data-v-5ab4ff28] {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".details[data-v-692f485a] {\n  display: flex;\n  flex-direction: column;\n  margin: 10px 32px 10px 0;\n  gap: 16px 0;\n  color: var(--color-text-lighter);\n}\n.details__groups[data-v-692f485a], .details__quota[data-v-692f485a] {\n  display: flex;\n  gap: 0 10px;\n}\n.details__groups-info[data-v-692f485a], .details__quota-info[data-v-692f485a] {\n  display: flex;\n  flex-direction: column;\n  width: 100%;\n  gap: 4px 0;\n}\n.details__groups-list[data-v-692f485a], .details__quota-list[data-v-692f485a] {\n  font-weight: bold;\n}\n.details__groups[data-v-692f485a] .material-design-icon, .details__quota[data-v-692f485a] .material-design-icon {\n  align-self: flex-start;\n  margin-top: 2px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".email[data-v-a9c46cb4] {\n  display: grid;\n  align-items: center;\n}\n.email input[data-v-a9c46cb4] {\n  grid-area: 1/1;\n  width: 100%;\n}\n.email .email__actions-container[data-v-a9c46cb4] {\n  grid-area: 1/1;\n  justify-self: flex-end;\n  height: 30px;\n  display: flex;\n  gap: 0 2px;\n  margin-right: 5px;\n}\n.email .email__actions-container .email__actions[data-v-a9c46cb4] {\n  opacity: 0.4 !important;\n}\n.email .email__actions-container .email__actions[data-v-a9c46cb4]:hover, .email .email__actions-container .email__actions[data-v-a9c46cb4]:focus, .email .email__actions-container .email__actions[data-v-a9c46cb4]:active {\n  opacity: 0.8 !important;\n}\n.email .email__actions-container .email__actions[data-v-a9c46cb4] button {\n  height: 30px !important;\n  min-height: 30px !important;\n  width: 30px !important;\n  min-width: 30px !important;\n}\n.email__helper-text-message[data-v-a9c46cb4] {\n  padding: 4px 0;\n  display: flex;\n  align-items: center;\n}\n.email__helper-text-message__icon[data-v-a9c46cb4] {\n  margin-right: 8px;\n  align-self: start;\n  margin-top: 4px;\n}\n.email__helper-text-message--error[data-v-a9c46cb4] {\n  color: var(--color-error);\n}\n.fade-enter[data-v-a9c46cb4],\n.fade-leave-to[data-v-a9c46cb4] {\n  opacity: 0;\n}\n.fade-enter-active[data-v-a9c46cb4] {\n  transition: opacity 200ms ease-out;\n}\n.fade-leave-active[data-v-a9c46cb4] {\n  transition: opacity 300ms ease-out;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-c3e547e2] {\n  padding: 10px 10px;\n}\nsection[data-v-c3e547e2] button:disabled {\n  cursor: default;\n}\nsection .additional-emails-label[data-v-c3e547e2] {\n  display: block;\n  margin-top: 16px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".language[data-v-0e2d022c] {\n  display: grid;\n}\n.language select[data-v-0e2d022c] {\n  width: 100%;\n}\n.language a[data-v-0e2d022c] {\n  color: var(--color-main-text);\n  text-decoration: none;\n  width: max-content;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-126bf24b] {\n  padding: 10px 10px;\n}\nsection[data-v-126bf24b] button:disabled {\n  cursor: default;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".locale[data-v-9ef24824] {\n  display: grid;\n}\n.locale select[data-v-9ef24824] {\n  width: 100%;\n}\n.example[data-v-9ef24824] {\n  margin: 10px 0;\n  display: flex;\n  gap: 0 10px;\n  color: var(--color-text-lighter);\n}\n.example[data-v-9ef24824] .material-design-icon {\n  align-self: flex-start;\n  margin-top: 2px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-6d00a8c7] {\n  padding: 10px 10px;\n}\nsection[data-v-6d00a8c7] button:disabled {\n  cursor: default;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "html {\n  scroll-behavior: smooth;\n}\n@media screen and (prefers-reduced-motion: reduce) {\nhtml {\n    scroll-behavior: auto;\n}\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "a[data-v-29aba6ea] {\n  display: block;\n  height: 44px;\n  width: 290px;\n  line-height: 44px;\n  padding: 0 16px;\n  margin: 14px auto;\n  border-radius: var(--border-radius-pill);\n  opacity: 0.4;\n  background-color: transparent;\n}\na .anchor-icon[data-v-29aba6ea] {\n  display: inline-block;\n  vertical-align: middle;\n  margin-top: 6px;\n  margin-right: 8px;\n}\na[data-v-29aba6ea]:hover, a[data-v-29aba6ea]:focus, a[data-v-29aba6ea]:active {\n  opacity: 0.8;\n  background-color: rgba(127, 127, 127, 0.25);\n}\na.disabled[data-v-29aba6ea] {\n  pointer-events: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".preview-card[data-v-3c8483c2] {\n  display: flex;\n  flex-direction: column;\n  position: relative;\n  width: 290px;\n  height: 116px;\n  margin: 14px auto;\n  border-radius: var(--border-radius-large);\n  background-color: var(--color-main-background);\n  font-weight: bold;\n  box-shadow: 0 2px 9px var(--color-box-shadow);\n}\n.preview-card[data-v-3c8483c2]:hover, .preview-card[data-v-3c8483c2]:focus, .preview-card[data-v-3c8483c2]:active {\n  box-shadow: 0 2px 12px var(--color-box-shadow);\n}\n.preview-card[data-v-3c8483c2]:focus-visible {\n  outline: var(--color-main-text) solid 1px;\n  outline-offset: 3px;\n}\n.preview-card.disabled[data-v-3c8483c2] {\n  filter: grayscale(1);\n  opacity: 0.5;\n  cursor: default;\n  box-shadow: 0 0 3px var(--color-box-shadow);\n}\n.preview-card.disabled *[data-v-3c8483c2], .preview-card.disabled[data-v-3c8483c2] * {\n  cursor: default;\n}\n.preview-card__avatar[data-v-3c8483c2] {\n  position: absolute !important;\n  top: 40px;\n  left: 18px;\n  z-index: 1;\n}\n.preview-card__avatar[data-v-3c8483c2]:not(.avatardiv--unknown) {\n  box-shadow: 0 0 0 3px var(--color-main-background) !important;\n}\n.preview-card__header[data-v-3c8483c2], .preview-card__footer[data-v-3c8483c2] {\n  position: relative;\n  width: auto;\n}\n.preview-card__header span[data-v-3c8483c2], .preview-card__footer span[data-v-3c8483c2] {\n  position: absolute;\n  left: 78px;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  overflow-wrap: anywhere;\n}\n@supports (-webkit-line-clamp: 2) {\n.preview-card__header span[data-v-3c8483c2], .preview-card__footer span[data-v-3c8483c2] {\n    display: -webkit-box;\n    -webkit-line-clamp: 2;\n    -webkit-box-orient: vertical;\n}\n}\n.preview-card__header[data-v-3c8483c2] {\n  height: 70px;\n  border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;\n  background-color: var(--color-primary);\n  background-image: var(--gradient-primary-background);\n}\n.preview-card__header span[data-v-3c8483c2] {\n  bottom: 0;\n  color: var(--color-primary-text);\n  font-size: 18px;\n  font-weight: bold;\n  margin: 0 4px 8px 0;\n}\n.preview-card__footer[data-v-3c8483c2] {\n  height: 46px;\n}\n.preview-card__footer span[data-v-3c8483c2] {\n  top: 0;\n  color: var(--color-text-maxcontrast);\n  font-size: 14px;\n  font-weight: normal;\n  margin: 4px 4px 0 0;\n  line-height: 1.3;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-22b97e2f] {\n  padding: 10px 10px;\n}\nsection[data-v-22b97e2f] button:disabled {\n  cursor: default;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-561c922f] {\n  padding: 30px;\n  max-width: 900px;\n  width: 100%;\n}\nsection em[data-v-561c922f] {\n  display: block;\n  margin: 16px 0;\n}\nsection em.disabled[data-v-561c922f] {\n  filter: grayscale(1);\n  opacity: 0.5;\n  cursor: default;\n  pointer-events: none;\n}\nsection em.disabled *[data-v-561c922f], section em.disabled[data-v-561c922f] * {\n  cursor: default;\n  pointer-events: none;\n}\nsection .visibility-dropdowns[data-v-561c922f] {\n  display: grid;\n  gap: 10px 40px;\n}\n@media (min-width: 1200px) {\nsection[data-v-561c922f] {\n    width: 940px;\n}\nsection .visibility-dropdowns[data-v-561c922f] {\n    grid-auto-flow: column;\n}\n}\n@media (max-width: 1200px) {\nsection[data-v-561c922f] {\n    width: 470px;\n}\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".visibility-container[data-v-5b020be8] {\n  display: flex;\n  width: max-content;\n}\n.visibility-container.disabled[data-v-5b020be8] {\n  filter: grayscale(1);\n  opacity: 0.5;\n  cursor: default;\n  pointer-events: none;\n}\n.visibility-container.disabled *[data-v-5b020be8], .visibility-container.disabled[data-v-5b020be8] * {\n  cursor: default;\n  pointer-events: none;\n}\n.visibility-container label[data-v-5b020be8] {\n  color: var(--color-text-lighter);\n  width: 150px;\n  line-height: 50px;\n}\n.visibility-container__multiselect[data-v-5b020be8] {\n  width: 260px;\n  max-width: 40vw;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "section[data-v-2906f1a6] {\n  padding: 10px 10px;\n}\nsection[data-v-2906f1a6] button:disabled {\n  cursor: default;\n}\nsection .property[data-v-2906f1a6] {\n  display: grid;\n  align-items: center;\n}\nsection .property textarea[data-v-2906f1a6] {\n  resize: vertical;\n  grid-area: 1/1;\n  width: 100%;\n}\nsection .property input[data-v-2906f1a6] {\n  grid-area: 1/1;\n  width: 100%;\n}\nsection .property .property__actions-container[data-v-2906f1a6] {\n  grid-area: 1/1;\n  justify-self: flex-end;\n  align-self: flex-end;\n  height: 30px;\n  display: flex;\n  gap: 0 2px;\n  margin-right: 5px;\n  margin-bottom: 5px;\n}\nsection .property__helper-text-message[data-v-2906f1a6] {\n  padding: 4px 0;\n  display: flex;\n  align-items: center;\n}\nsection .property__helper-text-message__icon[data-v-2906f1a6] {\n  margin-right: 8px;\n  align-self: start;\n  margin-top: 4px;\n}\nsection .property__helper-text-message--error[data-v-2906f1a6] {\n  color: var(--color-error);\n}\nsection .fade-enter[data-v-2906f1a6],\nsection .fade-leave-to[data-v-2906f1a6] {\n  opacity: 0;\n}\nsection .fade-enter-active[data-v-2906f1a6] {\n  transition: opacity 200ms ease-out;\n}\nsection .fade-leave-active[data-v-2906f1a6] {\n  transition: opacity 300ms ease-out;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".federation-actions[data-v-08cbb240],\n.federation-actions--additional[data-v-08cbb240] {\n  opacity: 0.4 !important;\n}\n.federation-actions[data-v-08cbb240]:hover, .federation-actions[data-v-08cbb240]:focus, .federation-actions[data-v-08cbb240]:active,\n.federation-actions--additional[data-v-08cbb240]:hover,\n.federation-actions--additional[data-v-08cbb240]:focus,\n.federation-actions--additional[data-v-08cbb240]:active {\n  opacity: 0.8 !important;\n}\n.federation-actions--additional[data-v-08cbb240] button {\n  padding-bottom: 7px;\n  height: 30px !important;\n  min-height: 30px !important;\n  width: 30px !important;\n  min-width: 30px !important;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".federation-actions__btn[data-v-ba1ab8d4] p {\n  width: 150px !important;\n  padding: 8px 0 !important;\n  color: var(--color-main-text) !important;\n  font-size: 12.8px !important;\n  line-height: 1.5em !important;\n}\n.federation-actions__btn--active[data-v-ba1ab8d4] {\n  background-color: var(--color-primary-light) !important;\n  box-shadow: inset 2px 0 var(--color-primary) !important;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "h3[data-v-6a8a7fec] {\n  display: inline-flex;\n  width: 100%;\n  margin: 12px 0 0 0;\n  gap: 8px;\n  align-items: center;\n  font-size: 16px;\n  color: var(--color-text-light);\n}\nh3.profile-property[data-v-6a8a7fec] {\n  height: 38px;\n}\nh3.setting-property[data-v-6a8a7fec] {\n  height: 44px;\n}\nh3 label[data-v-6a8a7fec] {\n  cursor: pointer;\n}\n.federation-control[data-v-6a8a7fec] {\n  margin: 0;\n}\n.button-vue[data-v-6a8a7fec] {\n  margin: 0 0 0 auto !important;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/moment/locale sync recursive ^\\.\\/.*$":
/*!***************************************************!*\
  !*** ./node_modules/moment/locale/ sync ^\.\/.*$ ***!
  \***************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./af": "./node_modules/moment/locale/af.js",
	"./af.js": "./node_modules/moment/locale/af.js",
	"./ar": "./node_modules/moment/locale/ar.js",
	"./ar-dz": "./node_modules/moment/locale/ar-dz.js",
	"./ar-dz.js": "./node_modules/moment/locale/ar-dz.js",
	"./ar-kw": "./node_modules/moment/locale/ar-kw.js",
	"./ar-kw.js": "./node_modules/moment/locale/ar-kw.js",
	"./ar-ly": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ly.js": "./node_modules/moment/locale/ar-ly.js",
	"./ar-ma": "./node_modules/moment/locale/ar-ma.js",
	"./ar-ma.js": "./node_modules/moment/locale/ar-ma.js",
	"./ar-sa": "./node_modules/moment/locale/ar-sa.js",
	"./ar-sa.js": "./node_modules/moment/locale/ar-sa.js",
	"./ar-tn": "./node_modules/moment/locale/ar-tn.js",
	"./ar-tn.js": "./node_modules/moment/locale/ar-tn.js",
	"./ar.js": "./node_modules/moment/locale/ar.js",
	"./az": "./node_modules/moment/locale/az.js",
	"./az.js": "./node_modules/moment/locale/az.js",
	"./be": "./node_modules/moment/locale/be.js",
	"./be.js": "./node_modules/moment/locale/be.js",
	"./bg": "./node_modules/moment/locale/bg.js",
	"./bg.js": "./node_modules/moment/locale/bg.js",
	"./bm": "./node_modules/moment/locale/bm.js",
	"./bm.js": "./node_modules/moment/locale/bm.js",
	"./bn": "./node_modules/moment/locale/bn.js",
	"./bn-bd": "./node_modules/moment/locale/bn-bd.js",
	"./bn-bd.js": "./node_modules/moment/locale/bn-bd.js",
	"./bn.js": "./node_modules/moment/locale/bn.js",
	"./bo": "./node_modules/moment/locale/bo.js",
	"./bo.js": "./node_modules/moment/locale/bo.js",
	"./br": "./node_modules/moment/locale/br.js",
	"./br.js": "./node_modules/moment/locale/br.js",
	"./bs": "./node_modules/moment/locale/bs.js",
	"./bs.js": "./node_modules/moment/locale/bs.js",
	"./ca": "./node_modules/moment/locale/ca.js",
	"./ca.js": "./node_modules/moment/locale/ca.js",
	"./cs": "./node_modules/moment/locale/cs.js",
	"./cs.js": "./node_modules/moment/locale/cs.js",
	"./cv": "./node_modules/moment/locale/cv.js",
	"./cv.js": "./node_modules/moment/locale/cv.js",
	"./cy": "./node_modules/moment/locale/cy.js",
	"./cy.js": "./node_modules/moment/locale/cy.js",
	"./da": "./node_modules/moment/locale/da.js",
	"./da.js": "./node_modules/moment/locale/da.js",
	"./de": "./node_modules/moment/locale/de.js",
	"./de-at": "./node_modules/moment/locale/de-at.js",
	"./de-at.js": "./node_modules/moment/locale/de-at.js",
	"./de-ch": "./node_modules/moment/locale/de-ch.js",
	"./de-ch.js": "./node_modules/moment/locale/de-ch.js",
	"./de.js": "./node_modules/moment/locale/de.js",
	"./dv": "./node_modules/moment/locale/dv.js",
	"./dv.js": "./node_modules/moment/locale/dv.js",
	"./el": "./node_modules/moment/locale/el.js",
	"./el.js": "./node_modules/moment/locale/el.js",
	"./en-au": "./node_modules/moment/locale/en-au.js",
	"./en-au.js": "./node_modules/moment/locale/en-au.js",
	"./en-ca": "./node_modules/moment/locale/en-ca.js",
	"./en-ca.js": "./node_modules/moment/locale/en-ca.js",
	"./en-gb": "./node_modules/moment/locale/en-gb.js",
	"./en-gb.js": "./node_modules/moment/locale/en-gb.js",
	"./en-ie": "./node_modules/moment/locale/en-ie.js",
	"./en-ie.js": "./node_modules/moment/locale/en-ie.js",
	"./en-il": "./node_modules/moment/locale/en-il.js",
	"./en-il.js": "./node_modules/moment/locale/en-il.js",
	"./en-in": "./node_modules/moment/locale/en-in.js",
	"./en-in.js": "./node_modules/moment/locale/en-in.js",
	"./en-nz": "./node_modules/moment/locale/en-nz.js",
	"./en-nz.js": "./node_modules/moment/locale/en-nz.js",
	"./en-sg": "./node_modules/moment/locale/en-sg.js",
	"./en-sg.js": "./node_modules/moment/locale/en-sg.js",
	"./eo": "./node_modules/moment/locale/eo.js",
	"./eo.js": "./node_modules/moment/locale/eo.js",
	"./es": "./node_modules/moment/locale/es.js",
	"./es-do": "./node_modules/moment/locale/es-do.js",
	"./es-do.js": "./node_modules/moment/locale/es-do.js",
	"./es-mx": "./node_modules/moment/locale/es-mx.js",
	"./es-mx.js": "./node_modules/moment/locale/es-mx.js",
	"./es-us": "./node_modules/moment/locale/es-us.js",
	"./es-us.js": "./node_modules/moment/locale/es-us.js",
	"./es.js": "./node_modules/moment/locale/es.js",
	"./et": "./node_modules/moment/locale/et.js",
	"./et.js": "./node_modules/moment/locale/et.js",
	"./eu": "./node_modules/moment/locale/eu.js",
	"./eu.js": "./node_modules/moment/locale/eu.js",
	"./fa": "./node_modules/moment/locale/fa.js",
	"./fa.js": "./node_modules/moment/locale/fa.js",
	"./fi": "./node_modules/moment/locale/fi.js",
	"./fi.js": "./node_modules/moment/locale/fi.js",
	"./fil": "./node_modules/moment/locale/fil.js",
	"./fil.js": "./node_modules/moment/locale/fil.js",
	"./fo": "./node_modules/moment/locale/fo.js",
	"./fo.js": "./node_modules/moment/locale/fo.js",
	"./fr": "./node_modules/moment/locale/fr.js",
	"./fr-ca": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ca.js": "./node_modules/moment/locale/fr-ca.js",
	"./fr-ch": "./node_modules/moment/locale/fr-ch.js",
	"./fr-ch.js": "./node_modules/moment/locale/fr-ch.js",
	"./fr.js": "./node_modules/moment/locale/fr.js",
	"./fy": "./node_modules/moment/locale/fy.js",
	"./fy.js": "./node_modules/moment/locale/fy.js",
	"./ga": "./node_modules/moment/locale/ga.js",
	"./ga.js": "./node_modules/moment/locale/ga.js",
	"./gd": "./node_modules/moment/locale/gd.js",
	"./gd.js": "./node_modules/moment/locale/gd.js",
	"./gl": "./node_modules/moment/locale/gl.js",
	"./gl.js": "./node_modules/moment/locale/gl.js",
	"./gom-deva": "./node_modules/moment/locale/gom-deva.js",
	"./gom-deva.js": "./node_modules/moment/locale/gom-deva.js",
	"./gom-latn": "./node_modules/moment/locale/gom-latn.js",
	"./gom-latn.js": "./node_modules/moment/locale/gom-latn.js",
	"./gu": "./node_modules/moment/locale/gu.js",
	"./gu.js": "./node_modules/moment/locale/gu.js",
	"./he": "./node_modules/moment/locale/he.js",
	"./he.js": "./node_modules/moment/locale/he.js",
	"./hi": "./node_modules/moment/locale/hi.js",
	"./hi.js": "./node_modules/moment/locale/hi.js",
	"./hr": "./node_modules/moment/locale/hr.js",
	"./hr.js": "./node_modules/moment/locale/hr.js",
	"./hu": "./node_modules/moment/locale/hu.js",
	"./hu.js": "./node_modules/moment/locale/hu.js",
	"./hy-am": "./node_modules/moment/locale/hy-am.js",
	"./hy-am.js": "./node_modules/moment/locale/hy-am.js",
	"./id": "./node_modules/moment/locale/id.js",
	"./id.js": "./node_modules/moment/locale/id.js",
	"./is": "./node_modules/moment/locale/is.js",
	"./is.js": "./node_modules/moment/locale/is.js",
	"./it": "./node_modules/moment/locale/it.js",
	"./it-ch": "./node_modules/moment/locale/it-ch.js",
	"./it-ch.js": "./node_modules/moment/locale/it-ch.js",
	"./it.js": "./node_modules/moment/locale/it.js",
	"./ja": "./node_modules/moment/locale/ja.js",
	"./ja.js": "./node_modules/moment/locale/ja.js",
	"./jv": "./node_modules/moment/locale/jv.js",
	"./jv.js": "./node_modules/moment/locale/jv.js",
	"./ka": "./node_modules/moment/locale/ka.js",
	"./ka.js": "./node_modules/moment/locale/ka.js",
	"./kk": "./node_modules/moment/locale/kk.js",
	"./kk.js": "./node_modules/moment/locale/kk.js",
	"./km": "./node_modules/moment/locale/km.js",
	"./km.js": "./node_modules/moment/locale/km.js",
	"./kn": "./node_modules/moment/locale/kn.js",
	"./kn.js": "./node_modules/moment/locale/kn.js",
	"./ko": "./node_modules/moment/locale/ko.js",
	"./ko.js": "./node_modules/moment/locale/ko.js",
	"./ku": "./node_modules/moment/locale/ku.js",
	"./ku.js": "./node_modules/moment/locale/ku.js",
	"./ky": "./node_modules/moment/locale/ky.js",
	"./ky.js": "./node_modules/moment/locale/ky.js",
	"./lb": "./node_modules/moment/locale/lb.js",
	"./lb.js": "./node_modules/moment/locale/lb.js",
	"./lo": "./node_modules/moment/locale/lo.js",
	"./lo.js": "./node_modules/moment/locale/lo.js",
	"./lt": "./node_modules/moment/locale/lt.js",
	"./lt.js": "./node_modules/moment/locale/lt.js",
	"./lv": "./node_modules/moment/locale/lv.js",
	"./lv.js": "./node_modules/moment/locale/lv.js",
	"./me": "./node_modules/moment/locale/me.js",
	"./me.js": "./node_modules/moment/locale/me.js",
	"./mi": "./node_modules/moment/locale/mi.js",
	"./mi.js": "./node_modules/moment/locale/mi.js",
	"./mk": "./node_modules/moment/locale/mk.js",
	"./mk.js": "./node_modules/moment/locale/mk.js",
	"./ml": "./node_modules/moment/locale/ml.js",
	"./ml.js": "./node_modules/moment/locale/ml.js",
	"./mn": "./node_modules/moment/locale/mn.js",
	"./mn.js": "./node_modules/moment/locale/mn.js",
	"./mr": "./node_modules/moment/locale/mr.js",
	"./mr.js": "./node_modules/moment/locale/mr.js",
	"./ms": "./node_modules/moment/locale/ms.js",
	"./ms-my": "./node_modules/moment/locale/ms-my.js",
	"./ms-my.js": "./node_modules/moment/locale/ms-my.js",
	"./ms.js": "./node_modules/moment/locale/ms.js",
	"./mt": "./node_modules/moment/locale/mt.js",
	"./mt.js": "./node_modules/moment/locale/mt.js",
	"./my": "./node_modules/moment/locale/my.js",
	"./my.js": "./node_modules/moment/locale/my.js",
	"./nb": "./node_modules/moment/locale/nb.js",
	"./nb.js": "./node_modules/moment/locale/nb.js",
	"./ne": "./node_modules/moment/locale/ne.js",
	"./ne.js": "./node_modules/moment/locale/ne.js",
	"./nl": "./node_modules/moment/locale/nl.js",
	"./nl-be": "./node_modules/moment/locale/nl-be.js",
	"./nl-be.js": "./node_modules/moment/locale/nl-be.js",
	"./nl.js": "./node_modules/moment/locale/nl.js",
	"./nn": "./node_modules/moment/locale/nn.js",
	"./nn.js": "./node_modules/moment/locale/nn.js",
	"./oc-lnc": "./node_modules/moment/locale/oc-lnc.js",
	"./oc-lnc.js": "./node_modules/moment/locale/oc-lnc.js",
	"./pa-in": "./node_modules/moment/locale/pa-in.js",
	"./pa-in.js": "./node_modules/moment/locale/pa-in.js",
	"./pl": "./node_modules/moment/locale/pl.js",
	"./pl.js": "./node_modules/moment/locale/pl.js",
	"./pt": "./node_modules/moment/locale/pt.js",
	"./pt-br": "./node_modules/moment/locale/pt-br.js",
	"./pt-br.js": "./node_modules/moment/locale/pt-br.js",
	"./pt.js": "./node_modules/moment/locale/pt.js",
	"./ro": "./node_modules/moment/locale/ro.js",
	"./ro.js": "./node_modules/moment/locale/ro.js",
	"./ru": "./node_modules/moment/locale/ru.js",
	"./ru.js": "./node_modules/moment/locale/ru.js",
	"./sd": "./node_modules/moment/locale/sd.js",
	"./sd.js": "./node_modules/moment/locale/sd.js",
	"./se": "./node_modules/moment/locale/se.js",
	"./se.js": "./node_modules/moment/locale/se.js",
	"./si": "./node_modules/moment/locale/si.js",
	"./si.js": "./node_modules/moment/locale/si.js",
	"./sk": "./node_modules/moment/locale/sk.js",
	"./sk.js": "./node_modules/moment/locale/sk.js",
	"./sl": "./node_modules/moment/locale/sl.js",
	"./sl.js": "./node_modules/moment/locale/sl.js",
	"./sq": "./node_modules/moment/locale/sq.js",
	"./sq.js": "./node_modules/moment/locale/sq.js",
	"./sr": "./node_modules/moment/locale/sr.js",
	"./sr-cyrl": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr-cyrl.js": "./node_modules/moment/locale/sr-cyrl.js",
	"./sr.js": "./node_modules/moment/locale/sr.js",
	"./ss": "./node_modules/moment/locale/ss.js",
	"./ss.js": "./node_modules/moment/locale/ss.js",
	"./sv": "./node_modules/moment/locale/sv.js",
	"./sv.js": "./node_modules/moment/locale/sv.js",
	"./sw": "./node_modules/moment/locale/sw.js",
	"./sw.js": "./node_modules/moment/locale/sw.js",
	"./ta": "./node_modules/moment/locale/ta.js",
	"./ta.js": "./node_modules/moment/locale/ta.js",
	"./te": "./node_modules/moment/locale/te.js",
	"./te.js": "./node_modules/moment/locale/te.js",
	"./tet": "./node_modules/moment/locale/tet.js",
	"./tet.js": "./node_modules/moment/locale/tet.js",
	"./tg": "./node_modules/moment/locale/tg.js",
	"./tg.js": "./node_modules/moment/locale/tg.js",
	"./th": "./node_modules/moment/locale/th.js",
	"./th.js": "./node_modules/moment/locale/th.js",
	"./tk": "./node_modules/moment/locale/tk.js",
	"./tk.js": "./node_modules/moment/locale/tk.js",
	"./tl-ph": "./node_modules/moment/locale/tl-ph.js",
	"./tl-ph.js": "./node_modules/moment/locale/tl-ph.js",
	"./tlh": "./node_modules/moment/locale/tlh.js",
	"./tlh.js": "./node_modules/moment/locale/tlh.js",
	"./tr": "./node_modules/moment/locale/tr.js",
	"./tr.js": "./node_modules/moment/locale/tr.js",
	"./tzl": "./node_modules/moment/locale/tzl.js",
	"./tzl.js": "./node_modules/moment/locale/tzl.js",
	"./tzm": "./node_modules/moment/locale/tzm.js",
	"./tzm-latn": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm-latn.js": "./node_modules/moment/locale/tzm-latn.js",
	"./tzm.js": "./node_modules/moment/locale/tzm.js",
	"./ug-cn": "./node_modules/moment/locale/ug-cn.js",
	"./ug-cn.js": "./node_modules/moment/locale/ug-cn.js",
	"./uk": "./node_modules/moment/locale/uk.js",
	"./uk.js": "./node_modules/moment/locale/uk.js",
	"./ur": "./node_modules/moment/locale/ur.js",
	"./ur.js": "./node_modules/moment/locale/ur.js",
	"./uz": "./node_modules/moment/locale/uz.js",
	"./uz-latn": "./node_modules/moment/locale/uz-latn.js",
	"./uz-latn.js": "./node_modules/moment/locale/uz-latn.js",
	"./uz.js": "./node_modules/moment/locale/uz.js",
	"./vi": "./node_modules/moment/locale/vi.js",
	"./vi.js": "./node_modules/moment/locale/vi.js",
	"./x-pseudo": "./node_modules/moment/locale/x-pseudo.js",
	"./x-pseudo.js": "./node_modules/moment/locale/x-pseudo.js",
	"./yo": "./node_modules/moment/locale/yo.js",
	"./yo.js": "./node_modules/moment/locale/yo.js",
	"./zh-cn": "./node_modules/moment/locale/zh-cn.js",
	"./zh-cn.js": "./node_modules/moment/locale/zh-cn.js",
	"./zh-hk": "./node_modules/moment/locale/zh-hk.js",
	"./zh-hk.js": "./node_modules/moment/locale/zh-hk.js",
	"./zh-mo": "./node_modules/moment/locale/zh-mo.js",
	"./zh-mo.js": "./node_modules/moment/locale/zh-mo.js",
	"./zh-tw": "./node_modules/moment/locale/zh-tw.js",
	"./zh-tw.js": "./node_modules/moment/locale/zh-tw.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./node_modules/moment/locale sync recursive ^\\.\\/.*$";

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue":
/*!*********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/AvatarSection.vue ***!
  \*********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true& */ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true&");
/* harmony import */ var _AvatarSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AvatarSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AvatarSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5ab4ff28",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/AvatarSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/BiographySection.vue":
/*!************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/BiographySection.vue ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./BiographySection.vue?vue&type=template&id=19df70fc& */ "./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc&");
/* harmony import */ var _BiographySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./BiographySection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _BiographySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__.render,
  _BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/BiographySection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue":
/*!**********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DetailsSection.vue ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DetailsSection.vue?vue&type=template&id=692f485a&scoped=true& */ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true&");
/* harmony import */ var _DetailsSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DetailsSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _DetailsSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "692f485a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/DetailsSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue":
/*!**************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue ***!
  \**************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./DisplayNameSection.vue?vue&type=template&id=2aaa7a70& */ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70&");
/* harmony import */ var _DisplayNameSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DisplayNameSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _DisplayNameSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__.render,
  _DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/DisplayNameSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue":
/*!**************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue ***!
  \**************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Email.vue?vue&type=template&id=a9c46cb4&scoped=true& */ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true&");
/* harmony import */ var _Email_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Email.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js&");
/* harmony import */ var _Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Email_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "a9c46cb4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/EmailSection/Email.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true& */ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true&");
/* harmony import */ var _EmailSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EmailSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _EmailSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "c3e547e2",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue":
/*!************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/FediverseSection.vue ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FediverseSection.vue?vue&type=template&id=3da3a2f8& */ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8&");
/* harmony import */ var _FediverseSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FediverseSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _FediverseSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__.render,
  _FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/FediverseSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue":
/*!***********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/HeadlineSection.vue ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./HeadlineSection.vue?vue&type=template&id=bebab1de& */ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de&");
/* harmony import */ var _HeadlineSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./HeadlineSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _HeadlineSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__.render,
  _HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/HeadlineSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue":
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Language.vue?vue&type=template&id=0e2d022c&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true&");
/* harmony import */ var _Language_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Language.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js&");
/* harmony import */ var _Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Language_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0e2d022c",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue":
/*!***************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true&");
/* harmony import */ var _LanguageSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LanguageSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LanguageSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "126bf24b",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue ***!
  \****************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Locale.vue?vue&type=template&id=9ef24824&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true&");
/* harmony import */ var _Locale_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Locale.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js&");
/* harmony import */ var _Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _Locale_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "9ef24824",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue":
/*!***********************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue ***!
  \***********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true&");
/* harmony import */ var _LocaleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LocaleSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _LocaleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6d00a8c7",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocationSection.vue":
/*!***********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocationSection.vue ***!
  \***********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LocationSection.vue?vue&type=template&id=f78b62e0& */ "./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0&");
/* harmony import */ var _LocationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LocationSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LocationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__.render,
  _LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/LocationSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue":
/*!***************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/OrganisationSection.vue ***!
  \***************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./OrganisationSection.vue?vue&type=template&id=3ece9f6a& */ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a&");
/* harmony import */ var _OrganisationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./OrganisationSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _OrganisationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__.render,
  _OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/OrganisationSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue":
/*!********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/PhoneSection.vue ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PhoneSection.vue?vue&type=template&id=eaab49b2& */ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2&");
/* harmony import */ var _PhoneSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PhoneSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _PhoneSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__.render,
  _PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/PhoneSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue":
/*!********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue ***!
  \********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true&");
/* harmony import */ var _EditProfileAnchorLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EditProfileAnchorLink.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js&");
/* harmony import */ var _EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&");
/* harmony import */ var _EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;



/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _EditProfileAnchorLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "29aba6ea",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProfileCheckbox.vue?vue&type=template&id=a46c582e& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e&");
/* harmony import */ var _ProfileCheckbox_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProfileCheckbox.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ProfileCheckbox_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__.render,
  _ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue":
/*!*****************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue ***!
  \*****************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true&");
/* harmony import */ var _ProfilePreviewCard_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProfilePreviewCard.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js&");
/* harmony import */ var _ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ProfilePreviewCard_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3c8483c2",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue":
/*!*************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true&");
/* harmony import */ var _ProfileSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProfileSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ProfileSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "22b97e2f",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true&");
/* harmony import */ var _ProfileVisibilitySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProfileVisibilitySection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js&");
/* harmony import */ var _ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ProfileVisibilitySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "561c922f",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true&");
/* harmony import */ var _VisibilityDropdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./VisibilityDropdown.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js&");
/* harmony import */ var _VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _VisibilityDropdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5b020be8",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/RoleSection.vue":
/*!*******************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/RoleSection.vue ***!
  \*******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./RoleSection.vue?vue&type=template&id=c066c1a2& */ "./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2&");
/* harmony import */ var _RoleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./RoleSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _RoleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__.render,
  _RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/RoleSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue":
/*!**********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/TwitterSection.vue ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TwitterSection.vue?vue&type=template&id=3c8569fc& */ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc&");
/* harmony import */ var _TwitterSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TwitterSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _TwitterSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__.render,
  _TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/TwitterSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue":
/*!**********************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/WebsiteSection.vue ***!
  \**********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./WebsiteSection.vue?vue&type=template&id=406da84c& */ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c&");
/* harmony import */ var _WebsiteSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./WebsiteSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _WebsiteSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__.render,
  _WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/WebsiteSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue":
/*!*************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true&");
/* harmony import */ var _AccountPropertySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AccountPropertySection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js&");
/* harmony import */ var _AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AccountPropertySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2906f1a6",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue":
/*!********************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FederationControl.vue?vue&type=template&id=08cbb240&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true&");
/* harmony import */ var _FederationControl_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FederationControl.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js&");
/* harmony import */ var _FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FederationControl_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "08cbb240",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/shared/FederationControl.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true&");
/* harmony import */ var _FederationControlAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FederationControlAction.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js&");
/* harmony import */ var _FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FederationControlAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "ba1ab8d4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue":
/*!************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue ***!
  \************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true&");
/* harmony import */ var _HeaderBar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./HeaderBar.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js&");
/* harmony import */ var _HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& */ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _HeaderBar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6a8a7fec",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AvatarSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_BiographySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./BiographySection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_BiographySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DetailsSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DisplayNameSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DisplayNameSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DisplayNameSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EmailSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FediverseSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FediverseSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FediverseSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeadlineSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeadlineSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeadlineSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Language.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LanguageSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Locale.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocaleSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocationSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OrganisationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OrganisationSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OrganisationSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PhoneSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PhoneSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PhoneSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileCheckbox_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileCheckbox.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileCheckbox_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfilePreviewCard.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileVisibilitySection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VisibilityDropdown.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js&":
/*!********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_RoleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./RoleSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_RoleSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TwitterSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TwitterSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TwitterSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_WebsiteSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./WebsiteSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_WebsiteSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AccountPropertySection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControl.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControlAction.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderBar.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true&":
/*!****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true& ***!
  \****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_template_id_5ab4ff28_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=template&id=5ab4ff28&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc&":
/*!*******************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_BiographySection_vue_vue_type_template_id_19df70fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./BiographySection.vue?vue&type=template&id=19df70fc& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/BiographySection.vue?vue&type=template&id=19df70fc&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true&":
/*!*****************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true& ***!
  \*****************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_template_id_692f485a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DetailsSection.vue?vue&type=template&id=692f485a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=template&id=692f485a&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70&":
/*!*********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70& ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_DisplayNameSection_vue_vue_type_template_id_2aaa7a70___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DisplayNameSection.vue?vue&type=template&id=2aaa7a70& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DisplayNameSection.vue?vue&type=template&id=2aaa7a70&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_a9c46cb4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=template&id=a9c46cb4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=template&id=a9c46cb4&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true&":
/*!****************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true& ***!
  \****************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_template_id_c3e547e2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=template&id=c3e547e2&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8&":
/*!*******************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FediverseSection_vue_vue_type_template_id_3da3a2f8___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FediverseSection.vue?vue&type=template&id=3da3a2f8& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/FediverseSection.vue?vue&type=template&id=3da3a2f8&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeadlineSection_vue_vue_type_template_id_bebab1de___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeadlineSection.vue?vue&type=template&id=bebab1de& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/HeadlineSection.vue?vue&type=template&id=bebab1de&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true&":
/*!***************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true& ***!
  \***************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_template_id_0e2d022c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Language.vue?vue&type=template&id=0e2d022c&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=template&id=0e2d022c&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true&":
/*!**********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_template_id_126bf24b_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=template&id=126bf24b&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true&":
/*!***********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true& ***!
  \***********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_template_id_9ef24824_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Locale.vue?vue&type=template&id=9ef24824&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=template&id=9ef24824&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true&":
/*!******************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true& ***!
  \******************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_template_id_6d00a8c7_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=template&id=6d00a8c7&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_LocationSection_vue_vue_type_template_id_f78b62e0___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocationSection.vue?vue&type=template&id=f78b62e0& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocationSection.vue?vue&type=template&id=f78b62e0&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a&":
/*!**********************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a& ***!
  \**********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_OrganisationSection_vue_vue_type_template_id_3ece9f6a___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OrganisationSection.vue?vue&type=template&id=3ece9f6a& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/OrganisationSection.vue?vue&type=template&id=3ece9f6a&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PhoneSection_vue_vue_type_template_id_eaab49b2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PhoneSection.vue?vue&type=template&id=eaab49b2& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/PhoneSection.vue?vue&type=template&id=eaab49b2&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true&":
/*!***************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true& ***!
  \***************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_template_id_29aba6ea_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=template&id=29aba6ea&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileCheckbox_vue_vue_type_template_id_a46c582e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileCheckbox.vue?vue&type=template&id=a46c582e& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileCheckbox.vue?vue&type=template&id=a46c582e&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true&":
/*!************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true& ***!
  \************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_template_id_3c8483c2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=template&id=3c8483c2&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true&":
/*!********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true& ***!
  \********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_template_id_22b97e2f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=template&id=22b97e2f&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true&":
/*!****************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true& ***!
  \****************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_template_id_561c922f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=template&id=561c922f&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true&":
/*!**********************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true& ***!
  \**********************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_template_id_5b020be8_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=template&id=5b020be8&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2&":
/*!**************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2& ***!
  \**************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_RoleSection_vue_vue_type_template_id_c066c1a2___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./RoleSection.vue?vue&type=template&id=c066c1a2& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/RoleSection.vue?vue&type=template&id=c066c1a2&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TwitterSection_vue_vue_type_template_id_3c8569fc___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TwitterSection.vue?vue&type=template&id=3c8569fc& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/TwitterSection.vue?vue&type=template&id=3c8569fc&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_WebsiteSection_vue_vue_type_template_id_406da84c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./WebsiteSection.vue?vue&type=template&id=406da84c& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/WebsiteSection.vue?vue&type=template&id=406da84c&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true&":
/*!********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true& ***!
  \********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_template_id_2906f1a6_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=template&id=2906f1a6&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true&":
/*!***************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true& ***!
  \***************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_template_id_08cbb240_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControl.vue?vue&type=template&id=08cbb240&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=template&id=08cbb240&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true&":
/*!*********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true& ***!
  \*********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_template_id_ba1ab8d4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=template&id=ba1ab8d4&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true&":
/*!*******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true& ***!
  \*******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_template_id_6a8a7fec_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib/index.js!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=template&id=6a8a7fec&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AvatarSection_vue_vue_type_style_index_0_id_5ab4ff28_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/AvatarSection.vue?vue&type=style&index=0&id=5ab4ff28&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&":
/*!********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_DetailsSection_vue_vue_type_style_index_0_id_692f485a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/DetailsSection.vue?vue&type=style&index=0&id=692f485a&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&":
/*!************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_style_index_0_id_a9c46cb4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/Email.vue?vue&type=style&index=0&id=a9c46cb4&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EmailSection_vue_vue_type_style_index_0_id_c3e547e2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/EmailSection/EmailSection.vue?vue&type=style&index=0&id=c3e547e2&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Language_vue_vue_type_style_index_0_id_0e2d022c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/Language.vue?vue&type=style&index=0&id=0e2d022c&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LanguageSection_vue_vue_type_style_index_0_id_126bf24b_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LanguageSection/LanguageSection.vue?vue&type=style&index=0&id=126bf24b&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Locale_vue_vue_type_style_index_0_id_9ef24824_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/Locale.vue?vue&type=style&index=0&id=9ef24824&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_LocaleSection_vue_vue_type_style_index_0_id_6d00a8c7_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/LocaleSection/LocaleSection.vue?vue&type=style&index=0&id=6d00a8c7&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&":
/*!******************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& ***!
  \******************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_0_id_29aba6ea_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=0&id=29aba6ea&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditProfileAnchorLink_vue_vue_type_style_index_1_id_29aba6ea_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/EditProfileAnchorLink.vue?vue&type=style&index=1&id=29aba6ea&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfilePreviewCard_vue_vue_type_style_index_0_id_3c8483c2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfilePreviewCard.vue?vue&type=style&index=0&id=3c8483c2&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileSection_vue_vue_type_style_index_0_id_22b97e2f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileSection/ProfileSection.vue?vue&type=style&index=0&id=22b97e2f&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ProfileVisibilitySection_vue_vue_type_style_index_0_id_561c922f_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/ProfileVisibilitySection.vue?vue&type=style&index=0&id=561c922f&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_VisibilityDropdown_vue_vue_type_style_index_0_id_5b020be8_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/ProfileVisibilitySection/VisibilityDropdown.vue?vue&type=style&index=0&id=5b020be8&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AccountPropertySection_vue_vue_type_style_index_0_id_2906f1a6_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/AccountPropertySection.vue?vue&type=style&index=0&id=2906f1a6&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControl_vue_vue_type_style_index_0_id_08cbb240_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControl.vue?vue&type=style&index=0&id=08cbb240&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FederationControlAction_vue_vue_type_style_index_0_id_ba1ab8d4_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/FederationControlAction.vue?vue&type=style&index=0&id=ba1ab8d4&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_HeaderBar_vue_vue_type_style_index_0_id_6a8a7fec_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/style-loader/dist/cjs.js!../../../../../../node_modules/css-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/PersonalInfo/shared/HeaderBar.vue?vue&type=style&index=0&id=6a8a7fec&lang=scss&scoped=true&");


/***/ }),

/***/ "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA3NCSVQICAjb4U/gAAAABlBMVEXMzMz////TjRV2AAAACXBIWXMAAArrAAAK6wGCiw1aAAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M26LyyjAAAABFJREFUCJlj+M/AgBVhF/0PAH6/D/HkDxOGAAAAAElFTkSuQmCC":
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA3NCSVQICAjb4U/gAAAABlBMVEXMzMz////TjRV2AAAACXBIWXMAAArrAAAK6wGCiw1aAAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M26LyyjAAAABFJREFUCJlj+M/AgBVhF/0PAH6/D/HkDxOGAAAAAElFTkSuQmCC ***!
  \**************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA3NCSVQICAjb4U/gAAAABlBMVEXMzMz////TjRV2AAAACXBIWXMAAArrAAAK6wGCiw1aAAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M26LyyjAAAABFJREFUCJlj+M/AgBVhF/0PAH6/D/HkDxOGAAAAAElFTkSuQmCC";

/***/ }),

/***/ "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6Ii8+Cjwvc3ZnPgo=":
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6Ii8+Cjwvc3ZnPgo= ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6Ii8+Cjwvc3ZnPgo=";

/***/ }),

/***/ "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6IiBzdHlsZT0iZmlsbC1vcGFjaXR5OjE7ZmlsbDojZmZmZmZmIi8+Cjwvc3ZnPgo=":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6IiBzdHlsZT0iZmlsbC1vcGFjaXR5OjE7ZmlsbDojZmZmZmZmIi8+Cjwvc3ZnPgo= ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module) {

"use strict";
module.exports = "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiI+CiAgPHBhdGggZD0iTTE0IDEyLjNMMTIuMyAxNCA4IDkuNyAzLjcgMTQgMiAxMi4zIDYuMyA4IDIgMy43IDMuNyAyIDggNi4zIDEyLjMgMiAxNCAzLjcgOS43IDh6IiBzdHlsZT0iZmlsbC1vcGFjaXR5OjE7ZmlsbDojZmZmZmZmIi8+Cjwvc3ZnPgo=";

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
/******/ 			"settings-vue-settings-personal-info": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/settings/src/main-personal-info.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=settings-vue-settings-personal-info.js.map?v=cd160cdb46fc8db32e5d