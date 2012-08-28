/*global alert, app, window, runtime*/
var LocalFileSystem = {
    PERSISTENT: 0,
    TEMPORARY: 1
};
function FileEntry(name, fullPath) {
    "use strict";
    this.isFile = true;
    this.isDirectory = false;
    this.name = name;
    this.fullPath = fullPath;
    this.file = function (onsuccess, onerror) {
        function File(fullPath) {
            this.name = name;
            this.fullPath = fullPath;
            this.type = "";
            this.size = -1;
            this.lastModifiedDate = -1;
        }
        var file = new File(fullPath);
        try {
            onsuccess(file);
        } catch (e) {
            alert("Error on determining file properties: " + e);
            onerror(e);
        }
    };
}
function FileReader() {
    "use strict";
    var fr = this;
    this.readAsArrayBuffer = function (file) {
        var path = file.fullPath.substr(7),
            data = runtime.readFileSync(path, 'binary');
        data = runtime.byteArrayFromString(data, "binary");
        window.setTimeout(function () {
            fr.onloadend({target: {result: data}});
        }, 1);
    };
}
var DirectoryReader;
function DirectoryEntry(name, fullPath) {
    "use strict";
    this.isFile = false;
    this.isDirectory = true;
    this.name = name;
    this.fullPath = fullPath;
    this.createReader = function () {
        var reader = new DirectoryReader(fullPath);
        return reader;
    };
}
function DirectoryReader(fullPath) {
    "use strict";
    this.readEntries = function (onsuccess, onerror) {
        window.setTimeout(function () {
            var entries = [];
            entries[entries.length] = new FileEntry("welcome.odt",
                    "welcome.odt");
            entries[entries.length] = new FileEntry("Traktatenblad.odt",
                    "Traktatenblad.odt");
            try {
                onsuccess(entries);
            } catch (e) {
                onerror(e);
            }
        }, 1);
    };
}
window.resolveLocalFileSystemURI = function (path, onsuccess, onerror) {
    "use strict";
    var p = path.lastIndexOf("/"),
        name = (p === -1) ? path : path.substr(p + 1);
    onsuccess(new FileEntry(name, path));
};
window.requestFileSystem = function (filesystem, id, onsuccess, onerror) {
    "use strict";
    var dirs = [], shared, subfolder, path;
    try {
        if (filesystem === LocalFileSystem.PERSISTENT) {
            path = "";
            onsuccess({
                name: "root",
                root: new DirectoryEntry("root", path)
            });
        } else {
            onerror("not defined");
        }
    } catch (e) {
        onerror(e);
    }
};
var device = {};
