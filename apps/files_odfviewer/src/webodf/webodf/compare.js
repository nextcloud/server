/*global runtime: true, odf: true*/
/*jslint white: false*/
function getURIParameters(window) {
    "use strict";
    var params = {},
        query = window.location.search.substring(1),
        parms = query.split('&'),
        i,
        pos,
        key,
        val;
    for (i = 0; i < parms.length; i += 1) {
        pos = parms[i].indexOf('=');
        if (pos > 0) {
            key = parms[i].substring(0, pos);
            val = parms[i].substring(pos + 1);
            params[key] = val;
        }
    }
    return params;
}
function init(window, document) {
    "use strict";
    runtime.loadClass("odf.OdfCanvas");
    var params = getURIParameters(window),
        odfelement = document.getElementById("odf"),
        odfcanvas = new odf.OdfCanvas(odfelement);
    if (!params.odf) {
        return;
    }
    odfcanvas.addListener("statereadychange", function () {
        var s = odfelement.style,
            bgzoom = "100% auto",
            pos;
        s.backgroundImage = "url(" + params.bg + ")";
        s.backgroundRepeat = "no-repeat";
        if (params.bgzoom) {
            bgzoom = params.bgzoom + "% auto";
        }
        s.backgroundSize = bgzoom;
        pos = (params.x || "0") + "px " + (params.y || "0") + "px";
        s.backgroundPosition = pos;
    });
    odfcanvas.load(params.odf);
}
