const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { C as Column, V as View, b as getNavigation, c as registerFileListAction, a as registerFileAction } from "./index-DCPyCjGS.chunk.mjs";
import { a as getCurrentUser, b as generateUrl, i as dirname, g as getLoggerBuilder, h as generateRemoteUrl, j as encodePath, f as emit } from "./index-rAufP352.chunk.mjs";
import { c as cancelableClient, i as isAxiosError } from "./index-D5H5XMHa.chunk.mjs";
import { a as showError, c as showSuccess, g as getDialogBuilder } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { P as Permission } from "./public-CKeAb98h.chunk.mjs";
import { g as getLanguage, b as getCanonicalLocale, t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { g as getClient, a as getDavNameSpaces, b as getDavProperties, r as resultToNode$1, d as defaultRemoteURL } from "./dav-DGipjjQH.chunk.mjs";
import { f as formatRelativeTime } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import { e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { N as NcUserBubble } from "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import { l as loadState } from "./index-o76qk6sn.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
const svgHistory = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-history" viewBox="0 0 24 24"><path d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3" /></svg>';
const svgDelete = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-trash-can-outline" viewBox="0 0 24 24"><path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z" /></svg>';
const rootPath = `/trashbin/${getCurrentUser()?.uid}/trash`;
const client = getClient();
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const data = `<?xml version="1.0"?>
<d:propfind ${getDavNameSpaces()}>
	<d:prop>
		<nc:trashbin-deletion-time />
		<nc:trashbin-original-location />
		<nc:trashbin-title />
		<nc:trashbin-deleted-by-id />
		<nc:trashbin-deleted-by-display-name />
		${getDavProperties()}
	</d:prop>
</d:propfind>`;
function resultToNode(stat) {
  const node = resultToNode$1(stat, rootPath);
  node.attributes.previewUrl = generateUrl("/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32", { fileid: node.fileid });
  return node;
}
async function getContents(path = "/") {
  const contentsResponse = await client.getDirectoryContents(`${rootPath}${path}`, {
    details: true,
    data,
    includeSelf: true
  });
  const contents = contentsResponse.data.map(resultToNode);
  const [folder] = contents.splice(contents.findIndex((node) => node.path === path), 1);
  return {
    folder,
    contents
  };
}
const originalLocation = new Column({
  id: "files_trashbin--original-location",
  title: translate("files_trashbin", "Original location"),
  render(node) {
    const originalLocation2 = parseOriginalLocation(node);
    const span = document.createElement("span");
    span.title = originalLocation2;
    span.textContent = originalLocation2;
    return span;
  },
  sort(nodeA, nodeB) {
    const locationA = parseOriginalLocation(nodeA);
    const locationB = parseOriginalLocation(nodeB);
    return locationA.localeCompare(locationB, [getLanguage(), getCanonicalLocale()], { numeric: true, usage: "sort" });
  }
});
const deletedBy = new Column({
  id: "files_trashbin--deleted-by",
  title: translate("files_trashbin", "Deleted by"),
  render(node) {
    const { userId, displayName, label } = parseDeletedBy(node);
    if (label) {
      const span = document.createElement("span");
      span.textContent = label;
      return span;
    }
    const el = document.createElement("div");
    createApp(NcUserBubble, {
      size: 32,
      user: userId ?? void 0,
      displayName: displayName ?? userId
    }).mount(el);
    return el;
  },
  sort(nodeA, nodeB) {
    const deletedByA = parseDeletedBy(nodeA);
    const deletedbyALabel = deletedByA.label ?? deletedByA.displayName ?? deletedByA.userId;
    const deletedByB = parseDeletedBy(nodeB);
    const deletedByBLabel = deletedByB.label ?? deletedByB.displayName ?? deletedByB.userId;
    return deletedbyALabel.localeCompare(deletedByBLabel, [getLanguage(), getCanonicalLocale()], { numeric: true, usage: "sort" });
  }
});
const deleted = new Column({
  id: "files_trashbin--deleted",
  title: translate("files_trashbin", "Deleted"),
  render(node) {
    const deletionTime = node.attributes?.["trashbin-deletion-time"] || (node?.mtime?.getTime() ?? 0) / 1e3;
    const span = document.createElement("span");
    if (deletionTime) {
      const formatter = Intl.DateTimeFormat([getCanonicalLocale()], { dateStyle: "long", timeStyle: "short" });
      const timestamp = new Date(deletionTime * 1e3);
      span.title = formatter.format(timestamp);
      span.textContent = formatRelativeTime(timestamp, { ignoreSeconds: translate("files", "few seconds ago") });
      return span;
    }
    span.textContent = translate("files_trashbin", "A long time ago");
    return span;
  },
  sort(nodeA, nodeB) {
    const deletionTimeA = nodeA.attributes?.["trashbin-deletion-time"] || (nodeA?.mtime?.getTime() ?? 0) / 1e3;
    const deletionTimeB = nodeB.attributes?.["trashbin-deletion-time"] || (nodeB?.mtime?.getTime() ?? 0) / 1e3;
    return deletionTimeB - deletionTimeA;
  }
});
function parseOriginalLocation(node) {
  const path = stringOrNull(node.attributes?.["trashbin-original-location"]);
  if (!path) {
    return translate("files_trashbin", "Unknown");
  }
  const dir = dirname(path);
  if (dir === "/" || dir === ".") {
    return translate("files_trashbin", "All files");
  }
  return dir.replace(/^\//, "");
}
function parseDeletedBy(node) {
  const userId = stringOrNull(node.attributes?.["trashbin-deleted-by-id"]);
  const displayName = stringOrNull(node.attributes?.["trashbin-deleted-by-display-name"]);
  let label;
  const currentUserId = getCurrentUser()?.uid;
  if (userId === currentUserId) {
    label = translate("files_trashbin", "You");
  }
  if (!userId && !displayName) {
    label = translate("files_trashbin", "Unknown");
  }
  return {
    userId,
    displayName,
    label
  };
}
function stringOrNull(attribute) {
  if (attribute) {
    return String(attribute);
  }
  return null;
}
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const TRASHBIN_VIEW_ID = "trashbin";
const trashbinView = new View({
  id: TRASHBIN_VIEW_ID,
  name: translate("files_trashbin", "Deleted files"),
  caption: translate("files_trashbin", "List of files that have been deleted."),
  emptyTitle: translate("files_trashbin", "No deleted files"),
  emptyCaption: translate("files_trashbin", "Files and folders you have deleted will show up here"),
  icon: svgDelete,
  order: 50,
  sticky: true,
  defaultSortKey: "deleted",
  columns: [
    originalLocation,
    deletedBy,
    deleted
  ],
  getContents
});
const logger = getLoggerBuilder().setApp("files_trashbin").detectUser().build();
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const restoreAction = {
  id: "restore",
  displayName() {
    return translate("files_trashbin", "Restore");
  },
  iconSvgInline: () => svgHistory,
  enabled({ nodes, view }) {
    if (view.id !== TRASHBIN_VIEW_ID) {
      return false;
    }
    return nodes.length > 0 && nodes.map((node) => node.permissions).every((permission) => Boolean(permission & Permission.READ));
  },
  async exec({ nodes }) {
    const node = nodes[0];
    try {
      const destination = generateRemoteUrl(encodePath(`dav/trashbin/${getCurrentUser().uid}/restore/${node.basename}`));
      await cancelableClient.request({
        method: "MOVE",
        url: node.encodedSource,
        headers: {
          destination
        }
      });
      emit("files:node:deleted", node);
      return true;
    } catch (error) {
      if (isAxiosError(error) && error.response?.status === 507) {
        showError(translate("files_trashbin", "Not enough free space to restore the file/folder"));
      }
      logger.error("Failed to restore node", { error, node });
      return false;
    }
  },
  async execBatch({ nodes, view, folder, contents }) {
    return Promise.all(nodes.map((node) => this.exec({ nodes: [node], view, folder, contents })));
  },
  order: 1,
  inline: () => true
};
async function emptyTrash() {
  try {
    await cancelableClient.delete(`${defaultRemoteURL}/trashbin/${getCurrentUser().uid}/trash`);
    showSuccess(translate("files_trashbin", "All files have been permanently deleted"));
    return true;
  } catch (error) {
    showError(translate("files_trashbin", "Failed to empty deleted files"));
    logger.error("Failed to empty deleted files", { error });
    return false;
  }
}
const emptyTrashAction = {
  id: "empty-trash",
  displayName: () => translate("files_trashbin", "Empty deleted files"),
  order: 0,
  enabled({ view, folder, contents }) {
    if (view.id !== TRASHBIN_VIEW_ID) {
      return false;
    }
    const config = loadState("files_trashbin", "config");
    if (!config.allow_delete) {
      return false;
    }
    return contents.length > 0 && folder.path === "/";
  },
  async exec({ contents }) {
    const askConfirmation = new Promise((resolve) => {
      const dialog = getDialogBuilder(translate("files_trashbin", "Confirm permanent deletion")).setSeverity("warning").setText(translate("files_trashbin", "Are you sure you want to permanently delete all files and folders in the trash? This cannot be undone.")).setButtons([
        {
          label: translate("files_trashbin", "Cancel"),
          variant: "secondary",
          callback: () => resolve(false)
        },
        {
          label: translate("files_trashbin", "Empty deleted files"),
          variant: "error",
          callback: () => resolve(true)
        }
      ]).build();
      dialog.show().then(() => {
        resolve(false);
      });
    });
    const result = await askConfirmation;
    if (result === true) {
      if (await emptyTrash()) {
        contents.forEach((node) => emit("files:node:deleted", node));
      }
      return null;
    }
    return null;
  }
};
const Navigation = getNavigation();
Navigation.register(trashbinView);
registerFileListAction(emptyTrashAction);
registerFileAction(restoreAction);
//# sourceMappingURL=files_trashbin-init.mjs.map
