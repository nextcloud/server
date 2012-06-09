/*

 @licstart
 The JavaScript code in this page is free software: you can redistribute it
 and/or modify it under the terms of the GNU Affero General Public License
 (GNU AGPL) as published by the Free Software Foundation, either version 3 of
 the License, or (at your option) any later version.  The code is distributed
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU AGPL for more details.

 As additional permission under GNU AGPL version 3 section 7, you
 may distribute non-source (e.g., minimized or compacted) forms of
 that code without the copy of the GNU GPL normally required by
 section 4, provided you include this license notice and a URL
 through which recipients can access the Corresponding Source.

 As a special exception to the AGPL, any HTML file which merely makes function
 calls to this code, and for that purpose includes it by reference shall be
 deemed a separate work for copyright law purposes. In addition, the copyright
 holders of this code give you permission to combine this code with free
 software libraries that are released under the GNU LGPL. You may copy and
 distribute such a system following the terms of the GNU AGPL for this code
 and the LGPL for the libraries. If you modify this code, you may extend this
 exception to your version of the code, but you are not obligated to do so.
 If you do not wish to do so, delete this exception statement from your
 version.

 This license applies to this entire compilation.
 @licend
 @source: http://www.webodf.org/
 @source: http://gitorious.org/odfkit/webodf/
*/
var core = {};
var gui = {};
var xmldom = {};
var odf = {};
function Runtime() {
}
Runtime.ByteArray = function(size) {
};
Runtime.ByteArray.prototype.slice = function(start, end) {
};
Runtime.prototype.byteArrayFromArray = function(array) {
};
Runtime.prototype.byteArrayFromString = function(string, encoding) {
};
Runtime.prototype.byteArrayToString = function(bytearray, encoding) {
};
Runtime.prototype.concatByteArrays = function(bytearray1, bytearray2) {
};
Runtime.prototype.read = function(path, offset, length, callback) {
};
Runtime.prototype.readFile = function(path, encoding, callback) {
};
Runtime.prototype.readFileSync = function(path, encoding) {
};
Runtime.prototype.loadXML = function(path, callback) {
};
Runtime.prototype.writeFile = function(path, data, callback) {
};
Runtime.prototype.isFile = function(path, callback) {
};
Runtime.prototype.getFileSize = function(path, callback) {
};
Runtime.prototype.deleteFile = function(path, callback) {
};
Runtime.prototype.log = function(msgOrCategory, msg) {
};
Runtime.prototype.setTimeout = function(callback, milliseconds) {
};
Runtime.prototype.libraryPaths = function() {
};
Runtime.prototype.type = function() {
};
Runtime.prototype.getDOMImplementation = function() {
};
Runtime.prototype.getWindow = function() {
};
var IS_COMPILED_CODE = false;
Runtime.byteArrayToString = function(bytearray, encoding) {
  function byteArrayToString(bytearray) {
    var s = "", i, l = bytearray.length;
    for(i = 0;i < l;i += 1) {
      s += String.fromCharCode(bytearray[i] & 255)
    }
    return s
  }
  function utf8ByteArrayToString(bytearray) {
    var s = "", i, l = bytearray.length, c0, c1, c2;
    for(i = 0;i < l;i += 1) {
      c0 = bytearray[i];
      if(c0 < 128) {
        s += String.fromCharCode(c0)
      }else {
        i += 1;
        c1 = bytearray[i];
        if(c0 < 224) {
          s += String.fromCharCode((c0 & 31) << 6 | c1 & 63)
        }else {
          i += 1;
          c2 = bytearray[i];
          s += String.fromCharCode((c0 & 15) << 12 | (c1 & 63) << 6 | c2 & 63)
        }
      }
    }
    return s
  }
  var result;
  if(encoding === "utf8") {
    result = utf8ByteArrayToString(bytearray)
  }else {
    if(encoding !== "binary") {
      this.log("Unsupported encoding: " + encoding)
    }
    result = byteArrayToString(bytearray)
  }
  return result
};
Runtime.getFunctionName = function getFunctionName(f) {
  var m;
  if(f.name === undefined) {
    m = (new RegExp("function\\s+(\\w+)")).exec(f);
    return m && m[1]
  }
  return f.name
};
function BrowserRuntime(logoutput) {
  var self = this, cache = {}, useNativeArray = window.ArrayBuffer && window.Uint8Array;
  this.ByteArray = useNativeArray ? function ByteArray(size) {
    Uint8Array.prototype.slice = function(begin, end) {
      if(end === undefined) {
        if(begin === undefined) {
          begin = 0
        }
        end = this.length
      }
      var view = this.subarray(begin, end), array, i;
      end -= begin;
      array = new Uint8Array(new ArrayBuffer(end));
      for(i = 0;i < end;i += 1) {
        array[i] = view[i]
      }
      return array
    };
    return new Uint8Array(new ArrayBuffer(size))
  } : function ByteArray(size) {
    var a = [];
    a.length = size;
    return a
  };
  this.concatByteArrays = useNativeArray ? function(bytearray1, bytearray2) {
    var i, l1 = bytearray1.length, l2 = bytearray2.length, a = new this.ByteArray(l1 + l2);
    for(i = 0;i < l1;i += 1) {
      a[i] = bytearray1[i]
    }
    for(i = 0;i < l2;i += 1) {
      a[i + l1] = bytearray2[i]
    }
    return a
  } : function(bytearray1, bytearray2) {
    return bytearray1.concat(bytearray2)
  };
  function utf8ByteArrayFromString(string) {
    var l = string.length, bytearray, i, n, j = 0;
    for(i = 0;i < l;i += 1) {
      n = string.charCodeAt(i);
      j += 1 + (n > 128) + (n > 2048)
    }
    bytearray = new self.ByteArray(j);
    j = 0;
    for(i = 0;i < l;i += 1) {
      n = string.charCodeAt(i);
      if(n < 128) {
        bytearray[j] = n;
        j += 1
      }else {
        if(n < 2048) {
          bytearray[j] = 192 | n >>> 6;
          bytearray[j + 1] = 128 | n & 63;
          j += 2
        }else {
          bytearray[j] = 224 | n >>> 12 & 15;
          bytearray[j + 1] = 128 | n >>> 6 & 63;
          bytearray[j + 2] = 128 | n & 63;
          j += 3
        }
      }
    }
    return bytearray
  }
  function byteArrayFromString(string) {
    var l = string.length, a = new self.ByteArray(l), i;
    for(i = 0;i < l;i += 1) {
      a[i] = string.charCodeAt(i) & 255
    }
    return a
  }
  this.byteArrayFromArray = function(array) {
    return array.slice()
  };
  this.byteArrayFromString = function(string, encoding) {
    var result;
    if(encoding === "utf8") {
      result = utf8ByteArrayFromString(string)
    }else {
      if(encoding !== "binary") {
        self.log("unknown encoding: " + encoding)
      }
      result = byteArrayFromString(string)
    }
    return result
  };
  this.byteArrayToString = Runtime.byteArrayToString;
  function log(msgOrCategory, msg) {
    var node, doc, category;
    if(msg) {
      category = msgOrCategory
    }else {
      msg = msgOrCategory
    }
    if(logoutput) {
      doc = logoutput.ownerDocument;
      if(category) {
        node = doc.createElement("span");
        node.className = category;
        node.appendChild(doc.createTextNode(category));
        logoutput.appendChild(node);
        logoutput.appendChild(doc.createTextNode(" "))
      }
      node = doc.createElement("span");
      node.appendChild(doc.createTextNode(msg));
      logoutput.appendChild(node);
      logoutput.appendChild(doc.createElement("br"))
    }else {
      if(console) {
        console.log(msg)
      }
    }
  }
  function readFile(path, encoding, callback) {
    if(cache.hasOwnProperty(path)) {
      callback(null, cache[path]);
      return
    }
    var xhr = new XMLHttpRequest;
    function handleResult() {
      var data;
      if(xhr.readyState === 4) {
        if(xhr.status === 0 && !xhr.responseText) {
          callback("File " + path + " is empty.")
        }else {
          if(xhr.status === 200 || xhr.status === 0) {
            if(encoding === "binary") {
              if(typeof VBArray !== "undefined") {
                data = (new VBArray(xhr.responseBody)).toArray()
              }else {
                data = self.byteArrayFromString(xhr.responseText, "binary")
              }
            }else {
              data = xhr.responseText
            }
            cache[path] = data;
            callback(null, data)
          }else {
            callback(xhr.responseText || xhr.statusText)
          }
        }
      }
    }
    xhr.open("GET", path, true);
    xhr.onreadystatechange = handleResult;
    if(xhr.overrideMimeType) {
      if(encoding !== "binary") {
        xhr.overrideMimeType("text/plain; charset=" + encoding)
      }else {
        xhr.overrideMimeType("text/plain; charset=x-user-defined")
      }
    }
    try {
      xhr.send(null)
    }catch(e) {
      callback(e.message)
    }
  }
  function read(path, offset, length, callback) {
    if(cache.hasOwnProperty(path)) {
      callback(null, cache[path].slice(offset, offset + length));
      return
    }
    var xhr = new XMLHttpRequest;
    function handleResult() {
      var data;
      if(xhr.readyState === 4) {
        if(xhr.status === 0 && !xhr.responseText) {
          callback("File " + path + " is empty.")
        }else {
          if(xhr.status === 200 || xhr.status === 0) {
            if(typeof VBArray !== "undefined") {
              data = (new VBArray(xhr.responseBody)).toArray()
            }else {
              data = self.byteArrayFromString(xhr.responseText, "binary")
            }
            cache[path] = data;
            callback(null, data.slice(offset, offset + length))
          }else {
            callback(xhr.responseText || xhr.statusText)
          }
        }
      }
    }
    xhr.open("GET", path, true);
    xhr.onreadystatechange = handleResult;
    if(xhr.overrideMimeType) {
      xhr.overrideMimeType("text/plain; charset=x-user-defined")
    }
    try {
      xhr.send(null)
    }catch(e) {
      callback(e.message)
    }
  }
  function readFileSync(path, encoding) {
    var xhr = new XMLHttpRequest, result;
    xhr.open("GET", path, false);
    if(xhr.overrideMimeType) {
      if(encoding !== "binary") {
        xhr.overrideMimeType("text/plain; charset=" + encoding)
      }else {
        xhr.overrideMimeType("text/plain; charset=x-user-defined")
      }
    }
    try {
      xhr.send(null);
      if(xhr.status === 200 || xhr.status === 0) {
        result = xhr.responseText
      }
    }catch(e) {
    }
    return result
  }
  function writeFile(path, data, callback) {
    cache[path] = data;
    var xhr = new XMLHttpRequest;
    function handleResult() {
      if(xhr.readyState === 4) {
        if(xhr.status === 0 && !xhr.responseText) {
          callback("File " + path + " is empty.")
        }else {
          if(xhr.status >= 200 && xhr.status < 300 || xhr.status === 0) {
            callback(null)
          }else {
            callback("Status " + String(xhr.status) + ": " + xhr.responseText || xhr.statusText)
          }
        }
      }
    }
    xhr.open("PUT", path, true);
    xhr.onreadystatechange = handleResult;
    if(data.buffer && !xhr.sendAsBinary) {
      data = data.buffer
    }else {
      data = self.byteArrayToString(data, "binary")
    }
    try {
      if(xhr.sendAsBinary) {
        xhr.sendAsBinary(data)
      }else {
        xhr.send(data)
      }
    }catch(e) {
      self.log("HUH? " + e + " " + data);
      callback(e.message)
    }
  }
  function deleteFile(path, callback) {
    var xhr = new XMLHttpRequest;
    xhr.open("DELETE", path, true);
    xhr.onreadystatechange = function() {
      if(xhr.readyState === 4) {
        if(xhr.status < 200 && xhr.status >= 300) {
          callback(xhr.responseText)
        }else {
          callback(null)
        }
      }
    };
    xhr.send(null)
  }
  function loadXML(path, callback) {
    var xhr = new XMLHttpRequest;
    function handleResult() {
      if(xhr.readyState === 4) {
        if(xhr.status === 0 && !xhr.responseText) {
          callback("File " + path + " is empty.")
        }else {
          if(xhr.status === 200 || xhr.status === 0) {
            callback(null, xhr.responseXML)
          }else {
            callback(xhr.responseText)
          }
        }
      }
    }
    xhr.open("GET", path, true);
    if(xhr.overrideMimeType) {
      xhr.overrideMimeType("text/xml")
    }
    xhr.onreadystatechange = handleResult;
    try {
      xhr.send(null)
    }catch(e) {
      callback(e.message)
    }
  }
  function isFile(path, callback) {
    self.getFileSize(path, function(size) {
      callback(size !== -1)
    })
  }
  function getFileSize(path, callback) {
    var xhr = new XMLHttpRequest;
    xhr.open("HEAD", path, true);
    xhr.onreadystatechange = function() {
      if(xhr.readyState !== 4) {
        return
      }
      var cl = xhr.getResponseHeader("Content-Length");
      if(cl) {
        callback(parseInt(cl, 10))
      }else {
        callback(-1)
      }
    };
    xhr.send(null)
  }
  function wrap(nativeFunction, nargs) {
    if(!nativeFunction) {
      return null
    }
    return function() {
      cache = {};
      var callback = arguments[nargs], args = Array.prototype.slice.call(arguments, 0, nargs), callbackname = "callback" + String(Math.random()).substring(2);
      window[callbackname] = function() {
        delete window[callbackname];
        callback.apply(this, arguments)
      };
      args.push(callbackname);
      nativeFunction.apply(this, args)
    }
  }
  this.readFile = readFile;
  this.read = read;
  this.readFileSync = readFileSync;
  this.writeFile = writeFile;
  this.deleteFile = deleteFile;
  this.loadXML = loadXML;
  this.isFile = isFile;
  this.getFileSize = getFileSize;
  this.log = log;
  this.setTimeout = function(f, msec) {
    setTimeout(function() {
      f()
    }, msec)
  };
  this.libraryPaths = function() {
    return["lib"]
  };
  this.setCurrentDirectory = function(dir) {
  };
  this.type = function() {
    return"BrowserRuntime"
  };
  this.getDOMImplementation = function() {
    return window.document.implementation
  };
  this.exit = function(exitCode) {
    log("Calling exit with code " + String(exitCode) + ", but exit() is not implemented.")
  };
  this.getWindow = function() {
    return window
  }
}
function NodeJSRuntime() {
  var self = this, fs = require("fs"), currentDirectory = "";
  this.ByteArray = function(size) {
    return new Buffer(size)
  };
  this.byteArrayFromArray = function(array) {
    var ba = new Buffer(array.length), i, l = array.length;
    for(i = 0;i < l;i += 1) {
      ba[i] = array[i]
    }
    return ba
  };
  this.concatByteArrays = function(a, b) {
    var ba = new Buffer(a.length + b.length);
    a.copy(ba, 0, 0);
    b.copy(ba, a.length, 0);
    return ba
  };
  this.byteArrayFromString = function(string, encoding) {
    return new Buffer(string, encoding)
  };
  this.byteArrayToString = function(bytearray, encoding) {
    return bytearray.toString(encoding)
  };
  function isFile(path, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    fs.stat(path, function(err, stats) {
      callback(!err && stats.isFile())
    })
  }
  function loadXML(path, callback) {
    throw"Not implemented.";
  }
  this.readFile = function(path, encoding, callback) {
    if(encoding !== "binary") {
      fs.readFile(path, encoding, callback)
    }else {
      fs.readFile(path, null, callback)
    }
  };
  this.writeFile = function(path, data, callback) {
    fs.writeFile(path, data, "binary", function(err) {
      callback(err || null)
    })
  };
  this.deleteFile = fs.unlink;
  this.read = function(path, offset, length, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    fs.open(path, "r+", 666, function(err, fd) {
      if(err) {
        callback(err);
        return
      }
      var buffer = new Buffer(length);
      fs.read(fd, buffer, 0, length, offset, function(err, bytesRead) {
        fs.close(fd);
        callback(err, buffer)
      })
    })
  };
  this.readFileSync = function(path, encoding) {
    if(!encoding) {
      return""
    }
    return fs.readFileSync(path, encoding)
  };
  this.loadXML = loadXML;
  this.isFile = isFile;
  this.getFileSize = function(path, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    fs.stat(path, function(err, stats) {
      if(err) {
        callback(-1)
      }else {
        callback(stats.size)
      }
    })
  };
  this.log = function(msg) {
    process.stderr.write(msg + "\n")
  };
  this.setTimeout = function(f, msec) {
    setTimeout(function() {
      f()
    }, msec)
  };
  this.libraryPaths = function() {
    return[__dirname]
  };
  this.setCurrentDirectory = function(dir) {
    currentDirectory = dir
  };
  this.currentDirectory = function() {
    return currentDirectory
  };
  this.type = function() {
    return"NodeJSRuntime"
  };
  this.getDOMImplementation = function() {
    return null
  };
  this.exit = process.exit;
  this.getWindow = function() {
    return null
  }
}
function RhinoRuntime() {
  var self = this, dom = Packages.javax.xml.parsers.DocumentBuilderFactory.newInstance(), builder, entityresolver, currentDirectory = "";
  dom.setValidating(false);
  dom.setNamespaceAware(true);
  dom.setExpandEntityReferences(false);
  dom.setSchema(null);
  entityresolver = Packages.org.xml.sax.EntityResolver({resolveEntity:function(publicId, systemId) {
    var file, open = function(path) {
      var reader = new Packages.java.io.FileReader(path), source = new Packages.org.xml.sax.InputSource(reader);
      return source
    };
    file = systemId;
    return open(file)
  }});
  builder = dom.newDocumentBuilder();
  builder.setEntityResolver(entityresolver);
  this.ByteArray = function ByteArray(size) {
    return[size]
  };
  this.byteArrayFromArray = function(array) {
    return array
  };
  this.byteArrayFromString = function(string, encoding) {
    var a = [], i, l = string.length;
    for(i = 0;i < l;i += 1) {
      a[i] = string.charCodeAt(i) & 255
    }
    return a
  };
  this.byteArrayToString = Runtime.byteArrayToString;
  this.concatByteArrays = function(bytearray1, bytearray2) {
    return bytearray1.concat(bytearray2)
  };
  function loadXML(path, callback) {
    var file = new Packages.java.io.File(path), document;
    try {
      document = builder.parse(file)
    }catch(err) {
      print(err);
      callback(err);
      return
    }
    callback(null, document)
  }
  function runtimeReadFile(path, encoding, callback) {
    var file = new Packages.java.io.File(path), data, rhinoencoding = encoding === "binary" ? "latin1" : encoding;
    if(!file.isFile()) {
      callback(path + " is not a file.")
    }else {
      data = readFile(path, rhinoencoding);
      if(encoding === "binary") {
        data = self.byteArrayFromString(data, "binary")
      }
      callback(null, data)
    }
  }
  function runtimeReadFileSync(path, encoding) {
    var file = new Packages.java.io.File(path), data, i;
    if(!file.isFile()) {
      return null
    }
    if(encoding === "binary") {
      encoding = "latin1"
    }
    return readFile(path, encoding)
  }
  function isFile(path, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    var file = new Packages.java.io.File(path);
    callback(file.isFile())
  }
  this.loadXML = loadXML;
  this.readFile = runtimeReadFile;
  this.writeFile = function(path, data, callback) {
    var out = new Packages.java.io.FileOutputStream(path), i, l = data.length;
    for(i = 0;i < l;i += 1) {
      out.write(data[i])
    }
    out.close();
    callback(null)
  };
  this.deleteFile = function(path, callback) {
    var file = new Packages.java.io.File(path);
    if(file["delete"]()) {
      callback(null)
    }else {
      callback("Could not delete " + path)
    }
  };
  this.read = function(path, offset, length, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    var data = runtimeReadFileSync(path, "binary");
    if(data) {
      callback(null, this.byteArrayFromString(data.substring(offset, offset + length), "binary"))
    }else {
      callback("Cannot read " + path)
    }
  };
  this.readFileSync = function(path, encoding) {
    if(!encoding) {
      return""
    }
    return readFile(path, encoding)
  };
  this.isFile = isFile;
  this.getFileSize = function(path, callback) {
    if(currentDirectory) {
      path = currentDirectory + "/" + path
    }
    var file = new Packages.java.io.File(path);
    callback(file.length())
  };
  this.log = print;
  this.setTimeout = function(f, msec) {
    f()
  };
  this.libraryPaths = function() {
    return["lib"]
  };
  this.setCurrentDirectory = function(dir) {
    currentDirectory = dir
  };
  this.currentDirectory = function() {
    return currentDirectory
  };
  this.type = function() {
    return"RhinoRuntime"
  };
  this.getDOMImplementation = function() {
    return builder.getDOMImplementation()
  };
  this.exit = quit;
  this.getWindow = function() {
    return null
  }
}
var runtime = function() {
  var result;
  if(typeof window !== "undefined") {
    result = new BrowserRuntime(window.document.getElementById("logoutput"))
  }else {
    if(typeof require !== "undefined") {
      result = new NodeJSRuntime
    }else {
      result = new RhinoRuntime
    }
  }
  return result
}();
(function() {
  var cache = {}, dircontents = {};
  function getOrDefinePackage(packageNameComponents) {
    var topname = packageNameComponents[0], i, pkg;
    pkg = eval("if (typeof " + topname + " === 'undefined') {" + "eval('" + topname + " = {};');}" + topname);
    for(i = 1;i < packageNameComponents.length - 1;i += 1) {
      if(!pkg.hasOwnProperty(packageNameComponents[i])) {
        pkg = pkg[packageNameComponents[i]] = {}
      }
    }
    return pkg[packageNameComponents[packageNameComponents.length - 1]]
  }
  runtime.loadClass = function(classpath) {
    if(IS_COMPILED_CODE) {
      return
    }
    if(cache.hasOwnProperty(classpath)) {
      return
    }
    var names = classpath.split("."), impl;
    impl = getOrDefinePackage(names);
    if(impl) {
      cache[classpath] = true;
      return
    }
    function getPathFromManifests(classpath) {
      var path = classpath.replace(".", "/") + ".js", dirs = runtime.libraryPaths(), i, dir, code;
      if(runtime.currentDirectory) {
        dirs.push(runtime.currentDirectory())
      }
      for(i = 0;i < dirs.length;i += 1) {
        dir = dirs[i];
        if(!dircontents.hasOwnProperty(dir)) {
          code = runtime.readFileSync(dirs[i] + "/manifest.js", "utf8");
          if(code && code.length) {
            try {
              dircontents[dir] = eval(code)
            }catch(e1) {
              dircontents[dir] = null;
              runtime.log("Cannot load manifest for " + dir + ".")
            }
          }else {
            dircontents[dir] = null
          }
        }
        code = null;
        dir = dircontents[dir];
        if(dir && dir.indexOf && dir.indexOf(path) !== -1) {
          return dirs[i] + "/" + path
        }
      }
      return null
    }
    function load(classpath) {
      var code, path;
      path = getPathFromManifests(classpath);
      if(!path) {
        throw classpath + " is not listed in any manifest.js.";
      }
      try {
        code = runtime.readFileSync(path, "utf8")
      }catch(e2) {
        runtime.log("Error loading " + classpath + " " + e2);
        throw e2;
      }
      if(code === undefined) {
        throw"Cannot load class " + classpath;
      }
      try {
        code = eval(classpath + " = eval(code);")
      }catch(e4) {
        runtime.log("Error loading " + classpath + " " + e4);
        throw e4;
      }
      return code
    }
    impl = load(classpath);
    if(!impl || Runtime.getFunctionName(impl) !== names[names.length - 1]) {
      runtime.log("Loaded code is not for " + names[names.length - 1]);
      throw"Loaded code is not for " + names[names.length - 1];
    }
    cache[classpath] = true
  }
})();
(function(args) {
  args = Array.prototype.slice.call(args);
  function run(argv) {
    if(!argv.length) {
      return
    }
    var script = argv[0];
    runtime.readFile(script, "utf8", function(err, code) {
      var path = "", paths = runtime.libraryPaths();
      if(script.indexOf("/") !== -1) {
        path = script.substring(0, script.indexOf("/"))
      }
      runtime.setCurrentDirectory(path);
      function run() {
        var script, path, paths, args, argv, result;
        result = eval(code);
        if(result) {
          runtime.exit(result)
        }
        return
      }
      if(err) {
        runtime.log(err);
        runtime.exit(1)
      }else {
        run.apply(null, argv)
      }
    })
  }
  if(runtime.type() === "NodeJSRuntime") {
    run(process.argv.slice(2))
  }else {
    if(runtime.type() === "RhinoRuntime") {
      run(args)
    }else {
      run(args.slice(1))
    }
  }
})(typeof arguments !== "undefined" && arguments);
core.Base64 = function() {
  var b64chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", b64charcodes = function() {
    var a = [], i, codeA = "A".charCodeAt(0), codea = "a".charCodeAt(0), code0 = "0".charCodeAt(0);
    for(i = 0;i < 26;i += 1) {
      a.push(codeA + i)
    }
    for(i = 0;i < 26;i += 1) {
      a.push(codea + i)
    }
    for(i = 0;i < 10;i += 1) {
      a.push(code0 + i)
    }
    a.push("+".charCodeAt(0));
    a.push("/".charCodeAt(0));
    return a
  }(), b64tab = function(bin) {
    var t = {}, i, l;
    for(i = 0, l = bin.length;i < l;i += 1) {
      t[bin.charAt(i)] = i
    }
    return t
  }(b64chars), convertUTF16StringToBase64, convertBase64ToUTF16String, btoa, atob;
  function stringToArray(s) {
    var a = [], i, l = s.length;
    for(i = 0;i < l;i += 1) {
      a[i] = s.charCodeAt(i) & 255
    }
    return a
  }
  function convertUTF8ArrayToBase64(bin) {
    var n, b64 = "", i, l = bin.length - 2;
    for(i = 0;i < l;i += 3) {
      n = bin[i] << 16 | bin[i + 1] << 8 | bin[i + 2];
      b64 += b64chars[n >>> 18];
      b64 += b64chars[n >>> 12 & 63];
      b64 += b64chars[n >>> 6 & 63];
      b64 += b64chars[n & 63]
    }
    if(i === l + 1) {
      n = bin[i] << 4;
      b64 += b64chars[n >>> 6];
      b64 += b64chars[n & 63];
      b64 += "=="
    }else {
      if(i === l) {
        n = bin[i] << 10 | bin[i + 1] << 2;
        b64 += b64chars[n >>> 12];
        b64 += b64chars[n >>> 6 & 63];
        b64 += b64chars[n & 63];
        b64 += "="
      }
    }
    return b64
  }
  function convertBase64ToUTF8Array(b64) {
    b64 = b64.replace(/[^A-Za-z0-9+\/]+/g, "");
    var bin = [], padlen = b64.length % 4, i, l = b64.length, n;
    for(i = 0;i < l;i += 4) {
      n = (b64tab[b64.charAt(i)] || 0) << 18 | (b64tab[b64.charAt(i + 1)] || 0) << 12 | (b64tab[b64.charAt(i + 2)] || 0) << 6 | (b64tab[b64.charAt(i + 3)] || 0);
      bin.push(n >> 16, n >> 8 & 255, n & 255)
    }
    bin.length -= [0, 0, 2, 1][padlen];
    return bin
  }
  function convertUTF16ArrayToUTF8Array(uni) {
    var bin = [], i, l = uni.length, n;
    for(i = 0;i < l;i += 1) {
      n = uni[i];
      if(n < 128) {
        bin.push(n)
      }else {
        if(n < 2048) {
          bin.push(192 | n >>> 6, 128 | n & 63)
        }else {
          bin.push(224 | n >>> 12 & 15, 128 | n >>> 6 & 63, 128 | n & 63)
        }
      }
    }
    return bin
  }
  function convertUTF8ArrayToUTF16Array(bin) {
    var uni = [], i, l = bin.length, c0, c1, c2;
    for(i = 0;i < l;i += 1) {
      c0 = bin[i];
      if(c0 < 128) {
        uni.push(c0)
      }else {
        i += 1;
        c1 = bin[i];
        if(c0 < 224) {
          uni.push((c0 & 31) << 6 | c1 & 63)
        }else {
          i += 1;
          c2 = bin[i];
          uni.push((c0 & 15) << 12 | (c1 & 63) << 6 | c2 & 63)
        }
      }
    }
    return uni
  }
  function convertUTF8StringToBase64(bin) {
    return convertUTF8ArrayToBase64(stringToArray(bin))
  }
  function convertBase64ToUTF8String(b64) {
    return String.fromCharCode.apply(String, convertBase64ToUTF8Array(b64))
  }
  function convertUTF8StringToUTF16Array(bin) {
    return convertUTF8ArrayToUTF16Array(stringToArray(bin))
  }
  function convertUTF8ArrayToUTF16String(bin) {
    var b = convertUTF8ArrayToUTF16Array(bin), r = "", i = 0, chunksize = 45E3;
    while(i < b.length) {
      r += String.fromCharCode.apply(String, b.slice(i, i + chunksize));
      i += chunksize
    }
    return r
  }
  function convertUTF8StringToUTF16String_internal(bin, i, end) {
    var str = "", c0, c1, c2, j;
    for(j = i;j < end;j += 1) {
      c0 = bin.charCodeAt(j) & 255;
      if(c0 < 128) {
        str += String.fromCharCode(c0)
      }else {
        j += 1;
        c1 = bin.charCodeAt(j) & 255;
        if(c0 < 224) {
          str += String.fromCharCode((c0 & 31) << 6 | c1 & 63)
        }else {
          j += 1;
          c2 = bin.charCodeAt(j) & 255;
          str += String.fromCharCode((c0 & 15) << 12 | (c1 & 63) << 6 | c2 & 63)
        }
      }
    }
    return str
  }
  function convertUTF8StringToUTF16String(bin, callback) {
    var partsize = 1E5, numparts = bin.length / partsize, str = "", pos = 0;
    if(bin.length < partsize) {
      callback(convertUTF8StringToUTF16String_internal(bin, 0, bin.length), true);
      return
    }
    if(typeof bin !== "string") {
      bin = bin.slice()
    }
    function f() {
      var end = pos + partsize;
      if(end > bin.length) {
        end = bin.length
      }
      str += convertUTF8StringToUTF16String_internal(bin, pos, end);
      pos = end;
      end = pos === bin.length;
      if(callback(str, end) && !end) {
        runtime.setTimeout(f, 0)
      }
    }
    f()
  }
  function convertUTF16StringToUTF8Array(uni) {
    return convertUTF16ArrayToUTF8Array(stringToArray(uni))
  }
  function convertUTF16ArrayToUTF8String(uni) {
    return String.fromCharCode.apply(String, convertUTF16ArrayToUTF8Array(uni))
  }
  function convertUTF16StringToUTF8String(uni) {
    return String.fromCharCode.apply(String, convertUTF16ArrayToUTF8Array(stringToArray(uni)))
  }
  btoa = runtime.getWindow() && runtime.getWindow().btoa;
  if(btoa) {
    convertUTF16StringToBase64 = function(uni) {
      return btoa(convertUTF16StringToUTF8String(uni))
    }
  }else {
    btoa = convertUTF8StringToBase64;
    convertUTF16StringToBase64 = function(uni) {
      return convertUTF8ArrayToBase64(convertUTF16StringToUTF8Array(uni))
    }
  }
  atob = runtime.getWindow() && runtime.getWindow().atob;
  if(atob) {
    convertBase64ToUTF16String = function(b64) {
      var b = atob(b64);
      return convertUTF8StringToUTF16String_internal(b, 0, b.length)
    }
  }else {
    atob = convertBase64ToUTF8String;
    convertBase64ToUTF16String = function(b64) {
      return convertUTF8ArrayToUTF16String(convertBase64ToUTF8Array(b64))
    }
  }
  function Base64() {
    this.convertUTF8ArrayToBase64 = convertUTF8ArrayToBase64;
    this.convertByteArrayToBase64 = convertUTF8ArrayToBase64;
    this.convertBase64ToUTF8Array = convertBase64ToUTF8Array;
    this.convertBase64ToByteArray = convertBase64ToUTF8Array;
    this.convertUTF16ArrayToUTF8Array = convertUTF16ArrayToUTF8Array;
    this.convertUTF16ArrayToByteArray = convertUTF16ArrayToUTF8Array;
    this.convertUTF8ArrayToUTF16Array = convertUTF8ArrayToUTF16Array;
    this.convertByteArrayToUTF16Array = convertUTF8ArrayToUTF16Array;
    this.convertUTF8StringToBase64 = convertUTF8StringToBase64;
    this.convertBase64ToUTF8String = convertBase64ToUTF8String;
    this.convertUTF8StringToUTF16Array = convertUTF8StringToUTF16Array;
    this.convertUTF8ArrayToUTF16String = convertUTF8ArrayToUTF16String;
    this.convertByteArrayToUTF16String = convertUTF8ArrayToUTF16String;
    this.convertUTF8StringToUTF16String = convertUTF8StringToUTF16String;
    this.convertUTF16StringToUTF8Array = convertUTF16StringToUTF8Array;
    this.convertUTF16StringToByteArray = convertUTF16StringToUTF8Array;
    this.convertUTF16ArrayToUTF8String = convertUTF16ArrayToUTF8String;
    this.convertUTF16StringToUTF8String = convertUTF16StringToUTF8String;
    this.convertUTF16StringToBase64 = convertUTF16StringToBase64;
    this.convertBase64ToUTF16String = convertBase64ToUTF16String;
    this.fromBase64 = convertBase64ToUTF8String;
    this.toBase64 = convertUTF8StringToBase64;
    this.atob = atob;
    this.btoa = btoa;
    this.utob = convertUTF16StringToUTF8String;
    this.btou = convertUTF8StringToUTF16String;
    this.encode = convertUTF16StringToBase64;
    this.encodeURI = function(u) {
      return convertUTF16StringToBase64(u).replace(/[+\/]/g, function(m0) {
        return m0 === "+" ? "-" : "_"
      }).replace(/\\=+$/, "")
    };
    this.decode = function(a) {
      return convertBase64ToUTF16String(a.replace(/[\-_]/g, function(m0) {
        return m0 === "-" ? "+" : "/"
      }))
    }
  }
  return Base64
}();
core.RawDeflate = function() {
  var zip_WSIZE = 32768, zip_STORED_BLOCK = 0, zip_STATIC_TREES = 1, zip_DYN_TREES = 2, zip_DEFAULT_LEVEL = 6, zip_FULL_SEARCH = true, zip_INBUFSIZ = 32768, zip_INBUF_EXTRA = 64, zip_OUTBUFSIZ = 1024 * 8, zip_window_size = 2 * zip_WSIZE, zip_MIN_MATCH = 3, zip_MAX_MATCH = 258, zip_BITS = 16, zip_LIT_BUFSIZE = 8192, zip_HASH_BITS = 13, zip_DIST_BUFSIZE = zip_LIT_BUFSIZE, zip_HASH_SIZE = 1 << zip_HASH_BITS, zip_HASH_MASK = zip_HASH_SIZE - 1, zip_WMASK = zip_WSIZE - 1, zip_NIL = 0, zip_TOO_FAR = 4096, 
  zip_MIN_LOOKAHEAD = zip_MAX_MATCH + zip_MIN_MATCH + 1, zip_MAX_DIST = zip_WSIZE - zip_MIN_LOOKAHEAD, zip_SMALLEST = 1, zip_MAX_BITS = 15, zip_MAX_BL_BITS = 7, zip_LENGTH_CODES = 29, zip_LITERALS = 256, zip_END_BLOCK = 256, zip_L_CODES = zip_LITERALS + 1 + zip_LENGTH_CODES, zip_D_CODES = 30, zip_BL_CODES = 19, zip_REP_3_6 = 16, zip_REPZ_3_10 = 17, zip_REPZ_11_138 = 18, zip_HEAP_SIZE = 2 * zip_L_CODES + 1, zip_H_SHIFT = parseInt((zip_HASH_BITS + zip_MIN_MATCH - 1) / zip_MIN_MATCH, 10), zip_free_queue, 
  zip_qhead, zip_qtail, zip_initflag, zip_outbuf = null, zip_outcnt, zip_outoff, zip_complete, zip_window, zip_d_buf, zip_l_buf, zip_prev, zip_bi_buf, zip_bi_valid, zip_block_start, zip_ins_h, zip_hash_head, zip_prev_match, zip_match_available, zip_match_length, zip_prev_length, zip_strstart, zip_match_start, zip_eofile, zip_lookahead, zip_max_chain_length, zip_max_lazy_match, zip_compr_level, zip_good_match, zip_nice_match, zip_dyn_ltree, zip_dyn_dtree, zip_static_ltree, zip_static_dtree, zip_bl_tree, 
  zip_l_desc, zip_d_desc, zip_bl_desc, zip_bl_count, zip_heap, zip_heap_len, zip_heap_max, zip_depth, zip_length_code, zip_dist_code, zip_base_length, zip_base_dist, zip_flag_buf, zip_last_lit, zip_last_dist, zip_last_flags, zip_flags, zip_flag_bit, zip_opt_len, zip_static_len, zip_deflate_data, zip_deflate_pos, zip_extra_lbits = [0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 0], zip_extra_dbits = [0, 0, 0, 0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 
  9, 10, 10, 11, 11, 12, 12, 13, 13], zip_extra_blbits = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 3, 7], zip_bl_order = [16, 17, 18, 0, 8, 7, 9, 6, 10, 5, 11, 4, 12, 3, 13, 2, 14, 1, 15], zip_configuration_table;
  if(zip_LIT_BUFSIZE > zip_INBUFSIZ) {
    runtime.log("error: zip_INBUFSIZ is too small")
  }
  if(zip_WSIZE << 1 > 1 << zip_BITS) {
    runtime.log("error: zip_WSIZE is too large")
  }
  if(zip_HASH_BITS > zip_BITS - 1) {
    runtime.log("error: zip_HASH_BITS is too large")
  }
  if(zip_HASH_BITS < 8 || zip_MAX_MATCH !== 258) {
    runtime.log("error: Code too clever")
  }
  function Zip_DeflateCT() {
    this.fc = 0;
    this.dl = 0
  }
  function Zip_DeflateTreeDesc() {
    this.dyn_tree = null;
    this.static_tree = null;
    this.extra_bits = null;
    this.extra_base = 0;
    this.elems = 0;
    this.max_length = 0;
    this.max_code = 0
  }
  function Zip_DeflateConfiguration(a, b, c, d) {
    this.good_length = a;
    this.max_lazy = b;
    this.nice_length = c;
    this.max_chain = d
  }
  function Zip_DeflateBuffer() {
    this.next = null;
    this.len = 0;
    this.ptr = [];
    this.ptr.length = zip_OUTBUFSIZ;
    this.off = 0
  }
  zip_configuration_table = [new Zip_DeflateConfiguration(0, 0, 0, 0), new Zip_DeflateConfiguration(4, 4, 8, 4), new Zip_DeflateConfiguration(4, 5, 16, 8), new Zip_DeflateConfiguration(4, 6, 32, 32), new Zip_DeflateConfiguration(4, 4, 16, 16), new Zip_DeflateConfiguration(8, 16, 32, 32), new Zip_DeflateConfiguration(8, 16, 128, 128), new Zip_DeflateConfiguration(8, 32, 128, 256), new Zip_DeflateConfiguration(32, 128, 258, 1024), new Zip_DeflateConfiguration(32, 258, 258, 4096)];
  function zip_deflate_start(level) {
    var i;
    if(!level) {
      level = zip_DEFAULT_LEVEL
    }else {
      if(level < 1) {
        level = 1
      }else {
        if(level > 9) {
          level = 9
        }
      }
    }
    zip_compr_level = level;
    zip_initflag = false;
    zip_eofile = false;
    if(zip_outbuf !== null) {
      return
    }
    zip_free_queue = zip_qhead = zip_qtail = null;
    zip_outbuf = [];
    zip_outbuf.length = zip_OUTBUFSIZ;
    zip_window = [];
    zip_window.length = zip_window_size;
    zip_d_buf = [];
    zip_d_buf.length = zip_DIST_BUFSIZE;
    zip_l_buf = [];
    zip_l_buf.length = zip_INBUFSIZ + zip_INBUF_EXTRA;
    zip_prev = [];
    zip_prev.length = 1 << zip_BITS;
    zip_dyn_ltree = [];
    zip_dyn_ltree.length = zip_HEAP_SIZE;
    for(i = 0;i < zip_HEAP_SIZE;i++) {
      zip_dyn_ltree[i] = new Zip_DeflateCT
    }
    zip_dyn_dtree = [];
    zip_dyn_dtree.length = 2 * zip_D_CODES + 1;
    for(i = 0;i < 2 * zip_D_CODES + 1;i++) {
      zip_dyn_dtree[i] = new Zip_DeflateCT
    }
    zip_static_ltree = [];
    zip_static_ltree.length = zip_L_CODES + 2;
    for(i = 0;i < zip_L_CODES + 2;i++) {
      zip_static_ltree[i] = new Zip_DeflateCT
    }
    zip_static_dtree = [];
    zip_static_dtree.length = zip_D_CODES;
    for(i = 0;i < zip_D_CODES;i++) {
      zip_static_dtree[i] = new Zip_DeflateCT
    }
    zip_bl_tree = [];
    zip_bl_tree.length = 2 * zip_BL_CODES + 1;
    for(i = 0;i < 2 * zip_BL_CODES + 1;i++) {
      zip_bl_tree[i] = new Zip_DeflateCT
    }
    zip_l_desc = new Zip_DeflateTreeDesc;
    zip_d_desc = new Zip_DeflateTreeDesc;
    zip_bl_desc = new Zip_DeflateTreeDesc;
    zip_bl_count = [];
    zip_bl_count.length = zip_MAX_BITS + 1;
    zip_heap = [];
    zip_heap.length = 2 * zip_L_CODES + 1;
    zip_depth = [];
    zip_depth.length = 2 * zip_L_CODES + 1;
    zip_length_code = [];
    zip_length_code.length = zip_MAX_MATCH - zip_MIN_MATCH + 1;
    zip_dist_code = [];
    zip_dist_code.length = 512;
    zip_base_length = [];
    zip_base_length.length = zip_LENGTH_CODES;
    zip_base_dist = [];
    zip_base_dist.length = zip_D_CODES;
    zip_flag_buf = [];
    zip_flag_buf.length = parseInt(zip_LIT_BUFSIZE / 8, 10)
  }
  var zip_deflate_end = function() {
    zip_free_queue = zip_qhead = zip_qtail = null;
    zip_outbuf = null;
    zip_window = null;
    zip_d_buf = null;
    zip_l_buf = null;
    zip_prev = null;
    zip_dyn_ltree = null;
    zip_dyn_dtree = null;
    zip_static_ltree = null;
    zip_static_dtree = null;
    zip_bl_tree = null;
    zip_l_desc = null;
    zip_d_desc = null;
    zip_bl_desc = null;
    zip_bl_count = null;
    zip_heap = null;
    zip_depth = null;
    zip_length_code = null;
    zip_dist_code = null;
    zip_base_length = null;
    zip_base_dist = null;
    zip_flag_buf = null
  };
  var zip_reuse_queue = function(p) {
    p.next = zip_free_queue;
    zip_free_queue = p
  };
  var zip_new_queue = function() {
    var p;
    if(zip_free_queue !== null) {
      p = zip_free_queue;
      zip_free_queue = zip_free_queue.next
    }else {
      p = new Zip_DeflateBuffer
    }
    p.next = null;
    p.len = p.off = 0;
    return p
  };
  var zip_head1 = function(i) {
    return zip_prev[zip_WSIZE + i]
  };
  var zip_head2 = function(i, val) {
    zip_prev[zip_WSIZE + i] = val;
    return val
  };
  var zip_qoutbuf = function() {
    var q, i;
    if(zip_outcnt !== 0) {
      q = zip_new_queue();
      if(zip_qhead === null) {
        zip_qhead = zip_qtail = q
      }else {
        zip_qtail = zip_qtail.next = q
      }
      q.len = zip_outcnt - zip_outoff;
      for(i = 0;i < q.len;i++) {
        q.ptr[i] = zip_outbuf[zip_outoff + i]
      }
      zip_outcnt = zip_outoff = 0
    }
  };
  var zip_put_byte = function(c) {
    zip_outbuf[zip_outoff + zip_outcnt++] = c;
    if(zip_outoff + zip_outcnt === zip_OUTBUFSIZ) {
      zip_qoutbuf()
    }
  };
  var zip_put_short = function(w) {
    w &= 65535;
    if(zip_outoff + zip_outcnt < zip_OUTBUFSIZ - 2) {
      zip_outbuf[zip_outoff + zip_outcnt++] = w & 255;
      zip_outbuf[zip_outoff + zip_outcnt++] = w >>> 8
    }else {
      zip_put_byte(w & 255);
      zip_put_byte(w >>> 8)
    }
  };
  var zip_INSERT_STRING = function() {
    zip_ins_h = (zip_ins_h << zip_H_SHIFT ^ zip_window[zip_strstart + zip_MIN_MATCH - 1] & 255) & zip_HASH_MASK;
    zip_hash_head = zip_head1(zip_ins_h);
    zip_prev[zip_strstart & zip_WMASK] = zip_hash_head;
    zip_head2(zip_ins_h, zip_strstart)
  };
  var zip_Buf_size = 16;
  var zip_send_bits = function(value, length) {
    if(zip_bi_valid > zip_Buf_size - length) {
      zip_bi_buf |= value << zip_bi_valid;
      zip_put_short(zip_bi_buf);
      zip_bi_buf = value >> zip_Buf_size - zip_bi_valid;
      zip_bi_valid += length - zip_Buf_size
    }else {
      zip_bi_buf |= value << zip_bi_valid;
      zip_bi_valid += length
    }
  };
  var zip_SEND_CODE = function(c, tree) {
    zip_send_bits(tree[c].fc, tree[c].dl)
  };
  var zip_D_CODE = function(dist) {
    return(dist < 256 ? zip_dist_code[dist] : zip_dist_code[256 + (dist >> 7)]) & 255
  };
  var zip_SMALLER = function(tree, n, m) {
    return tree[n].fc < tree[m].fc || tree[n].fc === tree[m].fc && zip_depth[n] <= zip_depth[m]
  };
  var zip_read_buff = function(buff, offset, n) {
    var i;
    for(i = 0;i < n && zip_deflate_pos < zip_deflate_data.length;i++) {
      buff[offset + i] = zip_deflate_data.charCodeAt(zip_deflate_pos++) & 255
    }
    return i
  };
  var zip_fill_window = function() {
    var n, m;
    var more = zip_window_size - zip_lookahead - zip_strstart;
    if(more === -1) {
      more--
    }else {
      if(zip_strstart >= zip_WSIZE + zip_MAX_DIST) {
        for(n = 0;n < zip_WSIZE;n++) {
          zip_window[n] = zip_window[n + zip_WSIZE]
        }
        zip_match_start -= zip_WSIZE;
        zip_strstart -= zip_WSIZE;
        zip_block_start -= zip_WSIZE;
        for(n = 0;n < zip_HASH_SIZE;n++) {
          m = zip_head1(n);
          zip_head2(n, m >= zip_WSIZE ? m - zip_WSIZE : zip_NIL)
        }
        for(n = 0;n < zip_WSIZE;n++) {
          m = zip_prev[n];
          zip_prev[n] = m >= zip_WSIZE ? m - zip_WSIZE : zip_NIL
        }
        more += zip_WSIZE
      }
    }
    if(!zip_eofile) {
      n = zip_read_buff(zip_window, zip_strstart + zip_lookahead, more);
      if(n <= 0) {
        zip_eofile = true
      }else {
        zip_lookahead += n
      }
    }
  };
  var zip_lm_init = function() {
    var j;
    for(j = 0;j < zip_HASH_SIZE;j++) {
      zip_prev[zip_WSIZE + j] = 0
    }
    zip_max_lazy_match = zip_configuration_table[zip_compr_level].max_lazy;
    zip_good_match = zip_configuration_table[zip_compr_level].good_length;
    if(!zip_FULL_SEARCH) {
      zip_nice_match = zip_configuration_table[zip_compr_level].nice_length
    }
    zip_max_chain_length = zip_configuration_table[zip_compr_level].max_chain;
    zip_strstart = 0;
    zip_block_start = 0;
    zip_lookahead = zip_read_buff(zip_window, 0, 2 * zip_WSIZE);
    if(zip_lookahead <= 0) {
      zip_eofile = true;
      zip_lookahead = 0;
      return
    }
    zip_eofile = false;
    while(zip_lookahead < zip_MIN_LOOKAHEAD && !zip_eofile) {
      zip_fill_window()
    }
    zip_ins_h = 0;
    for(j = 0;j < zip_MIN_MATCH - 1;j++) {
      zip_ins_h = (zip_ins_h << zip_H_SHIFT ^ zip_window[j] & 255) & zip_HASH_MASK
    }
  };
  var zip_longest_match = function(cur_match) {
    var chain_length = zip_max_chain_length;
    var scanp = zip_strstart;
    var matchp;
    var len;
    var best_len = zip_prev_length;
    var limit = zip_strstart > zip_MAX_DIST ? zip_strstart - zip_MAX_DIST : zip_NIL;
    var strendp = zip_strstart + zip_MAX_MATCH;
    var scan_end1 = zip_window[scanp + best_len - 1];
    var scan_end = zip_window[scanp + best_len];
    if(zip_prev_length >= zip_good_match) {
      chain_length >>= 2
    }
    do {
      matchp = cur_match;
      if(zip_window[matchp + best_len] !== scan_end || zip_window[matchp + best_len - 1] !== scan_end1 || zip_window[matchp] !== zip_window[scanp] || zip_window[++matchp] !== zip_window[scanp + 1]) {
        continue
      }
      scanp += 2;
      matchp++;
      do {
        ++scanp
      }while(zip_window[scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && zip_window[++scanp] === zip_window[++matchp] && scanp < strendp);
      len = zip_MAX_MATCH - (strendp - scanp);
      scanp = strendp - zip_MAX_MATCH;
      if(len > best_len) {
        zip_match_start = cur_match;
        best_len = len;
        if(zip_FULL_SEARCH) {
          if(len >= zip_MAX_MATCH) {
            break
          }
        }else {
          if(len >= zip_nice_match) {
            break
          }
        }
        scan_end1 = zip_window[scanp + best_len - 1];
        scan_end = zip_window[scanp + best_len]
      }
    }while((cur_match = zip_prev[cur_match & zip_WMASK]) > limit && --chain_length !== 0);
    return best_len
  };
  var zip_ct_tally = function(dist, lc) {
    zip_l_buf[zip_last_lit++] = lc;
    if(dist === 0) {
      zip_dyn_ltree[lc].fc++
    }else {
      dist--;
      zip_dyn_ltree[zip_length_code[lc] + zip_LITERALS + 1].fc++;
      zip_dyn_dtree[zip_D_CODE(dist)].fc++;
      zip_d_buf[zip_last_dist++] = dist;
      zip_flags |= zip_flag_bit
    }
    zip_flag_bit <<= 1;
    if((zip_last_lit & 7) === 0) {
      zip_flag_buf[zip_last_flags++] = zip_flags;
      zip_flags = 0;
      zip_flag_bit = 1
    }
    if(zip_compr_level > 2 && (zip_last_lit & 4095) === 0) {
      var out_length = zip_last_lit * 8;
      var in_length = zip_strstart - zip_block_start;
      var dcode;
      for(dcode = 0;dcode < zip_D_CODES;dcode++) {
        out_length += zip_dyn_dtree[dcode].fc * (5 + zip_extra_dbits[dcode])
      }
      out_length >>= 3;
      if(zip_last_dist < parseInt(zip_last_lit / 2, 10) && out_length < parseInt(in_length / 2, 10)) {
        return true
      }
    }
    return zip_last_lit === zip_LIT_BUFSIZE - 1 || zip_last_dist === zip_DIST_BUFSIZE
  };
  var zip_pqdownheap = function(tree, k) {
    var v = zip_heap[k];
    var j = k << 1;
    while(j <= zip_heap_len) {
      if(j < zip_heap_len && zip_SMALLER(tree, zip_heap[j + 1], zip_heap[j])) {
        j++
      }
      if(zip_SMALLER(tree, v, zip_heap[j])) {
        break
      }
      zip_heap[k] = zip_heap[j];
      k = j;
      j <<= 1
    }
    zip_heap[k] = v
  };
  var zip_gen_bitlen = function(desc) {
    var tree = desc.dyn_tree;
    var extra = desc.extra_bits;
    var base = desc.extra_base;
    var max_code = desc.max_code;
    var max_length = desc.max_length;
    var stree = desc.static_tree;
    var h;
    var n, m;
    var bits;
    var xbits;
    var f;
    var overflow = 0;
    for(bits = 0;bits <= zip_MAX_BITS;bits++) {
      zip_bl_count[bits] = 0
    }
    tree[zip_heap[zip_heap_max]].dl = 0;
    for(h = zip_heap_max + 1;h < zip_HEAP_SIZE;h++) {
      n = zip_heap[h];
      bits = tree[tree[n].dl].dl + 1;
      if(bits > max_length) {
        bits = max_length;
        overflow++
      }
      tree[n].dl = bits;
      if(n > max_code) {
        continue
      }
      zip_bl_count[bits]++;
      xbits = 0;
      if(n >= base) {
        xbits = extra[n - base]
      }
      f = tree[n].fc;
      zip_opt_len += f * (bits + xbits);
      if(stree !== null) {
        zip_static_len += f * (stree[n].dl + xbits)
      }
    }
    if(overflow === 0) {
      return
    }
    do {
      bits = max_length - 1;
      while(zip_bl_count[bits] === 0) {
        bits--
      }
      zip_bl_count[bits]--;
      zip_bl_count[bits + 1] += 2;
      zip_bl_count[max_length]--;
      overflow -= 2
    }while(overflow > 0);
    for(bits = max_length;bits !== 0;bits--) {
      n = zip_bl_count[bits];
      while(n !== 0) {
        m = zip_heap[--h];
        if(m > max_code) {
          continue
        }
        if(tree[m].dl !== bits) {
          zip_opt_len += (bits - tree[m].dl) * tree[m].fc;
          tree[m].fc = bits
        }
        n--
      }
    }
  };
  var zip_bi_reverse = function(code, len) {
    var res = 0;
    do {
      res |= code & 1;
      code >>= 1;
      res <<= 1
    }while(--len > 0);
    return res >> 1
  };
  var zip_gen_codes = function(tree, max_code) {
    var next_code = [];
    next_code.length = zip_MAX_BITS + 1;
    var code = 0;
    var bits;
    var n;
    for(bits = 1;bits <= zip_MAX_BITS;bits++) {
      code = code + zip_bl_count[bits - 1] << 1;
      next_code[bits] = code
    }
    for(n = 0;n <= max_code;n++) {
      var len = tree[n].dl;
      if(len === 0) {
        continue
      }
      tree[n].fc = zip_bi_reverse(next_code[len]++, len)
    }
  };
  var zip_build_tree = function(desc) {
    var tree = desc.dyn_tree;
    var stree = desc.static_tree;
    var elems = desc.elems;
    var n, m;
    var max_code = -1;
    var node = elems;
    zip_heap_len = 0;
    zip_heap_max = zip_HEAP_SIZE;
    for(n = 0;n < elems;n++) {
      if(tree[n].fc !== 0) {
        zip_heap[++zip_heap_len] = max_code = n;
        zip_depth[n] = 0
      }else {
        tree[n].dl = 0
      }
    }
    while(zip_heap_len < 2) {
      var xnew = zip_heap[++zip_heap_len] = max_code < 2 ? ++max_code : 0;
      tree[xnew].fc = 1;
      zip_depth[xnew] = 0;
      zip_opt_len--;
      if(stree !== null) {
        zip_static_len -= stree[xnew].dl
      }
    }
    desc.max_code = max_code;
    for(n = zip_heap_len >> 1;n >= 1;n--) {
      zip_pqdownheap(tree, n)
    }
    do {
      n = zip_heap[zip_SMALLEST];
      zip_heap[zip_SMALLEST] = zip_heap[zip_heap_len--];
      zip_pqdownheap(tree, zip_SMALLEST);
      m = zip_heap[zip_SMALLEST];
      zip_heap[--zip_heap_max] = n;
      zip_heap[--zip_heap_max] = m;
      tree[node].fc = tree[n].fc + tree[m].fc;
      if(zip_depth[n] > zip_depth[m] + 1) {
        zip_depth[node] = zip_depth[n]
      }else {
        zip_depth[node] = zip_depth[m] + 1
      }
      tree[n].dl = tree[m].dl = node;
      zip_heap[zip_SMALLEST] = node++;
      zip_pqdownheap(tree, zip_SMALLEST)
    }while(zip_heap_len >= 2);
    zip_heap[--zip_heap_max] = zip_heap[zip_SMALLEST];
    zip_gen_bitlen(desc);
    zip_gen_codes(tree, max_code)
  };
  var zip_scan_tree = function(tree, max_code) {
    var n;
    var prevlen = -1;
    var curlen;
    var nextlen = tree[0].dl;
    var count = 0;
    var max_count = 7;
    var min_count = 4;
    if(nextlen === 0) {
      max_count = 138;
      min_count = 3
    }
    tree[max_code + 1].dl = 65535;
    for(n = 0;n <= max_code;n++) {
      curlen = nextlen;
      nextlen = tree[n + 1].dl;
      if(++count < max_count && curlen === nextlen) {
        continue
      }else {
        if(count < min_count) {
          zip_bl_tree[curlen].fc += count
        }else {
          if(curlen !== 0) {
            if(curlen !== prevlen) {
              zip_bl_tree[curlen].fc++
            }
            zip_bl_tree[zip_REP_3_6].fc++
          }else {
            if(count <= 10) {
              zip_bl_tree[zip_REPZ_3_10].fc++
            }else {
              zip_bl_tree[zip_REPZ_11_138].fc++
            }
          }
        }
      }
      count = 0;
      prevlen = curlen;
      if(nextlen === 0) {
        max_count = 138;
        min_count = 3
      }else {
        if(curlen === nextlen) {
          max_count = 6;
          min_count = 3
        }else {
          max_count = 7;
          min_count = 4
        }
      }
    }
  };
  var zip_build_bl_tree = function() {
    var max_blindex;
    zip_scan_tree(zip_dyn_ltree, zip_l_desc.max_code);
    zip_scan_tree(zip_dyn_dtree, zip_d_desc.max_code);
    zip_build_tree(zip_bl_desc);
    for(max_blindex = zip_BL_CODES - 1;max_blindex >= 3;max_blindex--) {
      if(zip_bl_tree[zip_bl_order[max_blindex]].dl !== 0) {
        break
      }
    }
    zip_opt_len += 3 * (max_blindex + 1) + 5 + 5 + 4;
    return max_blindex
  };
  var zip_bi_windup = function() {
    if(zip_bi_valid > 8) {
      zip_put_short(zip_bi_buf)
    }else {
      if(zip_bi_valid > 0) {
        zip_put_byte(zip_bi_buf)
      }
    }
    zip_bi_buf = 0;
    zip_bi_valid = 0
  };
  var zip_compress_block = function(ltree, dtree) {
    var dist;
    var lc;
    var lx = 0;
    var dx = 0;
    var fx = 0;
    var flag = 0;
    var code;
    var extra;
    if(zip_last_lit !== 0) {
      do {
        if((lx & 7) === 0) {
          flag = zip_flag_buf[fx++]
        }
        lc = zip_l_buf[lx++] & 255;
        if((flag & 1) === 0) {
          zip_SEND_CODE(lc, ltree)
        }else {
          code = zip_length_code[lc];
          zip_SEND_CODE(code + zip_LITERALS + 1, ltree);
          extra = zip_extra_lbits[code];
          if(extra !== 0) {
            lc -= zip_base_length[code];
            zip_send_bits(lc, extra)
          }
          dist = zip_d_buf[dx++];
          code = zip_D_CODE(dist);
          zip_SEND_CODE(code, dtree);
          extra = zip_extra_dbits[code];
          if(extra !== 0) {
            dist -= zip_base_dist[code];
            zip_send_bits(dist, extra)
          }
        }
        flag >>= 1
      }while(lx < zip_last_lit)
    }
    zip_SEND_CODE(zip_END_BLOCK, ltree)
  };
  var zip_send_tree = function(tree, max_code) {
    var n;
    var prevlen = -1;
    var curlen;
    var nextlen = tree[0].dl;
    var count = 0;
    var max_count = 7;
    var min_count = 4;
    if(nextlen === 0) {
      max_count = 138;
      min_count = 3
    }
    for(n = 0;n <= max_code;n++) {
      curlen = nextlen;
      nextlen = tree[n + 1].dl;
      if(++count < max_count && curlen === nextlen) {
        continue
      }else {
        if(count < min_count) {
          do {
            zip_SEND_CODE(curlen, zip_bl_tree)
          }while(--count !== 0)
        }else {
          if(curlen !== 0) {
            if(curlen !== prevlen) {
              zip_SEND_CODE(curlen, zip_bl_tree);
              count--
            }
            zip_SEND_CODE(zip_REP_3_6, zip_bl_tree);
            zip_send_bits(count - 3, 2)
          }else {
            if(count <= 10) {
              zip_SEND_CODE(zip_REPZ_3_10, zip_bl_tree);
              zip_send_bits(count - 3, 3)
            }else {
              zip_SEND_CODE(zip_REPZ_11_138, zip_bl_tree);
              zip_send_bits(count - 11, 7)
            }
          }
        }
      }
      count = 0;
      prevlen = curlen;
      if(nextlen === 0) {
        max_count = 138;
        min_count = 3
      }else {
        if(curlen === nextlen) {
          max_count = 6;
          min_count = 3
        }else {
          max_count = 7;
          min_count = 4
        }
      }
    }
  };
  var zip_send_all_trees = function(lcodes, dcodes, blcodes) {
    var rank;
    zip_send_bits(lcodes - 257, 5);
    zip_send_bits(dcodes - 1, 5);
    zip_send_bits(blcodes - 4, 4);
    for(rank = 0;rank < blcodes;rank++) {
      zip_send_bits(zip_bl_tree[zip_bl_order[rank]].dl, 3)
    }
    zip_send_tree(zip_dyn_ltree, lcodes - 1);
    zip_send_tree(zip_dyn_dtree, dcodes - 1)
  };
  var zip_init_block = function() {
    var n;
    for(n = 0;n < zip_L_CODES;n++) {
      zip_dyn_ltree[n].fc = 0
    }
    for(n = 0;n < zip_D_CODES;n++) {
      zip_dyn_dtree[n].fc = 0
    }
    for(n = 0;n < zip_BL_CODES;n++) {
      zip_bl_tree[n].fc = 0
    }
    zip_dyn_ltree[zip_END_BLOCK].fc = 1;
    zip_opt_len = zip_static_len = 0;
    zip_last_lit = zip_last_dist = zip_last_flags = 0;
    zip_flags = 0;
    zip_flag_bit = 1
  };
  var zip_flush_block = function(eof) {
    var opt_lenb, static_lenb;
    var max_blindex;
    var stored_len;
    stored_len = zip_strstart - zip_block_start;
    zip_flag_buf[zip_last_flags] = zip_flags;
    zip_build_tree(zip_l_desc);
    zip_build_tree(zip_d_desc);
    max_blindex = zip_build_bl_tree();
    opt_lenb = zip_opt_len + 3 + 7 >> 3;
    static_lenb = zip_static_len + 3 + 7 >> 3;
    if(static_lenb <= opt_lenb) {
      opt_lenb = static_lenb
    }
    if(stored_len + 4 <= opt_lenb && zip_block_start >= 0) {
      var i;
      zip_send_bits((zip_STORED_BLOCK << 1) + eof, 3);
      zip_bi_windup();
      zip_put_short(stored_len);
      zip_put_short(~stored_len);
      for(i = 0;i < stored_len;i++) {
        zip_put_byte(zip_window[zip_block_start + i])
      }
    }else {
      if(static_lenb === opt_lenb) {
        zip_send_bits((zip_STATIC_TREES << 1) + eof, 3);
        zip_compress_block(zip_static_ltree, zip_static_dtree)
      }else {
        zip_send_bits((zip_DYN_TREES << 1) + eof, 3);
        zip_send_all_trees(zip_l_desc.max_code + 1, zip_d_desc.max_code + 1, max_blindex + 1);
        zip_compress_block(zip_dyn_ltree, zip_dyn_dtree)
      }
    }
    zip_init_block();
    if(eof !== 0) {
      zip_bi_windup()
    }
  };
  var zip_deflate_fast = function() {
    while(zip_lookahead !== 0 && zip_qhead === null) {
      var flush;
      zip_INSERT_STRING();
      if(zip_hash_head !== zip_NIL && zip_strstart - zip_hash_head <= zip_MAX_DIST) {
        zip_match_length = zip_longest_match(zip_hash_head);
        if(zip_match_length > zip_lookahead) {
          zip_match_length = zip_lookahead
        }
      }
      if(zip_match_length >= zip_MIN_MATCH) {
        flush = zip_ct_tally(zip_strstart - zip_match_start, zip_match_length - zip_MIN_MATCH);
        zip_lookahead -= zip_match_length;
        if(zip_match_length <= zip_max_lazy_match) {
          zip_match_length--;
          do {
            zip_strstart++;
            zip_INSERT_STRING()
          }while(--zip_match_length !== 0);
          zip_strstart++
        }else {
          zip_strstart += zip_match_length;
          zip_match_length = 0;
          zip_ins_h = zip_window[zip_strstart] & 255;
          zip_ins_h = (zip_ins_h << zip_H_SHIFT ^ zip_window[zip_strstart + 1] & 255) & zip_HASH_MASK
        }
      }else {
        flush = zip_ct_tally(0, zip_window[zip_strstart] & 255);
        zip_lookahead--;
        zip_strstart++
      }
      if(flush) {
        zip_flush_block(0);
        zip_block_start = zip_strstart
      }
      while(zip_lookahead < zip_MIN_LOOKAHEAD && !zip_eofile) {
        zip_fill_window()
      }
    }
  };
  var zip_deflate_better = function() {
    while(zip_lookahead !== 0 && zip_qhead === null) {
      zip_INSERT_STRING();
      zip_prev_length = zip_match_length;
      zip_prev_match = zip_match_start;
      zip_match_length = zip_MIN_MATCH - 1;
      if(zip_hash_head !== zip_NIL && zip_prev_length < zip_max_lazy_match && zip_strstart - zip_hash_head <= zip_MAX_DIST) {
        zip_match_length = zip_longest_match(zip_hash_head);
        if(zip_match_length > zip_lookahead) {
          zip_match_length = zip_lookahead
        }
        if(zip_match_length === zip_MIN_MATCH && zip_strstart - zip_match_start > zip_TOO_FAR) {
          zip_match_length--
        }
      }
      if(zip_prev_length >= zip_MIN_MATCH && zip_match_length <= zip_prev_length) {
        var flush;
        flush = zip_ct_tally(zip_strstart - 1 - zip_prev_match, zip_prev_length - zip_MIN_MATCH);
        zip_lookahead -= zip_prev_length - 1;
        zip_prev_length -= 2;
        do {
          zip_strstart++;
          zip_INSERT_STRING()
        }while(--zip_prev_length !== 0);
        zip_match_available = 0;
        zip_match_length = zip_MIN_MATCH - 1;
        zip_strstart++;
        if(flush) {
          zip_flush_block(0);
          zip_block_start = zip_strstart
        }
      }else {
        if(zip_match_available !== 0) {
          if(zip_ct_tally(0, zip_window[zip_strstart - 1] & 255)) {
            zip_flush_block(0);
            zip_block_start = zip_strstart
          }
          zip_strstart++;
          zip_lookahead--
        }else {
          zip_match_available = 1;
          zip_strstart++;
          zip_lookahead--
        }
      }
      while(zip_lookahead < zip_MIN_LOOKAHEAD && !zip_eofile) {
        zip_fill_window()
      }
    }
  };
  var zip_ct_init = function() {
    var n;
    var bits;
    var length;
    var code;
    var dist;
    if(zip_static_dtree[0].dl !== 0) {
      return
    }
    zip_l_desc.dyn_tree = zip_dyn_ltree;
    zip_l_desc.static_tree = zip_static_ltree;
    zip_l_desc.extra_bits = zip_extra_lbits;
    zip_l_desc.extra_base = zip_LITERALS + 1;
    zip_l_desc.elems = zip_L_CODES;
    zip_l_desc.max_length = zip_MAX_BITS;
    zip_l_desc.max_code = 0;
    zip_d_desc.dyn_tree = zip_dyn_dtree;
    zip_d_desc.static_tree = zip_static_dtree;
    zip_d_desc.extra_bits = zip_extra_dbits;
    zip_d_desc.extra_base = 0;
    zip_d_desc.elems = zip_D_CODES;
    zip_d_desc.max_length = zip_MAX_BITS;
    zip_d_desc.max_code = 0;
    zip_bl_desc.dyn_tree = zip_bl_tree;
    zip_bl_desc.static_tree = null;
    zip_bl_desc.extra_bits = zip_extra_blbits;
    zip_bl_desc.extra_base = 0;
    zip_bl_desc.elems = zip_BL_CODES;
    zip_bl_desc.max_length = zip_MAX_BL_BITS;
    zip_bl_desc.max_code = 0;
    length = 0;
    for(code = 0;code < zip_LENGTH_CODES - 1;code++) {
      zip_base_length[code] = length;
      for(n = 0;n < 1 << zip_extra_lbits[code];n++) {
        zip_length_code[length++] = code
      }
    }
    zip_length_code[length - 1] = code;
    dist = 0;
    for(code = 0;code < 16;code++) {
      zip_base_dist[code] = dist;
      for(n = 0;n < 1 << zip_extra_dbits[code];n++) {
        zip_dist_code[dist++] = code
      }
    }
    dist >>= 7;
    n = code;
    for(code = n;code < zip_D_CODES;code++) {
      zip_base_dist[code] = dist << 7;
      for(n = 0;n < 1 << zip_extra_dbits[code] - 7;n++) {
        zip_dist_code[256 + dist++] = code
      }
    }
    for(bits = 0;bits <= zip_MAX_BITS;bits++) {
      zip_bl_count[bits] = 0
    }
    n = 0;
    while(n <= 143) {
      zip_static_ltree[n++].dl = 8;
      zip_bl_count[8]++
    }
    while(n <= 255) {
      zip_static_ltree[n++].dl = 9;
      zip_bl_count[9]++
    }
    while(n <= 279) {
      zip_static_ltree[n++].dl = 7;
      zip_bl_count[7]++
    }
    while(n <= 287) {
      zip_static_ltree[n++].dl = 8;
      zip_bl_count[8]++
    }
    zip_gen_codes(zip_static_ltree, zip_L_CODES + 1);
    for(n = 0;n < zip_D_CODES;n++) {
      zip_static_dtree[n].dl = 5;
      zip_static_dtree[n].fc = zip_bi_reverse(n, 5)
    }
    zip_init_block()
  };
  var zip_init_deflate = function() {
    if(zip_eofile) {
      return
    }
    zip_bi_buf = 0;
    zip_bi_valid = 0;
    zip_ct_init();
    zip_lm_init();
    zip_qhead = null;
    zip_outcnt = 0;
    zip_outoff = 0;
    if(zip_compr_level <= 3) {
      zip_prev_length = zip_MIN_MATCH - 1;
      zip_match_length = 0
    }else {
      zip_match_length = zip_MIN_MATCH - 1;
      zip_match_available = 0
    }
    zip_complete = false
  };
  var zip_qcopy = function(buff, off, buff_size) {
    var n, i, j;
    n = 0;
    while(zip_qhead !== null && n < buff_size) {
      i = buff_size - n;
      if(i > zip_qhead.len) {
        i = zip_qhead.len
      }
      for(j = 0;j < i;j++) {
        buff[off + n + j] = zip_qhead.ptr[zip_qhead.off + j]
      }
      zip_qhead.off += i;
      zip_qhead.len -= i;
      n += i;
      if(zip_qhead.len === 0) {
        var p;
        p = zip_qhead;
        zip_qhead = zip_qhead.next;
        zip_reuse_queue(p)
      }
    }
    if(n === buff_size) {
      return n
    }
    if(zip_outoff < zip_outcnt) {
      i = buff_size - n;
      if(i > zip_outcnt - zip_outoff) {
        i = zip_outcnt - zip_outoff
      }
      for(j = 0;j < i;j++) {
        buff[off + n + j] = zip_outbuf[zip_outoff + j]
      }
      zip_outoff += i;
      n += i;
      if(zip_outcnt === zip_outoff) {
        zip_outcnt = zip_outoff = 0
      }
    }
    return n
  };
  var zip_deflate_internal = function(buff, off, buff_size) {
    var n;
    if(!zip_initflag) {
      zip_init_deflate();
      zip_initflag = true;
      if(zip_lookahead === 0) {
        zip_complete = true;
        return 0
      }
    }
    if((n = zip_qcopy(buff, off, buff_size)) === buff_size) {
      return buff_size
    }
    if(zip_complete) {
      return n
    }
    if(zip_compr_level <= 3) {
      zip_deflate_fast()
    }else {
      zip_deflate_better()
    }
    if(zip_lookahead === 0) {
      if(zip_match_available !== 0) {
        zip_ct_tally(0, zip_window[zip_strstart - 1] & 255)
      }
      zip_flush_block(1);
      zip_complete = true
    }
    return n + zip_qcopy(buff, n + off, buff_size - n)
  };
  var zip_deflate = function(str, level) {
    var i, j;
    zip_deflate_data = str;
    zip_deflate_pos = 0;
    if(typeof level === "undefined") {
      level = zip_DEFAULT_LEVEL
    }
    zip_deflate_start(level);
    var buff = new Array(1024);
    var aout = [];
    while((i = zip_deflate_internal(buff, 0, buff.length)) > 0) {
      var cbuf = [];
      cbuf.length = i;
      for(j = 0;j < i;j++) {
        cbuf[j] = String.fromCharCode(buff[j])
      }
      aout[aout.length] = cbuf.join("")
    }
    zip_deflate_data = null;
    return aout.join("")
  };
  this.deflate = zip_deflate
};
core.ByteArray = function ByteArray(data) {
  this.pos = 0;
  this.data = data;
  this.readUInt32LE = function() {
    var data = this.data, pos = this.pos += 4;
    return data[--pos] << 24 | data[--pos] << 16 | data[--pos] << 8 | data[--pos]
  };
  this.readUInt16LE = function() {
    var data = this.data, pos = this.pos += 2;
    return data[--pos] << 8 | data[--pos]
  }
};
core.ByteArrayWriter = function ByteArrayWriter(encoding) {
  var self = this, data = new runtime.ByteArray(0);
  this.appendByteArrayWriter = function(writer) {
    data = runtime.concatByteArrays(data, writer.getByteArray())
  };
  this.appendByteArray = function(array) {
    data = runtime.concatByteArrays(data, array)
  };
  this.appendArray = function(array) {
    data = runtime.concatByteArrays(data, runtime.byteArrayFromArray(array))
  };
  this.appendUInt16LE = function(value) {
    self.appendArray([value & 255, value >> 8 & 255])
  };
  this.appendUInt32LE = function(value) {
    self.appendArray([value & 255, value >> 8 & 255, value >> 16 & 255, value >> 24 & 255])
  };
  this.appendString = function(string) {
    data = runtime.concatByteArrays(data, runtime.byteArrayFromString(string, encoding))
  };
  this.getLength = function() {
    return data.length
  };
  this.getByteArray = function() {
    return data
  }
};
core.RawInflate = function RawInflate() {
  var zip_WSIZE = 32768;
  var zip_STORED_BLOCK = 0;
  var zip_STATIC_TREES = 1;
  var zip_DYN_TREES = 2;
  var zip_lbits = 9;
  var zip_dbits = 6;
  var zip_INBUFSIZ = 32768;
  var zip_INBUF_EXTRA = 64;
  var zip_slide;
  var zip_wp;
  var zip_fixed_tl = null;
  var zip_fixed_td;
  var zip_fixed_bl, fixed_bd;
  var zip_bit_buf;
  var zip_bit_len;
  var zip_method;
  var zip_eof;
  var zip_copy_leng;
  var zip_copy_dist;
  var zip_tl, zip_td;
  var zip_bl, zip_bd;
  var zip_inflate_data;
  var zip_inflate_pos;
  var zip_MASK_BITS = new Array(0, 1, 3, 7, 15, 31, 63, 127, 255, 511, 1023, 2047, 4095, 8191, 16383, 32767, 65535);
  var zip_cplens = new Array(3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 15, 17, 19, 23, 27, 31, 35, 43, 51, 59, 67, 83, 99, 115, 131, 163, 195, 227, 258, 0, 0);
  var zip_cplext = new Array(0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 0, 99, 99);
  var zip_cpdist = new Array(1, 2, 3, 4, 5, 7, 9, 13, 17, 25, 33, 49, 65, 97, 129, 193, 257, 385, 513, 769, 1025, 1537, 2049, 3073, 4097, 6145, 8193, 12289, 16385, 24577);
  var zip_cpdext = new Array(0, 0, 0, 0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10, 11, 11, 12, 12, 13, 13);
  var zip_border = new Array(16, 17, 18, 0, 8, 7, 9, 6, 10, 5, 11, 4, 12, 3, 13, 2, 14, 1, 15);
  var zip_HuftList = function() {
    this.next = null;
    this.list = null
  };
  var zip_HuftNode = function() {
    this.e = 0;
    this.b = 0;
    this.n = 0;
    this.t = null
  };
  var zip_HuftBuild = function(b, n, s, d, e, mm) {
    this.BMAX = 16;
    this.N_MAX = 288;
    this.status = 0;
    this.root = null;
    this.m = 0;
    var a;
    var c = new Array(this.BMAX + 1);
    var el;
    var f;
    var g;
    var h;
    var i;
    var j;
    var k;
    var lx = new Array(this.BMAX + 1);
    var p;
    var pidx;
    var q;
    var r = new zip_HuftNode;
    var u = new Array(this.BMAX);
    var v = new Array(this.N_MAX);
    var w;
    var x = new Array(this.BMAX + 1);
    var xp;
    var y;
    var z;
    var o;
    var tail;
    tail = this.root = null;
    for(i = 0;i < c.length;i++) {
      c[i] = 0
    }
    for(i = 0;i < lx.length;i++) {
      lx[i] = 0
    }
    for(i = 0;i < u.length;i++) {
      u[i] = null
    }
    for(i = 0;i < v.length;i++) {
      v[i] = 0
    }
    for(i = 0;i < x.length;i++) {
      x[i] = 0
    }
    el = n > 256 ? b[256] : this.BMAX;
    p = b;
    pidx = 0;
    i = n;
    do {
      c[p[pidx]]++;
      pidx++
    }while(--i > 0);
    if(c[0] == n) {
      this.root = null;
      this.m = 0;
      this.status = 0;
      return
    }
    for(j = 1;j <= this.BMAX;j++) {
      if(c[j] != 0) {
        break
      }
    }
    k = j;
    if(mm < j) {
      mm = j
    }
    for(i = this.BMAX;i != 0;i--) {
      if(c[i] != 0) {
        break
      }
    }
    g = i;
    if(mm > i) {
      mm = i
    }
    for(y = 1 << j;j < i;j++, y <<= 1) {
      if((y -= c[j]) < 0) {
        this.status = 2;
        this.m = mm;
        return
      }
    }
    if((y -= c[i]) < 0) {
      this.status = 2;
      this.m = mm;
      return
    }
    c[i] += y;
    x[1] = j = 0;
    p = c;
    pidx = 1;
    xp = 2;
    while(--i > 0) {
      x[xp++] = j += p[pidx++]
    }
    p = b;
    pidx = 0;
    i = 0;
    do {
      if((j = p[pidx++]) != 0) {
        v[x[j]++] = i
      }
    }while(++i < n);
    n = x[g];
    x[0] = i = 0;
    p = v;
    pidx = 0;
    h = -1;
    w = lx[0] = 0;
    q = null;
    z = 0;
    for(;k <= g;k++) {
      a = c[k];
      while(a-- > 0) {
        while(k > w + lx[1 + h]) {
          w += lx[1 + h];
          h++;
          z = (z = g - w) > mm ? mm : z;
          if((f = 1 << (j = k - w)) > a + 1) {
            f -= a + 1;
            xp = k;
            while(++j < z) {
              if((f <<= 1) <= c[++xp]) {
                break
              }
              f -= c[xp]
            }
          }
          if(w + j > el && w < el) {
            j = el - w
          }
          z = 1 << j;
          lx[1 + h] = j;
          q = new Array(z);
          for(o = 0;o < z;o++) {
            q[o] = new zip_HuftNode
          }
          if(tail == null) {
            tail = this.root = new zip_HuftList
          }else {
            tail = tail.next = new zip_HuftList
          }
          tail.next = null;
          tail.list = q;
          u[h] = q;
          if(h > 0) {
            x[h] = i;
            r.b = lx[h];
            r.e = 16 + j;
            r.t = q;
            j = (i & (1 << w) - 1) >> w - lx[h];
            u[h - 1][j].e = r.e;
            u[h - 1][j].b = r.b;
            u[h - 1][j].n = r.n;
            u[h - 1][j].t = r.t
          }
        }
        r.b = k - w;
        if(pidx >= n) {
          r.e = 99
        }else {
          if(p[pidx] < s) {
            r.e = p[pidx] < 256 ? 16 : 15;
            r.n = p[pidx++]
          }else {
            r.e = e[p[pidx] - s];
            r.n = d[p[pidx++] - s]
          }
        }
        f = 1 << k - w;
        for(j = i >> w;j < z;j += f) {
          q[j].e = r.e;
          q[j].b = r.b;
          q[j].n = r.n;
          q[j].t = r.t
        }
        for(j = 1 << k - 1;(i & j) != 0;j >>= 1) {
          i ^= j
        }
        i ^= j;
        while((i & (1 << w) - 1) != x[h]) {
          w -= lx[h];
          h--
        }
      }
    }
    this.m = lx[1];
    this.status = y != 0 && g != 1 ? 1 : 0
  };
  var zip_GET_BYTE = function() {
    if(zip_inflate_data.length == zip_inflate_pos) {
      return-1
    }
    return zip_inflate_data[zip_inflate_pos++]
  };
  var zip_NEEDBITS = function(n) {
    while(zip_bit_len < n) {
      zip_bit_buf |= zip_GET_BYTE() << zip_bit_len;
      zip_bit_len += 8
    }
  };
  var zip_GETBITS = function(n) {
    return zip_bit_buf & zip_MASK_BITS[n]
  };
  var zip_DUMPBITS = function(n) {
    zip_bit_buf >>= n;
    zip_bit_len -= n
  };
  var zip_inflate_codes = function(buff, off, size) {
    var e;
    var t;
    var n;
    if(size == 0) {
      return 0
    }
    n = 0;
    for(;;) {
      zip_NEEDBITS(zip_bl);
      t = zip_tl.list[zip_GETBITS(zip_bl)];
      e = t.e;
      while(e > 16) {
        if(e == 99) {
          return-1
        }
        zip_DUMPBITS(t.b);
        e -= 16;
        zip_NEEDBITS(e);
        t = t.t[zip_GETBITS(e)];
        e = t.e
      }
      zip_DUMPBITS(t.b);
      if(e == 16) {
        zip_wp &= zip_WSIZE - 1;
        buff[off + n++] = zip_slide[zip_wp++] = t.n;
        if(n == size) {
          return size
        }
        continue
      }
      if(e == 15) {
        break
      }
      zip_NEEDBITS(e);
      zip_copy_leng = t.n + zip_GETBITS(e);
      zip_DUMPBITS(e);
      zip_NEEDBITS(zip_bd);
      t = zip_td.list[zip_GETBITS(zip_bd)];
      e = t.e;
      while(e > 16) {
        if(e == 99) {
          return-1
        }
        zip_DUMPBITS(t.b);
        e -= 16;
        zip_NEEDBITS(e);
        t = t.t[zip_GETBITS(e)];
        e = t.e
      }
      zip_DUMPBITS(t.b);
      zip_NEEDBITS(e);
      zip_copy_dist = zip_wp - t.n - zip_GETBITS(e);
      zip_DUMPBITS(e);
      while(zip_copy_leng > 0 && n < size) {
        zip_copy_leng--;
        zip_copy_dist &= zip_WSIZE - 1;
        zip_wp &= zip_WSIZE - 1;
        buff[off + n++] = zip_slide[zip_wp++] = zip_slide[zip_copy_dist++]
      }
      if(n == size) {
        return size
      }
    }
    zip_method = -1;
    return n
  };
  var zip_inflate_stored = function(buff, off, size) {
    var n;
    n = zip_bit_len & 7;
    zip_DUMPBITS(n);
    zip_NEEDBITS(16);
    n = zip_GETBITS(16);
    zip_DUMPBITS(16);
    zip_NEEDBITS(16);
    if(n != (~zip_bit_buf & 65535)) {
      return-1
    }
    zip_DUMPBITS(16);
    zip_copy_leng = n;
    n = 0;
    while(zip_copy_leng > 0 && n < size) {
      zip_copy_leng--;
      zip_wp &= zip_WSIZE - 1;
      zip_NEEDBITS(8);
      buff[off + n++] = zip_slide[zip_wp++] = zip_GETBITS(8);
      zip_DUMPBITS(8)
    }
    if(zip_copy_leng == 0) {
      zip_method = -1
    }
    return n
  };
  var zip_fixed_bd;
  var zip_inflate_fixed = function(buff, off, size) {
    if(zip_fixed_tl == null) {
      var i;
      var l = new Array(288);
      var h;
      for(i = 0;i < 144;i++) {
        l[i] = 8
      }
      for(;i < 256;i++) {
        l[i] = 9
      }
      for(;i < 280;i++) {
        l[i] = 7
      }
      for(;i < 288;i++) {
        l[i] = 8
      }
      zip_fixed_bl = 7;
      h = new zip_HuftBuild(l, 288, 257, zip_cplens, zip_cplext, zip_fixed_bl);
      if(h.status != 0) {
        alert("HufBuild error: " + h.status);
        return-1
      }
      zip_fixed_tl = h.root;
      zip_fixed_bl = h.m;
      for(i = 0;i < 30;i++) {
        l[i] = 5
      }
      zip_fixed_bd = 5;
      h = new zip_HuftBuild(l, 30, 0, zip_cpdist, zip_cpdext, zip_fixed_bd);
      if(h.status > 1) {
        zip_fixed_tl = null;
        alert("HufBuild error: " + h.status);
        return-1
      }
      zip_fixed_td = h.root;
      zip_fixed_bd = h.m
    }
    zip_tl = zip_fixed_tl;
    zip_td = zip_fixed_td;
    zip_bl = zip_fixed_bl;
    zip_bd = zip_fixed_bd;
    return zip_inflate_codes(buff, off, size)
  };
  var zip_inflate_dynamic = function(buff, off, size) {
    var i;
    var j;
    var l;
    var n;
    var t;
    var nb;
    var nl;
    var nd;
    var ll = new Array(286 + 30);
    var h;
    for(i = 0;i < ll.length;i++) {
      ll[i] = 0
    }
    zip_NEEDBITS(5);
    nl = 257 + zip_GETBITS(5);
    zip_DUMPBITS(5);
    zip_NEEDBITS(5);
    nd = 1 + zip_GETBITS(5);
    zip_DUMPBITS(5);
    zip_NEEDBITS(4);
    nb = 4 + zip_GETBITS(4);
    zip_DUMPBITS(4);
    if(nl > 286 || nd > 30) {
      return-1
    }
    for(j = 0;j < nb;j++) {
      zip_NEEDBITS(3);
      ll[zip_border[j]] = zip_GETBITS(3);
      zip_DUMPBITS(3)
    }
    for(;j < 19;j++) {
      ll[zip_border[j]] = 0
    }
    zip_bl = 7;
    h = new zip_HuftBuild(ll, 19, 19, null, null, zip_bl);
    if(h.status != 0) {
      return-1
    }
    zip_tl = h.root;
    zip_bl = h.m;
    n = nl + nd;
    i = l = 0;
    while(i < n) {
      zip_NEEDBITS(zip_bl);
      t = zip_tl.list[zip_GETBITS(zip_bl)];
      j = t.b;
      zip_DUMPBITS(j);
      j = t.n;
      if(j < 16) {
        ll[i++] = l = j
      }else {
        if(j == 16) {
          zip_NEEDBITS(2);
          j = 3 + zip_GETBITS(2);
          zip_DUMPBITS(2);
          if(i + j > n) {
            return-1
          }
          while(j-- > 0) {
            ll[i++] = l
          }
        }else {
          if(j == 17) {
            zip_NEEDBITS(3);
            j = 3 + zip_GETBITS(3);
            zip_DUMPBITS(3);
            if(i + j > n) {
              return-1
            }
            while(j-- > 0) {
              ll[i++] = 0
            }
            l = 0
          }else {
            zip_NEEDBITS(7);
            j = 11 + zip_GETBITS(7);
            zip_DUMPBITS(7);
            if(i + j > n) {
              return-1
            }
            while(j-- > 0) {
              ll[i++] = 0
            }
            l = 0
          }
        }
      }
    }
    zip_bl = zip_lbits;
    h = new zip_HuftBuild(ll, nl, 257, zip_cplens, zip_cplext, zip_bl);
    if(zip_bl == 0) {
      h.status = 1
    }
    if(h.status != 0) {
      return-1
    }
    zip_tl = h.root;
    zip_bl = h.m;
    for(i = 0;i < nd;i++) {
      ll[i] = ll[i + nl]
    }
    zip_bd = zip_dbits;
    h = new zip_HuftBuild(ll, nd, 0, zip_cpdist, zip_cpdext, zip_bd);
    zip_td = h.root;
    zip_bd = h.m;
    if(zip_bd == 0 && nl > 257) {
      return-1
    }
    if(h.status != 0) {
      return-1
    }
    return zip_inflate_codes(buff, off, size)
  };
  var zip_inflate_start = function() {
    var i;
    if(zip_slide == null) {
      zip_slide = new Array(2 * zip_WSIZE)
    }
    zip_wp = 0;
    zip_bit_buf = 0;
    zip_bit_len = 0;
    zip_method = -1;
    zip_eof = false;
    zip_copy_leng = zip_copy_dist = 0;
    zip_tl = null
  };
  var zip_inflate_internal = function(buff, off, size) {
    var n, i;
    n = 0;
    while(n < size) {
      if(zip_eof && zip_method == -1) {
        return n
      }
      if(zip_copy_leng > 0) {
        if(zip_method != zip_STORED_BLOCK) {
          while(zip_copy_leng > 0 && n < size) {
            zip_copy_leng--;
            zip_copy_dist &= zip_WSIZE - 1;
            zip_wp &= zip_WSIZE - 1;
            buff[off + n++] = zip_slide[zip_wp++] = zip_slide[zip_copy_dist++]
          }
        }else {
          while(zip_copy_leng > 0 && n < size) {
            zip_copy_leng--;
            zip_wp &= zip_WSIZE - 1;
            zip_NEEDBITS(8);
            buff[off + n++] = zip_slide[zip_wp++] = zip_GETBITS(8);
            zip_DUMPBITS(8)
          }
          if(zip_copy_leng == 0) {
            zip_method = -1
          }
        }
        if(n == size) {
          return n
        }
      }
      if(zip_method == -1) {
        if(zip_eof) {
          break
        }
        zip_NEEDBITS(1);
        if(zip_GETBITS(1) != 0) {
          zip_eof = true
        }
        zip_DUMPBITS(1);
        zip_NEEDBITS(2);
        zip_method = zip_GETBITS(2);
        zip_DUMPBITS(2);
        zip_tl = null;
        zip_copy_leng = 0
      }
      switch(zip_method) {
        case 0:
          i = zip_inflate_stored(buff, off + n, size - n);
          break;
        case 1:
          if(zip_tl != null) {
            i = zip_inflate_codes(buff, off + n, size - n)
          }else {
            i = zip_inflate_fixed(buff, off + n, size - n)
          }
          break;
        case 2:
          if(zip_tl != null) {
            i = zip_inflate_codes(buff, off + n, size - n)
          }else {
            i = zip_inflate_dynamic(buff, off + n, size - n)
          }
          break;
        default:
          i = -1;
          break
      }
      if(i == -1) {
        if(zip_eof) {
          return 0
        }
        return-1
      }
      n += i
    }
    return n
  };
  var zip_inflate = function(data, size) {
    var i, j;
    zip_inflate_start();
    zip_inflate_data = data;
    zip_inflate_pos = 0;
    var buff = new runtime.ByteArray(size);
    zip_inflate_internal(buff, 0, size);
    zip_inflate_data = null;
    return buff
  };
  this.inflate = zip_inflate
};
core.Cursor = function Cursor(selection, document) {
  var cursorns, cursorNode;
  cursorns = "urn:webodf:names:cursor";
  cursorNode = document.createElementNS(cursorns, "cursor");
  function putCursorIntoTextNode(container, offset) {
    var len, ref, textnode, parent;
    parent = container.parentNode;
    if(offset === 0) {
      parent.insertBefore(cursorNode, container)
    }else {
      if(offset === container.length) {
        parent.appendChild(cursorNode)
      }else {
        len = container.length;
        ref = container.nextSibling;
        textnode = document.createTextNode(container.substringData(offset, len));
        container.deleteData(offset, len);
        if(ref) {
          parent.insertBefore(textnode, ref)
        }else {
          parent.appendChild(textnode)
        }
        parent.insertBefore(cursorNode, textnode)
      }
    }
  }
  function putCursorIntoContainer(container, offset) {
    var node;
    node = container.firstChild;
    while(node && offset) {
      node = node.nextSibling;
      offset -= 1
    }
    container.insertBefore(cursorNode, node)
  }
  function getPotentialParentOrNode(parent, node) {
    var n = node;
    while(n && n !== parent) {
      n = n.parentNode
    }
    return n || node
  }
  function removeCursorFromSelectionRange(range, cursorpos) {
    var cursorParent, start, end;
    cursorParent = cursorNode.parentNode;
    start = getPotentialParentOrNode(cursorNode, range.startContainer);
    end = getPotentialParentOrNode(cursorNode, range.endContainer);
    if(start === cursorNode) {
      range.setStart(cursorParent, cursorpos)
    }else {
      if(start === cursorParent && range.startOffset > cursorpos) {
        range.setStart(cursorParent, range.startOffset - 1)
      }
    }
    if(range.endContainer === cursorNode) {
      range.setEnd(cursorParent, cursorpos)
    }else {
      if(range.endContainer === cursorParent && range.endOffset > cursorpos) {
        range.setEnd(cursorParent, range.endOffset - 1)
      }
    }
  }
  function adaptRangeToMergedText(range, prev, textnodetomerge, cursorpos) {
    var diff = prev.length - textnodetomerge.length;
    if(range.startContainer === textnodetomerge) {
      range.setStart(prev, diff + range.startOffset)
    }else {
      if(range.startContainer === prev.parentNode && range.startOffset === cursorpos) {
        range.setStart(prev, diff)
      }
    }
    if(range.endContainer === textnodetomerge) {
      range.setEnd(prev, diff + range.endOffset)
    }else {
      if(range.endContainer === prev.parentNode && range.endOffset === cursorpos) {
        range.setEnd(prev, diff)
      }
    }
  }
  function removeCursor() {
    var i, cursorpos, node, textnodetoremove, range;
    if(!cursorNode.parentNode) {
      return
    }
    cursorpos = 0;
    node = cursorNode.parentNode.firstChild;
    while(node && node !== cursorNode) {
      cursorpos += 1;
      node = node.nextSibling
    }
    if(cursorNode.previousSibling && cursorNode.previousSibling.nodeType === 3 && cursorNode.nextSibling && cursorNode.nextSibling.nodeType === 3) {
      textnodetoremove = cursorNode.nextSibling;
      cursorNode.previousSibling.appendData(textnodetoremove.nodeValue)
    }
    for(i = 0;i < selection.rangeCount;i += 1) {
      removeCursorFromSelectionRange(selection.getRangeAt(i), cursorpos)
    }
    if(textnodetoremove) {
      for(i = 0;i < selection.rangeCount;i += 1) {
        adaptRangeToMergedText(selection.getRangeAt(i), cursorNode.previousSibling, textnodetoremove, cursorpos)
      }
      textnodetoremove.parentNode.removeChild(textnodetoremove)
    }
    cursorNode.parentNode.removeChild(cursorNode)
  }
  function putCursor(container, offset) {
    if(container.nodeType === 3) {
      putCursorIntoTextNode(container, offset)
    }else {
      if(container.nodeType !== 9) {
        putCursorIntoContainer(container, offset)
      }
    }
  }
  this.getNode = function() {
    return cursorNode
  };
  this.updateToSelection = function() {
    var range;
    removeCursor();
    if(selection.focusNode) {
      putCursor(selection.focusNode, selection.focusOffset)
    }
  };
  this.remove = function() {
    removeCursor()
  }
};
core.UnitTest = function UnitTest() {
};
core.UnitTest.prototype.setUp = function() {
};
core.UnitTest.prototype.tearDown = function() {
};
core.UnitTest.prototype.description = function() {
};
core.UnitTest.prototype.tests = function() {
};
core.UnitTest.prototype.asyncTests = function() {
};
core.UnitTestRunner = function UnitTestRunner() {
  var failedTests = 0;
  function debug(msg) {
    runtime.log(msg)
  }
  function testFailed(msg) {
    failedTests += 1;
    runtime.log("fail", msg)
  }
  function testPassed(msg) {
    runtime.log("pass", msg)
  }
  function areArraysEqual(a, b) {
    var i;
    try {
      if(a.length !== b.length) {
        return false
      }
      for(i = 0;i < a.length;i += 1) {
        if(a[i] !== b[i]) {
          return false
        }
      }
    }catch(ex) {
      return false
    }
    return true
  }
  function isResultCorrect(actual, expected) {
    if(expected === 0) {
      return actual === expected && 1 / actual === 1 / expected
    }
    if(actual === expected) {
      return true
    }
    if(typeof expected === "number" && isNaN(expected)) {
      return typeof actual === "number" && isNaN(actual)
    }
    if(Object.prototype.toString.call(expected) === Object.prototype.toString.call([])) {
      return areArraysEqual(actual, expected)
    }
    return false
  }
  function stringify(v) {
    if(v === 0 && 1 / v < 0) {
      return"-0"
    }
    return String(v)
  }
  function shouldBe(t, a, b) {
    if(typeof a !== "string" || typeof b !== "string") {
      debug("WARN: shouldBe() expects string arguments")
    }
    var exception, av, bv;
    try {
      av = eval(a)
    }catch(e) {
      exception = e
    }
    bv = eval(b);
    if(exception) {
      testFailed(a + " should be " + bv + ". Threw exception " + exception)
    }else {
      if(isResultCorrect(av, bv)) {
        testPassed(a + " is " + b)
      }else {
        if(typeof av === typeof bv) {
          testFailed(a + " should be " + bv + ". Was " + stringify(av) + ".")
        }else {
          testFailed(a + " should be " + bv + " (of type " + typeof bv + "). Was " + av + " (of type " + typeof av + ").")
        }
      }
    }
  }
  function shouldBeNonNull(t, a) {
    var exception, av;
    try {
      av = eval(a)
    }catch(e) {
      exception = e
    }
    if(exception) {
      testFailed(a + " should be non-null. Threw exception " + exception)
    }else {
      if(av !== null) {
        testPassed(a + " is non-null.")
      }else {
        testFailed(a + " should be non-null. Was " + av)
      }
    }
  }
  function shouldBeNull(t, a) {
    shouldBe(t, a, "null")
  }
  this.shouldBeNull = shouldBeNull;
  this.shouldBeNonNull = shouldBeNonNull;
  this.shouldBe = shouldBe;
  this.countFailedTests = function() {
    return failedTests
  }
};
core.UnitTester = function UnitTester() {
  var failedTests = 0, results = {};
  this.runTests = function(TestClass, callback) {
    var testName = Runtime.getFunctionName(TestClass), tname, runner = new core.UnitTestRunner, test = new TestClass(runner), testResults = {}, i, t, tests, lastFailCount;
    if(testName.hasOwnProperty(results)) {
      runtime.log("Test " + testName + " has already run.");
      return
    }
    runtime.log("Running " + testName + ": " + test.description());
    tests = test.tests();
    for(i = 0;i < tests.length;i += 1) {
      t = tests[i];
      tname = Runtime.getFunctionName(t);
      runtime.log("Running " + tname);
      lastFailCount = runner.countFailedTests();
      test.setUp();
      t();
      test.tearDown();
      testResults[tname] = lastFailCount === runner.countFailedTests()
    }
    function runAsyncTests(todo) {
      if(todo.length === 0) {
        results[testName] = testResults;
        failedTests += runner.countFailedTests();
        callback();
        return
      }
      t = todo[0];
      var tname = Runtime.getFunctionName(t);
      runtime.log("Running " + tname);
      lastFailCount = runner.countFailedTests();
      test.setUp();
      t(function() {
        test.tearDown();
        testResults[tname] = lastFailCount === runner.countFailedTests();
        runAsyncTests(todo.slice(1))
      })
    }
    runAsyncTests(test.asyncTests())
  };
  this.countFailedTests = function() {
    return failedTests
  };
  this.results = function() {
    return results
  }
};
core.PointWalker = function PointWalker(node) {
  var currentNode = node, before = null, after = node && node.firstChild, root = node, pos = 0;
  function getPosition(node) {
    var p = -1, n = node;
    while(n) {
      n = n.previousSibling;
      p += 1
    }
    return p
  }
  this.setPoint = function(node, position) {
    currentNode = node;
    pos = position;
    if(currentNode.nodeType === 3) {
      after = null;
      before = null
    }else {
      after = currentNode.firstChild;
      while(position) {
        position -= 1;
        after = after.nextSibling
      }
      if(after) {
        before = after.previousSibling
      }else {
        before = currentNode.lastChild
      }
    }
  };
  this.stepForward = function() {
    var len;
    if(currentNode.nodeType === 3) {
      if(typeof currentNode.nodeValue.length === "number") {
        len = currentNode.nodeValue.length
      }else {
        len = currentNode.nodeValue.length()
      }
      if(pos < len) {
        pos += 1;
        return true
      }
    }
    if(after) {
      if(after.nodeType === 1) {
        currentNode = after;
        before = null;
        after = currentNode.firstChild;
        pos = 0
      }else {
        if(after.nodeType === 3) {
          currentNode = after;
          before = null;
          after = null;
          pos = 0
        }else {
          before = after;
          after = after.nextSibling;
          pos += 1
        }
      }
      return true
    }
    if(currentNode !== root) {
      before = currentNode;
      after = before.nextSibling;
      currentNode = currentNode.parentNode;
      pos = getPosition(before) + 1;
      return true
    }
    return false
  };
  this.stepBackward = function() {
    if(currentNode.nodeType === 3) {
      if(pos > 0) {
        pos -= 1;
        return true
      }
    }
    if(before) {
      if(before.nodeType === 1) {
        currentNode = before;
        before = currentNode.lastChild;
        after = null;
        pos = getPosition(before) + 1
      }else {
        if(before.nodeType === 3) {
          currentNode = before;
          before = null;
          after = null;
          if(typeof currentNode.nodeValue.length === "number") {
            pos = currentNode.nodeValue.length
          }else {
            pos = currentNode.nodeValue.length()
          }
        }else {
          after = before;
          before = before.previousSibling;
          pos -= 1
        }
      }
      return true
    }
    if(currentNode !== root) {
      after = currentNode;
      before = after.previousSibling;
      currentNode = currentNode.parentNode;
      pos = getPosition(after);
      return true
    }
    return false
  };
  this.node = function() {
    return currentNode
  };
  this.position = function() {
    return pos
  };
  this.precedingSibling = function() {
    return before
  };
  this.followingSibling = function() {
    return after
  }
};
core.Async = function Async() {
  this.forEach = function(items, f, callback) {
    var i, l = items.length, itemsDone = 0;
    function end(err) {
      if(itemsDone !== l) {
        if(err) {
          itemsDone = l;
          callback(err)
        }else {
          itemsDone += 1;
          if(itemsDone === l) {
            callback(null)
          }
        }
      }
    }
    for(i = 0;i < l;i += 1) {
      f(items[i], end)
    }
  }
};
runtime.loadClass("core.RawInflate");
runtime.loadClass("core.ByteArray");
runtime.loadClass("core.ByteArrayWriter");
runtime.loadClass("core.Base64");
core.Zip = function Zip(url, entriesReadCallback) {
  var entries, filesize, nEntries, inflate = (new core.RawInflate).inflate, zip = this, base64 = new core.Base64;
  function crc32(data) {
    var table = [0, 1996959894, 3993919788, 2567524794, 124634137, 1886057615, 3915621685, 2657392035, 249268274, 2044508324, 3772115230, 2547177864, 162941995, 2125561021, 3887607047, 2428444049, 498536548, 1789927666, 4089016648, 2227061214, 450548861, 1843258603, 4107580753, 2211677639, 325883990, 1684777152, 4251122042, 2321926636, 335633487, 1661365465, 4195302755, 2366115317, 997073096, 1281953886, 3579855332, 2724688242, 1006888145, 1258607687, 3524101629, 2768942443, 901097722, 1119000684, 
    3686517206, 2898065728, 853044451, 1172266101, 3705015759, 2882616665, 651767980, 1373503546, 3369554304, 3218104598, 565507253, 1454621731, 3485111705, 3099436303, 671266974, 1594198024, 3322730930, 2970347812, 795835527, 1483230225, 3244367275, 3060149565, 1994146192, 31158534, 2563907772, 4023717930, 1907459465, 112637215, 2680153253, 3904427059, 2013776290, 251722036, 2517215374, 3775830040, 2137656763, 141376813, 2439277719, 3865271297, 1802195444, 476864866, 2238001368, 4066508878, 1812370925, 
    453092731, 2181625025, 4111451223, 1706088902, 314042704, 2344532202, 4240017532, 1658658271, 366619977, 2362670323, 4224994405, 1303535960, 984961486, 2747007092, 3569037538, 1256170817, 1037604311, 2765210733, 3554079995, 1131014506, 879679996, 2909243462, 3663771856, 1141124467, 855842277, 2852801631, 3708648649, 1342533948, 654459306, 3188396048, 3373015174, 1466479909, 544179635, 3110523913, 3462522015, 1591671054, 702138776, 2966460450, 3352799412, 1504918807, 783551873, 3082640443, 3233442989, 
    3988292384, 2596254646, 62317068, 1957810842, 3939845945, 2647816111, 81470997, 1943803523, 3814918930, 2489596804, 225274430, 2053790376, 3826175755, 2466906013, 167816743, 2097651377, 4027552580, 2265490386, 503444072, 1762050814, 4150417245, 2154129355, 426522225, 1852507879, 4275313526, 2312317920, 282753626, 1742555852, 4189708143, 2394877945, 397917763, 1622183637, 3604390888, 2714866558, 953729732, 1340076626, 3518719985, 2797360999, 1068828381, 1219638859, 3624741850, 2936675148, 906185462, 
    1090812512, 3747672003, 2825379669, 829329135, 1181335161, 3412177804, 3160834842, 628085408, 1382605366, 3423369109, 3138078467, 570562233, 1426400815, 3317316542, 2998733608, 733239954, 1555261956, 3268935591, 3050360625, 752459403, 1541320221, 2607071920, 3965973030, 1969922972, 40735498, 2617837225, 3943577151, 1913087877, 83908371, 2512341634, 3803740692, 2075208622, 213261112, 2463272603, 3855990285, 2094854071, 198958881, 2262029012, 4057260610, 1759359992, 534414190, 2176718541, 4139329115, 
    1873836001, 414664567, 2282248934, 4279200368, 1711684554, 285281116, 2405801727, 4167216745, 1634467795, 376229701, 2685067896, 3608007406, 1308918612, 956543938, 2808555105, 3495958263, 1231636301, 1047427035, 2932959818, 3654703836, 1088359270, 936918E3, 2847714899, 3736837829, 1202900863, 817233897, 3183342108, 3401237130, 1404277552, 615818150, 3134207493, 3453421203, 1423857449, 601450431, 3009837614, 3294710456, 1567103746, 711928724, 3020668471, 3272380065, 1510334235, 755167117], crc = 
    0, i, iTop = data.length, x = 0, y = 0;
    crc = crc ^ -1;
    for(i = 0;i < iTop;i += 1) {
      y = (crc ^ data[i]) & 255;
      x = table[y];
      crc = crc >>> 8 ^ x
    }
    return crc ^ -1
  }
  function dosTime2Date(dostime) {
    var year = (dostime >> 25 & 127) + 1980, month = (dostime >> 21 & 15) - 1, mday = dostime >> 16 & 31, hour = dostime >> 11 & 15, min = dostime >> 5 & 63, sec = (dostime & 31) << 1, d = new Date(year, month, mday, hour, min, sec);
    return d
  }
  function date2DosTime(date) {
    var y = date.getFullYear();
    return y < 1980 ? 0 : y - 1980 << 25 | date.getMonth() + 1 << 21 | date.getDate() << 16 | date.getHours() << 11 | date.getMinutes() << 5 | date.getSeconds() >> 1
  }
  function ZipEntry(url, stream) {
    var sig, namelen, extralen, commentlen, compressionMethod, compressedSize, uncompressedSize, offset, crc, entry = this;
    function handleEntryData(data, callback) {
      var stream = new core.ByteArray(data), sig = stream.readUInt32LE(), filenamelen, extralen;
      if(sig !== 67324752) {
        callback("File entry signature is wrong." + sig.toString() + " " + data.length.toString(), null);
        return
      }
      stream.pos += 22;
      filenamelen = stream.readUInt16LE();
      extralen = stream.readUInt16LE();
      stream.pos += filenamelen + extralen;
      if(compressionMethod) {
        data = data.slice(stream.pos, stream.pos + compressedSize);
        if(compressedSize !== data.length) {
          callback("The amount of compressed bytes read was " + data.length.toString() + " instead of " + compressedSize.toString() + " for " + entry.filename + " in " + url + ".", null);
          return
        }
        data = inflate(data, uncompressedSize)
      }else {
        data = data.slice(stream.pos, stream.pos + uncompressedSize)
      }
      if(uncompressedSize !== data.length) {
        callback("The amount of bytes read was " + data.length.toString() + " instead of " + uncompressedSize.toString() + " for " + entry.filename + " in " + url + ".", null);
        return
      }
      entry.data = data;
      callback(null, data)
    }
    function load(callback) {
      if(entry.data !== undefined) {
        callback(null, entry.data);
        return
      }
      var size = compressedSize + 34 + namelen + extralen + 256;
      if(size + offset > filesize) {
        size = filesize - offset
      }
      runtime.read(url, offset, size, function(err, data) {
        if(err) {
          callback(err, data)
        }else {
          handleEntryData(data, callback)
        }
      })
    }
    this.load = load;
    function set(filename, data, compressed, date) {
      entry.filename = filename;
      entry.data = data;
      entry.compressed = compressed;
      entry.date = date
    }
    this.set = set;
    this.error = null;
    if(!stream) {
      return
    }
    sig = stream.readUInt32LE();
    if(sig !== 33639248) {
      this.error = "Central directory entry has wrong signature at position " + (stream.pos - 4).toString() + ' for file "' + url + '": ' + stream.data.length.toString();
      return
    }
    stream.pos += 6;
    compressionMethod = stream.readUInt16LE();
    this.date = dosTime2Date(stream.readUInt32LE());
    crc = stream.readUInt32LE();
    compressedSize = stream.readUInt32LE();
    uncompressedSize = stream.readUInt32LE();
    namelen = stream.readUInt16LE();
    extralen = stream.readUInt16LE();
    commentlen = stream.readUInt16LE();
    stream.pos += 8;
    offset = stream.readUInt32LE();
    this.filename = runtime.byteArrayToString(stream.data.slice(stream.pos, stream.pos + namelen), "utf8");
    stream.pos += namelen + extralen + commentlen
  }
  function handleCentralDirectory(data, callback) {
    var stream = new core.ByteArray(data), i, e;
    entries = [];
    for(i = 0;i < nEntries;i += 1) {
      e = new ZipEntry(url, stream);
      if(e.error) {
        callback(e.error, zip);
        return
      }
      entries[entries.length] = e
    }
    callback(null, zip)
  }
  function handleCentralDirectoryEnd(data, callback) {
    if(data.length !== 22) {
      callback("Central directory length should be 22.", zip);
      return
    }
    var stream = new core.ByteArray(data), sig, disk, cddisk, diskNEntries, cdsSize, cdsOffset;
    sig = stream.readUInt32LE();
    if(sig !== 101010256) {
      callback("Central directory signature is wrong: " + sig.toString(), zip);
      return
    }
    disk = stream.readUInt16LE();
    if(disk !== 0) {
      callback("Zip files with non-zero disk numbers are not supported.", zip);
      return
    }
    cddisk = stream.readUInt16LE();
    if(cddisk !== 0) {
      callback("Zip files with non-zero disk numbers are not supported.", zip);
      return
    }
    diskNEntries = stream.readUInt16LE();
    nEntries = stream.readUInt16LE();
    if(diskNEntries !== nEntries) {
      callback("Number of entries is inconsistent.", zip);
      return
    }
    cdsSize = stream.readUInt32LE();
    cdsOffset = stream.readUInt16LE();
    cdsOffset = filesize - 22 - cdsSize;
    runtime.read(url, cdsOffset, filesize - cdsOffset, function(err, data) {
      handleCentralDirectory(data, callback)
    })
  }
  function load(filename, callback) {
    var entry = null, end = filesize, e, i;
    for(i = 0;i < entries.length;i += 1) {
      e = entries[i];
      if(e.filename === filename) {
        entry = e;
        break
      }
    }
    if(entry) {
      if(entry.data) {
        callback(null, entry.data)
      }else {
        entry.load(callback)
      }
    }else {
      callback(filename + " not found.", null)
    }
  }
  function loadAsString(filename, callback) {
    load(filename, function(err, data) {
      if(err) {
        return callback(err, null)
      }
      data = runtime.byteArrayToString(data, "utf8");
      callback(null, data)
    })
  }
  function loadContentXmlAsFragments(filename, handler) {
    loadAsString(filename, function(err, data) {
      if(err) {
        return handler.rootElementReady(err)
      }
      handler.rootElementReady(null, data, true)
    })
  }
  function loadAsDataURL(filename, mimetype, callback) {
    load(filename, function(err, data) {
      if(err) {
        return callback(err, null)
      }
      var p = data, chunksize = 45E3, i = 0, url;
      if(!mimetype) {
        if(p[1] === 80 && p[2] === 78 && p[3] === 71) {
          mimetype = "image/png"
        }else {
          if(p[0] === 255 && p[1] === 216 && p[2] === 255) {
            mimetype = "image/jpeg"
          }else {
            if(p[0] === 71 && p[1] === 73 && p[2] === 70) {
              mimetype = "image/gif"
            }else {
              mimetype = ""
            }
          }
        }
      }
      url = "data:" + mimetype + ";base64,";
      while(i < data.length) {
        url += base64.convertUTF8ArrayToBase64(p.slice(i, Math.min(i + chunksize, p.length)));
        i += chunksize
      }
      callback(null, url)
    })
  }
  function loadAsDOM(filename, callback) {
    loadAsString(filename, function(err, xmldata) {
      if(err) {
        callback(err, null);
        return
      }
      var parser = new DOMParser;
      xmldata = parser.parseFromString(xmldata, "text/xml");
      callback(null, xmldata)
    })
  }
  function save(filename, data, compressed, date) {
    var i, entry;
    for(i = 0;i < entries.length;i += 1) {
      entry = entries[i];
      if(entry.filename === filename) {
        entry.set(filename, data, compressed, date);
        return
      }
    }
    entry = new ZipEntry(url);
    entry.set(filename, data, compressed, date);
    entries.push(entry)
  }
  function writeEntry(entry) {
    var data = new core.ByteArrayWriter("utf8"), length = 0;
    data.appendArray([80, 75, 3, 4, 20, 0, 0, 0, 0, 0]);
    if(entry.data) {
      length = entry.data.length
    }
    data.appendUInt32LE(date2DosTime(entry.date));
    data.appendUInt32LE(crc32(entry.data));
    data.appendUInt32LE(length);
    data.appendUInt32LE(length);
    data.appendUInt16LE(entry.filename.length);
    data.appendUInt16LE(0);
    data.appendString(entry.filename);
    if(entry.data) {
      data.appendByteArray(entry.data)
    }
    return data
  }
  function writeCODEntry(entry, offset) {
    var data = new core.ByteArrayWriter("utf8"), length = 0;
    data.appendArray([80, 75, 1, 2, 20, 0, 20, 0, 0, 0, 0, 0]);
    if(entry.data) {
      length = entry.data.length
    }
    data.appendUInt32LE(date2DosTime(entry.date));
    data.appendUInt32LE(crc32(entry.data));
    data.appendUInt32LE(length);
    data.appendUInt32LE(length);
    data.appendUInt16LE(entry.filename.length);
    data.appendArray([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
    data.appendUInt32LE(offset);
    data.appendString(entry.filename);
    return data
  }
  function loadAllEntries(position, callback) {
    if(position === entries.length) {
      callback(null);
      return
    }
    var entry = entries[position];
    if(entry.data !== undefined) {
      loadAllEntries(position + 1, callback);
      return
    }
    entry.load(function(err) {
      if(err) {
        callback(err);
        return
      }
      loadAllEntries(position + 1, callback)
    })
  }
  function write(callback) {
    loadAllEntries(0, function(err) {
      if(err) {
        callback(err);
        return
      }
      var data = new core.ByteArrayWriter("utf8"), i, e, codoffset, codsize, offsets = [0];
      for(i = 0;i < entries.length;i += 1) {
        data.appendByteArrayWriter(writeEntry(entries[i]));
        offsets.push(data.getLength())
      }
      codoffset = data.getLength();
      for(i = 0;i < entries.length;i += 1) {
        e = entries[i];
        data.appendByteArrayWriter(writeCODEntry(e, offsets[i]))
      }
      codsize = data.getLength() - codoffset;
      data.appendArray([80, 75, 5, 6, 0, 0, 0, 0]);
      data.appendUInt16LE(entries.length);
      data.appendUInt16LE(entries.length);
      data.appendUInt32LE(codsize);
      data.appendUInt32LE(codoffset);
      data.appendArray([0, 0]);
      runtime.writeFile(url, data.getByteArray(), callback)
    })
  }
  this.load = load;
  this.save = save;
  this.write = write;
  this.loadContentXmlAsFragments = loadContentXmlAsFragments;
  this.loadAsString = loadAsString;
  this.loadAsDOM = loadAsDOM;
  this.loadAsDataURL = loadAsDataURL;
  this.getEntries = function() {
    return entries.slice()
  };
  filesize = -1;
  if(entriesReadCallback === null) {
    entries = [];
    return
  }
  runtime.getFileSize(url, function(size) {
    filesize = size;
    if(filesize < 0) {
      entriesReadCallback("File '" + url + "' cannot be read.", zip)
    }else {
      runtime.read(url, filesize - 22, 22, function(err, data) {
        if(err || entriesReadCallback === null) {
          entriesReadCallback(err, zip)
        }else {
          handleCentralDirectoryEnd(data, entriesReadCallback)
        }
      })
    }
  })
};
xmldom.LSSerializerFilter = function LSSerializerFilter() {
};
if(typeof Object.create !== "function") {
  Object["create"] = function(o) {
    var F = function() {
    };
    F.prototype = o;
    return new F
  }
}
xmldom.LSSerializer = function LSSerializer() {
  var self = this;
  function serializeAttribute(prefix, attr) {
    var s = prefix + attr.localName + '="' + attr.nodeValue + '"';
    return s
  }
  function attributePrefix(nsmap, prefix, ns) {
    if(nsmap.hasOwnProperty(ns)) {
      return nsmap[ns] + ":"
    }
    if(nsmap[ns] !== prefix) {
      nsmap[ns] = prefix
    }
    return prefix + ":"
  }
  function startNode(nsmap, node) {
    var s = "", atts = node.attributes, length, i, attr, attstr = "", accept, prefix;
    if(atts) {
      if(nsmap[node.namespaceURI] !== node.prefix) {
        nsmap[node.namespaceURI] = node.prefix
      }
      s += "<" + node.nodeName;
      length = atts.length;
      for(i = 0;i < length;i += 1) {
        attr = atts.item(i);
        if(attr.namespaceURI !== "http://www.w3.org/2000/xmlns/") {
          accept = self.filter ? self.filter.acceptNode(attr) : 1;
          if(accept === 1) {
            if(attr.namespaceURI) {
              prefix = attributePrefix(nsmap, attr.prefix, attr.namespaceURI)
            }else {
              prefix = ""
            }
            attstr += " " + serializeAttribute(prefix, attr)
          }
        }
      }
      for(i in nsmap) {
        if(nsmap.hasOwnProperty(i)) {
          prefix = nsmap[i];
          if(!prefix) {
            s += ' xmlns="' + i + '"'
          }else {
            if(prefix !== "xmlns") {
              s += " xmlns:" + nsmap[i] + '="' + i + '"'
            }
          }
        }
      }
      s += attstr + ">"
    }
    return s
  }
  function endNode(node) {
    var s = "";
    if(node.nodeType === 1) {
      s += "</" + node.nodeName + ">"
    }
    return s
  }
  function serializeNode(parentnsmap, node) {
    var s = "", nsmap = Object.create(parentnsmap), accept = self.filter ? self.filter.acceptNode(node) : 1, child;
    if(accept === 1) {
      s += startNode(nsmap, node)
    }
    if(accept === 1 || accept === 3) {
      child = node.firstChild;
      while(child) {
        s += serializeNode(nsmap, child);
        child = child.nextSibling
      }
      if(node.nodeValue) {
        s += node.nodeValue
      }
    }
    if(accept === 1) {
      s += endNode(node)
    }
    return s
  }
  function invertMap(map) {
    var m = {}, i;
    for(i in map) {
      if(map.hasOwnProperty(i)) {
        m[map[i]] = i
      }
    }
    return m
  }
  this.filter = null;
  this.writeToString = function(node, nsmap) {
    if(!node) {
      return""
    }
    nsmap = nsmap ? invertMap(nsmap) : {};
    return serializeNode(nsmap, node)
  }
};
xmldom.RelaxNGParser = function RelaxNGParser() {
  var self = this, rngns = "http://relaxng.org/ns/structure/1.0", xmlnsns = "http://www.w3.org/2000/xmlns/", start, nsmap = {"http://www.w3.org/XML/1998/namespace":"xml"}, parse;
  function RelaxNGParseError(error, context) {
    this.message = function() {
      if(context) {
        error += context.nodeType === 1 ? " Element " : " Node ";
        error += context.nodeName;
        if(context.nodeValue) {
          error += " with value '" + context.nodeValue + "'"
        }
        error += "."
      }
      return error
    }
  }
  function splitToDuos(e) {
    if(e.e.length <= 2) {
      return e
    }
    var o = {name:e.name, e:e.e.slice(0, 2)};
    return splitToDuos({name:e.name, e:[o].concat(e.e.slice(2))})
  }
  function splitQName(name) {
    var r = name.split(":", 2), prefix = "", i;
    if(r.length === 1) {
      r = ["", r[0]]
    }else {
      prefix = r[0]
    }
    for(i in nsmap) {
      if(nsmap[i] === prefix) {
        r[0] = i
      }
    }
    return r
  }
  function splitQNames(def) {
    var i, l = def.names ? def.names.length : 0, name, localnames = def.localnames = [l], namespaces = def.namespaces = [l];
    for(i = 0;i < l;i += 1) {
      name = splitQName(def.names[i]);
      namespaces[i] = name[0];
      localnames[i] = name[1]
    }
  }
  function trim(str) {
    str = str.replace(/^\s\s*/, "");
    var ws = /\s/, i = str.length - 1;
    while(ws.test(str.charAt(i))) {
      i -= 1
    }
    return str.slice(0, i + 1)
  }
  function copyAttributes(atts, name, names) {
    var a = {}, i, att;
    for(i = 0;i < atts.length;i += 1) {
      att = atts.item(i);
      if(!att.namespaceURI) {
        if(att.localName === "name" && (name === "element" || name === "attribute")) {
          names.push(att.value)
        }
        if(att.localName === "name" || att.localName === "combine" || att.localName === "type") {
          att.value = trim(att.value)
        }
        a[att.localName] = att.value
      }else {
        if(att.namespaceURI === xmlnsns) {
          nsmap[att.value] = att.localName
        }
      }
    }
    return a
  }
  function parseChildren(c, e, elements, names) {
    var text = "", ce;
    while(c) {
      if(c.nodeType === 1 && c.namespaceURI === rngns) {
        ce = parse(c, elements, e);
        if(ce) {
          if(ce.name === "name") {
            names.push(nsmap[ce.a.ns] + ":" + ce.text);
            e.push(ce)
          }else {
            if(ce.name === "choice" && ce.names && ce.names.length) {
              names = names.concat(ce.names);
              delete ce.names;
              e.push(ce)
            }else {
              e.push(ce)
            }
          }
        }
      }else {
        if(c.nodeType === 3) {
          text += c.nodeValue
        }
      }
      c = c.nextSibling
    }
    return text
  }
  function combineDefines(combine, name, e, siblings) {
    var i, ce;
    for(i = 0;siblings && i < siblings.length;i += 1) {
      ce = siblings[i];
      if(ce.name === "define" && ce.a && ce.a.name === name) {
        ce.e = [{name:combine, e:ce.e.concat(e)}];
        return ce
      }
    }
    return null
  }
  parse = function parse(element, elements, siblings) {
    var e = [], a, ce, i, text, name = element.localName, names = [];
    a = copyAttributes(element.attributes, name, names);
    a.combine = a.combine || undefined;
    text = parseChildren(element.firstChild, e, elements, names);
    if(name !== "value" && name !== "param") {
      text = /^\s*([\s\S]*\S)?\s*$/.exec(text)[1]
    }
    if(name === "value" && a.type === undefined) {
      a.type = "token";
      a.datatypeLibrary = ""
    }
    if((name === "attribute" || name === "element") && a.name !== undefined) {
      i = splitQName(a.name);
      e = [{name:"name", text:i[1], a:{ns:i[0]}}].concat(e);
      delete a.name
    }
    if(name === "name" || name === "nsName" || name === "value") {
      if(a.ns === undefined) {
        a.ns = ""
      }
    }else {
      delete a.ns
    }
    if(name === "name") {
      i = splitQName(text);
      a.ns = i[0];
      text = i[1]
    }
    if(e.length > 1 && (name === "define" || name === "oneOrMore" || name === "zeroOrMore" || name === "optional" || name === "list" || name === "mixed")) {
      e = [{name:"group", e:splitToDuos({name:"group", e:e}).e}]
    }
    if(e.length > 2 && name === "element") {
      e = [e[0]].concat({name:"group", e:splitToDuos({name:"group", e:e.slice(1)}).e})
    }
    if(e.length === 1 && name === "attribute") {
      e.push({name:"text", text:text})
    }
    if(e.length === 1 && (name === "choice" || name === "group" || name === "interleave")) {
      name = e[0].name;
      names = e[0].names;
      a = e[0].a;
      text = e[0].text;
      e = e[0].e
    }else {
      if(e.length > 2 && (name === "choice" || name === "group" || name === "interleave")) {
        e = splitToDuos({name:name, e:e}).e
      }
    }
    if(name === "mixed") {
      name = "interleave";
      e = [e[0], {name:"text"}]
    }
    if(name === "optional") {
      name = "choice";
      e = [e[0], {name:"empty"}]
    }
    if(name === "zeroOrMore") {
      name = "choice";
      e = [{name:"oneOrMore", e:[e[0]]}, {name:"empty"}]
    }
    if(name === "define" && a.combine) {
      ce = combineDefines(a.combine, a.name, e, siblings);
      if(ce) {
        return
      }
    }
    ce = {name:name};
    if(e && e.length > 0) {
      ce.e = e
    }
    for(i in a) {
      if(a.hasOwnProperty(i)) {
        ce.a = a;
        break
      }
    }
    if(text !== undefined) {
      ce.text = text
    }
    if(names && names.length > 0) {
      ce.names = names
    }
    if(name === "element") {
      ce.id = elements.length;
      elements.push(ce);
      ce = {name:"elementref", id:ce.id}
    }
    return ce
  };
  function resolveDefines(def, defines) {
    var i = 0, e, defs, end, name = def.name;
    while(def.e && i < def.e.length) {
      e = def.e[i];
      if(e.name === "ref") {
        defs = defines[e.a.name];
        if(!defs) {
          throw e.a.name + " was not defined.";
        }
        end = def.e.slice(i + 1);
        def.e = def.e.slice(0, i);
        def.e = def.e.concat(defs.e);
        def.e = def.e.concat(end)
      }else {
        i += 1;
        resolveDefines(e, defines)
      }
    }
    e = def.e;
    if(name === "choice") {
      if(!e || !e[1] || e[1].name === "empty") {
        if(!e || !e[0] || e[0].name === "empty") {
          delete def.e;
          def.name = "empty"
        }else {
          e[1] = e[0];
          e[0] = {name:"empty"}
        }
      }
    }
    if(name === "group" || name === "interleave") {
      if(e[0].name === "empty") {
        if(e[1].name === "empty") {
          delete def.e;
          def.name = "empty"
        }else {
          name = def.name = e[1].name;
          def.names = e[1].names;
          e = def.e = e[1].e
        }
      }else {
        if(e[1].name === "empty") {
          name = def.name = e[0].name;
          def.names = e[0].names;
          e = def.e = e[0].e
        }
      }
    }
    if(name === "oneOrMore" && e[0].name === "empty") {
      delete def.e;
      def.name = "empty"
    }
    if(name === "attribute") {
      splitQNames(def)
    }
    if(name === "interleave") {
      if(e[0].name === "interleave") {
        if(e[1].name === "interleave") {
          e = def.e = e[0].e.concat(e[1].e)
        }else {
          e = def.e = [e[1]].concat(e[0].e)
        }
      }else {
        if(e[1].name === "interleave") {
          e = def.e = [e[0]].concat(e[1].e)
        }
      }
    }
  }
  function resolveElements(def, elements) {
    var i = 0, e, name;
    while(def.e && i < def.e.length) {
      e = def.e[i];
      if(e.name === "elementref") {
        e.id = e.id || 0;
        def.e[i] = elements[e.id]
      }else {
        if(e.name !== "element") {
          resolveElements(e, elements)
        }
      }
      i += 1
    }
  }
  function main(dom, callback) {
    var elements = [], grammar = parse(dom && dom.documentElement, elements, undefined), i, e, defines = {};
    for(i = 0;i < grammar.e.length;i += 1) {
      e = grammar.e[i];
      if(e.name === "define") {
        defines[e.a.name] = e
      }else {
        if(e.name === "start") {
          start = e
        }
      }
    }
    if(!start) {
      return[new RelaxNGParseError("No Relax NG start element was found.")]
    }
    resolveDefines(start, defines);
    for(i in defines) {
      if(defines.hasOwnProperty(i)) {
        resolveDefines(defines[i], defines)
      }
    }
    for(i = 0;i < elements.length;i += 1) {
      resolveDefines(elements[i], defines)
    }
    if(callback) {
      self.rootPattern = callback(start.e[0], elements)
    }
    resolveElements(start, elements);
    for(i = 0;i < elements.length;i += 1) {
      resolveElements(elements[i], elements)
    }
    self.start = start;
    self.elements = elements;
    self.nsmap = nsmap;
    return null
  }
  this.parseRelaxNGDOM = main
};
runtime.loadClass("xmldom.RelaxNGParser");
xmldom.RelaxNG = function RelaxNG() {
  var xmlnsns = "http://www.w3.org/2000/xmlns/", createChoice, createInterleave, createGroup, createAfter, createOneOrMore, createValue, createAttribute, createNameClass, createData, makePattern, notAllowed = {type:"notAllowed", nullable:false, hash:"notAllowed", textDeriv:function() {
    return notAllowed
  }, startTagOpenDeriv:function() {
    return notAllowed
  }, attDeriv:function() {
    return notAllowed
  }, startTagCloseDeriv:function() {
    return notAllowed
  }, endTagDeriv:function() {
    return notAllowed
  }}, empty = {type:"empty", nullable:true, hash:"empty", textDeriv:function() {
    return notAllowed
  }, startTagOpenDeriv:function() {
    return notAllowed
  }, attDeriv:function(context, attribute) {
    return notAllowed
  }, startTagCloseDeriv:function() {
    return empty
  }, endTagDeriv:function() {
    return notAllowed
  }}, text = {type:"text", nullable:true, hash:"text", textDeriv:function() {
    return text
  }, startTagOpenDeriv:function() {
    return notAllowed
  }, attDeriv:function() {
    return notAllowed
  }, startTagCloseDeriv:function() {
    return text
  }, endTagDeriv:function() {
    return notAllowed
  }}, applyAfter, childDeriv, rootPattern;
  function memoize0arg(func) {
    return function() {
      var cache;
      return function() {
        if(cache === undefined) {
          cache = func()
        }
        return cache
      }
    }()
  }
  function memoize1arg(type, func) {
    return function() {
      var cache = {}, cachecount = 0;
      return function(a) {
        var ahash = a.hash || a.toString(), v;
        v = cache[ahash];
        if(v !== undefined) {
          return v
        }
        cache[ahash] = v = func(a);
        v.hash = type + cachecount.toString();
        cachecount += 1;
        return v
      }
    }()
  }
  function memoizeNode(func) {
    return function() {
      var cache = {};
      return function(node) {
        var v, m;
        m = cache[node.localName];
        if(m === undefined) {
          cache[node.localName] = m = {}
        }else {
          v = m[node.namespaceURI];
          if(v !== undefined) {
            return v
          }
        }
        m[node.namespaceURI] = v = func(node);
        return v
      }
    }()
  }
  function memoize2arg(type, fastfunc, func) {
    return function() {
      var cache = {}, cachecount = 0;
      return function(a, b) {
        var v = fastfunc && fastfunc(a, b), ahash, bhash, m;
        if(v !== undefined) {
          return v
        }
        ahash = a.hash || a.toString();
        bhash = b.hash || b.toString();
        m = cache[ahash];
        if(m === undefined) {
          cache[ahash] = m = {}
        }else {
          v = m[bhash];
          if(v !== undefined) {
            return v
          }
        }
        m[bhash] = v = func(a, b);
        v.hash = type + cachecount.toString();
        cachecount += 1;
        return v
      }
    }()
  }
  function unorderedMemoize2arg(type, fastfunc, func) {
    return function() {
      var cache = {}, cachecount = 0;
      return function(a, b) {
        var v = fastfunc && fastfunc(a, b), ahash, bhash, m;
        if(v !== undefined) {
          return v
        }
        ahash = a.hash || a.toString();
        bhash = b.hash || b.toString();
        if(ahash < bhash) {
          m = ahash;
          ahash = bhash;
          bhash = m;
          m = a;
          a = b;
          b = m
        }
        m = cache[ahash];
        if(m === undefined) {
          cache[ahash] = m = {}
        }else {
          v = m[bhash];
          if(v !== undefined) {
            return v
          }
        }
        m[bhash] = v = func(a, b);
        v.hash = type + cachecount.toString();
        cachecount += 1;
        return v
      }
    }()
  }
  function getUniqueLeaves(leaves, pattern) {
    if(pattern.p1.type === "choice") {
      getUniqueLeaves(leaves, pattern.p1)
    }else {
      leaves[pattern.p1.hash] = pattern.p1
    }
    if(pattern.p2.type === "choice") {
      getUniqueLeaves(leaves, pattern.p2)
    }else {
      leaves[pattern.p2.hash] = pattern.p2
    }
  }
  createChoice = memoize2arg("choice", function(p1, p2) {
    if(p1 === notAllowed) {
      return p2
    }
    if(p2 === notAllowed) {
      return p1
    }
    if(p1 === p2) {
      return p1
    }
  }, function(p1, p2) {
    function makeChoice(p1, p2) {
      return{type:"choice", p1:p1, p2:p2, nullable:p1.nullable || p2.nullable, textDeriv:function(context, text) {
        return createChoice(p1.textDeriv(context, text), p2.textDeriv(context, text))
      }, startTagOpenDeriv:memoizeNode(function(node) {
        return createChoice(p1.startTagOpenDeriv(node), p2.startTagOpenDeriv(node))
      }), attDeriv:function(context, attribute) {
        return createChoice(p1.attDeriv(context, attribute), p2.attDeriv(context, attribute))
      }, startTagCloseDeriv:memoize0arg(function() {
        return createChoice(p1.startTagCloseDeriv(), p2.startTagCloseDeriv())
      }), endTagDeriv:memoize0arg(function() {
        return createChoice(p1.endTagDeriv(), p2.endTagDeriv())
      })}
    }
    var leaves = {}, i;
    getUniqueLeaves(leaves, {p1:p1, p2:p2});
    p1 = undefined;
    p2 = undefined;
    for(i in leaves) {
      if(leaves.hasOwnProperty(i)) {
        if(p1 === undefined) {
          p1 = leaves[i]
        }else {
          if(p2 === undefined) {
            p2 = leaves[i]
          }else {
            p2 = createChoice(p2, leaves[i])
          }
        }
      }
    }
    return makeChoice(p1, p2)
  });
  createInterleave = unorderedMemoize2arg("interleave", function(p1, p2) {
    if(p1 === notAllowed || p2 === notAllowed) {
      return notAllowed
    }
    if(p1 === empty) {
      return p2
    }
    if(p2 === empty) {
      return p1
    }
  }, function(p1, p2) {
    return{type:"interleave", p1:p1, p2:p2, nullable:p1.nullable && p2.nullable, textDeriv:function(context, text) {
      return createChoice(createInterleave(p1.textDeriv(context, text), p2), createInterleave(p1, p2.textDeriv(context, text)))
    }, startTagOpenDeriv:memoizeNode(function(node) {
      return createChoice(applyAfter(function(p) {
        return createInterleave(p, p2)
      }, p1.startTagOpenDeriv(node)), applyAfter(function(p) {
        return createInterleave(p1, p)
      }, p2.startTagOpenDeriv(node)))
    }), attDeriv:function(context, attribute) {
      return createChoice(createInterleave(p1.attDeriv(context, attribute), p2), createInterleave(p1, p2.attDeriv(context, attribute)))
    }, startTagCloseDeriv:memoize0arg(function() {
      return createInterleave(p1.startTagCloseDeriv(), p2.startTagCloseDeriv())
    })}
  });
  createGroup = memoize2arg("group", function(p1, p2) {
    if(p1 === notAllowed || p2 === notAllowed) {
      return notAllowed
    }
    if(p1 === empty) {
      return p2
    }
    if(p2 === empty) {
      return p1
    }
  }, function(p1, p2) {
    return{type:"group", p1:p1, p2:p2, nullable:p1.nullable && p2.nullable, textDeriv:function(context, text) {
      var p = createGroup(p1.textDeriv(context, text), p2);
      if(p1.nullable) {
        return createChoice(p, p2.textDeriv(context, text))
      }
      return p
    }, startTagOpenDeriv:function(node) {
      var x = applyAfter(function(p) {
        return createGroup(p, p2)
      }, p1.startTagOpenDeriv(node));
      if(p1.nullable) {
        return createChoice(x, p2.startTagOpenDeriv(node))
      }
      return x
    }, attDeriv:function(context, attribute) {
      return createChoice(createGroup(p1.attDeriv(context, attribute), p2), createGroup(p1, p2.attDeriv(context, attribute)))
    }, startTagCloseDeriv:memoize0arg(function() {
      return createGroup(p1.startTagCloseDeriv(), p2.startTagCloseDeriv())
    })}
  });
  createAfter = memoize2arg("after", function(p1, p2) {
    if(p1 === notAllowed || p2 === notAllowed) {
      return notAllowed
    }
  }, function(p1, p2) {
    return{type:"after", p1:p1, p2:p2, nullable:false, textDeriv:function(context, text) {
      return createAfter(p1.textDeriv(context, text), p2)
    }, startTagOpenDeriv:memoizeNode(function(node) {
      return applyAfter(function(p) {
        return createAfter(p, p2)
      }, p1.startTagOpenDeriv(node))
    }), attDeriv:function(context, attribute) {
      return createAfter(p1.attDeriv(context, attribute), p2)
    }, startTagCloseDeriv:memoize0arg(function() {
      return createAfter(p1.startTagCloseDeriv(), p2)
    }), endTagDeriv:memoize0arg(function() {
      return p1.nullable ? p2 : notAllowed
    })}
  });
  createOneOrMore = memoize1arg("oneormore", function(p) {
    if(p === notAllowed) {
      return notAllowed
    }
    return{type:"oneOrMore", p:p, nullable:p.nullable, textDeriv:function(context, text) {
      return createGroup(p.textDeriv(context, text), createChoice(this, empty))
    }, startTagOpenDeriv:function(node) {
      var oneOrMore = this;
      return applyAfter(function(pf) {
        return createGroup(pf, createChoice(oneOrMore, empty))
      }, p.startTagOpenDeriv(node))
    }, attDeriv:function(context, attribute) {
      var oneOrMore = this;
      return createGroup(p.attDeriv(context, attribute), createChoice(oneOrMore, empty))
    }, startTagCloseDeriv:memoize0arg(function() {
      return createOneOrMore(p.startTagCloseDeriv())
    })}
  });
  function createElement(nc, p) {
    return{type:"element", nc:nc, nullable:false, textDeriv:function() {
      return notAllowed
    }, startTagOpenDeriv:function(node) {
      if(nc.contains(node)) {
        return createAfter(p, empty)
      }
      return notAllowed
    }, attDeriv:function(context, attribute) {
      return notAllowed
    }, startTagCloseDeriv:function() {
      return this
    }}
  }
  function valueMatch(context, pattern, text) {
    return pattern.nullable && /^\s+$/.test(text) || pattern.textDeriv(context, text).nullable
  }
  createAttribute = memoize2arg("attribute", undefined, function(nc, p) {
    return{type:"attribute", nullable:false, nc:nc, p:p, attDeriv:function(context, attribute) {
      if(nc.contains(attribute) && valueMatch(context, p, attribute.nodeValue)) {
        return empty
      }
      return notAllowed
    }, startTagCloseDeriv:function() {
      return notAllowed
    }}
  });
  function createList() {
    return{type:"list", nullable:false, hash:"list", textDeriv:function(context, text) {
      return empty
    }}
  }
  createValue = memoize1arg("value", function(value) {
    return{type:"value", nullable:false, value:value, textDeriv:function(context, text) {
      return text === value ? empty : notAllowed
    }, attDeriv:function() {
      return notAllowed
    }, startTagCloseDeriv:function() {
      return this
    }}
  });
  createData = memoize1arg("data", function(type) {
    return{type:"data", nullable:false, dataType:type, textDeriv:function() {
      return empty
    }, attDeriv:function() {
      return notAllowed
    }, startTagCloseDeriv:function() {
      return this
    }}
  });
  function createDataExcept() {
    return{type:"dataExcept", nullable:false, hash:"dataExcept"}
  }
  applyAfter = function applyAfter(f, p) {
    var result;
    if(p.type === "after") {
      result = createAfter(p.p1, f(p.p2))
    }else {
      if(p.type === "choice") {
        result = createChoice(applyAfter(f, p.p1), applyAfter(f, p.p2))
      }else {
        result = p
      }
    }
    return result
  };
  function attsDeriv(context, pattern, attributes, position) {
    if(pattern === notAllowed) {
      return notAllowed
    }
    if(position >= attributes.length) {
      return pattern
    }
    if(position === 0) {
      position = 0
    }
    var a = attributes.item(position);
    while(a.namespaceURI === xmlnsns) {
      position += 1;
      if(position >= attributes.length) {
        return pattern
      }
      a = attributes.item(position)
    }
    a = attsDeriv(context, pattern.attDeriv(context, attributes.item(position)), attributes, position + 1);
    return a
  }
  function childrenDeriv(context, pattern, walker) {
    var element = walker.currentNode, childNode = walker.firstChild(), numberOfTextNodes = 0, childNodes = [], i, p;
    while(childNode) {
      if(childNode.nodeType === 1) {
        childNodes.push(childNode)
      }else {
        if(childNode.nodeType === 3 && !/^\s*$/.test(childNode.nodeValue)) {
          childNodes.push(childNode.nodeValue);
          numberOfTextNodes += 1
        }
      }
      childNode = walker.nextSibling()
    }
    if(childNodes.length === 0) {
      childNodes = [""]
    }
    p = pattern;
    for(i = 0;p !== notAllowed && i < childNodes.length;i += 1) {
      childNode = childNodes[i];
      if(typeof childNode === "string") {
        if(/^\s*$/.test(childNode)) {
          p = createChoice(p, p.textDeriv(context, childNode))
        }else {
          p = p.textDeriv(context, childNode)
        }
      }else {
        walker.currentNode = childNode;
        p = childDeriv(context, p, walker)
      }
    }
    walker.currentNode = element;
    return p
  }
  childDeriv = function childDeriv(context, pattern, walker) {
    var childNode = walker.currentNode, p;
    p = pattern.startTagOpenDeriv(childNode);
    p = attsDeriv(context, p, childNode.attributes, 0);
    p = p.startTagCloseDeriv();
    p = childrenDeriv(context, p, walker);
    p = p.endTagDeriv();
    return p
  };
  function addNames(name, ns, pattern) {
    if(pattern.e[0].a) {
      name.push(pattern.e[0].text);
      ns.push(pattern.e[0].a.ns)
    }else {
      addNames(name, ns, pattern.e[0])
    }
    if(pattern.e[1].a) {
      name.push(pattern.e[1].text);
      ns.push(pattern.e[1].a.ns)
    }else {
      addNames(name, ns, pattern.e[1])
    }
  }
  createNameClass = function createNameClass(pattern) {
    var name, ns, hash, i, result;
    if(pattern.name === "name") {
      name = pattern.text;
      ns = pattern.a.ns;
      result = {name:name, ns:ns, hash:"{" + ns + "}" + name, contains:function(node) {
        return node.namespaceURI === ns && node.localName === name
      }}
    }else {
      if(pattern.name === "choice") {
        name = [];
        ns = [];
        addNames(name, ns, pattern);
        hash = "";
        for(i = 0;i < name.length;i += 1) {
          hash += "{" + ns[i] + "}" + name[i] + ","
        }
        result = {hash:hash, contains:function(node) {
          var i;
          for(i = 0;i < name.length;i += 1) {
            if(name[i] === node.localName && ns[i] === node.namespaceURI) {
              return true
            }
          }
          return false
        }}
      }else {
        result = {hash:"anyName", contains:function() {
          return true
        }}
      }
    }
    return result
  };
  function resolveElement(pattern, elements) {
    var element, p, i, hash;
    hash = "element" + pattern.id.toString();
    p = elements[pattern.id] = {hash:hash};
    element = createElement(createNameClass(pattern.e[0]), makePattern(pattern.e[1], elements));
    for(i in element) {
      if(element.hasOwnProperty(i)) {
        p[i] = element[i]
      }
    }
    return p
  }
  makePattern = function makePattern(pattern, elements) {
    var p, i;
    if(pattern.name === "elementref") {
      p = pattern.id || 0;
      pattern = elements[p];
      if(pattern.name !== undefined) {
        return resolveElement(pattern, elements)
      }
      return pattern
    }
    switch(pattern.name) {
      case "empty":
        return empty;
      case "notAllowed":
        return notAllowed;
      case "text":
        return text;
      case "choice":
        return createChoice(makePattern(pattern.e[0], elements), makePattern(pattern.e[1], elements));
      case "interleave":
        p = makePattern(pattern.e[0], elements);
        for(i = 1;i < pattern.e.length;i += 1) {
          p = createInterleave(p, makePattern(pattern.e[i], elements))
        }
        return p;
      case "group":
        return createGroup(makePattern(pattern.e[0], elements), makePattern(pattern.e[1], elements));
      case "oneOrMore":
        return createOneOrMore(makePattern(pattern.e[0], elements));
      case "attribute":
        return createAttribute(createNameClass(pattern.e[0]), makePattern(pattern.e[1], elements));
      case "value":
        return createValue(pattern.text);
      case "data":
        p = pattern.a && pattern.a.type;
        if(p === undefined) {
          p = ""
        }
        return createData(p);
      case "list":
        return createList()
    }
    throw"No support for " + pattern.name;
  };
  this.makePattern = function(pattern, elements) {
    var copy = {}, i;
    for(i in elements) {
      if(elements.hasOwnProperty(i)) {
        copy[i] = elements[i]
      }
    }
    i = makePattern(pattern, copy);
    return i
  };
  this.validate = function validate(walker, callback) {
    var errors;
    walker.currentNode = walker.root;
    errors = childDeriv(null, rootPattern, walker);
    if(!errors.nullable) {
      runtime.log("Error in Relax NG validation: " + errors);
      callback(["Error in Relax NG validation: " + errors])
    }else {
      callback(null)
    }
  };
  this.init = function init(rootPattern1) {
    rootPattern = rootPattern1
  }
};
runtime.loadClass("xmldom.RelaxNGParser");
xmldom.RelaxNG2 = function RelaxNG2() {
  var start, validateNonEmptyPattern, nsmap, depth = 0, p = "                                                                ";
  function RelaxNGParseError(error, context) {
    this.message = function() {
      if(context) {
        error += context.nodeType === 1 ? " Element " : " Node ";
        error += context.nodeName;
        if(context.nodeValue) {
          error += " with value '" + context.nodeValue + "'"
        }
        error += "."
      }
      return error
    }
  }
  function validateOneOrMore(elementdef, walker, element) {
    var node, i = 0, err;
    do {
      node = walker.currentNode;
      err = validateNonEmptyPattern(elementdef.e[0], walker, element);
      i += 1
    }while(!err && node !== walker.currentNode);
    if(i > 1) {
      walker.currentNode = node;
      return null
    }
    return err
  }
  function qName(node) {
    return nsmap[node.namespaceURI] + ":" + node.localName
  }
  function isWhitespace(node) {
    return node && node.nodeType === 3 && /^\s+$/.test(node.nodeValue)
  }
  function validatePattern(elementdef, walker, element, data) {
    if(elementdef.name === "empty") {
      return null
    }
    return validateNonEmptyPattern(elementdef, walker, element, data)
  }
  function validateAttribute(elementdef, walker, element) {
    if(elementdef.e.length !== 2) {
      throw"Attribute with wrong # of elements: " + elementdef.e.length;
    }
    var att, a, l = elementdef.localnames.length, i;
    for(i = 0;i < l;i += 1) {
      a = element.getAttributeNS(elementdef.namespaces[i], elementdef.localnames[i]);
      if(a === "" && !element.hasAttributeNS(elementdef.namespaces[i], elementdef.localnames[i])) {
        a = undefined
      }
      if(att !== undefined && a !== undefined) {
        return[new RelaxNGParseError("Attribute defined too often.", element)]
      }
      att = a
    }
    if(att === undefined) {
      return[new RelaxNGParseError("Attribute not found: " + elementdef.names, element)]
    }
    return validatePattern(elementdef.e[1], walker, element, att)
  }
  function validateTop(elementdef, walker, element) {
    return validatePattern(elementdef, walker, element)
  }
  function validateElement(elementdef, walker, element) {
    if(elementdef.e.length !== 2) {
      throw"Element with wrong # of elements: " + elementdef.e.length;
    }
    depth += 1;
    var node = walker.currentNode, type = node ? node.nodeType : 0, error = null;
    while(type > 1) {
      if(type !== 8 && (type !== 3 || !/^\s+$/.test(walker.currentNode.nodeValue))) {
        depth -= 1;
        return[new RelaxNGParseError("Not allowed node of type " + type + ".")]
      }
      node = walker.nextSibling();
      type = node ? node.nodeType : 0
    }
    if(!node) {
      depth -= 1;
      return[new RelaxNGParseError("Missing element " + elementdef.names)]
    }
    if(elementdef.names && elementdef.names.indexOf(qName(node)) === -1) {
      depth -= 1;
      return[new RelaxNGParseError("Found " + node.nodeName + " instead of " + elementdef.names + ".", node)]
    }
    if(walker.firstChild()) {
      error = validateTop(elementdef.e[1], walker, node);
      while(walker.nextSibling()) {
        type = walker.currentNode.nodeType;
        if(!isWhitespace(walker.currentNode) && type !== 8) {
          depth -= 1;
          return[new RelaxNGParseError("Spurious content.", walker.currentNode)]
        }
      }
      if(walker.parentNode() !== node) {
        depth -= 1;
        return[new RelaxNGParseError("Implementation error.")]
      }
    }else {
      error = validateTop(elementdef.e[1], walker, node)
    }
    depth -= 1;
    node = walker.nextSibling();
    return error
  }
  function validateChoice(elementdef, walker, element, data) {
    if(elementdef.e.length !== 2) {
      throw"Choice with wrong # of options: " + elementdef.e.length;
    }
    var node = walker.currentNode, err;
    if(elementdef.e[0].name === "empty") {
      err = validateNonEmptyPattern(elementdef.e[1], walker, element, data);
      if(err) {
        walker.currentNode = node
      }
      return null
    }
    err = validatePattern(elementdef.e[0], walker, element, data);
    if(err) {
      walker.currentNode = node;
      err = validateNonEmptyPattern(elementdef.e[1], walker, element, data)
    }
    return err
  }
  function validateInterleave(elementdef, walker, element) {
    var l = elementdef.e.length, n = [l], err, i, todo = l, donethisround, node, subnode, e;
    while(todo > 0) {
      donethisround = 0;
      node = walker.currentNode;
      for(i = 0;i < l;i += 1) {
        subnode = walker.currentNode;
        if(n[i] !== true && n[i] !== subnode) {
          e = elementdef.e[i];
          err = validateNonEmptyPattern(e, walker, element);
          if(err) {
            walker.currentNode = subnode;
            if(n[i] === undefined) {
              n[i] = false
            }
          }else {
            if(subnode === walker.currentNode || e.name === "oneOrMore" || e.name === "choice" && (e.e[0].name === "oneOrMore" || e.e[1].name === "oneOrMore")) {
              donethisround += 1;
              n[i] = subnode
            }else {
              donethisround += 1;
              n[i] = true
            }
          }
        }
      }
      if(node === walker.currentNode && donethisround === todo) {
        return null
      }
      if(donethisround === 0) {
        for(i = 0;i < l;i += 1) {
          if(n[i] === false) {
            return[new RelaxNGParseError("Interleave does not match.", element)]
          }
        }
        return null
      }
      todo = 0;
      for(i = 0;i < l;i += 1) {
        if(n[i] !== true) {
          todo += 1
        }
      }
    }
    return null
  }
  function validateGroup(elementdef, walker, element) {
    if(elementdef.e.length !== 2) {
      throw"Group with wrong # of members: " + elementdef.e.length;
    }
    return validateNonEmptyPattern(elementdef.e[0], walker, element) || validateNonEmptyPattern(elementdef.e[1], walker, element)
  }
  function validateText(elementdef, walker, element) {
    var node = walker.currentNode, type = node ? node.nodeType : 0, error = null;
    while(node !== element && type !== 3) {
      if(type === 1) {
        return[new RelaxNGParseError("Element not allowed here.", node)]
      }
      node = walker.nextSibling();
      type = node ? node.nodeType : 0
    }
    walker.nextSibling();
    return null
  }
  validateNonEmptyPattern = function validateNonEmptyPattern(elementdef, walker, element, data) {
    var name = elementdef.name, err = null;
    if(name === "text") {
      err = validateText(elementdef, walker, element)
    }else {
      if(name === "data") {
        err = null
      }else {
        if(name === "value") {
          if(data !== elementdef.text) {
            err = [new RelaxNGParseError("Wrong value, should be '" + elementdef.text + "', not '" + data + "'", element)]
          }
        }else {
          if(name === "list") {
            err = null
          }else {
            if(name === "attribute") {
              err = validateAttribute(elementdef, walker, element)
            }else {
              if(name === "element") {
                err = validateElement(elementdef, walker, element)
              }else {
                if(name === "oneOrMore") {
                  err = validateOneOrMore(elementdef, walker, element)
                }else {
                  if(name === "choice") {
                    err = validateChoice(elementdef, walker, element, data)
                  }else {
                    if(name === "group") {
                      err = validateGroup(elementdef, walker, element)
                    }else {
                      if(name === "interleave") {
                        err = validateInterleave(elementdef, walker, element)
                      }else {
                        throw name + " not allowed in nonEmptyPattern.";
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return err
  };
  this.validate = function validate(walker, callback) {
    walker.currentNode = walker.root;
    var errors = validatePattern(start.e[0], walker, walker.root);
    callback(errors)
  };
  this.init = function init(start1, nsmap1) {
    start = start1;
    nsmap = nsmap1
  }
};
xmldom.OperationalTransformInterface = function() {
};
xmldom.OperationalTransformInterface.prototype.retain = function(amount) {
};
xmldom.OperationalTransformInterface.prototype.insertCharacters = function(chars) {
};
xmldom.OperationalTransformInterface.prototype.insertElementStart = function(tagname, attributes) {
};
xmldom.OperationalTransformInterface.prototype.insertElementEnd = function() {
};
xmldom.OperationalTransformInterface.prototype.deleteCharacters = function(amount) {
};
xmldom.OperationalTransformInterface.prototype.deleteElementStart = function() {
};
xmldom.OperationalTransformInterface.prototype.deleteElementEnd = function() {
};
xmldom.OperationalTransformInterface.prototype.replaceAttributes = function(atts) {
};
xmldom.OperationalTransformInterface.prototype.updateAttributes = function(atts) {
};
xmldom.OperationalTransformDOM = function OperationalTransformDOM(root, serializer) {
  var pos, length;
  function retain(amount) {
  }
  function insertCharacters(chars) {
  }
  function insertElementStart(tagname, attributes) {
  }
  function insertElementEnd() {
  }
  function deleteCharacters(amount) {
  }
  function deleteElementStart() {
  }
  function deleteElementEnd() {
  }
  function replaceAttributes(atts) {
  }
  function updateAttributes(atts) {
  }
  function atEnd() {
    return pos === length
  }
  this.retain = retain;
  this.insertCharacters = insertCharacters;
  this.insertElementStart = insertElementStart;
  this.insertElementEnd = insertElementEnd;
  this.deleteCharacters = deleteCharacters;
  this.deleteElementStart = deleteElementStart;
  this.deleteElementEnd = deleteElementEnd;
  this.replaceAttributes = replaceAttributes;
  this.updateAttributes = updateAttributes;
  this.atEnd = atEnd
};
xmldom.XPath = function() {
  var createXPathPathIterator, parsePredicates;
  function isSmallestPositive(a, b, c) {
    return a !== -1 && (a < b || b === -1) && (a < c || c === -1)
  }
  function parseXPathStep(xpath, pos, end, steps) {
    var location = "", predicates = [], value, brapos = xpath.indexOf("[", pos), slapos = xpath.indexOf("/", pos), eqpos = xpath.indexOf("=", pos), depth = 0, start = 0;
    if(isSmallestPositive(slapos, brapos, eqpos)) {
      location = xpath.substring(pos, slapos);
      pos = slapos + 1
    }else {
      if(isSmallestPositive(brapos, slapos, eqpos)) {
        location = xpath.substring(pos, brapos);
        pos = parsePredicates(xpath, brapos, predicates)
      }else {
        if(isSmallestPositive(eqpos, slapos, brapos)) {
          location = xpath.substring(pos, eqpos);
          pos = eqpos
        }else {
          location = xpath.substring(pos, end);
          pos = end
        }
      }
    }
    steps.push({location:location, predicates:predicates});
    return pos
  }
  function parseXPath(xpath) {
    var steps = [], p = 0, end = xpath.length, value;
    while(p < end) {
      p = parseXPathStep(xpath, p, end, steps);
      if(p < end && xpath[p] === "=") {
        value = xpath.substring(p + 1, end);
        if(value.length > 2 && (value[0] === "'" || value[0] === '"')) {
          value = value.slice(1, value.length - 1)
        }else {
          try {
            value = parseInt(value, 10)
          }catch(e) {
          }
        }
        p = end
      }
    }
    return{steps:steps, value:value}
  }
  parsePredicates = function parsePredicates(xpath, start, predicates) {
    var pos = start, l = xpath.length, selector, depth = 0;
    while(pos < l) {
      if(xpath[pos] === "]") {
        depth -= 1;
        if(depth <= 0) {
          predicates.push(parseXPath(xpath.substring(start, pos)))
        }
      }else {
        if(xpath[pos] === "[") {
          if(depth <= 0) {
            start = pos + 1
          }
          depth += 1
        }
      }
      pos += 1
    }
    return pos
  };
  function XPathIterator() {
  }
  XPathIterator.prototype.next = function() {
  };
  XPathIterator.prototype.reset = function() {
  };
  function NodeIterator() {
    var node, done = false;
    this.setNode = function setNode(n) {
      node = n
    };
    this.reset = function() {
      done = false
    };
    this.next = function next() {
      var val = done ? null : node;
      done = true;
      return val
    }
  }
  function AttributeIterator(it, namespace, localName) {
    this.reset = function reset() {
      it.reset()
    };
    this.next = function next() {
      var node = it.next(), attr;
      while(node) {
        node = node.getAttributeNodeNS(namespace, localName);
        if(node) {
          return node
        }
        node = it.next()
      }
      return node
    }
  }
  function AllChildElementIterator(it, recurse) {
    var root = it.next(), node = null;
    this.reset = function reset() {
      it.reset();
      root = it.next();
      node = null
    };
    this.next = function next() {
      while(root) {
        if(node) {
          if(recurse && node.firstChild) {
            node = node.firstChild
          }else {
            while(!node.nextSibling && node !== root) {
              node = node.parentNode
            }
            if(node === root) {
              root = it.next()
            }else {
              node = node.nextSibling
            }
          }
        }else {
          do {
            node = root.firstChild;
            if(!node) {
              root = it.next()
            }
          }while(root && !node)
        }
        if(node && node.nodeType === 1) {
          return node
        }
      }
      return null
    }
  }
  function ConditionIterator(it, condition) {
    this.reset = function reset() {
      it.reset()
    };
    this.next = function next() {
      var n = it.next();
      while(n && !condition(n)) {
        n = it.next()
      }
      return n
    }
  }
  function createNodenameFilter(it, name, namespaceResolver) {
    var s = name.split(":", 2), namespace = namespaceResolver(s[0]), localName = s[1];
    return new ConditionIterator(it, function(node) {
      return node.localName === localName && node.namespaceURI === namespace
    })
  }
  function createPredicateFilteredIterator(it, p, namespaceResolver) {
    var nit = new NodeIterator, pit = createXPathPathIterator(nit, p, namespaceResolver), value = p.value;
    if(value === undefined) {
      return new ConditionIterator(it, function(node) {
        nit.setNode(node);
        pit.reset();
        return pit.next()
      })
    }
    return new ConditionIterator(it, function(node) {
      nit.setNode(node);
      pit.reset();
      var n = pit.next();
      return n && n.nodeValue === value
    })
  }
  createXPathPathIterator = function createXPathPathIterator(it, xpath, namespaceResolver) {
    var i, j, step, location, namespace, localName, prefix, p;
    for(i = 0;i < xpath.steps.length;i += 1) {
      step = xpath.steps[i];
      location = step.location;
      if(location === "") {
        it = new AllChildElementIterator(it, false)
      }else {
        if(location[0] === "@") {
          p = location.slice(1).split(":", 2);
          it = new AttributeIterator(it, namespaceResolver(p[0]), p[1])
        }else {
          if(location !== ".") {
            it = new AllChildElementIterator(it, false);
            if(location.indexOf(":") !== -1) {
              it = createNodenameFilter(it, location, namespaceResolver)
            }
          }
        }
      }
      for(j = 0;j < step.predicates.length;j += 1) {
        p = step.predicates[j];
        it = createPredicateFilteredIterator(it, p, namespaceResolver)
      }
    }
    return it
  };
  function fallback(node, xpath, namespaceResolver) {
    var it = new NodeIterator, i, nodelist, parsedXPath, pos;
    it.setNode(node);
    parsedXPath = parseXPath(xpath);
    it = createXPathPathIterator(it, parsedXPath, namespaceResolver);
    nodelist = [];
    i = it.next();
    while(i) {
      nodelist.push(i);
      i = it.next()
    }
    return nodelist
  }
  function getODFElementsWithXPath(node, xpath, namespaceResolver) {
    var doc = node.ownerDocument, nodes, elements = [], n = null;
    if(!doc || !doc.evaluate || !n) {
      elements = fallback(node, xpath, namespaceResolver)
    }else {
      nodes = doc.evaluate(xpath, node, namespaceResolver, XPathResult.UNORDERED_NODE_ITERATOR_TYPE, null);
      n = nodes.iterateNext();
      while(n !== null) {
        if(n.nodeType === 1) {
          elements.push(n)
        }
        n = nodes.iterateNext()
      }
    }
    return elements
  }
  xmldom.XPath = function XPath() {
    this.getODFElementsWithXPath = getODFElementsWithXPath
  };
  return xmldom.XPath
}();
odf.StyleInfo = function StyleInfo() {
  var chartns = "urn:oasis:names:tc:opendocument:xmlns:chart:1.0", dbns = "urn:oasis:names:tc:opendocument:xmlns:database:1.0", dr3dns = "urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0", drawns = "urn:oasis:names:tc:opendocument:xmlns:drawing:1.0", fons = "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0", formns = "urn:oasis:names:tc:opendocument:xmlns:form:1.0", numberns = "urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0", officens = "urn:oasis:names:tc:opendocument:xmlns:office:1.0", 
  presentationns = "urn:oasis:names:tc:opendocument:xmlns:presentation:1.0", stylens = "urn:oasis:names:tc:opendocument:xmlns:style:1.0", svgns = "urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0", tablens = "urn:oasis:names:tc:opendocument:xmlns:table:1.0", textns = "urn:oasis:names:tc:opendocument:xmlns:text:1.0", elementstyles = {"text":[{ens:stylens, en:"tab-stop", ans:stylens, a:"leader-text-style"}, {ens:stylens, en:"drop-cap", ans:stylens, a:"style-name"}, {ens:textns, en:"notes-configuration", 
  ans:textns, a:"citation-body-style-name"}, {ens:textns, en:"notes-configuration", ans:textns, a:"citation-style-name"}, {ens:textns, en:"a", ans:textns, a:"style-name"}, {ens:textns, en:"alphabetical-index", ans:textns, a:"style-name"}, {ens:textns, en:"linenumbering-configuration", ans:textns, a:"style-name"}, {ens:textns, en:"list-level-style-number", ans:textns, a:"style-name"}, {ens:textns, en:"ruby-text", ans:textns, a:"style-name"}, {ens:textns, en:"span", ans:textns, a:"style-name"}, {ens:textns, 
  en:"a", ans:textns, a:"visited-style-name"}, {ens:stylens, en:"text-properties", ans:stylens, a:"text-line-through-text-style"}, {ens:textns, en:"alphabetical-index-source", ans:textns, a:"main-entry-style-name"}, {ens:textns, en:"index-entry-bibliography", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-chapter", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-link-end", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-link-start", ans:textns, a:"style-name"}, {ens:textns, 
  en:"index-entry-page-number", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-span", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-tab-stop", ans:textns, a:"style-name"}, {ens:textns, en:"index-entry-text", ans:textns, a:"style-name"}, {ens:textns, en:"index-title-template", ans:textns, a:"style-name"}, {ens:textns, en:"list-level-style-bullet", ans:textns, a:"style-name"}, {ens:textns, en:"outline-level-style", ans:textns, a:"style-name"}], "paragraph":[{ens:drawns, en:"caption", 
  ans:drawns, a:"text-style-name"}, {ens:drawns, en:"circle", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"connector", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"control", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"custom-shape", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"ellipse", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"frame", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"line", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"measure", ans:drawns, 
  a:"text-style-name"}, {ens:drawns, en:"path", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"polygon", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"polyline", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"rect", ans:drawns, a:"text-style-name"}, {ens:drawns, en:"regular-polygon", ans:drawns, a:"text-style-name"}, {ens:officens, en:"annotation", ans:drawns, a:"text-style-name"}, {ens:formns, en:"column", ans:formns, a:"text-style-name"}, {ens:stylens, en:"style", ans:stylens, a:"next-style-name"}, 
  {ens:tablens, en:"body", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"even-columns", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"even-rows", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"first-column", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"first-row", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"last-column", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"last-row", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, 
  en:"odd-columns", ans:tablens, a:"paragraph-style-name"}, {ens:tablens, en:"odd-rows", ans:tablens, a:"paragraph-style-name"}, {ens:textns, en:"notes-configuration", ans:textns, a:"default-style-name"}, {ens:textns, en:"alphabetical-index-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"bibliography-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"h", ans:textns, a:"style-name"}, {ens:textns, en:"illustration-index-entry-template", ans:textns, a:"style-name"}, {ens:textns, 
  en:"index-source-style", ans:textns, a:"style-name"}, {ens:textns, en:"object-index-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"p", ans:textns, a:"style-name"}, {ens:textns, en:"table-index-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"table-of-content-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"table-index-entry-template", ans:textns, a:"style-name"}, {ens:textns, en:"user-index-entry-template", ans:textns, a:"style-name"}, {ens:stylens, en:"page-layout-properties", 
  ans:stylens, a:"register-truth-ref-style-name"}], "chart":[{ens:chartns, en:"axis", ans:chartns, a:"style-name"}, {ens:chartns, en:"chart", ans:chartns, a:"style-name"}, {ens:chartns, en:"data-label", ans:chartns, a:"style-name"}, {ens:chartns, en:"data-point", ans:chartns, a:"style-name"}, {ens:chartns, en:"equation", ans:chartns, a:"style-name"}, {ens:chartns, en:"error-indicator", ans:chartns, a:"style-name"}, {ens:chartns, en:"floor", ans:chartns, a:"style-name"}, {ens:chartns, en:"footer", 
  ans:chartns, a:"style-name"}, {ens:chartns, en:"grid", ans:chartns, a:"style-name"}, {ens:chartns, en:"legend", ans:chartns, a:"style-name"}, {ens:chartns, en:"mean-value", ans:chartns, a:"style-name"}, {ens:chartns, en:"plot-area", ans:chartns, a:"style-name"}, {ens:chartns, en:"regression-curve", ans:chartns, a:"style-name"}, {ens:chartns, en:"series", ans:chartns, a:"style-name"}, {ens:chartns, en:"stock-gain-marker", ans:chartns, a:"style-name"}, {ens:chartns, en:"stock-loss-marker", ans:chartns, 
  a:"style-name"}, {ens:chartns, en:"stock-range-line", ans:chartns, a:"style-name"}, {ens:chartns, en:"subtitle", ans:chartns, a:"style-name"}, {ens:chartns, en:"title", ans:chartns, a:"style-name"}, {ens:chartns, en:"wall", ans:chartns, a:"style-name"}], "section":[{ens:textns, en:"alphabetical-index", ans:textns, a:"style-name"}, {ens:textns, en:"bibliography", ans:textns, a:"style-name"}, {ens:textns, en:"illustration-index", ans:textns, a:"style-name"}, {ens:textns, en:"index-title", ans:textns, 
  a:"style-name"}, {ens:textns, en:"object-index", ans:textns, a:"style-name"}, {ens:textns, en:"section", ans:textns, a:"style-name"}, {ens:textns, en:"table-of-content", ans:textns, a:"style-name"}, {ens:textns, en:"table-index", ans:textns, a:"style-name"}, {ens:textns, en:"user-index", ans:textns, a:"style-name"}], "ruby":[{ens:textns, en:"ruby", ans:textns, a:"style-name"}], "table":[{ens:dbns, en:"query", ans:dbns, a:"style-name"}, {ens:dbns, en:"table-representation", ans:dbns, a:"style-name"}, 
  {ens:tablens, en:"background", ans:tablens, a:"style-name"}, {ens:tablens, en:"table", ans:tablens, a:"style-name"}], "table-column":[{ens:dbns, en:"column", ans:dbns, a:"style-name"}, {ens:tablens, en:"table-column", ans:tablens, a:"style-name"}], "table-row":[{ens:dbns, en:"query", ans:dbns, a:"default-row-style-name"}, {ens:dbns, en:"table-representation", ans:dbns, a:"default-row-style-name"}, {ens:tablens, en:"table-row", ans:tablens, a:"style-name"}], "table-cell":[{ens:dbns, en:"column", 
  ans:dbns, a:"default-cell-style-name"}, {ens:tablens, en:"table-column", ans:tablens, a:"default-cell-style-name"}, {ens:tablens, en:"table-row", ans:tablens, a:"default-cell-style-name"}, {ens:tablens, en:"body", ans:tablens, a:"style-name"}, {ens:tablens, en:"covered-table-cell", ans:tablens, a:"style-name"}, {ens:tablens, en:"even-columns", ans:tablens, a:"style-name"}, {ens:tablens, en:"covered-table-cell", ans:tablens, a:"style-name"}, {ens:tablens, en:"even-columns", ans:tablens, a:"style-name"}, 
  {ens:tablens, en:"even-rows", ans:tablens, a:"style-name"}, {ens:tablens, en:"first-column", ans:tablens, a:"style-name"}, {ens:tablens, en:"first-row", ans:tablens, a:"style-name"}, {ens:tablens, en:"last-column", ans:tablens, a:"style-name"}, {ens:tablens, en:"last-row", ans:tablens, a:"style-name"}, {ens:tablens, en:"odd-columns", ans:tablens, a:"style-name"}, {ens:tablens, en:"odd-rows", ans:tablens, a:"style-name"}, {ens:tablens, en:"table-cell", ans:tablens, a:"style-name"}], "graphic":[{ens:dr3dns, 
  en:"cube", ans:drawns, a:"style-name"}, {ens:dr3dns, en:"extrude", ans:drawns, a:"style-name"}, {ens:dr3dns, en:"rotate", ans:drawns, a:"style-name"}, {ens:dr3dns, en:"scene", ans:drawns, a:"style-name"}, {ens:dr3dns, en:"sphere", ans:drawns, a:"style-name"}, {ens:drawns, en:"caption", ans:drawns, a:"style-name"}, {ens:drawns, en:"circle", ans:drawns, a:"style-name"}, {ens:drawns, en:"connector", ans:drawns, a:"style-name"}, {ens:drawns, en:"control", ans:drawns, a:"style-name"}, {ens:drawns, en:"custom-shape", 
  ans:drawns, a:"style-name"}, {ens:drawns, en:"ellipse", ans:drawns, a:"style-name"}, {ens:drawns, en:"frame", ans:drawns, a:"style-name"}, {ens:drawns, en:"g", ans:drawns, a:"style-name"}, {ens:drawns, en:"line", ans:drawns, a:"style-name"}, {ens:drawns, en:"measure", ans:drawns, a:"style-name"}, {ens:drawns, en:"page-thumbnail", ans:drawns, a:"style-name"}, {ens:drawns, en:"path", ans:drawns, a:"style-name"}, {ens:drawns, en:"polygon", ans:drawns, a:"style-name"}, {ens:drawns, en:"polyline", ans:drawns, 
  a:"style-name"}, {ens:drawns, en:"rect", ans:drawns, a:"style-name"}, {ens:drawns, en:"regular-polygon", ans:drawns, a:"style-name"}, {ens:officens, en:"annotation", ans:drawns, a:"style-name"}], "presentation":[{ens:dr3dns, en:"cube", ans:presentationns, a:"style-name"}, {ens:dr3dns, en:"extrude", ans:presentationns, a:"style-name"}, {ens:dr3dns, en:"rotate", ans:presentationns, a:"style-name"}, {ens:dr3dns, en:"scene", ans:presentationns, a:"style-name"}, {ens:dr3dns, en:"sphere", ans:presentationns, 
  a:"style-name"}, {ens:drawns, en:"caption", ans:presentationns, a:"style-name"}, {ens:drawns, en:"circle", ans:presentationns, a:"style-name"}, {ens:drawns, en:"connector", ans:presentationns, a:"style-name"}, {ens:drawns, en:"control", ans:presentationns, a:"style-name"}, {ens:drawns, en:"custom-shape", ans:presentationns, a:"style-name"}, {ens:drawns, en:"ellipse", ans:presentationns, a:"style-name"}, {ens:drawns, en:"frame", ans:presentationns, a:"style-name"}, {ens:drawns, en:"g", ans:presentationns, 
  a:"style-name"}, {ens:drawns, en:"line", ans:presentationns, a:"style-name"}, {ens:drawns, en:"measure", ans:presentationns, a:"style-name"}, {ens:drawns, en:"page-thumbnail", ans:presentationns, a:"style-name"}, {ens:drawns, en:"path", ans:presentationns, a:"style-name"}, {ens:drawns, en:"polygon", ans:presentationns, a:"style-name"}, {ens:drawns, en:"polyline", ans:presentationns, a:"style-name"}, {ens:drawns, en:"rect", ans:presentationns, a:"style-name"}, {ens:drawns, en:"regular-polygon", 
  ans:presentationns, a:"style-name"}, {ens:officens, en:"annotation", ans:presentationns, a:"style-name"}], "drawing-page":[{ens:drawns, en:"page", ans:drawns, a:"style-name"}, {ens:presentationns, en:"notes", ans:drawns, a:"style-name"}, {ens:stylens, en:"handout-master", ans:drawns, a:"style-name"}, {ens:stylens, en:"master-page", ans:drawns, a:"style-name"}], "list-style":[{ens:textns, en:"list", ans:textns, a:"style-name"}, {ens:textns, en:"numbered-paragraph", ans:textns, a:"style-name"}, {ens:textns, 
  en:"list-item", ans:textns, a:"style-override"}, {ens:stylens, en:"style", ans:stylens, a:"list-style-name"}, {ens:stylens, en:"style", ans:stylens, a:"data-style-name"}, {ens:stylens, en:"style", ans:stylens, a:"percentage-data-style-name"}, {ens:presentationns, en:"date-time-decl", ans:stylens, a:"data-style-name"}, {ens:textns, en:"creation-date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"creation-time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"database-display", ans:stylens, 
  a:"data-style-name"}, {ens:textns, en:"date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"editing-duration", ans:stylens, a:"data-style-name"}, {ens:textns, en:"expression", ans:stylens, a:"data-style-name"}, {ens:textns, en:"meta-field", ans:stylens, a:"data-style-name"}, {ens:textns, en:"modification-date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"modification-time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"print-date", ans:stylens, a:"data-style-name"}, {ens:textns, 
  en:"print-time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"table-formula", ans:stylens, a:"data-style-name"}, {ens:textns, en:"time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-defined", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-field-get", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-field-input", ans:stylens, a:"data-style-name"}, {ens:textns, en:"variable-get", ans:stylens, a:"data-style-name"}, {ens:textns, en:"variable-input", ans:stylens, 
  a:"data-style-name"}, {ens:textns, en:"variable-set", ans:stylens, a:"data-style-name"}], "data":[{ens:stylens, en:"style", ans:stylens, a:"data-style-name"}, {ens:stylens, en:"style", ans:stylens, a:"percentage-data-style-name"}, {ens:presentationns, en:"date-time-decl", ans:stylens, a:"data-style-name"}, {ens:textns, en:"creation-date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"creation-time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"database-display", ans:stylens, a:"data-style-name"}, 
  {ens:textns, en:"date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"editing-duration", ans:stylens, a:"data-style-name"}, {ens:textns, en:"expression", ans:stylens, a:"data-style-name"}, {ens:textns, en:"meta-field", ans:stylens, a:"data-style-name"}, {ens:textns, en:"modification-date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"modification-time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"print-date", ans:stylens, a:"data-style-name"}, {ens:textns, en:"print-time", 
  ans:stylens, a:"data-style-name"}, {ens:textns, en:"table-formula", ans:stylens, a:"data-style-name"}, {ens:textns, en:"time", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-defined", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-field-get", ans:stylens, a:"data-style-name"}, {ens:textns, en:"user-field-input", ans:stylens, a:"data-style-name"}, {ens:textns, en:"variable-get", ans:stylens, a:"data-style-name"}, {ens:textns, en:"variable-input", ans:stylens, a:"data-style-name"}, 
  {ens:textns, en:"variable-set", ans:stylens, a:"data-style-name"}], "page-layout":[{ens:presentationns, en:"notes", ans:stylens, a:"page-layout-name"}, {ens:stylens, en:"handout-master", ans:stylens, a:"page-layout-name"}, {ens:stylens, en:"master-page", ans:stylens, a:"page-layout-name"}]}, elements;
  function canElementHaveStyle(family, element) {
    var elname = elements[element.localName], elns = elname && elname[element.namespaceURI], length = elns ? elns.length : 0, i;
    return elns && elns.length > 0
  }
  function getStyleRef(family, element) {
    var elname = elements[element.localName], elns = elname && elname[element.namespaceURI], length = elns ? elns.length : 0, i, attr;
    for(i = 0;i < length;i += 1) {
      attr = element.getAttributeNS(elns[i].ns, elns[i].localname)
    }
    return null
  }
  function getUsedStylesForAutomatic(element, keys) {
    var elname = elements[element.localName], elns = elname && elname[element.namespaceURI], length = elns ? elns.length : 0, i, attr, group, map, e;
    for(i = 0;i < length;i += 1) {
      attr = element.getAttributeNS(elns[i].ns, elns[i].localname);
      if(attr) {
        group = elns[i].keygroup;
        map = keys[group];
        if(!map) {
          map = keys[group] = {}
        }
        map[attr] = 1
      }
    }
    i = element.firstChild;
    while(i) {
      if(i.nodeType === 1) {
        e = i;
        getUsedStylesForAutomatic(e, keys)
      }
      i = i.nextSibling
    }
  }
  function inverse(elementstyles) {
    var keyname, i, list, item, l, elements = {}, map, array;
    for(keyname in elementstyles) {
      if(elementstyles.hasOwnProperty(keyname)) {
        list = elementstyles[keyname];
        l = list.length;
        for(i = 0;i < l;i += 1) {
          item = list[i];
          map = elements[item.en] = elements[item.en] || {};
          array = map[item.ens] = map[item.ens] || [];
          array.push({ns:item.ans, localname:item.a, keygroup:keyname})
        }
      }
    }
    return elements
  }
  this.UsedKeysList = function(element) {
    var usedKeys = {};
    this.uses = function(element) {
      var localName = element.localName, name = element.getAttributeNS(drawns, "name") || element.getAttributeNS(stylens, "name"), keyName, map;
      if(localName === "style") {
        keyName = element.getAttributeNS(stylens, "family")
      }else {
        if(element.namespaceURI === numberns) {
          keyName = "data"
        }else {
          keyName = localName
        }
      }
      map = usedKeys[keyName];
      return map ? map[name] > 0 : false
    };
    getUsedStylesForAutomatic(element, usedKeys)
  };
  this.canElementHaveStyle = canElementHaveStyle;
  elements = inverse(elementstyles)
};
odf.Style2CSS = function Style2CSS() {
  var xlinkns = "http://www.w3.org/1999/xlink", drawns = "urn:oasis:names:tc:opendocument:xmlns:drawing:1.0", fons = "urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0", officens = "urn:oasis:names:tc:opendocument:xmlns:office:1.0", presentationns = "urn:oasis:names:tc:opendocument:xmlns:presentation:1.0", stylens = "urn:oasis:names:tc:opendocument:xmlns:style:1.0", svgns = "urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0", tablens = "urn:oasis:names:tc:opendocument:xmlns:table:1.0", 
  textns = "urn:oasis:names:tc:opendocument:xmlns:text:1.0", namespaces = {"draw":drawns, "fo":fons, "office":officens, "presentation":presentationns, "style":stylens, "svg":svgns, "table":tablens, "text":textns, "xlink":xlinkns}, familynamespaceprefixes = {"graphic":"draw", "paragraph":"text", "presentation":"presentation", "ruby":"text", "section":"text", "table":"table", "table-cell":"table", "table-column":"table", "table-row":"table", "text":"text", "list":"text"}, familytagnames = {"graphic":["circle", 
  "connected", "control", "custom-shape", "ellipse", "frame", "g", "line", "measure", "page", "page-thumbnail", "path", "polygon", "polyline", "rect", "regular-polygon"], "paragraph":["alphabetical-index-entry-template", "h", "illustration-index-entry-template", "index-source-style", "object-index-entry-template", "p", "table-index-entry-template", "table-of-content-entry-template", "user-index-entry-template"], "presentation":["caption", "circle", "connector", "control", "custom-shape", "ellipse", 
  "frame", "g", "line", "measure", "page-thumbnail", "path", "polygon", "polyline", "rect", "regular-polygon"], "ruby":["ruby", "ruby-text"], "section":["alphabetical-index", "bibliography", "illustration-index", "index-title", "object-index", "section", "table-of-content", "table-index", "user-index"], "table":["background", "table"], "table-cell":["body", "covered-table-cell", "even-columns", "even-rows", "first-column", "first-row", "last-column", "last-row", "odd-columns", "odd-rows", "table-cell"], 
  "table-column":["table-column"], "table-row":["table-row"], "text":["a", "index-entry-chapter", "index-entry-link-end", "index-entry-link-start", "index-entry-page-number", "index-entry-span", "index-entry-tab-stop", "index-entry-text", "index-title-template", "linenumbering-configuration", "list-level-style-number", "list-level-style-bullet", "outline-level-style", "span"], "list":["list-item"]}, textPropertySimpleMapping = [[fons, "color", "color"], [fons, "background-color", "background-color"], 
  [fons, "font-weight", "font-weight"], [fons, "font-style", "font-style"], [fons, "font-size", "font-size"]], bgImageSimpleMapping = [[stylens, "repeat", "background-repeat"]], paragraphPropertySimpleMapping = [[fons, "background-color", "background-color"], [fons, "text-align", "text-align"], [fons, "padding-left", "padding-left"], [fons, "padding-right", "padding-right"], [fons, "padding-top", "padding-top"], [fons, "padding-bottom", "padding-bottom"], [fons, "border-left", "border-left"], [fons, 
  "border-right", "border-right"], [fons, "border-top", "border-top"], [fons, "border-bottom", "border-bottom"], [fons, "margin-left", "margin-left"], [fons, "margin-right", "margin-right"], [fons, "margin-top", "margin-top"], [fons, "margin-bottom", "margin-bottom"], [fons, "border", "border"]], graphicPropertySimpleMapping = [[drawns, "fill-color", "background-color"], [drawns, "fill", "background"], [fons, "min-height", "min-height"], [drawns, "stroke", "border"], [svgns, "stroke-color", "border-color"]], 
  tablecellPropertySimpleMapping = [[fons, "background-color", "background-color"], [fons, "border-left", "border-left"], [fons, "border-right", "border-right"], [fons, "border-top", "border-top"], [fons, "border-bottom", "border-bottom"]];
  function namespaceResolver(prefix) {
    return namespaces[prefix] || null
  }
  function getStyleMap(doc, stylesnode) {
    var stylemap = {}, node, name, family, map;
    if(!stylesnode) {
      return stylemap
    }
    node = stylesnode.firstChild;
    while(node) {
      if(node.namespaceURI === stylens && node.localName === "style") {
        family = node.getAttributeNS(stylens, "family")
      }else {
        if(node.namespaceURI === textns && node.localName === "list-style") {
          family = "list"
        }
      }
      name = family && node.getAttributeNS && node.getAttributeNS(stylens, "name");
      if(name) {
        if(!stylemap[family]) {
          stylemap[family] = {}
        }
        stylemap[family][name] = node
      }
      node = node.nextSibling
    }
    return stylemap
  }
  function findStyle(stylestree, name) {
    if(!name || !stylestree) {
      return null
    }
    if(stylestree[name]) {
      return stylestree[name]
    }
    var derivedStyles = stylestree.derivedStyles, n, style;
    for(n in stylestree) {
      if(stylestree.hasOwnProperty(n)) {
        style = findStyle(stylestree[n].derivedStyles, name);
        if(style) {
          return style
        }
      }
    }
    return null
  }
  function addStyleToStyleTree(stylename, stylesmap, stylestree) {
    var style = stylesmap[stylename], parentname, parentstyle;
    if(!style) {
      return
    }
    parentname = style.getAttributeNS(stylens, "parent-style-name");
    parentstyle = null;
    if(parentname) {
      parentstyle = findStyle(stylestree, parentname);
      if(!parentstyle && stylesmap[parentname]) {
        addStyleToStyleTree(parentname, stylesmap, stylestree);
        parentstyle = stylesmap[parentname];
        stylesmap[parentname] = null
      }
    }
    if(parentstyle) {
      if(!parentstyle.derivedStyles) {
        parentstyle.derivedStyles = {}
      }
      parentstyle.derivedStyles[stylename] = style
    }else {
      stylestree[stylename] = style
    }
  }
  function addStyleMapToStyleTree(stylesmap, stylestree) {
    var name;
    for(name in stylesmap) {
      if(stylesmap.hasOwnProperty(name)) {
        addStyleToStyleTree(name, stylesmap, stylestree);
        stylesmap[name] = null
      }
    }
  }
  function createSelector(family, name) {
    var prefix = familynamespaceprefixes[family], namepart, selector = "", first = true;
    if(prefix === null) {
      return null
    }
    namepart = "[" + prefix + '|style-name="' + name + '"]';
    if(prefix === "presentation") {
      prefix = "draw";
      namepart = '[presentation|style-name="' + name + '"]'
    }
    return prefix + "|" + familytagnames[family].join(namepart + "," + prefix + "|") + namepart
  }
  function getSelectors(family, name, node) {
    var selectors = [], n, ss, s;
    selectors.push(createSelector(family, name));
    for(n in node.derivedStyles) {
      if(node.derivedStyles.hasOwnProperty(n)) {
        ss = getSelectors(family, n, node.derivedStyles[n]);
        for(s in ss) {
          if(ss.hasOwnProperty(s)) {
            selectors.push(ss[s])
          }
        }
      }
    }
    return selectors
  }
  function getDirectChild(node, ns, name) {
    if(!node) {
      return null
    }
    var c = node.firstChild, e;
    while(c) {
      if(c.namespaceURI === ns && c.localName === name) {
        e = c;
        return e
      }
      c = c.nextSibling
    }
    return null
  }
  function applySimpleMapping(props, mapping) {
    var rule = "", r, value;
    for(r in mapping) {
      if(mapping.hasOwnProperty(r)) {
        r = mapping[r];
        value = props.getAttributeNS(r[0], r[1]);
        if(value) {
          rule += r[2] + ":" + value + ";"
        }
      }
    }
    return rule
  }
  function getFontDeclaration(name) {
    return'"' + name + '"'
  }
  function getTextProperties(props) {
    var rule = "", value;
    rule += applySimpleMapping(props, textPropertySimpleMapping);
    value = props.getAttributeNS(stylens, "text-underline-style");
    if(value === "solid") {
      rule += "text-decoration: underline;"
    }
    value = props.getAttributeNS(stylens, "font-name");
    if(value) {
      value = getFontDeclaration(value);
      if(value) {
        rule += "font-family: " + value + ";"
      }
    }
    return rule
  }
  function getParagraphProperties(props) {
    var rule = "", imageProps, url, element;
    rule += applySimpleMapping(props, paragraphPropertySimpleMapping);
    imageProps = props.getElementsByTagNameNS(stylens, "background-image");
    if(imageProps.length > 0) {
      url = imageProps.item(0).getAttributeNS(xlinkns, "href");
      if(url) {
        rule += "background-image: url('odfkit:" + url + "');";
        element = imageProps.item(0);
        rule += applySimpleMapping(element, bgImageSimpleMapping)
      }
    }
    return rule
  }
  function getGraphicProperties(props) {
    var rule = "";
    rule += applySimpleMapping(props, graphicPropertySimpleMapping);
    return rule
  }
  function getTableCellProperties(props) {
    var rule = "";
    rule += applySimpleMapping(props, tablecellPropertySimpleMapping);
    return rule
  }
  function addStyleRule(sheet, family, name, node) {
    var selectors = getSelectors(family, name, node), selector = selectors.join(","), rule = "", properties = getDirectChild(node, stylens, "text-properties");
    if(properties) {
      rule += getTextProperties(properties)
    }
    properties = getDirectChild(node, stylens, "paragraph-properties");
    if(properties) {
      rule += getParagraphProperties(properties)
    }
    properties = getDirectChild(node, stylens, "graphic-properties");
    if(properties) {
      rule += getGraphicProperties(properties)
    }
    properties = getDirectChild(node, stylens, "table-cell-properties");
    if(properties) {
      rule += getTableCellProperties(properties)
    }
    if(rule.length === 0) {
      return
    }
    rule = selector + "{" + rule + "}";
    try {
      sheet.insertRule(rule, sheet.cssRules.length)
    }catch(e) {
      throw e;
    }
  }
  function getNumberRule(node) {
    var style = node.getAttributeNS(stylens, "num-format"), suffix = node.getAttributeNS(stylens, "num-suffix"), prefix = node.getAttributeNS(stylens, "num-prefix"), rule = "", stylemap = {1:"decimal", "a":"lower-latin", "A":"upper-latin", "i":"lower-roman", "I":"upper-roman"}, content = "";
    content = prefix || "";
    if(stylemap.hasOwnProperty(style)) {
      content += " counter(list, " + stylemap[style] + ")"
    }else {
      if(style) {
        content += "'" + style + "';"
      }else {
        content += " ''"
      }
    }
    if(suffix) {
      content += " '" + suffix + "'"
    }
    rule = "content: " + content + ";";
    return rule
  }
  function getImageRule(node) {
    var rule = "content: none;";
    return rule
  }
  function getBulletRule(node) {
    var rule = "", bulletChar = node.getAttributeNS(textns, "bullet-char");
    return"content: '" + bulletChar + "';"
  }
  function addListStyleRule(sheet, name, node, itemrule) {
    var selector = 'text|list[text|style-name="' + name + '"]', level = node.getAttributeNS(textns, "level"), rule = "";
    level = level && parseInt(level, 10);
    while(level > 1) {
      selector += " > text|list-item > text|list";
      level -= 1
    }
    selector += " > list-item:before";
    rule = itemrule;
    rule = selector + "{" + rule + "}";
    try {
      sheet.insertRule(rule, sheet.cssRules.length)
    }catch(e) {
      throw e;
    }
  }
  function addListStyleRules(sheet, name, node) {
    var n = node.firstChild, e, itemrule;
    while(n) {
      if(n.namespaceURI === textns) {
        e = n;
        if(n.localName === "list-level-style-number") {
          itemrule = getNumberRule(e);
          addListStyleRule(sheet, name, e, itemrule)
        }else {
          if(n.localName === "list-level-style-image") {
            itemrule = getImageRule(e);
            addListStyleRule(sheet, name, e, itemrule)
          }else {
            if(n.localName === "list-level-style-bullet") {
              itemrule = getBulletRule(e);
              addListStyleRule(sheet, name, e, itemrule)
            }
          }
        }
      }
      n = n.nextSibling
    }
  }
  function addRule(sheet, family, name, node) {
    if(family === "list") {
      addListStyleRules(sheet, name, node)
    }else {
      addStyleRule(sheet, family, name, node)
    }
  }
  function addRules(sheet, family, name, node) {
    addRule(sheet, family, name, node);
    var n;
    for(n in node.derivedStyles) {
      if(node.derivedStyles.hasOwnProperty(n)) {
        addRules(sheet, family, n, node.derivedStyles[n])
      }
    }
  }
  this.namespaces = namespaces;
  this.namespaceResolver = namespaceResolver;
  this.namespaceResolver.lookupNamespaceURI = this.namespaceResolver;
  this.style2css = function(stylesheet, styles, autostyles) {
    var doc, prefix, styletree, tree, name, rule, family, stylenodes, styleautonodes;
    while(stylesheet.cssRules.length) {
      stylesheet.deleteRule(stylesheet.cssRules.length - 1)
    }
    doc = null;
    if(styles) {
      doc = styles.ownerDocument
    }
    if(autostyles) {
      doc = autostyles.ownerDocument
    }
    if(!doc) {
      return
    }
    for(prefix in namespaces) {
      if(namespaces.hasOwnProperty(prefix)) {
        rule = "@namespace " + prefix + " url(" + namespaces[prefix] + ");";
        try {
          stylesheet.insertRule(rule, stylesheet.cssRules.length)
        }catch(e) {
        }
      }
    }
    stylenodes = getStyleMap(doc, styles);
    styleautonodes = getStyleMap(doc, autostyles);
    styletree = {};
    for(family in familynamespaceprefixes) {
      if(familynamespaceprefixes.hasOwnProperty(family)) {
        tree = styletree[family] = {};
        addStyleMapToStyleTree(stylenodes[family], tree);
        addStyleMapToStyleTree(styleautonodes[family], tree);
        for(name in tree) {
          if(tree.hasOwnProperty(name)) {
            addRules(stylesheet, family, name, tree[name])
          }
        }
      }
    }
  }
};
runtime.loadClass("core.Base64");
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");
odf.FontLoader = function() {
  var style2CSS = new odf.Style2CSS, xpath = new xmldom.XPath, base64 = new core.Base64;
  function getEmbeddedFontDeclarations(fontFaceDecls) {
    var decls = {}, fonts, i, font, name, uris, href;
    if(!fontFaceDecls) {
      return decls
    }
    fonts = xpath.getODFElementsWithXPath(fontFaceDecls, "style:font-face[svg:font-face-src]", style2CSS.namespaceResolver);
    for(i = 0;i < fonts.length;i += 1) {
      font = fonts[i];
      name = font.getAttributeNS(style2CSS.namespaces["style"], "name");
      uris = xpath.getODFElementsWithXPath(font, "svg:font-face-src/svg:font-face-uri", style2CSS.namespaceResolver);
      if(uris.length > 0) {
        href = uris[0].getAttributeNS(style2CSS.namespaces["xlink"], "href");
        decls[name] = {href:href}
      }
    }
    return decls
  }
  function addFontToCSS(name, font, fontdata, stylesheet) {
    stylesheet = document.styleSheets[0];
    var rule = '@font-face { font-family: "' + name + '"; src: ' + "url(data:application/x-font-ttf;charset=binary;base64," + base64.convertUTF8ArrayToBase64(fontdata) + ') format("truetype"); }';
    try {
      stylesheet.insertRule(rule, stylesheet.cssRules.length)
    }catch(e) {
      runtime.log("Problem inserting rule in CSS: " + rule)
    }
  }
  function loadFontIntoCSS(embeddedFontDeclarations, zip, pos, stylesheet, callback) {
    var name, i = 0, n;
    for(n in embeddedFontDeclarations) {
      if(embeddedFontDeclarations.hasOwnProperty(n)) {
        if(i === pos) {
          name = n
        }
        i += 1
      }
    }
    if(!name) {
      return callback()
    }
    zip.load(embeddedFontDeclarations[name].href, function(err, fontdata) {
      if(err) {
        runtime.log(err)
      }else {
        addFontToCSS(name, embeddedFontDeclarations[name], fontdata, stylesheet)
      }
      return loadFontIntoCSS(embeddedFontDeclarations, zip, pos + 1, stylesheet, callback)
    })
  }
  function loadFontsIntoCSS(embeddedFontDeclarations, zip, stylesheet) {
    loadFontIntoCSS(embeddedFontDeclarations, zip, 0, stylesheet, function() {
    })
  }
  odf.FontLoader = function FontLoader() {
    var self = this;
    this.loadFonts = function(fontFaceDecls, zip, stylesheet) {
      var embeddedFontDeclarations = getEmbeddedFontDeclarations(fontFaceDecls);
      loadFontsIntoCSS(embeddedFontDeclarations, zip, stylesheet)
    }
  };
  return odf.FontLoader
}();
runtime.loadClass("core.Base64");
runtime.loadClass("core.Zip");
runtime.loadClass("xmldom.LSSerializer");
runtime.loadClass("odf.StyleInfo");
runtime.loadClass("odf.Style2CSS");
runtime.loadClass("odf.FontLoader");
odf.OdfContainer = function() {
  var styleInfo = new odf.StyleInfo, style2CSS = new odf.Style2CSS, namespaces = style2CSS.namespaces, officens = "urn:oasis:names:tc:opendocument:xmlns:office:1.0", manifestns = "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0", nodeorder = ["meta", "settings", "scripts", "font-face-decls", "styles", "automatic-styles", "master-styles", "body"], base64 = new core.Base64, fontLoader = new odf.FontLoader, partMimetypes = {};
  function getDirectChild(node, ns, name) {
    node = node ? node.firstChild : null;
    while(node) {
      if(node.localName === name && node.namespaceURI === ns) {
        return node
      }
      node = node.nextSibling
    }
    return null
  }
  function getNodePosition(child) {
    var childpos = 0, i, l = nodeorder.length;
    for(i = 0;i < l;i += 1) {
      if(child.namespaceURI === officens && child.localName === nodeorder[i]) {
        return i
      }
    }
    return-1
  }
  function OdfNodeFilter(odfroot, usedStylesElement) {
    var automaticStyles = odfroot.automaticStyles, usedKeysList;
    if(usedStylesElement) {
      usedKeysList = new styleInfo.UsedKeysList(usedStylesElement)
    }
    this.acceptNode = function(node) {
      var styleName, styleFamily, result;
      if(node.namespaceURI === "http://www.w3.org/1999/xhtml") {
        result = 3
      }else {
        if(usedKeysList && node.parentNode === automaticStyles && node.nodeType === 1) {
          if(usedKeysList.uses(node)) {
            result = 1
          }else {
            result = 2
          }
        }else {
          result = 1
        }
      }
      return result
    }
  }
  function setChild(node, child) {
    if(!child) {
      return
    }
    var childpos = getNodePosition(child), pos, c = node.firstChild;
    if(childpos === -1) {
      return
    }
    while(c) {
      pos = getNodePosition(c);
      if(pos !== -1 && pos > childpos) {
        break
      }
      c = c.nextSibling
    }
    node.insertBefore(child, c)
  }
  function ODFElement() {
  }
  function ODFDocumentElement(odfcontainer) {
    this.OdfContainer = odfcontainer
  }
  ODFDocumentElement.prototype = new ODFElement;
  ODFDocumentElement.prototype.constructor = ODFDocumentElement;
  ODFDocumentElement.namespaceURI = officens;
  ODFDocumentElement.localName = "document";
  function OdfPart(name, container, zip) {
    var self = this, privatedata;
    this.size = 0;
    this.type = null;
    this.name = name;
    this.container = container;
    this.url = null;
    this.mimetype = null;
    this.document = null;
    this.onreadystatechange = null;
    this.onchange = null;
    this.EMPTY = 0;
    this.LOADING = 1;
    this.DONE = 2;
    this.state = this.EMPTY;
    this.load = function() {
      var mimetype = partMimetypes[name];
      this.mimetype = mimetype;
      zip.loadAsDataURL(name, mimetype, function(err, url) {
        self.url = url;
        if(self.onchange) {
          self.onchange(self)
        }
        if(self.onstatereadychange) {
          self.onstatereadychange(self)
        }
      })
    };
    this.abort = function() {
    }
  }
  OdfPart.prototype.load = function() {
  };
  OdfPart.prototype.getUrl = function() {
    if(this.data) {
      return"data:;base64," + base64.toBase64(this.data)
    }
    return null
  };
  function OdfPartList(odfcontainer) {
    var self = this;
    this.length = 0;
    this.item = function(index) {
    }
  }
  odf.OdfContainer = function OdfContainer(url, onstatereadychange) {
    var self = this, zip = null, contentXmlCompletelyLoaded = false;
    this.onstatereadychange = onstatereadychange;
    this.onchange = null;
    this.state = null;
    this.rootElement = null;
    this.parts = null;
    function removeProcessingInstructions(element) {
      var n = element.firstChild, next, e;
      while(n) {
        next = n.nextSibling;
        if(n.nodeType === 1) {
          e = n;
          removeProcessingInstructions(e)
        }else {
          if(n.nodeType === 7) {
            element.removeChild(n)
          }
        }
        n = next
      }
    }
    function importRootNode(xmldoc) {
      var doc = self.rootElement.ownerDocument, node;
      if(xmldoc) {
        removeProcessingInstructions(xmldoc.documentElement);
        try {
          node = doc.importNode(xmldoc.documentElement, true)
        }catch(e) {
        }
      }
      return node
    }
    function setState(state) {
      self.state = state;
      if(self.onchange) {
        self.onchange(self)
      }
      if(self.onstatereadychange) {
        self.onstatereadychange(self)
      }
    }
    function handleFlatXml(xmldoc) {
      var root = importRootNode(xmldoc);
      if(!root || root.localName !== "document" || root.namespaceURI !== officens) {
        setState(OdfContainer.INVALID);
        return
      }
      self.rootElement = root;
      root.fontFaceDecls = getDirectChild(root, officens, "font-face-decls");
      root.styles = getDirectChild(root, officens, "styles");
      root.automaticStyles = getDirectChild(root, officens, "automatic-styles");
      root.masterStyles = getDirectChild(root, officens, "master-styles");
      root.body = getDirectChild(root, officens, "body");
      root.meta = getDirectChild(root, officens, "meta");
      setState(OdfContainer.DONE)
    }
    function handleStylesXml(xmldoc) {
      var node = importRootNode(xmldoc), root = self.rootElement;
      if(!node || node.localName !== "document-styles" || node.namespaceURI !== officens) {
        setState(OdfContainer.INVALID);
        return
      }
      root.fontFaceDecls = getDirectChild(node, officens, "font-face-decls");
      setChild(root, root.fontFaceDecls);
      root.styles = getDirectChild(node, officens, "styles");
      setChild(root, root.styles);
      root.automaticStyles = getDirectChild(node, officens, "automatic-styles");
      setChild(root, root.automaticStyles);
      root.masterStyles = getDirectChild(node, officens, "master-styles");
      setChild(root, root.masterStyles);
      fontLoader.loadFonts(root.fontFaceDecls, zip, null)
    }
    function handleContentXml(xmldoc) {
      var node = importRootNode(xmldoc), root, automaticStyles, fontFaceDecls, c;
      if(!node || node.localName !== "document-content" || node.namespaceURI !== officens) {
        setState(OdfContainer.INVALID);
        return
      }
      root = self.rootElement;
      fontFaceDecls = getDirectChild(node, officens, "font-face-decls");
      if(root.fontFaceDecls && fontFaceDecls) {
        c = fontFaceDecls.firstChild;
        while(c) {
          root.fontFaceDecls.appendChild(c);
          c = fontFaceDecls.firstChild
        }
      }else {
        if(fontFaceDecls) {
          root.fontFaceDecls = fontFaceDecls;
          setChild(root, fontFaceDecls)
        }
      }
      automaticStyles = getDirectChild(node, officens, "automatic-styles");
      if(root.automaticStyles && automaticStyles) {
        c = automaticStyles.firstChild;
        while(c) {
          root.automaticStyles.appendChild(c);
          c = automaticStyles.firstChild
        }
      }else {
        if(automaticStyles) {
          root.automaticStyles = automaticStyles;
          setChild(root, automaticStyles)
        }
      }
      root.body = getDirectChild(node, officens, "body");
      setChild(root, root.body)
    }
    function handleMetaXml(xmldoc) {
      var node = importRootNode(xmldoc), root;
      if(!node || node.localName !== "document-meta" || node.namespaceURI !== officens) {
        return
      }
      root = self.rootElement;
      root.meta = getDirectChild(node, officens, "meta");
      setChild(root, root.meta)
    }
    function handleSettingsXml(xmldoc) {
      var node = importRootNode(xmldoc), root;
      if(!node || node.localName !== "document-settings" || node.namespaceURI !== officens) {
        return
      }
      root = self.rootElement;
      root.settings = getDirectChild(node, officens, "settings");
      setChild(root, root.settings)
    }
    function handleManifestXml(xmldoc) {
      var node = importRootNode(xmldoc), root, n;
      if(!node || node.localName !== "manifest" || node.namespaceURI !== manifestns) {
        return
      }
      root = self.rootElement;
      root.manifest = node;
      n = root.manifest.firstChild;
      while(n) {
        if(n.nodeType === 1 && n.localName === "file-entry" && n.namespaceURI === manifestns) {
          partMimetypes[n.getAttributeNS(manifestns, "full-path")] = n.getAttributeNS(manifestns, "media-type")
        }
        n = n.nextSibling
      }
    }
    function getContentXmlNode(callback) {
      var handler = {rootElementReady:function(err, rootxml, done) {
        contentXmlCompletelyLoaded = err || done;
        if(err) {
          return callback(err, null)
        }
        var parser = new DOMParser;
        rootxml = parser.parseFromString(rootxml, "text/xml");
        callback(null, rootxml)
      }, bodyChildElementsReady:function(err, nodes, done) {
      }};
      zip.loadContentXmlAsFragments("content.xml", handler)
    }
    function getXmlNode(filepath, callback) {
      zip.loadAsDOM(filepath, callback)
    }
    function loadComponents() {
      getXmlNode("styles.xml", function(err, xmldoc) {
        handleStylesXml(xmldoc);
        if(self.state === OdfContainer.INVALID) {
          return
        }
        getXmlNode("content.xml", function(err, xmldoc) {
          handleContentXml(xmldoc);
          if(self.state === OdfContainer.INVALID) {
            return
          }
          getXmlNode("meta.xml", function(err, xmldoc) {
            handleMetaXml(xmldoc);
            if(self.state === OdfContainer.INVALID) {
              return
            }
            getXmlNode("settings.xml", function(err, xmldoc) {
              if(xmldoc) {
                handleSettingsXml(xmldoc)
              }
              getXmlNode("META-INF/manifest.xml", function(err, xmldoc) {
                if(xmldoc) {
                  handleManifestXml(xmldoc)
                }
                if(self.state !== OdfContainer.INVALID) {
                  setState(OdfContainer.DONE)
                }
              })
            })
          })
        })
      })
    }
    function documentElement(name, map) {
      var s = "", i;
      for(i in map) {
        if(map.hasOwnProperty(i)) {
          s += " xmlns:" + i + '="' + map[i] + '"'
        }
      }
      return'<?xml version="1.0" encoding="UTF-8"?><office:' + name + " " + s + ' office:version="1.2">'
    }
    function serializeMetaXml() {
      var nsmap = style2CSS.namespaces, serializer = new xmldom.LSSerializer, s = documentElement("document-meta", nsmap);
      serializer.filter = new OdfNodeFilter(self.rootElement);
      s += serializer.writeToString(self.rootElement.meta, nsmap);
      s += "</office:document-meta>";
      return s
    }
    function serializeSettingsXml() {
      var nsmap = style2CSS.namespaces, serializer = new xmldom.LSSerializer, s = documentElement("document-settings", nsmap);
      serializer.filter = new OdfNodeFilter(self.rootElement);
      s += serializer.writeToString(self.rootElement.settings, nsmap);
      s += "</office:document-settings>";
      return s
    }
    function serializeStylesXml() {
      var nsmap = style2CSS.namespaces, serializer = new xmldom.LSSerializer, s = documentElement("document-styles", nsmap);
      serializer.filter = new OdfNodeFilter(self.rootElement, self.rootElement.masterStyles);
      s += serializer.writeToString(self.rootElement.fontFaceDecls, nsmap);
      s += serializer.writeToString(self.rootElement.styles, nsmap);
      s += serializer.writeToString(self.rootElement.automaticStyles, nsmap);
      s += serializer.writeToString(self.rootElement.masterStyles, nsmap);
      s += "</office:document-styles>";
      return s
    }
    function serializeContentXml() {
      var nsmap = style2CSS.namespaces, serializer = new xmldom.LSSerializer, s = documentElement("document-content", nsmap);
      serializer.filter = new OdfNodeFilter(self.rootElement, self.rootElement.body);
      s += serializer.writeToString(self.rootElement.automaticStyles, nsmap);
      s += serializer.writeToString(self.rootElement.body, nsmap);
      s += "</office:document-content>";
      return s
    }
    function createElement(Type) {
      var original = document.createElementNS(Type.namespaceURI, Type.localName), method, iface = new Type;
      for(method in iface) {
        if(iface.hasOwnProperty(method)) {
          original[method] = iface[method]
        }
      }
      return original
    }
    function loadFromXML(url, callback) {
      runtime.loadXML(url, function(err, dom) {
        if(err) {
          callback(err)
        }else {
          handleFlatXml(dom)
        }
      })
    }
    this.getPart = function(partname) {
      return new OdfPart(partname, self, zip)
    };
    this.save = function(callback) {
      var data;
      data = runtime.byteArrayFromString(serializeSettingsXml(), "utf8");
      zip.save("settings.xml", data, true, new Date);
      data = runtime.byteArrayFromString(serializeMetaXml(), "utf8");
      zip.save("meta.xml", data, true, new Date);
      data = runtime.byteArrayFromString(serializeStylesXml(), "utf8");
      zip.save("styles.xml", data, true, new Date);
      data = runtime.byteArrayFromString(serializeContentXml(), "utf8");
      zip.save("content.xml", data, true, new Date);
      zip.write(function(err) {
        callback(err)
      })
    };
    this.state = OdfContainer.LOADING;
    this.rootElement = createElement(ODFDocumentElement);
    this.parts = new OdfPartList(this);
    zip = new core.Zip(url, function(err, zipobject) {
      zip = zipobject;
      if(err) {
        loadFromXML(url, function(xmlerr) {
          if(err) {
            zip.error = err + "\n" + xmlerr;
            setState(OdfContainer.INVALID)
          }
        })
      }else {
        loadComponents()
      }
    })
  };
  odf.OdfContainer.EMPTY = 0;
  odf.OdfContainer.LOADING = 1;
  odf.OdfContainer.DONE = 2;
  odf.OdfContainer.INVALID = 3;
  odf.OdfContainer.SAVING = 4;
  odf.OdfContainer.MODIFIED = 5;
  odf.OdfContainer.getContainer = function(url) {
    return new odf.OdfContainer(url, null)
  };
  return odf.OdfContainer
}();
odf.Formatting = function Formatting() {
  var odfContainer, styleInfo = new odf.StyleInfo;
  function RangeElementIterator(range) {
    function getNthChild(parent, n) {
      var c = parent && parent.firstChild;
      while(c && n) {
        c = c.nextSibling;
        n -= 1
      }
      return c
    }
    var start = getNthChild(range.startContainer, range.startOffset), end = getNthChild(range.endContainer, range.endOffset), current = start;
    this.next = function() {
      var c = current;
      if(c === null) {
        return c
      }
      return null
    }
  }
  function getParentStyle(element) {
    var n = element.firstChild, e;
    if(n.nodeType === 1) {
      e = n;
      return e
    }
    return null
  }
  function getParagraphStyles(range) {
    var iter = new RangeElementIterator(range), e, styles = [];
    e = iter.next();
    while(e) {
      if(styleInfo.canElementHaveStyle("paragraph", e)) {
        styles.push(e)
      }
    }
    return styles
  }
  this.setOdfContainer = function(odfcontainer) {
    odfContainer = odfcontainer
  };
  this.isCompletelyBold = function(selection) {
    return false
  };
  this.getAlignment = function(selection) {
    var styles = this.getParagraphStyles(selection), i, l = styles.length;
    return undefined
  };
  this.getParagraphStyles = function(selection) {
    var i, j, s, styles = [];
    for(i = 0;i < selection.length;i += 0) {
      s = getParagraphStyles(selection[i]);
      for(j = 0;j < s.length;j += 1) {
        if(styles.indexOf(s[j]) === -1) {
          styles.push(s[j])
        }
      }
    }
    return styles
  };
  this.getTextStyles = function(selection) {
    return[]
  }
};
runtime.loadClass("odf.OdfContainer");
runtime.loadClass("odf.Formatting");
runtime.loadClass("xmldom.XPath");
odf.OdfCanvas = function() {
  function LoadingQueue() {
    var queue = [], taskRunning = false;
    function run(task) {
      taskRunning = true;
      runtime.setTimeout(function() {
        try {
          task()
        }catch(e) {
          runtime.log(e)
        }
        taskRunning = false;
        if(queue.length > 0) {
          run(queue.pop())
        }
      }, 10)
    }
    this.clearQueue = function() {
      queue.length = 0
    };
    this.addToQueue = function(loadingTask) {
      if(queue.length === 0 && !taskRunning) {
        return run(loadingTask)
      }
      queue.push(loadingTask)
    }
  }
  function PageSwitcher(css) {
    var sheet = css.sheet, position = 1;
    function updateCSS() {
      while(sheet.cssRules.length > 0) {
        sheet.deleteRule(0)
      }
      sheet.insertRule("office|presentation draw|page {display:none;}", 0);
      sheet.insertRule("office|presentation draw|page:nth-child(" + position + ") {display:block;}", 1)
    }
    this.showNextPage = function() {
      position += 1;
      updateCSS()
    };
    this.showPreviousPage = function() {
      if(position > 1) {
        position -= 1;
        updateCSS()
      }
    };
    this.css = css
  }
  function listenEvent(eventTarget, eventType, eventHandler) {
    if(eventTarget.addEventListener) {
      eventTarget.addEventListener(eventType, eventHandler, false)
    }else {
      if(eventTarget.attachEvent) {
        eventType = "on" + eventType;
        eventTarget.attachEvent(eventType, eventHandler)
      }else {
        eventTarget["on" + eventType] = eventHandler
      }
    }
  }
  function SelectionWatcher(element) {
    var selection = [], count = 0, listeners = [];
    function isAncestorOf(ancestor, descendant) {
      while(descendant) {
        if(descendant === ancestor) {
          return true
        }
        descendant = descendant.parentNode
      }
      return false
    }
    function fallsWithin(element, range) {
      return isAncestorOf(element, range.startContainer) && isAncestorOf(element, range.endContainer)
    }
    function getCurrentSelection() {
      var s = [], selection = runtime.getWindow().getSelection(), i, r;
      for(i = 0;i < selection.rangeCount;i += 1) {
        r = selection.getRangeAt(i);
        if(r !== null && fallsWithin(element, r)) {
          s.push(r)
        }
      }
      return s
    }
    function rangesNotEqual(rangeA, rangeB) {
      if(rangeA === rangeB) {
        return false
      }
      if(rangeA === null || rangeB === null) {
        return true
      }
      return rangeA.startContainer !== rangeB.startContainer || rangeA.startOffset !== rangeB.startOffset || rangeA.endContainer !== rangeB.endContainer || rangeA.endOffset !== rangeB.endOffset
    }
    function emitNewSelection() {
      var i, l = listeners.length;
      for(i = 0;i < l;i += 1) {
        listeners[i](element, selection)
      }
    }
    function copySelection(selection) {
      var s = [selection.length], i, oldr, r, doc = element.ownerDocument;
      for(i = 0;i < selection.length;i += 1) {
        oldr = selection[i];
        r = doc.createRange();
        r.setStart(oldr.startContainer, oldr.startOffset);
        r.setEnd(oldr.endContainer, oldr.endOffset);
        s[i] = r
      }
      return s
    }
    function checkSelection() {
      var s = getCurrentSelection(), i;
      if(s.length === selection.length) {
        for(i = 0;i < s.length;i += 1) {
          if(rangesNotEqual(s[i], selection[i])) {
            break
          }
        }
        if(i === s.length) {
          return
        }
      }
      selection = s;
      selection = copySelection(s);
      emitNewSelection()
    }
    this.addListener = function(eventName, handler) {
      var i, l = listeners.length;
      for(i = 0;i < l;i += 1) {
        if(listeners[i] === handler) {
          return
        }
      }
      listeners.push(handler)
    };
    listenEvent(element, "mouseup", checkSelection);
    listenEvent(element, "keyup", checkSelection);
    listenEvent(element, "keydown", checkSelection)
  }
  var style2CSS = new odf.Style2CSS, namespaces = style2CSS.namespaces, drawns = namespaces.draw, fons = namespaces.fo, officens = namespaces.office, svgns = namespaces.svg, textns = namespaces.text, xlinkns = namespaces.xlink, window = runtime.getWindow(), xpath = new xmldom.XPath, eventHandlers = {}, editparagraph, loadingQueue = new LoadingQueue;
  function addEventListener(eventType, eventHandler) {
    var handlers = eventHandlers[eventType];
    if(handlers === undefined) {
      handlers = eventHandlers[eventType] = []
    }
    if(eventHandler && handlers.indexOf(eventHandler) === -1) {
      handlers.push(eventHandler)
    }
  }
  function fireEvent(eventType, args) {
    if(!eventHandlers.hasOwnProperty(eventType)) {
      return
    }
    var handlers = eventHandlers[eventType], i;
    for(i = 0;i < handlers.length;i += 1) {
      handlers[i](args)
    }
  }
  function clear(element) {
    while(element.firstChild) {
      element.removeChild(element.firstChild)
    }
  }
  function handleStyles(odfelement, stylesxmlcss) {
    var style2css = new odf.Style2CSS;
    style2css.style2css(stylesxmlcss.sheet, odfelement.styles, odfelement.automaticStyles)
  }
  function setFramePosition(id, frame, stylesheet) {
    frame.setAttribute("styleid", id);
    var rule, anchor = frame.getAttributeNS(textns, "anchor-type"), x = frame.getAttributeNS(svgns, "x"), y = frame.getAttributeNS(svgns, "y"), width = frame.getAttributeNS(svgns, "width"), height = frame.getAttributeNS(svgns, "height"), minheight = frame.getAttributeNS(fons, "min-height"), minwidth = frame.getAttributeNS(fons, "min-width");
    if(anchor === "as-char") {
      rule = "display: inline-block;"
    }else {
      if(anchor || x || y) {
        rule = "position: absolute;"
      }else {
        if(width || height || minheight || minwidth) {
          rule = "display: block;"
        }
      }
    }
    if(x) {
      rule += "left: " + x + ";"
    }
    if(y) {
      rule += "top: " + y + ";"
    }
    if(width) {
      rule += "width: " + width + ";"
    }
    if(height) {
      rule += "height: " + height + ";"
    }
    if(minheight) {
      rule += "min-height: " + minheight + ";"
    }
    if(minwidth) {
      rule += "min-width: " + minwidth + ";"
    }
    if(rule) {
      rule = "draw|" + frame.localName + '[styleid="' + id + '"] {' + rule + "}";
      stylesheet.insertRule(rule, stylesheet.cssRules.length)
    }
  }
  function getUrlFromBinaryDataElement(image) {
    var node = image.firstChild;
    while(node) {
      if(node.namespaceURI === officens && node.localName === "binary-data") {
        return"data:image/png;base64," + node.textContent
      }
      node = node.nextSibling
    }
    return""
  }
  function setImage(id, container, image, stylesheet) {
    image.setAttribute("styleid", id);
    var url = image.getAttributeNS(xlinkns, "href"), part, node;
    function callback(url) {
      var rule = "background-image: url(" + url + ");";
      rule = 'draw|image[styleid="' + id + '"] {' + rule + "}";
      stylesheet.insertRule(rule, stylesheet.cssRules.length)
    }
    if(url) {
      try {
        if(container.getPartUrl) {
          url = container.getPartUrl(url);
          callback(url)
        }else {
          part = container.getPart(url);
          part.onchange = function(part) {
            callback(part.url)
          };
          part.load()
        }
      }catch(e) {
        runtime.log("slight problem: " + e)
      }
    }else {
      url = getUrlFromBinaryDataElement(image);
      callback(url)
    }
  }
  function formatParagraphAnchors(odfbody) {
    var runtimens = "urn:webodf", n, i, nodes = xpath.getODFElementsWithXPath(odfbody, ".//*[*[@text:anchor-type='paragraph']]", style2CSS.namespaceResolver);
    for(i = 0;i < nodes.length;i += 1) {
      n = nodes[i];
      if(n.setAttributeNS) {
        n.setAttributeNS(runtimens, "containsparagraphanchor", true)
      }
    }
  }
  function modifyImages(container, odfbody, stylesheet) {
    var node, frames, i, images;
    function namespaceResolver(prefix) {
      return namespaces[prefix]
    }
    frames = [];
    node = odfbody.firstChild;
    while(node && node !== odfbody) {
      if(node.namespaceURI === drawns) {
        frames[frames.length] = node
      }
      if(node.firstChild) {
        node = node.firstChild
      }else {
        while(node && node !== odfbody && !node.nextSibling) {
          node = node.parentNode
        }
        if(node && node.nextSibling) {
          node = node.nextSibling
        }
      }
    }
    for(i = 0;i < frames.length;i += 1) {
      node = frames[i];
      setFramePosition("frame" + String(i), node, stylesheet)
    }
    formatParagraphAnchors(odfbody)
  }
  function loadImages(container, odffragment, stylesheet) {
    var i, images, node;
    function loadImage(name, container, node, stylesheet) {
      loadingQueue.addToQueue(function() {
        setImage(name, container, node, stylesheet)
      })
    }
    images = odffragment.getElementsByTagNameNS(drawns, "image");
    for(i = 0;i < images.length;i += 1) {
      node = images.item(i);
      loadImage("image" + String(i), container, node, stylesheet)
    }
  }
  function setVideo(id, container, plugin, stylesheet) {
    var video, source, url, videoType, doc = plugin.ownerDocument, part, node;
    url = plugin.getAttributeNS(xlinkns, "href");
    function callback(url, mimetype) {
      if(mimetype.substr(0, 6) === "video/") {
        video = doc.createElementNS(doc.documentElement.namespaceURI, "video");
        video.setAttribute("controls", "controls");
        source = doc.createElement("source");
        source.setAttribute("src", url);
        source.setAttribute("type", mimetype);
        video.appendChild(source);
        plugin.parentNode.appendChild(video)
      }else {
        plugin.innerHtml = "Unrecognised Plugin"
      }
    }
    if(url) {
      try {
        if(container.getPartUrl) {
          url = container.getPartUrl(url);
          callback(url, "video/mp4")
        }else {
          part = container.getPart(url);
          part.onchange = function(part) {
            callback(part.url, part.mimetype)
          };
          part.load()
        }
      }catch(e) {
        runtime.log("slight problem: " + e)
      }
    }else {
      runtime.log("using MP4 data fallback");
      url = getUrlFromBinaryDataElement(plugin);
      callback(url, "video/mp4")
    }
  }
  function loadVideos(container, odffragment, stylesheet) {
    var i, plugins, node;
    function loadVideo(name, container, node, stylesheet) {
      loadingQueue.addToQueue(function() {
        setVideo(name, container, node, stylesheet)
      })
    }
    plugins = odffragment.getElementsByTagNameNS(drawns, "plugin");
    runtime.log("Loading Videos:");
    for(i = 0;i < plugins.length;i += 1) {
      runtime.log("...Found a video.");
      node = plugins.item(i);
      loadVideo("video" + String(i), container, node, stylesheet)
    }
  }
  function addStyleSheet(document) {
    var styles = document.getElementsByTagName("style"), head = document.getElementsByTagName("head")[0], text = "", prefix, a = "", b;
    if(styles && styles.length > 0) {
      styles = styles[0].cloneNode(false)
    }else {
      styles = document.createElement("style")
    }
    for(prefix in namespaces) {
      if(namespaces.hasOwnProperty(prefix) && prefix) {
        text += "@namespace " + prefix + " url(" + namespaces[prefix] + ");\n"
      }
    }
    styles.appendChild(document.createTextNode(text));
    head.appendChild(styles);
    return styles
  }
  odf.OdfCanvas = function OdfCanvas(element) {
    var self = this, document = element.ownerDocument, odfcontainer, formatting = new odf.Formatting, selectionWatcher = new SelectionWatcher(element), slidecssindex = 0, pageSwitcher = new PageSwitcher(addStyleSheet(document)), stylesxmlcss = addStyleSheet(document), positioncss = addStyleSheet(document), editable = false, zoomLevel = 1;
    function fixContainerSize() {
      var sizer = element.firstChild, odfdoc = sizer.firstChild;
      if(!odfdoc) {
        return
      }
      element.style.WebkitTransform = "scale(" + zoomLevel + ")";
      element.style.WebkitTransformOrigin = "left top";
      element.style.width = Math.round(zoomLevel * odfdoc.offsetWidth) + "px";
      element.style.height = Math.round(zoomLevel * odfdoc.offsetHeight) + "px"
    }
    function handleContent(container, odfnode) {
      var css = positioncss.sheet, sizer;
      modifyImages(container, odfnode.body, css);
      css.insertRule("draw|page { background-color:#fff; }", css.cssRules.length);
      clear(element);
      sizer = document.createElement("div");
      sizer.style.display = "inline-block";
      sizer.style.background = "white";
      sizer.appendChild(odfnode);
      element.appendChild(sizer);
      loadImages(container, odfnode.body, css);
      loadVideos(container, odfnode.body, css);
      fixContainerSize()
    }
    function refreshOdf(container) {
      if(odfcontainer !== container) {
        return
      }
      function callback() {
        clear(element);
        element.style.display = "inline-block";
        var odfnode = container.rootElement;
        element.ownerDocument.importNode(odfnode, true);
        formatting.setOdfContainer(container);
        handleStyles(odfnode, stylesxmlcss);
        handleContent(container, odfnode);
        fireEvent("statereadychange")
      }
      if(odfcontainer.state === odf.OdfContainer.DONE) {
        callback()
      }else {
        odfcontainer.onchange = callback
      }
    }
    this.odfContainer = function() {
      return odfcontainer
    };
    this.slidevisibilitycss = function() {
      return pageSwitcher.css
    };
    this["load"] = this.load = function(url) {
      loadingQueue.clearQueue();
      element.innerHTML = "loading " + url;
      odfcontainer = new odf.OdfContainer(url, function(container) {
        odfcontainer = container;
        refreshOdf(container)
      });
      odfcontainer.onstatereadychange = refreshOdf
    };
    function stopEditing() {
      if(!editparagraph) {
        return
      }
      var fragment = editparagraph.ownerDocument.createDocumentFragment();
      while(editparagraph.firstChild) {
        fragment.insertBefore(editparagraph.firstChild, null)
      }
      editparagraph.parentNode.replaceChild(fragment, editparagraph)
    }
    this.save = function(callback) {
      stopEditing();
      odfcontainer.save(callback)
    };
    function cancelPropagation(event) {
      if(event.stopPropagation) {
        event.stopPropagation()
      }else {
        event.cancelBubble = true
      }
    }
    function cancelEvent(event) {
      if(event.preventDefault) {
        event.preventDefault();
        event.stopPropagation()
      }else {
        event.returnValue = false;
        event.cancelBubble = true
      }
    }
    this.setEditable = function(iseditable) {
      editable = iseditable;
      if(!editable) {
        stopEditing()
      }
    };
    function processClick(evt) {
      evt = evt || window.event;
      var e = evt.target, selection = window.getSelection(), range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null, startContainer = range && range.startContainer, startOffset = range && range.startOffset, endContainer = range && range.endContainer, endOffset = range && range.endOffset;
      while(e && !((e.localName === "p" || e.localName === "h") && e.namespaceURI === textns)) {
        e = e.parentNode
      }
      if(!editable) {
        return
      }
      if(!e || e.parentNode === editparagraph) {
        return
      }
      if(!editparagraph) {
        editparagraph = e.ownerDocument.createElement("p");
        if(!editparagraph.style) {
          editparagraph = e.ownerDocument.createElementNS("http://www.w3.org/1999/xhtml", "p")
        }
        editparagraph.style.margin = "0px";
        editparagraph.style.padding = "0px";
        editparagraph.style.border = "0px";
        editparagraph.setAttribute("contenteditable", true)
      }else {
        if(editparagraph.parentNode) {
          stopEditing()
        }
      }
      e.parentNode.replaceChild(editparagraph, e);
      editparagraph.appendChild(e);
      editparagraph.focus();
      if(range) {
        selection.removeAllRanges();
        range = e.ownerDocument.createRange();
        range.setStart(startContainer, startOffset);
        range.setEnd(endContainer, endOffset);
        selection.addRange(range)
      }
      cancelEvent(evt)
    }
    this.addListener = function(eventName, handler) {
      if(eventName === "selectionchange") {
        selectionWatcher.addListener(eventName, handler)
      }else {
        addEventListener(eventName, handler)
      }
    };
    this.getFormatting = function() {
      return formatting
    };
    this.setZoomLevel = function(zoom) {
      zoomLevel = zoom;
      fixContainerSize()
    };
    this.getZoomLevel = function() {
      return zoomLevel
    };
    this.fitToContainingElement = function(width, height) {
      var realWidth = element.offsetWidth / zoomLevel, realHeight = element.offsetHeight / zoomLevel;
      zoomLevel = width / realWidth;
      if(height / realHeight < zoomLevel) {
        zoomLevel = height / realHeight
      }
      fixContainerSize()
    };
    this.fitToWidth = function(width) {
      var realWidth = element.offsetWidth / zoomLevel;
      zoomLevel = width / realWidth;
      fixContainerSize()
    };
    this.fitToHeight = function(height) {
      var realHeight = element.offsetHeight / zoomLevel;
      zoomLevel = height / realHeight;
      fixContainerSize()
    };
    this.showNextPage = function() {
      pageSwitcher.showNextPage()
    };
    this.showPreviousPage = function() {
      pageSwitcher.showPreviousPage()
    };
    this.showAllPages = function() {
    };
    listenEvent(element, "click", processClick)
  };
  return odf.OdfCanvas
}();
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");
gui.PresenterUI = function() {
  var s2css = new odf.Style2CSS, xpath = new xmldom.XPath, nsResolver = s2css.namespaceResolver;
  return function PresenterUI(odf_element) {
    var self = this;
    self.setInitialSlideMode = function() {
      self.startSlideMode("single")
    };
    self.keyDownHandler = function(ev) {
      if(ev.target.isContentEditable) {
        return
      }
      if(ev.target.nodeName === "input") {
        return
      }
      switch(ev.keyCode) {
        case 84:
          self.toggleToolbar();
          break;
        case 37:
        ;
        case 8:
          self.prevSlide();
          break;
        case 39:
        ;
        case 32:
          self.nextSlide();
          break;
        case 36:
          self.firstSlide();
          break;
        case 35:
          self.lastSlide();
          break
      }
    };
    self.root = function() {
      return self.odf_canvas.odfContainer().rootElement
    };
    self.firstSlide = function() {
      self.slideChange(function(old, pc) {
        return 0
      })
    };
    self.lastSlide = function() {
      self.slideChange(function(old, pc) {
        return pc - 1
      })
    };
    self.nextSlide = function() {
      self.slideChange(function(old, pc) {
        return old + 1 < pc ? old + 1 : -1
      })
    };
    self.prevSlide = function() {
      self.slideChange(function(old, pc) {
        return old < 1 ? -1 : old - 1
      })
    };
    self.slideChange = function(indexChanger) {
      var pages = self.getPages(self.odf_canvas.odfContainer().rootElement), last = -1, i = 0, newidx, pagelist;
      pages.forEach(function(tuple) {
        var name = tuple[0], node = tuple[1];
        if(node.hasAttribute("slide_current")) {
          last = i;
          node.removeAttribute("slide_current")
        }
        i += 1
      });
      newidx = indexChanger(last, pages.length);
      if(newidx === -1) {
        newidx = last
      }
      pages[newidx][1].setAttribute("slide_current", "1");
      pagelist = document.getElementById("pagelist");
      pagelist.selectedIndex = newidx;
      if(self.slide_mode === "cont") {
        window.scrollBy(0, pages[newidx][1].getBoundingClientRect().top - 30)
      }
    };
    self.selectSlide = function(idx) {
      self.slideChange(function(old, pc) {
        if(idx >= pc) {
          return-1
        }
        if(idx < 0) {
          return-1
        }
        return idx
      })
    };
    self.scrollIntoContView = function(idx) {
      var pages = self.getPages(self.odf_canvas.odfContainer().rootElement);
      if(pages.length === 0) {
        return
      }
      window.scrollBy(0, pages[idx][1].getBoundingClientRect().top - 30)
    };
    self.getPages = function(root) {
      var pagenodes = root.getElementsByTagNameNS(nsResolver("draw"), "page"), pages = [], i;
      for(i = 0;i < pagenodes.length;i += 1) {
        pages.push([pagenodes[i].getAttribute("draw:name"), pagenodes[i]])
      }
      return pages
    };
    self.fillPageList = function(odfdom_root, html_select) {
      var pages = self.getPages(odfdom_root), i, html_option, res, page_denom;
      while(html_select.firstChild) {
        html_select.removeChild(html_select.firstChild)
      }
      for(i = 0;i < pages.length;i += 1) {
        html_option = document.createElement("option");
        res = xpath.getODFElementsWithXPath(pages[i][1], './draw:frame[@presentation:class="title"]//draw:text-box/text:p', xmldom.XPath);
        page_denom = res.length > 0 ? res[0].textContent : pages[i][0];
        html_option.textContent = i + 1 + ": " + page_denom;
        html_select.appendChild(html_option)
      }
    };
    self.startSlideMode = function(mode) {
      var pagelist = document.getElementById("pagelist"), css = self.odf_canvas.slidevisibilitycss().sheet;
      self.slide_mode = mode;
      while(css.cssRules.length > 0) {
        css.deleteRule(0)
      }
      self.selectSlide(0);
      if(self.slide_mode === "single") {
        css.insertRule("draw|page { position:fixed; left:0px;top:30px; z-index:1; }", 0);
        css.insertRule("draw|page[slide_current]  { z-index:2;}", 1);
        css.insertRule("draw|page  { -webkit-transform: scale(1);}", 2);
        self.fitToWindow();
        window.addEventListener("resize", self.fitToWindow, false)
      }else {
        if(self.slide_mode === "cont") {
          window.removeEventListener("resize", self.fitToWindow, false)
        }
      }
      self.fillPageList(self.odf_canvas.odfContainer().rootElement, pagelist)
    };
    self.toggleToolbar = function() {
      var css, found, i;
      css = self.odf_canvas.slidevisibilitycss().sheet;
      found = -1;
      for(i = 0;i < css.cssRules.length;i += 1) {
        if(css.cssRules[i].cssText.substring(0, 8) === ".toolbar") {
          found = i;
          break
        }
      }
      if(found > -1) {
        css.deleteRule(found)
      }else {
        css.insertRule(".toolbar { position:fixed; left:0px;top:-200px; z-index:0; }", 0)
      }
    };
    self.fitToWindow = function() {
      function ruleByFactor(f) {
        return"draw|page { \n" + "-moz-transform: scale(" + f + "); \n" + "-moz-transform-origin: 0% 0%; " + "-webkit-transform-origin: 0% 0%; -webkit-transform: scale(" + f + "); " + "-o-transform-origin: 0% 0%; -o-transform: scale(" + f + "); " + "-ms-transform-origin: 0% 0%; -ms-transform: scale(" + f + "); " + "}"
      }
      var pages = self.getPages(self.root()), factorVert = (window.innerHeight - 40) / pages[0][1].clientHeight, factorHoriz = (window.innerWidth - 10) / pages[0][1].clientWidth, factor = factorVert < factorHoriz ? factorVert : factorHoriz, css = self.odf_canvas.slidevisibilitycss().sheet;
      css.deleteRule(2);
      css.insertRule(ruleByFactor(factor), 2)
    };
    self.load = function(url) {
      self.odf_canvas.load(url)
    };
    self.odf_element = odf_element;
    self.odf_canvas = new odf.OdfCanvas(self.odf_element);
    self.odf_canvas.addListener("statereadychange", self.setInitialSlideMode);
    self.slide_mode = "undefined";
    document.addEventListener("keydown", self.keyDownHandler, false)
  }
}();
gui.Caret = function Caret(selection, rootNode) {
  var document = rootNode.ownerDocument, cursorns, cursorNode;
  cursorns = "urn:webodf:names:cursor";
  cursorNode = document.createElementNS(cursorns, "cursor");
  this.updateToSelection = function() {
    var range;
    if(selection.rangeCount === 1) {
      range = selection.getRangeAt(0)
    }
  }
};
runtime.loadClass("core.Cursor");
gui.SelectionMover = function SelectionMover(selection, pointWalker) {
  var doc = pointWalker.node().ownerDocument, cursor = new core.Cursor(selection, doc);
  function getActiveRange(node) {
    var range;
    if(selection.rangeCount === 0) {
      selection.addRange(node.ownerDocument.createRange())
    }
    return selection.getRangeAt(selection.rangeCount - 1)
  }
  function setStart(node, offset) {
    var ranges = [], i, range;
    for(i = 0;i < selection.rangeCount;i += 1) {
      ranges[i] = selection.getRangeAt(i)
    }
    selection.removeAllRanges();
    if(ranges.length === 0) {
      ranges[0] = node.ownerDocument.createRange()
    }
    ranges[ranges.length - 1].setStart(pointWalker.node(), pointWalker.position());
    for(i = 0;i < ranges.length;i += 1) {
      selection.addRange(ranges[i])
    }
  }
  function doMove(extend, move) {
    if(selection.rangeCount === 0) {
      return
    }
    var range = selection.getRangeAt(0), element;
    if(!range.startContainer || range.startContainer.nodeType !== 1) {
      return
    }
    element = range.startContainer;
    pointWalker.setPoint(element, range.startOffset);
    move();
    setStart(pointWalker.node(), pointWalker.position())
  }
  function doMoveForward(extend, move) {
    if(selection.rangeCount === 0) {
      return
    }
    move();
    var range = selection.getRangeAt(0), element;
    if(!range.startContainer || range.startContainer.nodeType !== 1) {
      return
    }
    element = range.startContainer;
    pointWalker.setPoint(element, range.startOffset)
  }
  function moveCursor(node, offset, selectMode) {
    if(selectMode) {
      selection.extend(node, offset)
    }else {
      selection.collapse(node, offset)
    }
    cursor.updateToSelection()
  }
  function moveCursorLeft() {
    var element;
    if(!selection.focusNode || selection.focusNode.nodeType !== 1) {
      return
    }
    element = selection.focusNode;
    pointWalker.setPoint(element, selection.focusOffset);
    pointWalker.stepBackward();
    moveCursor(pointWalker.node(), pointWalker.position(), false)
  }
  function moveCursorRight() {
    cursor.remove();
    var element;
    if(!selection.focusNode || selection.focusNode.nodeType !== 1) {
      return
    }
    element = selection.focusNode;
    pointWalker.setPoint(element, selection.focusOffset);
    pointWalker.stepForward();
    moveCursor(pointWalker.node(), pointWalker.position(), false)
  }
  function moveCursorUp() {
    var rect = cursor.getNode().getBoundingClientRect(), x = rect.left, y = rect.top, arrived = false, left = 200;
    while(!arrived && left) {
      left -= 1;
      moveCursorLeft();
      rect = cursor.getNode().getBoundingClientRect();
      arrived = rect.top !== y && rect.left < x
    }
  }
  function moveCursorDown() {
    cursor.updateToSelection();
    var rect = cursor.getNode().getBoundingClientRect(), x = rect.left, y = rect.top, arrived = false, left = 200;
    while(!arrived) {
      left -= 1;
      moveCursorRight();
      rect = cursor.getNode().getBoundingClientRect();
      arrived = rect.top !== y && rect.left > x
    }
  }
  this.movePointForward = function(extend) {
    doMove(extend, pointWalker.stepForward)
  };
  this.movePointBackward = function(extend) {
    doMove(extend, pointWalker.stepBackward)
  };
  this.moveLineForward = function(extend) {
    if(selection.modify) {
      selection.modify(extend ? "extend" : "move", "forward", "line")
    }else {
      doMove(extend, moveCursorDown)
    }
  };
  this.moveLineBackward = function(extend) {
    if(selection.modify) {
      selection.modify(extend ? "extend" : "move", "backward", "line")
    }else {
      doMove(extend, function() {
      })
    }
  };
  return this
};
runtime.loadClass("core.PointWalker");
runtime.loadClass("core.Cursor");
gui.XMLEdit = function XMLEdit(element, stylesheet) {
  var simplecss, cssprefix, documentElement, customNS = "customns", walker = null;
  if(!element.id) {
    element.id = "xml" + String(Math.random()).substring(2)
  }
  cssprefix = "#" + element.id + " ";
  function installHandlers() {
  }
  simplecss = cssprefix + "*," + cssprefix + ":visited, " + cssprefix + ":link {display:block; margin: 0px; margin-left: 10px; font-size: medium; color: black; background: white; font-variant: normal; font-weight: normal; font-style: normal; font-family: sans-serif; text-decoration: none; white-space: pre-wrap; height: auto; width: auto}\n" + cssprefix + ":before {color: blue; content: '<' attr(customns_name) attr(customns_atts) '>';}\n" + cssprefix + ":after {color: blue; content: '</' attr(customns_name) '>';}\n" + 
  cssprefix + "{overflow: auto;}\n";
  function listenEvent(eventTarget, eventType, eventHandler) {
    if(eventTarget.addEventListener) {
      eventTarget.addEventListener(eventType, eventHandler, false)
    }else {
      if(eventTarget.attachEvent) {
        eventType = "on" + eventType;
        eventTarget.attachEvent(eventType, eventHandler)
      }else {
        eventTarget["on" + eventType] = eventHandler
      }
    }
  }
  function cancelEvent(event) {
    if(event.preventDefault) {
      event.preventDefault()
    }else {
      event.returnValue = false
    }
  }
  function isCaretMoveCommand(charCode) {
    if(charCode >= 16 && charCode <= 20) {
      return true
    }
    if(charCode >= 33 && charCode <= 40) {
      return true
    }
    return false
  }
  function syncSelectionWithWalker() {
    var sel = element.ownerDocument.defaultView.getSelection(), r;
    if(!sel || sel.rangeCount <= 0 || !walker) {
      return
    }
    r = sel.getRangeAt(0);
    walker.setPoint(r.startContainer, r.startOffset)
  }
  function syncWalkerWithSelection() {
    var sel = element.ownerDocument.defaultView.getSelection(), n, r;
    sel.removeAllRanges();
    if(!walker || !walker.node()) {
      return
    }
    n = walker.node();
    r = n.ownerDocument.createRange();
    r.setStart(n, walker.position());
    r.collapse(true);
    sel.addRange(r)
  }
  function handleKeyDown(event) {
    var charCode = event.charCode || event.keyCode;
    walker = null;
    if(walker && charCode === 39) {
      syncSelectionWithWalker();
      walker.stepForward();
      syncWalkerWithSelection()
    }else {
      if(walker && charCode === 37) {
        syncSelectionWithWalker();
        walker.stepBackward();
        syncWalkerWithSelection()
      }else {
        if(isCaretMoveCommand(charCode)) {
          return
        }
      }
    }
    cancelEvent(event)
  }
  function handleKeyPress(event) {
  }
  function handleClick(event) {
    var sel = element.ownerDocument.defaultView.getSelection(), r = sel.getRangeAt(0), n = r.startContainer;
    cancelEvent(event)
  }
  function initElement(element) {
    listenEvent(element, "click", handleClick);
    listenEvent(element, "keydown", handleKeyDown);
    listenEvent(element, "keypress", handleKeyPress);
    listenEvent(element, "drop", cancelEvent);
    listenEvent(element, "dragend", cancelEvent);
    listenEvent(element, "beforepaste", cancelEvent);
    listenEvent(element, "paste", cancelEvent)
  }
  function cleanWhitespace(node) {
    var n = node.firstChild, p, re = /^\s*$/;
    while(n && n !== node) {
      p = n;
      n = n.nextSibling || n.parentNode;
      if(p.nodeType === 3 && re.test(p.nodeValue)) {
        p.parentNode.removeChild(p)
      }
    }
  }
  function setCssHelperAttributes(node) {
    var atts, attsv, a, i;
    atts = node.attributes;
    attsv = "";
    for(i = atts.length - 1;i >= 0;i -= 1) {
      a = atts.item(i);
      attsv = attsv + " " + a.nodeName + '="' + a.nodeValue + '"'
    }
    node.setAttribute("customns_name", node.nodeName);
    node.setAttribute("customns_atts", attsv)
  }
  function addExplicitAttributes(node) {
    var n = node.firstChild;
    while(n && n !== node) {
      if(n.nodeType === 1) {
        addExplicitAttributes(n)
      }
      n = n.nextSibling || n.parentNode
    }
    setCssHelperAttributes(node);
    cleanWhitespace(node)
  }
  function getNamespacePrefixes(node, prefixes) {
    var n = node.firstChild, atts, att, i;
    while(n && n !== node) {
      if(n.nodeType === 1) {
        getNamespacePrefixes(n, prefixes);
        atts = n.attributes;
        for(i = atts.length - 1;i >= 0;i -= 1) {
          att = atts.item(i);
          if(att.namespaceURI === "http://www.w3.org/2000/xmlns/") {
            if(!prefixes[att.nodeValue]) {
              prefixes[att.nodeValue] = att.localName
            }
          }
        }
      }
      n = n.nextSibling || n.parentNode
    }
  }
  function generateUniquePrefixes(prefixes) {
    var taken = {}, ns, p, n = 0;
    for(ns in prefixes) {
      if(prefixes.hasOwnProperty(ns) && ns) {
        p = prefixes[ns];
        if(!p || taken.hasOwnProperty(p) || p === "xmlns") {
          do {
            p = "ns" + n;
            n += 1
          }while(taken.hasOwnProperty(p));
          prefixes[ns] = p
        }
        taken[p] = true
      }
    }
  }
  function createCssFromXmlInstance(node) {
    var prefixes = {}, css = "@namespace customns url(customns);\n", name, pre, ns, names, csssel;
    getNamespacePrefixes(node, prefixes);
    generateUniquePrefixes(prefixes);
    return css
  }
  function updateCSS() {
    var css = element.ownerDocument.createElement("style"), text = createCssFromXmlInstance(element);
    css.type = "text/css";
    text = text + simplecss;
    css.appendChild(element.ownerDocument.createTextNode(text));
    stylesheet = stylesheet.parentNode.replaceChild(css, stylesheet)
  }
  function getXML() {
    return documentElement
  }
  function setXML(xml) {
    var node = xml.documentElement || xml;
    node = element.ownerDocument.importNode(node, true);
    documentElement = node;
    addExplicitAttributes(node);
    while(element.lastChild) {
      element.removeChild(element.lastChild)
    }
    element.appendChild(node);
    updateCSS();
    walker = new core.PointWalker(node)
  }
  initElement(element);
  this.updateCSS = updateCSS;
  this.setXML = setXML;
  this.getXML = getXML
};
(function() {
  return["core/Async.js", "core/Base64.js", "core/ByteArray.js", "core/ByteArrayWriter.js", "core/Cursor.js", "core/JSLint.js", "core/PointWalker.js", "core/RawDeflate.js", "core/RawInflate.js", "core/UnitTester.js", "core/Zip.js", "gui/Caret.js", "gui/SelectionMover.js", "gui/XMLEdit.js", "gui/PresenterUI.js", "odf/FontLoader.js", "odf/Formatting.js", "odf/OdfCanvas.js", "odf/OdfContainer.js", "odf/Style2CSS.js", "odf/StyleInfo.js", "xmldom/LSSerializer.js", "xmldom/LSSerializerFilter.js", "xmldom/OperationalTransformDOM.js", 
  "xmldom/OperationalTransformInterface.js", "xmldom/RelaxNG.js", "xmldom/RelaxNG2.js", "xmldom/RelaxNGParser.js", "xmldom/XPath.js"]
})();

