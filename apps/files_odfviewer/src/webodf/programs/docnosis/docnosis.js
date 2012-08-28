/*global runtime, Node, window, DOMParser, core, xmldom, NodeFilter, alert,
   FileReader*/
runtime.loadClass("core.Zip");
runtime.loadClass("core.Base64");
runtime.loadClass("xmldom.RelaxNG");

/** This code runs a number of tests on an ODF document.
 * Ideally, it would use ODFContainer, but for now, it uses a custome container
 * for loaded odf files.
 */

function conformsToPattern(object, pattern, name) {
    "use strict";
    var i;
    if (object === undefined || object === null) {
        return pattern === null || (typeof pattern) !== "object";
    }
    for (i in pattern) {
        if (pattern.hasOwnProperty(i)) {
            if (!(object.hasOwnProperty(i) ||
                        (i === "length" && object.length)) ||
                    !conformsToPattern(object[i], pattern[i], i)) {
                return false;
            }
        }
    }
    return true;
}

function getConformingObjects(object, pattern, name) {
    "use strict";
    var c = [], i, j;
    name = name || "??";
    // we do not look inside long arrays and strings atm,
    // detection of these types could be better
    function accept(object) {
        return object !== null && object !== undefined &&
            (typeof object) === "object" &&
            (object.length === undefined || object.length < 1000) &&
            !(object instanceof Node) &&
            !(object.constructor && object.constructor === window.Uint8Array);
    }
    for (i in object) {
        if (object.hasOwnProperty(i) && accept(object[i])) {
            c = c.concat(getConformingObjects(object[i], pattern, i));
        }
    }
    if (conformsToPattern(object, pattern, "?")) {
        c.push(object);
    }
    return c;
}
function parseXml(data, errorlog, name) {
    "use strict";
    function getText(e) {
        var str = "", c = e.firstChild;
        while (c) {
            if (c.nodeType === 3) {
                str += c.nodeValue;
            } else {
                str += getText(c);
            }
            c = c.nextSibling;
        }
        return str;
    }
    var str, parser, errorelements;
    try {
        str = runtime.byteArrayToString(data, "utf8");
        parser = new DOMParser();
        str = parser.parseFromString(str, "text/xml");
        if (str.documentElement.localName === "parsererror"
                || str.documentElement.localName === "html") {
            errorelements = str.getElementsByTagName("parsererror");
            if (errorelements.length > 0) {
                errorlog.push("invalid XML in " + name + ": " +
                    getText(errorelements[0]));
                str = null;
            }
        }
    } catch (err) {
        errorlog.push(err);
    }
    return str;
}

/*** the jobs / tests ***/

