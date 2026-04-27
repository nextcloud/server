const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { m as mdiNetworkOffOutline, a as mdiCloseNetworkOutline, b as mdiHelpNetworkOutline, c as mdiNetworkOutline, d as mdiCheckNetworkOutline } from "./mdi-BGU2G5q5.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const ConfigurationType = Object.freeze({
  String: 0,
  Boolean: 1,
  Password: 2
});
const ConfigurationFlag = Object.freeze({
  None: 0,
  /**
   * Indicates that the configuration option is optional and can be left empty ("not required")
   */
  Optional: 1,
  UserProvided: 2,
  Hidden: 4
});
const StorageStatus = Object.freeze({
  Success: 0,
  Error: 1,
  Indeterminate: 2,
  IncompleteConf: 3,
  Unauthorized: 4,
  Timeout: 5,
  NetworkError: 6
});
const MountOptionsCheckFilesystem = Object.freeze({
  /**
   * Never check the underlying filesystem for updates
   */
  Never: 0,
  /**
   * check the underlying filesystem for updates once every request for each file
   */
  OncePerRequest: 1,
  /**
   * Always check the underlying filesystem for updates
   */
  Always: 2
});
const StorageStatusIcons = Object.freeze({
  [StorageStatus.Success]: mdiCheckNetworkOutline,
  [StorageStatus.Error]: mdiCloseNetworkOutline,
  [StorageStatus.Indeterminate]: mdiNetworkOutline,
  [StorageStatus.IncompleteConf]: mdiHelpNetworkOutline,
  [StorageStatus.Unauthorized]: mdiCloseNetworkOutline,
  [StorageStatus.Timeout]: mdiNetworkOffOutline,
  [StorageStatus.NetworkError]: mdiNetworkOffOutline
});
const StorageStatusMessage = Object.freeze({
  [StorageStatus.Success]: translate("files_external", "Connected"),
  [StorageStatus.Error]: translate("files_external", "Error"),
  [StorageStatus.Indeterminate]: translate("files_external", "Indeterminate"),
  [StorageStatus.IncompleteConf]: translate("files_external", "Incomplete configuration"),
  [StorageStatus.Unauthorized]: translate("files_external", "Unauthorized"),
  [StorageStatus.Timeout]: translate("files_external", "Timeout"),
  [StorageStatus.NetworkError]: translate("files_external", "Network error")
});
export {
  ConfigurationType as C,
  MountOptionsCheckFilesystem as M,
  StorageStatus as S,
  ConfigurationFlag as a,
  StorageStatusMessage as b,
  StorageStatusIcons as c
};
//# sourceMappingURL=types-B1VCwyqH.chunk.mjs.map
