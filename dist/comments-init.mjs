const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { g as getSidebar, a as registerFileAction } from "./index-DCPyCjGS.chunk.mjs";
import { a as translatePlural, t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { i as isUsingActivityIntegration, l as logger } from "./activity-C3wf9N73.chunk.mjs";
import "./public-CKeAb98h.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
const CommentProcessingSvg = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-comment-processing" viewBox="0 0 24 24"><path d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9M17,11V9H15V11H17M13,11V9H11V11H13M9,11V9H7V11H9Z" /></svg>';
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const action = {
  id: "comments-unread",
  title({ nodes }) {
    const unread = nodes[0]?.attributes["comments-unread"];
    if (typeof unread === "number" && unread >= 0) {
      return translatePlural("comments", "1 new comment", "{unread} new comments", unread, { unread });
    }
    return translate("comments", "Comment");
  },
  // Empty string when rendered inline
  displayName: () => "",
  iconSvgInline: () => CommentProcessingSvg,
  enabled({ nodes }) {
    const unread = nodes[0]?.attributes?.["comments-unread"];
    return typeof unread === "number" && unread > 0;
  },
  async exec({ nodes }) {
    if (nodes.length !== 1 || !nodes[0]) {
      return false;
    }
    try {
      const sidebar = getSidebar();
      const sidebarTabId = isUsingActivityIntegration() ? "activity" : "comments";
      if (sidebar.isOpen && sidebar.node?.source === nodes[0].source) {
        logger.debug("Sidebar already open for this node, just activating comments tab");
        sidebar.setActiveTab(sidebarTabId);
        return null;
      }
      sidebar.open(nodes[0], sidebarTabId);
      return null;
    } catch (error) {
      logger.error("Error while opening sidebar", { error });
      return false;
    }
  },
  inline: () => true,
  order: -140
};
registerFileAction(action);
//# sourceMappingURL=comments-init.mjs.map