function ParseXMLJob() {
    "use strict";
    this.inputpattern = { file: { entries: [] } };
    this.outputpattern  = {
        file: { entries: [] },
        errors: { parseXmlErrors: [] },
        content_xml: null,
        manifest_xml: null,
        settings_xml: null,
        meta_xml: null,
        styles_xml: null
    };
    function parseXmlFiles(input, position, callback) {
        var e = input.file.entries,
            filename,
            ext,
            dom;
        if (position >= e.length) {
            return callback();
        }
        filename = e[position].filename;
        ext = filename.substring(filename.length - 4);
        if (ext === ".xml" || ext === ".rdf") {
            dom = parseXml(e[position].data, input.errors.parseXmlErrors,
                    filename);
            if (filename === "content.xml") {
                input.content_xml = dom;
            } else if (filename === "META-INF/manifest.xml") {
                input.manifest_xml = dom;
            } else if (filename === "styles.xml") {
                input.styles_xml = dom;
            } else if (filename === "meta.xml") {
                input.meta_xml = dom;
            } else if (filename === "settings.xml") {
                input.settings_xml = dom;
            }
            e[position].dom = dom;
        }
        window.setTimeout(function () {
            parseXmlFiles(input, position + 1, callback);
        }, 0);
    }
    this.run = function (input, callback) {
        input.errors = input.errors || {};
        input.errors.parseXmlErrors = [];
        input.content_xml = null;
        input.manifest_xml = null;
        input.styles_xml = null;
        input.meta_xml = null;
        input.settings_xml = null;
        parseXmlFiles(input, 0, callback);
    };
}
function UnpackJob() {
    "use strict";
    this.inputpattern = { file: { path: "", data: { length: 0 } } };
    this.outputpattern  = {
        file: { entries: [], dom: null }, errors: { unpackErrors: [] }
    };
    function getText(e) {
        var str = "", c = e.firstChild;
        while (c) {
            if (c.nodeType === 3) {
                str += c.nodeValue;
            } else {
                str += getText(c);
            }
            c = c.nextSibling;
        }
        return str;
    }
    function loadZipEntries(input, position, callback) {
        if (position >= input.file.entries.length) {
            return callback();
        }
        var e = input.file.entries[position];
        e.load(function (err, data) {
            if (err) {
                input.errors.unpackErrors.push(err);
            }
            e.error = err;
            e.data = data;
            window.setTimeout(function () {
                loadZipEntries(input, position + 1, callback);
            }, 0);
        });
    }
    function loadZip(input, callback) {
        var zip = new core.Zip(input.file.path, function (err, zip) {
            var i;
            if (err) {
                input.errors.unpackErrors.push(err);
                callback();
            } else {
                input.file.entries = zip.getEntries();
                loadZipEntries(input, 0, callback);
            }
        });
    }
    function loadXml(input, callback) {
        input.file.dom = parseXml(input.file.data, input.errors.unpackErrors,
                input.file.name);
        callback();
    }
    this.run = function (input, callback) {
        input.errors = input.errors || {};
        input.errors.unpackErrors = [];
        input.file.dom = null;
        input.file.entries = [];

        if (input.file.data.length < 1) {
            input.errors.unpackErrors.push("Input data is empty.");
            return;
        }
        if (input.file.data[0] === 80) { // a ZIP file starts with 'P'
            loadZip(input, callback);
        } else {
            loadXml(input, callback);
        }
    };
}
function MimetypeTestJob(odffile) {
    "use strict";
    this.inputpattern = {
        file: { entries: [], dom: null },
        manifest_xml: null
    };
    this.outputpattern = { mimetype: "", errors: { mimetypeErrors: [] } };
    var manifestns = "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0";
    function getManifestMimetype(manifest) {
        if (!manifest) {
            return null;
        }
        var path, mimetype, node;
        node = manifest.documentElement.firstChild;
        while (node) {
            if (node.nodeType === 1 && node.localName === "file-entry" &&
                    node.namespaceURI === manifestns) {
                path = node.getAttributeNS(manifestns, "full-path");
                if (path === "/") {
                    mimetype = node.getAttributeNS(manifestns, "media-type");
                    break;
                }
            }
            node = node.nextSibling;
        }
        return mimetype;
    }
    this.run = function (input, callback) {
        input.mimetype = null;
        input.errors.mimetypeErrors = [];
        var mime = null,
            altmime,
            e = input.file.entries,
            i;
        if (input.file.dom) {
            mime = input.file.dom.documentElement.getAttributeNS(
                "urn:oasis:names:tc:opendocument:xmlns:office:1.0", "mimetype");
        } else {
            if (e.length < 1 || e[0].filename !== "mimetype") {
                input.errors.mimetypeErrors.push(
                        "First file in zip is not 'mimetype'");
            }
            for (i = 0; i < e.length; i += 1) {
                if (e[i].filename === "mimetype") {
                    mime = runtime.byteArrayToString(e[i].data, "binary");
                    break;
                }
            }
            if (mime) {
                altmime = input.file.data.slice(38, 38 + mime.length);
                altmime = runtime.byteArrayToString(altmime, "binary");
                if (mime !== altmime) {
                    input.errors.mimetypeErrors.push(
                           "mimetype should start at byte 38 in the zip file.");
                }
            }
            // compare with mimetype from manifest_xml
            altmime = getManifestMimetype(input.manifest_xml);
            if (altmime !== mime) {
                input.errors.mimetypeErrors.push(
                    "manifest.xml has a different mimetype.");
            }
        }
        if (!mime) {
            input.errors.mimetypeErrors.push("No mimetype was found.");
        }
        input.mimetype = mime;
        callback();
    };
}
function VersionTestJob() {
    "use strict";
    this.inputpattern = {
        file: { dom: null },
        content_xml: null,
        styles_xml: null,
        meta_xml: null,
        settings_xml: null,
        manifest_xml: null
    };
    this.outputpattern = { version: "", errors: { versionErrors: [] } };
    var officens = "urn:oasis:names:tc:opendocument:xmlns:office:1.0";
    function getVersion(dom, filename, log, vinfo, filerequired) {
        var v, ns = officens;
        if (!dom) {
            if (filerequired) {
                log.push(filename + " is missing, so version cannot be found.");
            }
            return;
        }
        if (filename === "META-INF/manifest.xml") {
            ns = "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0";
        }
        if (!dom.documentElement.hasAttributeNS(ns, "version")) {
            if (vinfo.versionrequired) {
                log.push(filename + " has no version number.");
            }
            return;
        }
        v = dom.documentElement.getAttributeNS(ns, "version");
        if (vinfo.version === undefined) {
            vinfo.version = v;
            // version number is required since ODF 1.2
            vinfo.needversion = vinfo.version === "1.2";
            vinfo.versionSource = filename;
        } else if (v !== vinfo.version) {
            log.push(vinfo.versionSource + " and " + filename + " " +
                    " have  different version number.");
        }
    }
    this.run = function (input, callback) {
        input.errors.versionErrors = [];
        var v,
            e = input.file.entries,
            log = input.errors.versionErrors,
            vinfo = {
                version: undefined,
                needversion: null,
                versionSource: null
            },
            contentxmlhasnoversionnumber;
        if (input.file.dom) {
            getVersion(input.file.dom, input.file.name, log, vinfo, true);
        } else {
            // until we know the version number, we cannot claim that
            // content.xml needs a version number
            getVersion(input.content_xml, "content.xml", log, vinfo, true);
            contentxmlhasnoversionnumber = vinfo.version === undefined;
            getVersion(input.manifest_xml, "META-INF/manifest.xml", log,
                    vinfo, true);
            getVersion(input.styles_xml, "styles.xml", log, vinfo);
            getVersion(input.meta_xml, "meta.xml", log, vinfo);
            getVersion(input.settings_xml, "settings.xml", log, vinfo);
            if (vinfo.needversion && contentxmlhasnoversionnumber) {
                log.push("content.xml has no version number.");
            }
        }
        input.version = vinfo.version;
        callback();
    };
}
function GetThumbnailJob() {
    "use strict";
    var base64 = new core.Base64();
    this.inputpattern = { file: { entries: [] }, errors: {}, mimetype: "" };
    this.outputpattern = { thumbnail: "", errors: { thumbnailErrors: [] } };
    this.run = function (input, callback) {
        input.thumbnail = null;
        input.errors.thumbnailErrors = [];
        var i, e = input.file.entries, mime = input.mimetype, thumb = null;
        if (mime === "application/vnd.oasis.opendocument.text") {
            thumb = "application-vnd.oasis.opendocument.text.png";
        } else if (mime === "application/vnd.oasis.opendocument.spreadsheet") {
            thumb = "application-vnd.oasis.opendocument.spreadsheet.png";
        } else if (mime === "application/vnd.oasis.opendocument.presentation") {
            thumb = "application-vnd.oasis.opendocument.presentation.png";
        }
        for (i = 0; i < e.length; i += 1) {
            if (e[i].filename === "Thumbnails/thumbnail.png") {
                thumb = "data:image/png;base64," +
                        base64.convertUTF8ArrayToBase64(e[i].data);
                break;
            }
        }
        input.thumbnail = thumb;
        callback();
    };
}
function RelaxNGJob() {
    "use strict";
    var parser = new xmldom.RelaxNGParser(),
        validators = {};
    this.inputpattern = { file: {dom: null}, version: null };
    this.outputpattern = { errors: { relaxngErrors: [] } };
    function loadValidator(ns, version, callback) {
        var rng;
        if (ns === "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0") {
            if (version === "1.2") {
                rng = "OpenDocument-v1.2-cos01-manifest-schema.rng";
            } else if (version === "1.1") {
                rng = "OpenDocument-manifest-schema-v1.1.rng";
            } else if (version === "1.0") {
                rng = "OpenDocument-manifest-schema-v1.0-os.rng";
            }
        } else if (ns === "urn:oasis:names:tc:opendocument:xmlns:office:1.0") {
            if (version === "1.2") {
                rng = "OpenDocument-v1.2-cos01-schema.rng";
            } else if (version === "1.1") {
                rng = "OpenDocument-schema-v1.1.rng";
            } else if (version === "1.0") {
                rng = "OpenDocument-schema-v1.0-os.rng";
            }
        }
        if (rng) {
            runtime.loadXML(rng, function (err, dom) {
                var relaxng;
                if (err) {
                    runtime.log(err);
                } else {
                    relaxng = new xmldom.RelaxNG();
                    err = parser.parseRelaxNGDOM(dom, relaxng.makePattern);
                    if (err) {
                        runtime.log(err);
                    } else {
                        relaxng.init(parser.rootPattern);
                    }
                }
                validators[ns] = validators[ns] || {};
                validators[ns][version] = relaxng;
                callback(relaxng);
            });
        } else {
            callback(null);
        }
    }
    function getValidator(ns, version, callback) {
        if (ns === "urn:oasis:names:tc:opendocument:xmlns:office:1.0" ||
                ns === "urn:oasis:names:tc:opendocument:xmlns:manifest:1.0") {
            if (!version) {
                version = "1.1";
            }
        }
        if (validators[ns] && validators[ns][version]) {
            return callback(validators[ns][version]);
        }
        loadValidator(ns, version, callback);
    }
    function validate(log, dom, filename, version, callback) {
        var ns = dom.documentElement.namespaceURI;
        getValidator(ns, version, function (relaxng) {
            if (!relaxng) {
                return callback();
            }
            var walker = dom.createTreeWalker(dom.firstChild, 0xFFFFFFFF,
                    { acceptNode: function(node) {
                        return NodeFilter.FILTER_ACCEPT; }
                    }, false),
                err;
runtime.log("START VALIDATING");
            err = relaxng.validate(walker, function (err) {
runtime.log("FINISHED VALIDATING");
                var i;
                if (err) {
                    for (i = 0; i < err.length; i += 1) {
                        log.push(filename + ": " + err[i]);
                    }
                }
                callback();
            });
        });
    }
    function validateEntries(log, entries, position, version, callback) {
        if (position >= entries.length) {
            return callback();
        }
        var e = entries[position];
        if (e.dom) {
            validate(log, e.dom, e.filename, version, function () {
                window.setTimeout(function () {
                    validateEntries(log, entries, position + 1, version,
                            callback);
                }, 0);
            });
        } else {
            validateEntries(log, entries, position + 1, version, callback);
        }
    }
    this.run = function (input, callback) {
        input.errors = input.errors || {};
        input.errors.relaxngErrors = [];
        runtime.log(input.version);
        if (input.file.dom) {
            validate(input.errors.relaxngErrors, input.file.dom,
                input.file.path, input.version, callback);
            return;
        }
        var i, e = input.file.entries;
        validateEntries(input.errors.relaxngErrors, input.file.entries, 0,
            input.version, callback);
    };
}

