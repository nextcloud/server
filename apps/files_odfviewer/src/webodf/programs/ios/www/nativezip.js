/*global PhoneGap, core*/

var ZipPlugin = {
    loadAsString: function (zippath, entrypath, success, fail) {
        "use strict";
        return PhoneGap.exec(success, fail, "ZipClass", "loadAsString", [zippath, entrypath]);
    },
    loadAsDataURL: function (zippath, entrypath, mimetype, success, fail) {
        "use strict";
        return PhoneGap.exec(success, fail, "ZipClass", "loadAsDataURL", [zippath, entrypath, mimetype]);
    }
};
core.Zip = function (url, entriesReadCallback) {
    "use strict";
    // remove 'odf:' prefix
    url = url.substr(4);
    var zip = this;
    this.load = function (filename, callback) {
        //alert(filename);
        callback(null, "");
    };
    this.loadAsString = function (filename, callback) {
        alert("loadAsString");
    };
    this.loadAsDOM = function (filename, callback) {
        var xhr = new XMLHttpRequest();
        function handleResult() {
            var xml;
            console.log("loading " + filename + " status " + xhr.status + " readyState " + xhr.readyState);
            if (xhr.readyState === 4) {
                xml = xhr.responseXML;
                console.log("done accessing responseXML " + xml + " " + (xhr.responseText && xhr.responseText.length)
                    + " " + xhr.statusText);
                console.log("statusText " + xhr.statusText);
                if (xhr.status === 0 && !xml) {
                    // empty files are considered as errors
                    callback("File " + path + " is not valid XML.");
                } else if (xhr.status === 200 || xhr.status === 0) {
                    try {
                        callback(null, xml);
                    } catch (e) {
                        console.log(e);
                    }
                } else {
                    // report error
                    callback(xhr.responseText || xhr.statusText);
                }
            }
        }
        xhr.open('GET', "http://zipserver" + url + "?" + filename, true);
        xhr.onreadystatechange = handleResult;
        xhr.send(null);
    };
    this.loadAsDataURL = function (filename, mimetype, callback) {
        callback(null, "http://zipserver" + url + "?" + filename);
        /*
        ZipPlugin.loadAsDataURL(url, filename, mimetype,
            function (content) {
                callback(null, content);
            },
            function (err) { callback(err, null); }
            );
            */
    };
    this.getEntries = function () {
        alert("getEntries");
    };
    this.loadContentXmlAsFragments = function (filename, handler) {
        // the javascript implementation simply reads the file
        zip.loadAsString(filename, function (err, data) {
            if (err) {
                return handler.rootElementReady(err);
            }
            handler.rootElementReady(null, data, true);
        });
    };
    this.save = function () {
        alert("save");
    };
    this.write = function () {
        alert("write");
    };
    entriesReadCallback(null, this);
};
