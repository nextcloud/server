const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { g as getLoggerBuilder, j as encodePath, v as basename, r as extname, i as dirname, n as join } from "./index-rAufP352.chunk.mjs";
import { l as loadState } from "./index-o76qk6sn.chunk.mjs";
window._nc_files_scope ??= {};
window._nc_files_scope.v4_0 ??= {};
const scopedGlobals = window._nc_files_scope.v4_0;
const logger = getLoggerBuilder().setApp("@nextcloud/files").detectUser().build();
const FileType = Object.freeze({
  Folder: "folder",
  File: "file"
});
const Permission = Object.freeze({
  /**
   * No permissions granted
   */
  NONE: 0,
  /**
   * Can read the file content
   */
  READ: 1,
  /**
   * Can modify the file itself (move, rename, etc)
   */
  UPDATE: 2,
  /**
   * Can create new files/folders inside a folder
   */
  CREATE: 4,
  /**
   * Can change the file content
   */
  WRITE: 4,
  /**
   * Can delete the node
   */
  DELETE: 8,
  /**
   * Can share the node
   */
  SHARE: 16,
  /**
   * All permissions are granted
   */
  ALL: 31
});
const NodeStatus = Object.freeze({
  /** This is a new node and it doesn't exists on the filesystem yet */
  NEW: "new",
  /** This node has failed and is unavailable  */
  FAILED: "failed",
  /** This node is currently loading or have an operation in progress */
  LOADING: "loading",
  /** This node is locked and cannot be modified */
  LOCKED: "locked"
});
function isDavResource(source, davService) {
  return source.match(davService) !== null;
}
function validateData(data, davService) {
  if (data.id && typeof data.id !== "number" && typeof data.id !== "string") {
    throw new Error("Invalid id type of value");
  }
  if (!data.source) {
    throw new Error("Missing mandatory source");
  }
  try {
    new URL(data.source);
  } catch {
    throw new Error("Invalid source format, source must be a valid URL");
  }
  if (!data.source.startsWith("http")) {
    throw new Error("Invalid source format, only http(s) is supported");
  }
  if (!data.root) {
    throw new Error("Missing mandatory root");
  }
  if (typeof data.root !== "string") {
    throw new Error("Invalid root type");
  }
  if (!data.root.startsWith("/")) {
    throw new Error("Root must start with a leading slash");
  }
  if (!data.source.includes(data.root)) {
    throw new Error("Root must be part of the source");
  }
  if (isDavResource(data.source, davService)) {
    const service = data.source.match(davService)[0];
    if (!data.source.includes(join(service, data.root))) {
      throw new Error("The root must be relative to the service. e.g /files/emma");
    }
  }
  if (data.displayname && typeof data.displayname !== "string") {
    throw new Error("Invalid displayname type");
  }
  if (data.mtime && !(data.mtime instanceof Date)) {
    throw new Error("Invalid mtime type");
  }
  if (data.crtime && !(data.crtime instanceof Date)) {
    throw new Error("Invalid crtime type");
  }
  if (!data.mime || typeof data.mime !== "string" || !data.mime.match(/^[-\w.]+\/[-+\w.]+$/gi)) {
    throw new Error("Missing or invalid mandatory mime");
  }
  if ("size" in data && typeof data.size !== "number" && data.size !== void 0) {
    throw new Error("Invalid size type");
  }
  if ("permissions" in data && data.permissions !== void 0 && !(typeof data.permissions === "number" && data.permissions >= Permission.NONE && data.permissions <= Permission.ALL)) {
    throw new Error("Invalid permissions");
  }
  if (data.owner && data.owner !== null && typeof data.owner !== "string") {
    throw new Error("Invalid owner type");
  }
  if (data.attributes && typeof data.attributes !== "object") {
    throw new Error("Invalid attributes type");
  }
  if (data.status && !Object.values(NodeStatus).includes(data.status)) {
    throw new Error("Status must be a valid NodeStatus");
  }
}
function fixDates(data) {
  if (data.mtime && typeof data.mtime === "string") {
    if (!isNaN(Date.parse(data.mtime)) && JSON.stringify(new Date(data.mtime)) === JSON.stringify(data.mtime)) {
      data.mtime = new Date(data.mtime);
    }
  }
  if (data.crtime && typeof data.crtime === "string") {
    if (!isNaN(Date.parse(data.crtime)) && JSON.stringify(new Date(data.crtime)) === JSON.stringify(data.crtime)) {
      data.crtime = new Date(data.crtime);
    }
  }
}
function fixRegExp(pattern) {
  if (pattern instanceof RegExp) {
    return pattern;
  }
  const matches = pattern.match(/(\/?)(.+)\1([a-z]*)/i);
  if (!matches) {
    throw new Error("Invalid regular expression format.");
  }
  const validFlags = Array.from(new Set(matches[3])).filter((flag) => "gimsuy".includes(flag)).join("");
  return new RegExp(matches[2], validFlags);
}
class Node {
  _attributes;
  _data;
  _knownDavService = /(remote|public)\.php\/(web)?dav/i;
  readonlyAttributes = Object.entries(Object.getOwnPropertyDescriptors(Node.prototype)).filter((e) => typeof e[1].get === "function" && e[0] !== "__proto__").map((e) => e[0]);
  handler = {
    set: (target, prop, value) => {
      if (this.readonlyAttributes.includes(prop)) {
        return false;
      }
      return Reflect.set(target, prop, value);
    },
    deleteProperty: (target, prop) => {
      if (this.readonlyAttributes.includes(prop)) {
        return false;
      }
      return Reflect.deleteProperty(target, prop);
    }
  };
  constructor(...[data, davService]) {
    if (!data.mime) {
      data.mime = "application/octet-stream";
    }
    fixDates(data);
    davService = fixRegExp(davService || this._knownDavService);
    validateData(data, davService);
    this._data = {
      ...data,
      attributes: {}
    };
    this._attributes = new Proxy(this._data.attributes, this.handler);
    this.update(data.attributes ?? {});
    if (davService) {
      this._knownDavService = davService;
    }
  }
  /**
   * Get the source url to this object
   * There is no setter as the source is not meant to be changed manually.
   * You can use the rename or move method to change the source.
   */
  get source() {
    return this._data.source.replace(/\/$/i, "");
  }
  /**
   * Get the encoded source url to this object for requests purposes
   */
  get encodedSource() {
    const { origin } = new URL(this.source);
    return origin + encodePath(this.source.slice(origin.length));
  }
  /**
   * Get this object name
   * There is no setter as the source is not meant to be changed manually.
   * You can use the rename or move method to change the source.
   */
  get basename() {
    return basename(this.source);
  }
  /**
   * The nodes displayname
   * By default the display name and the `basename` are identical,
   * but it is possible to have a different name. This happens
   * on the files app for example for shared folders.
   */
  get displayname() {
    return this._data.displayname || this.basename;
  }
  /**
   * Set the displayname
   */
  set displayname(displayname) {
    validateData({ ...this._data, displayname }, this._knownDavService);
    this._data.displayname = displayname;
  }
  /**
   * Get this object's extension
   * There is no setter as the source is not meant to be changed manually.
   * You can use the rename or move method to change the source.
   */
  get extension() {
    return extname(this.source);
  }
  /**
   * Get the directory path leading to this object
   * Will use the relative path to root if available
   *
   * There is no setter as the source is not meant to be changed manually.
   * You can use the rename or move method to change the source.
   */
  get dirname() {
    return dirname(this.path);
  }
  /**
   * Get the file mime
   */
  get mime() {
    return this._data.mime || "application/octet-stream";
  }
  /**
   * Set the file mime
   * Removing the mime type will set it to `application/octet-stream`
   */
  set mime(mime) {
    mime ??= "application/octet-stream";
    validateData({ ...this._data, mime }, this._knownDavService);
    this._data.mime = mime;
  }
  /**
   * Get the file modification time
   */
  get mtime() {
    return this._data.mtime;
  }
  /**
   * Set the file modification time
   */
  set mtime(mtime) {
    validateData({ ...this._data, mtime }, this._knownDavService);
    this._data.mtime = mtime;
  }
  /**
   * Get the file creation time
   * There is no setter as the creation time is not meant to be changed
   */
  get crtime() {
    return this._data.crtime;
  }
  /**
   * Get the file size
   */
  get size() {
    return this._data.size;
  }
  /**
   * Set the file size
   */
  set size(size) {
    validateData({ ...this._data, size }, this._knownDavService);
    this.updateMtime();
    this._data.size = size;
  }
  /**
   * Get the file attribute
   * This contains all additional attributes not provided by the Node class
   */
  get attributes() {
    return this._attributes;
  }
  /**
   * Get the file permissions
   */
  get permissions() {
    if (this.owner === null && !this.isDavResource) {
      return Permission.READ;
    }
    return this._data.permissions !== void 0 ? this._data.permissions : Permission.NONE;
  }
  /**
   * Set the file permissions
   */
  set permissions(permissions) {
    validateData({ ...this._data, permissions }, this._knownDavService);
    this.updateMtime();
    this._data.permissions = permissions;
  }
  /**
   * Get the file owner
   * There is no setter as the owner is not meant to be changed
   */
  get owner() {
    if (!this.isDavResource) {
      return null;
    }
    return this._data.owner;
  }
  /**
   * Is this a dav-related resource ?
   */
  get isDavResource() {
    return isDavResource(this.source, this._knownDavService);
  }
  /**
   * Get the dav root of this object
   * There is no setter as the root is not meant to be changed
   */
  get root() {
    return this._data.root.replace(/^(.+)\/$/, "$1");
  }
  /**
   * Get the absolute path of this object relative to the root
   */
  get path() {
    const idx = this.source.indexOf("://");
    const protocol = this.source.slice(0, idx);
    const remainder = this.source.slice(idx + 3);
    const slashIndex = remainder.indexOf("/");
    const host = remainder.slice(0, slashIndex);
    const rawPath = remainder.slice(slashIndex);
    const safeUrl = `${protocol}://${host}${encodePath(rawPath)}`;
    const url = new URL(safeUrl);
    let source = decodeURIComponent(url.pathname);
    if (this.isDavResource) {
      source = source.split(this._knownDavService).pop();
    }
    const firstMatch = source.indexOf(this.root);
    const root = this.root.replace(/\/$/, "");
    return source.slice(firstMatch + root.length) || "/";
  }
  /**
   * Get the nodes file id if defined.
   * There is no setter as the fileid is not meant to be changed.
   *
   * @deprecated Nextcloud is migrating to snowflake ids which are strings. Use the `id` attribute instead.
   */
  get fileid() {
    return typeof this._data?.id === "number" ? this._data.id : void 0;
  }
  /**
   * Get the nodes id - if defined.
   *
   * Note: As Nextcloud is migrating to snowflake ids the id has to be a string,
   * due to limitations of the JavaScript number type (snowflake ids are 64bit JavaScript numbers can only accurately represent integers up to 53 bit).
   */
  get id() {
    if (typeof this._data?.id === "undefined" || typeof this._data.id === "number" && this._data.id < 0) {
      return void 0;
    }
    return String(this._data.id);
  }
  /**
   * Get the node status.
   */
  get status() {
    return this._data?.status;
  }
  /**
   * Set the node status.
   */
  set status(status) {
    validateData({ ...this._data, status }, this._knownDavService);
    this._data.status = status;
  }
  /**
   * Move the node to a new destination
   *
   * @param destination the new source.
   * e.g. https://cloud.domain.com/remote.php/dav/files/emma/Photos/picture.jpg
   */
  move(destination) {
    validateData({ ...this._data, source: destination }, this._knownDavService);
    const oldBasename = this.basename;
    this._data.source = destination;
    if (this.displayname === oldBasename && this.basename !== oldBasename) {
      this.displayname = this.basename;
    }
  }
  /**
   * Rename the node
   * This aliases the move method for easier usage
   *
   * @param basename The new name of the node
   */
  rename(basename2) {
    if (basename2.includes("/")) {
      throw new Error("Invalid basename");
    }
    this.move(dirname(this.source) + "/" + basename2);
  }
  /**
   * Update the mtime if exists
   */
  updateMtime() {
    if (this._data.mtime) {
      this._data.mtime = /* @__PURE__ */ new Date();
    }
  }
  /**
   * Update the attributes of the node
   * Warning, updating attributes will NOT automatically update the mtime.
   *
   * @param attributes The new attributes to update on the Node attributes
   */
  update(attributes) {
    for (const [name, value] of Object.entries(attributes)) {
      try {
        if (value === void 0) {
          delete this.attributes[name];
        } else {
          this.attributes[name] = value;
        }
      } catch (e) {
        if (e instanceof TypeError) {
          continue;
        }
        throw e;
      }
    }
  }
  /**
   * Returns a clone of the node
   */
  clone() {
    return new this.constructor(structuredClone(this._data), this._knownDavService);
  }
  /**
   * JSON representation of the node
   */
  toJSON() {
    return JSON.stringify([structuredClone(this._data), this._knownDavService.toString()]);
  }
}
class File extends Node {
  constructor(...[data, davService]) {
    super(data, davService);
  }
  get type() {
    return FileType.File;
  }
}
class Folder extends Node {
  constructor(...[data, davService]) {
    super({
      ...data,
      mime: "httpd/unix-directory"
    }, davService);
  }
  get type() {
    return FileType.Folder;
  }
  get extension() {
    return null;
  }
  get mime() {
    return "httpd/unix-directory";
  }
}
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
function isPublicShare() {
  return loadState("files_sharing", "isPublic", null) ?? document.querySelector('input#isPublic[type="hidden"][name="isPublic"][value="1"]') !== null;
}
export {
  FileType as F,
  NodeStatus as N,
  Permission as P,
  File as a,
  Folder as b,
  isPublicShare as i,
  logger as l,
  scopedGlobals as s
};
//# sourceMappingURL=public-CKeAb98h.chunk.mjs.map