function DataRenderer(parentelement) {
    "use strict";
    var doc = parentelement.ownerDocument,
        element = doc.createElement("div"),
        lastrendertime,
        delayedRenderComing,
        renderinterval = 300; // minimal milliseconds between renders
    function clear(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }
    function addParagraph(div, text) {
        var p = doc.createElement("p");
        p.appendChild(doc.createTextNode(text));
        div.appendChild(p);
    }
    function addSpan(parent, nodename, text) {
        var e = doc.createElement(nodename);
        e.appendChild(doc.createTextNode(text));
        parent.appendChild(e);
    }
    function addErrors(div, e, active) {
        var i, o;
        for (i in e) {
            if (e.hasOwnProperty(i)) {
                o = e[i];
                if (active && ((typeof o) === "string"
                        || o instanceof String)) {
                    addParagraph(div, o);
                } else if (o && (typeof o) === "object" &&
                        !(o instanceof Node) &&
                        !(o.constructor &&
                          o.constructor === window.Uint8Array)) {
                    addErrors(div, o, active || i === "errors");
                }
            }
        }
    }
    function renderFile(data) {
        var div = doc.createElement("div"),
            h1 = doc.createElement("h1"),
            icon = doc.createElement("img");
        div.style.clear = "both";
        div.appendChild(h1);
        div.appendChild(icon);
        h1.appendChild(doc.createTextNode(data.file.path));
        element.appendChild(div);
        if (data.thumbnail) {
            icon.src = data.thumbnail;
        }
        icon.style.width = "128px";
        icon.style.float = "left";
        icon.style.mozBoxShadow = icon.style.webkitBoxShadow =
                icon.style.boxShadow = "3px 3px 4px #000";
        icon.style.marginRight = icon.style.marginBottom = "10px";
        addParagraph(div, "mimetype: " + data.mimetype);
        addParagraph(div, "version: " + data.version);
        addParagraph(div, "document representation: " +
                ((data.file.dom) ? "single XML document" :"package"));
        addErrors(div, data, false);
    }
    function dorender(data) {
        clear(element);
        var i;
        for (i = 0; i < data.length; i += 1) {
            renderFile(data[i]);
        }
    }
    this.render = function render(data) {
        var now = Date.now();
        if (!lastrendertime || now - lastrendertime > renderinterval) {
            lastrendertime = now;
            dorender(data);
        } else if (!delayedRenderComing) {
            delayedRenderComing = true;
            window.setTimeout(function () {
                delayedRenderComing = false;
                lastrendertime = now + renderinterval;
                dorender(data);
            }, renderinterval);
        }
    };
    parentelement.appendChild(element);
}

