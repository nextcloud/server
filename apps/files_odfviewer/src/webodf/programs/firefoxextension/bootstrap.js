/*global Components: true, dump: true, Services: true*/

var Cc = Components.classes;
var Ci = Components.interfaces;
var Cm = Components.manager;
var Cu = Components.utils;

Cu["import"]('resource://gre/modules/Services.jsm');

function log(str) {
    "use strict";
    dump(str + '\n');
}

function startup(aData, aReason) {
    "use strict";
    var manifestPath = 'chrome.manifest',
        file = Cc['@mozilla.org/file/local;1'].createInstance(Ci.nsILocalFile);
    try {
        file.initWithPath(aData.installPath.path);
        file.append(manifestPath);
        Cm.QueryInterface(Ci.nsIComponentRegistrar).autoRegister(file);
    } catch (e) {
        log(e);
    }
}

function shutdown(aData, aReason) {
    "use strict";
}

function install(aData, aReason) {
    "use strict";
    var url = 'chrome://webodf.js/content/odf.html?file=%s';
    Services.prefs.setCharPref('extensions.webodf.js.url', url);
}
