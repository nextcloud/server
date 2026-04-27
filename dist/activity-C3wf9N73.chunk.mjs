const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { g as getLoggerBuilder } from "./index-rAufP352.chunk.mjs";
import { l as loadState } from "./index-o76qk6sn.chunk.mjs";
const logger = getLoggerBuilder().setApp("comments").detectUser().build();
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
function isUsingActivityIntegration() {
  return loadState("comments", "activityEnabled", false) && window.OCA?.Activity?.registerSidebarAction !== void 0;
}
export {
  isUsingActivityIntegration as i,
  logger as l
};
//# sourceMappingURL=activity-C3wf9N73.chunk.mjs.map
