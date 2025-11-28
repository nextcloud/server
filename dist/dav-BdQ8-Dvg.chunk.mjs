const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { l as loadState, x as getCurrentUser, y as generateRemoteUrl, z as getRequestToken, A as onRequestTokenUpdate } from "./TrashCanOutline-CLxw5nIJ.chunk.mjs";
import { a as an, u as un } from "./index-CR-E-1ua.chunk.mjs";
import { l as logger, N as NodeStatus, F as File, a as Folder, P as Permission } from "./index-DamcDk4s.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
function isPublicShare() {
  return loadState("files_sharing", "isPublic", null) ?? document.querySelector('input#isPublic[type="hidden"][name="isPublic"][value="1"]') !== null;
}
function getSharingToken() {
  return loadState("files_sharing", "sharingToken", null) ?? document.querySelector('input#sharingToken[type="hidden"]')?.value ?? null;
}
function _defineProperty(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, { value, enumerable: true, configurable: true, writable: true });
  } else {
    obj[key] = value;
  }
  return obj;
}
function _classPrivateFieldInitSpec(obj, privateMap, value) {
  _checkPrivateRedeclaration(obj, privateMap);
  privateMap.set(obj, value);
}
function _checkPrivateRedeclaration(obj, privateCollection) {
  if (privateCollection.has(obj)) {
    throw new TypeError("Cannot initialize the same private elements twice on an object");
  }
}
function _classPrivateFieldGet(receiver, privateMap) {
  var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "get");
  return _classApplyDescriptorGet(receiver, descriptor);
}
function _classApplyDescriptorGet(receiver, descriptor) {
  if (descriptor.get) {
    return descriptor.get.call(receiver);
  }
  return descriptor.value;
}
function _classPrivateFieldSet(receiver, privateMap, value) {
  var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "set");
  _classApplyDescriptorSet(receiver, descriptor, value);
  return value;
}
function _classExtractFieldDescriptor(receiver, privateMap, action) {
  if (!privateMap.has(receiver)) {
    throw new TypeError("attempted to " + action + " private field on non-instance");
  }
  return privateMap.get(receiver);
}
function _classApplyDescriptorSet(receiver, descriptor, value) {
  if (descriptor.set) {
    descriptor.set.call(receiver, value);
  } else {
    if (!descriptor.writable) {
      throw new TypeError("attempted to set read only private field");
    }
    descriptor.value = value;
  }
}
var toStringTag = typeof Symbol !== "undefined" ? Symbol.toStringTag : "@@toStringTag";
var _internals = /* @__PURE__ */ new WeakMap();
var _promise = /* @__PURE__ */ new WeakMap();
class CancelablePromiseInternal {
  constructor(_ref) {
    var {
      executor = () => {
      },
      internals = defaultInternals(),
      promise = new Promise((resolve2, reject2) => executor(resolve2, reject2, (onCancel) => {
        internals.onCancelList.push(onCancel);
      }))
    } = _ref;
    _classPrivateFieldInitSpec(this, _internals, {
      writable: true,
      value: void 0
    });
    _classPrivateFieldInitSpec(this, _promise, {
      writable: true,
      value: void 0
    });
    _defineProperty(this, toStringTag, "CancelablePromise");
    this.cancel = this.cancel.bind(this);
    _classPrivateFieldSet(this, _internals, internals);
    _classPrivateFieldSet(this, _promise, promise || new Promise((resolve2, reject2) => executor(resolve2, reject2, (onCancel) => {
      internals.onCancelList.push(onCancel);
    })));
  }
  then(onfulfilled, onrejected) {
    return makeCancelable(_classPrivateFieldGet(this, _promise).then(createCallback(onfulfilled, _classPrivateFieldGet(this, _internals)), createCallback(onrejected, _classPrivateFieldGet(this, _internals))), _classPrivateFieldGet(this, _internals));
  }
  catch(onrejected) {
    return makeCancelable(_classPrivateFieldGet(this, _promise).catch(createCallback(onrejected, _classPrivateFieldGet(this, _internals))), _classPrivateFieldGet(this, _internals));
  }
  finally(onfinally, runWhenCanceled) {
    if (runWhenCanceled) {
      _classPrivateFieldGet(this, _internals).onCancelList.push(onfinally);
    }
    return makeCancelable(_classPrivateFieldGet(this, _promise).finally(createCallback(() => {
      if (onfinally) {
        if (runWhenCanceled) {
          _classPrivateFieldGet(this, _internals).onCancelList = _classPrivateFieldGet(this, _internals).onCancelList.filter((callback) => callback !== onfinally);
        }
        return onfinally();
      }
    }, _classPrivateFieldGet(this, _internals))), _classPrivateFieldGet(this, _internals));
  }
  cancel() {
    _classPrivateFieldGet(this, _internals).isCanceled = true;
    var callbacks = _classPrivateFieldGet(this, _internals).onCancelList;
    _classPrivateFieldGet(this, _internals).onCancelList = [];
    for (var callback of callbacks) {
      if (typeof callback === "function") {
        try {
          callback();
        } catch (err) {
          console.error(err);
        }
      }
    }
  }
  isCanceled() {
    return _classPrivateFieldGet(this, _internals).isCanceled === true;
  }
}
class CancelablePromise extends CancelablePromiseInternal {
  constructor(executor) {
    super({
      executor
    });
  }
}
_defineProperty(CancelablePromise, "all", function all(iterable) {
  return makeAllCancelable(iterable, Promise.all(iterable));
});
_defineProperty(CancelablePromise, "allSettled", function allSettled(iterable) {
  return makeAllCancelable(iterable, Promise.allSettled(iterable));
});
_defineProperty(CancelablePromise, "any", function any(iterable) {
  return makeAllCancelable(iterable, Promise.any(iterable));
});
_defineProperty(CancelablePromise, "race", function race(iterable) {
  return makeAllCancelable(iterable, Promise.race(iterable));
});
_defineProperty(CancelablePromise, "resolve", function resolve(value) {
  return cancelable(Promise.resolve(value));
});
_defineProperty(CancelablePromise, "reject", function reject(reason) {
  return cancelable(Promise.reject(reason));
});
_defineProperty(CancelablePromise, "isCancelable", isCancelablePromise);
function cancelable(promise) {
  return makeCancelable(promise, defaultInternals());
}
function isCancelablePromise(promise) {
  return promise instanceof CancelablePromise || promise instanceof CancelablePromiseInternal;
}
function createCallback(onResult, internals) {
  if (onResult) {
    return (arg) => {
      if (!internals.isCanceled) {
        var result = onResult(arg);
        if (isCancelablePromise(result)) {
          internals.onCancelList.push(result.cancel);
        }
        return result;
      }
      return arg;
    };
  }
}
function makeCancelable(promise, internals) {
  return new CancelablePromiseInternal({
    internals,
    promise
  });
}
function makeAllCancelable(iterable, promise) {
  var internals = defaultInternals();
  internals.onCancelList.push(() => {
    for (var resolvable of iterable) {
      if (isCancelablePromise(resolvable)) {
        resolvable.cancel();
      }
    }
  });
  return new CancelablePromiseInternal({
    internals,
    promise
  });
}
function defaultInternals() {
  return {
    isCanceled: false,
    onCancelList: []
  };
}
const parsePermissions = function(permString = "") {
  let permissions = Permission.NONE;
  if (!permString) {
    return permissions;
  }
  if (permString.includes("C") || permString.includes("K")) {
    permissions |= Permission.CREATE;
  }
  if (permString.includes("G")) {
    permissions |= Permission.READ;
  }
  if (permString.includes("W") || permString.includes("N") || permString.includes("V")) {
    permissions |= Permission.UPDATE;
  }
  if (permString.includes("D")) {
    permissions |= Permission.DELETE;
  }
  if (permString.includes("R")) {
    permissions |= Permission.SHARE;
  }
  return permissions;
};
const defaultDavProperties = [
  "d:getcontentlength",
  "d:getcontenttype",
  "d:getetag",
  "d:getlastmodified",
  "d:creationdate",
  "d:displayname",
  "d:quota-available-bytes",
  "d:resourcetype",
  "nc:has-preview",
  "nc:is-encrypted",
  "nc:mount-type",
  "oc:comments-unread",
  "oc:favorite",
  "oc:fileid",
  "oc:owner-display-name",
  "oc:owner-id",
  "oc:permissions",
  "oc:size"
];
const defaultDavNamespaces = {
  d: "DAV:",
  nc: "http://nextcloud.org/ns",
  oc: "http://owncloud.org/ns",
  ocs: "http://open-collaboration-services.org/ns"
};
const registerDavProperty = function(prop, namespace = { nc: "http://nextcloud.org/ns" }) {
  if (typeof window._nc_dav_properties === "undefined") {
    window._nc_dav_properties = [...defaultDavProperties];
    window._nc_dav_namespaces = { ...defaultDavNamespaces };
  }
  const namespaces = { ...window._nc_dav_namespaces, ...namespace };
  if (window._nc_dav_properties.find((search) => search === prop)) {
    logger.warn(`${prop} already registered`, { prop });
    return false;
  }
  if (prop.startsWith("<") || prop.split(":").length !== 2) {
    logger.error(`${prop} is not valid. See example: 'oc:fileid'`, { prop });
    return false;
  }
  const ns = prop.split(":")[0];
  if (!namespaces[ns]) {
    logger.error(`${prop} namespace unknown`, { prop, namespaces });
    return false;
  }
  window._nc_dav_properties.push(prop);
  window._nc_dav_namespaces = namespaces;
  return true;
};
const getDavProperties = function() {
  if (typeof window._nc_dav_properties === "undefined") {
    window._nc_dav_properties = [...defaultDavProperties];
  }
  return window._nc_dav_properties.map((prop) => `<${prop} />`).join(" ");
};
const getDavNameSpaces = function() {
  if (typeof window._nc_dav_namespaces === "undefined") {
    window._nc_dav_namespaces = { ...defaultDavNamespaces };
  }
  return Object.keys(window._nc_dav_namespaces).map((ns) => `xmlns:${ns}="${window._nc_dav_namespaces?.[ns]}"`).join(" ");
};
function getRootPath() {
  if (isPublicShare()) {
    return `/files/${getSharingToken()}`;
  }
  return `/files/${getCurrentUser()?.uid}`;
}
const defaultRootPath = getRootPath();
function getRemoteURL() {
  const url = generateRemoteUrl("dav");
  if (isPublicShare()) {
    return url.replace("remote.php", "public.php");
  }
  return url;
}
const defaultRemoteURL = getRemoteURL();
const getClient = function(remoteURL = defaultRemoteURL, headers = {}) {
  const client = an(remoteURL, { headers });
  function setHeaders(token) {
    client.setHeaders({
      ...headers,
      // Add this so the server knows it is an request from the browser
      "X-Requested-With": "XMLHttpRequest",
      // Inject user auth
      requesttoken: token ?? ""
    });
  }
  onRequestTokenUpdate(setHeaders);
  setHeaders(getRequestToken());
  const patcher = un();
  patcher.patch("fetch", (url, options) => {
    const headers2 = options.headers;
    if (headers2?.method) {
      options.method = headers2.method;
      delete headers2.method;
    }
    return fetch(url, options);
  });
  return client;
};
const resultToNode = function(node, filesRoot = defaultRootPath, remoteURL = defaultRemoteURL) {
  let userId = getCurrentUser()?.uid;
  if (isPublicShare()) {
    userId = userId ?? "anonymous";
  } else if (!userId) {
    throw new Error("No user id found");
  }
  const props = node.props;
  const permissions = parsePermissions(props?.permissions);
  const owner = String(props?.["owner-id"] || userId);
  const id = props.fileid || 0;
  const mtime = new Date(Date.parse(node.lastmod));
  const crtime = new Date(Date.parse(props.creationdate));
  const nodeData = {
    id,
    source: `${remoteURL}${node.filename}`,
    mtime: !isNaN(mtime.getTime()) && mtime.getTime() !== 0 ? mtime : void 0,
    crtime: !isNaN(crtime.getTime()) && crtime.getTime() !== 0 ? crtime : void 0,
    mime: node.mime || "application/octet-stream",
    // Manually cast to work around for https://github.com/perry-mitchell/webdav-client/pull/380
    displayname: props.displayname !== void 0 ? String(props.displayname) : void 0,
    size: props?.size || Number.parseInt(props.getcontentlength || "0"),
    // The fileid is set to -1 for failed requests
    status: id < 0 ? NodeStatus.FAILED : void 0,
    permissions,
    owner,
    root: filesRoot,
    attributes: {
      ...node,
      ...props,
      hasPreview: props?.["has-preview"]
    }
  };
  delete nodeData.attributes?.props;
  return node.type === "file" ? new File(nodeData) : new Folder(nodeData);
};
export {
  getDavNameSpaces as a,
  getDavProperties as b,
  registerDavProperty as c,
  defaultRemoteURL as d,
  getClient as g,
  resultToNode as r
};
//# sourceMappingURL=dav-BdQ8-Dvg.chunk.mjs.map