function JobRunner(datarenderer) {
    "use strict";
    var jobrunner = this,
        jobtypes = [],
        data,
        busy = false,
        todo = [];
        
    jobtypes.push(new UnpackJob());
    jobtypes.push(new MimetypeTestJob());
    jobtypes.push(new GetThumbnailJob());
    jobtypes.push(new VersionTestJob());
    jobtypes.push(new ParseXMLJob());
    jobtypes.push(new RelaxNGJob());

    function run() {
        if (busy) {
           return;
        }
        var job = todo.shift();
        if (job) {
            busy = true;
            job.job.run(job.object, function () {
                busy = false;
                if (!conformsToPattern(job.object, job.job.outputpattern)) {
                    throw "Job does not give correct output.";
                }
                datarenderer.render(data);
                window.setTimeout(run, 0);
            });
        }
    }

    function update(ignore, callback) {
        var i, jobtype, j, inobjects, outobjects;
        todo = [];
        for (i = 0; i < jobtypes.length; i += 1) {
            jobtype = jobtypes[i];
            inobjects = getConformingObjects(data, jobtype.inputpattern);
            outobjects = getConformingObjects(data, jobtype.outputpattern);
            for (j = 0; j < inobjects.length; j += 1) {
                if (outobjects.indexOf(inobjects[j]) === -1) {
                    todo.push({job: jobtype, object: inobjects[j]});
                }
            }
        }
        if (todo.length > 0) {
            // run update again after all todos are done
            todo.push({job: jobrunner, object: null});
        }
        if (callback) {
            callback();
        } else {
            run();
        }
    }

    this.run = update;

    this.setData = function setData(newdata) {
        data = newdata;
        if (busy) {
            todo = [];
            todo.push({job: jobrunner, object: null});
        } else {
            update();
        }
    };
}
function LoadingFile(file) {
    "use strict";
    var data,
        error,
        readRequests = [];
    function load(callback) {
        var reader = new FileReader();
        reader.onloadend = function(evt) {
            data = runtime.byteArrayFromString(evt.target.result, "binary");
            error = evt.target.error && String(evt.target.error);
            var i = 0;
            for (i = 0; i < readRequests.length; i += 1) {
                readRequests[i]();
            }
            readRequests = undefined;
            reader = undefined;
            callback(error, data);
        };
        reader.readAsBinaryString(file);
    }
    this.file = file;
    this.read = function (offset, length, callback) {
        function read() {
            if (error) {
                return callback(error);
            }
            if (data) {
                return callback(error, data.slice(offset, offset + length));
            }
            readRequests.push(read);
        }
        read();
    };
    this.load = load;
}
function Docnosis(element) {
    "use strict";
    var doc = element.ownerDocument,
        form,
        diagnoses = doc.createElement("div"),
        openedFiles = {},
        datarenderer = new DataRenderer(diagnoses),
        jobrunner = new JobRunner(datarenderer),
        jobrunnerdata = [];

    function dragHandler(evt) {
        var over = evt.type === "dragover" && evt.target.nodeName !== "INPUT";
        if (over || evt.type === "drop") {
            evt.stopPropagation();
            evt.preventDefault();
        }
        if (evt.target.style) {
            evt.target.style.background = (over ? "#CCCCCC" : "inherit");
        }
    }

    function fileSelectHandler(evt) {
        // cancel event and hover styling
        dragHandler(evt);

        function diagnoseFile(file) {
            var loadingfile, path;
            path = file.name;
            loadingfile = new LoadingFile(file);
            openedFiles[path] = loadingfile;
            loadingfile.load(function (error, data) {
                jobrunnerdata.push({file:{
                    path: path,
                    data: data
                }});
                jobrunner.setData(jobrunnerdata);
            });
        }
        // process all File objects
        var i, files, div;
        files = (evt.target && evt.target.files) ||
                (evt.dataTransfer && evt.dataTransfer.files);
        if (files) {
            for (i = 0; files && i < files.length; i += 1) {
                div = doc.createElement("div");
                diagnoses.appendChild(div);
                diagnoseFile(files[i]);
            }
        } else {
            alert("File(s) could not be opened in this browser.");
        }
    }

    function createForm() {
        var form = doc.createElement("form"),
            fieldset = doc.createElement("fieldset"),
            legend = doc.createElement("legend"),
            input = doc.createElement("input");
        form = doc.createElement("form");
        form.appendChild(fieldset);
        fieldset.appendChild(legend);
        input.setAttribute("type", "file");
        input.setAttribute("name", "fileselect[]");
        input.setAttribute("multiple", "multiple");
        input.addEventListener("change", fileSelectHandler, false);
        fieldset.appendChild(input);
        fieldset.appendChild(doc.createTextNode("or drop files here"));
        legend.appendChild(doc.createTextNode("docnosis"));
        form.addEventListener("dragover", dragHandler, false);
        form.addEventListener("dragleave", dragHandler, false);
        form.addEventListener("drop", fileSelectHandler, false);
        return form;
    }

    function enhanceRuntime() {
        var read = runtime.read,
            getFileSize = runtime.getFileSize;
        runtime.read = function (path, offset, length, callback) {
            if (openedFiles.hasOwnProperty(path)) {
                return openedFiles[path].read(offset, length, callback);
            } else {
                return read(path, offset, length, callback);
            }
        };
        runtime.getFileSize = function (path, callback) {
            if (openedFiles.hasOwnProperty(path)) {
                return callback(openedFiles[path].file.size);
            } else {
                return getFileSize(path, callback);
            }
        };
    }

    form = createForm();
    element.appendChild(form);
    element.appendChild(diagnoses);
    enhanceRuntime();
}
