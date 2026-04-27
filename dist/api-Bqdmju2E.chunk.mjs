const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { g as getLoggerBuilder, f as emit, c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as confirmPassword } from "./index-Dl6U1WCt.chunk.mjs";
import { g as getClient } from "./dav-DGipjjQH.chunk.mjs";
const logger = getLoggerBuilder().setApp("systemtags").detectUser().build();
const defaultBaseTag = {
  userVisible: true,
  userAssignable: true,
  canAssign: true
};
const propertyMappings = Object.freeze({
  "display-name": "displayName",
  "user-visible": "userVisible",
  "user-assignable": "userAssignable",
  "can-assign": "canAssign"
});
function parseTags(tags) {
  return tags.map(({ props }) => Object.fromEntries(Object.entries(props).map(([key, value]) => {
    key = propertyMappings[key] ?? key;
    value = key === "displayName" ? String(value) : value;
    return [key, value];
  })));
}
function parseIdFromLocation(url) {
  const queryPos = url.indexOf("?");
  if (queryPos > 0) {
    url = url.substring(0, queryPos);
  }
  const parts = url.split("/");
  let result;
  do {
    result = parts[parts.length - 1];
    parts.pop();
  } while (!result && parts.length > 0);
  return Number(result);
}
function formatTag(initialTag) {
  if ("name" in initialTag && !("displayName" in initialTag)) {
    return { ...initialTag };
  }
  const tag = { ...initialTag };
  tag.name = tag.displayName;
  delete tag.displayName;
  return tag;
}
function getNodeSystemTags(node) {
  const attribute = node.attributes?.["system-tags"]?.["system-tag"];
  if (attribute === void 0) {
    return [];
  }
  return [attribute].flat().map((tag) => typeof tag === "string" ? tag : tag.text);
}
function setNodeSystemTags(node, tags) {
  node.attributes["system-tags"] = {
    "system-tag": tags
  };
  emit("files:node:updated", node);
}
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const davClient = getClient();
const fetchTagsPayload = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
		<d:getetag />
		<nc:color />
	</d:prop>
</d:propfind>`;
async function fetchTags() {
  const path = "/systemtags";
  try {
    const { data: tags } = await davClient.getDirectoryContents(path, {
      data: fetchTagsPayload,
      details: true,
      glob: "/systemtags/*"
      // Filter out first empty tag
    });
    return parseTags(tags);
  } catch (error) {
    logger.error(translate("systemtags", "Failed to load tags"), { error });
    throw new Error(translate("systemtags", "Failed to load tags"), { cause: error });
  }
}
async function fetchTag(tagId) {
  const path = "/systemtags/" + tagId;
  try {
    const { data: tag } = await davClient.stat(path, {
      data: fetchTagsPayload,
      details: true
    });
    return parseTags([tag])[0];
  } catch (error) {
    logger.error(translate("systemtags", "Failed to load tag"), { error });
    throw new Error(translate("systemtags", "Failed to load tag"), { cause: error });
  }
}
async function createTag(tag) {
  const path = "/systemtags";
  const tagToPost = formatTag(tag);
  try {
    const { headers } = await davClient.customRequest(path, {
      method: "POST",
      data: tagToPost
    });
    const contentLocation = headers.get("content-location");
    if (contentLocation) {
      emit("systemtags:tag:created", tag);
      return parseIdFromLocation(contentLocation);
    }
    logger.error(translate("systemtags", 'Missing "Content-Location" header'));
    throw new Error(translate("systemtags", 'Missing "Content-Location" header'));
  } catch (error) {
    if (error?.response?.status === 409) {
      logger.error(translate("systemtags", "A tag with the same name already exists"), { error });
      throw new Error(translate("systemtags", "A tag with the same name already exists"), { cause: error });
    }
    logger.error(translate("systemtags", "Failed to create tag"), { error });
    throw new Error(translate("systemtags", "Failed to create tag"), { cause: error });
  }
}
async function updateTag(tag) {
  const path = "/systemtags/" + tag.id;
  const data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${tag.displayName}</oc:display-name>
				<oc:user-visible>${tag.userVisible}</oc:user-visible>
				<oc:user-assignable>${tag.userAssignable}</oc:user-assignable>
				<nc:color>${tag?.color || null}</nc:color>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;
  try {
    await davClient.customRequest(path, {
      method: "PROPPATCH",
      data
    });
    emit("systemtags:tag:updated", tag);
  } catch (error) {
    logger.error(translate("systemtags", "Failed to update tag"), { error });
    throw new Error(translate("systemtags", "Failed to update tag"), { cause: error });
  }
}
async function deleteTag(tag) {
  const path = "/systemtags/" + tag.id;
  try {
    await davClient.deleteFile(path);
    emit("systemtags:tag:deleted", tag);
  } catch (error) {
    logger.error(translate("systemtags", "Failed to delete tag"), { error });
    throw new Error(translate("systemtags", "Failed to delete tag"), { cause: error });
  }
}
async function getTagObjects(tag, type) {
  const path = `/systemtags/${tag.id}/${type}`;
  const data = `<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:prop>
			<nc:object-ids />
			<d:getetag />
		</d:prop>
	</d:propfind>`;
  const response = await davClient.stat(path, { data, details: true });
  const etag = response?.data?.props?.getetag || '""';
  const objects = Object.values(response?.data?.props?.["object-ids"] || []).flat();
  return {
    etag,
    objects
  };
}
async function setTagObjects(tag, type, objectIds, etag = "") {
  const path = `/systemtags/${tag.id}/${type}`;
  let data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<nc:object-ids>${objectIds.map(({ id, type: type2 }) => `<nc:object-id><nc:id>${id}</nc:id><nc:type>${type2}</nc:type></nc:object-id>`).join("")}</nc:object-ids>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;
  if (objectIds.length === 0) {
    data = `<?xml version="1.0"?>
		<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
			<d:remove>
				<d:prop>
					<nc:object-ids />
				</d:prop>
			</d:remove>
		</d:propertyupdate>`;
  }
  await davClient.customRequest(path, {
    method: "PROPPATCH",
    data,
    headers: {
      "if-match": etag
    }
  });
}
async function updateSystemTagsAdminRestriction(isAllowed) {
  const isAllowedString = isAllowed ? "1" : "0";
  const url = generateOcsUrl("/apps/provisioning_api/api/v1/config/apps/{appId}/{key}", {
    appId: "systemtags",
    key: "restrict_creation_to_admin"
  });
  await confirmPassword();
  const { data } = await cancelableClient.post(url, {
    value: isAllowedString
  });
  return data;
}
export {
  deleteTag as a,
  updateSystemTagsAdminRestriction as b,
  createTag as c,
  defaultBaseTag as d,
  getNodeSystemTags as e,
  fetchTags as f,
  getTagObjects as g,
  setNodeSystemTags as h,
  fetchTag as i,
  logger as l,
  setTagObjects as s,
  updateTag as u
};
//# sourceMappingURL=api-Bqdmju2E.chunk.mjs.map
