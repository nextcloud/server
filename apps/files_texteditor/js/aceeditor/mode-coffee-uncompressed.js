/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Satoshi Murakami <murky.satyr AT gmail DOT com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/coffee', ['require', 'exports', 'module' , 'ace/tokenizer', 'ace/mode/coffee_highlight_rules', 'ace/mode/matching_brace_outdent', 'ace/mode/folding/pythonic', 'ace/range', 'ace/mode/text', 'ace/worker/worker_client', 'ace/lib/oop'], function(require, exports, module) {
"use strict";

var Tokenizer = require("../tokenizer").Tokenizer;
var Rules = require("./coffee_highlight_rules").CoffeeHighlightRules;
var Outdent = require("./matching_brace_outdent").MatchingBraceOutdent;
var PythonFoldMode = require("./folding/pythonic").FoldMode;
var Range = require("../range").Range;
var TextMode = require("./text").Mode;
var WorkerClient = require("../worker/worker_client").WorkerClient;
var oop = require("../lib/oop");

function Mode() {
    this.$tokenizer = new Tokenizer(new Rules().getRules());
    this.$outdent   = new Outdent();
    this.foldingRules = new PythonFoldMode("=|=>|->|\\s*class [^#]*");
}

oop.inherits(Mode, TextMode);

(function() {
    
    var indenter = /(?:[({[=:]|[-=]>|\b(?:else|switch|try|catch(?:\s*[$A-Za-z_\x7f-\uffff][$\w\x7f-\uffff]*)?|finally))\s*$/;
    var commentLine = /^(\s*)#/;
    var hereComment = /^\s*###(?!#)/;
    var indentation = /^\s*/;
    
    this.getNextLineIndent = function(state, line, tab) {
        var indent = this.$getIndent(line);
        var tokens = this.$tokenizer.getLineTokens(line, state).tokens;
    
        if (!(tokens.length && tokens[tokens.length - 1].type === 'comment') &&
            state === 'start' && indenter.test(line))
            indent += tab;
        return indent;
    };
    
    this.toggleCommentLines = function(state, doc, startRow, endRow){
        console.log("toggle");
        var range = new Range(0, 0, 0, 0);
        for (var i = startRow; i <= endRow; ++i) {
            var line = doc.getLine(i);
            if (hereComment.test(line))
                continue;
                
            if (commentLine.test(line))
                line = line.replace(commentLine, '$1');
            else
                line = line.replace(indentation, '$&#');
    
            range.end.row = range.start.row = i;
            range.end.column = line.length + 1;
            doc.replace(range, line);
        }
    };
    
    this.checkOutdent = function(state, line, input) {
        return this.$outdent.checkOutdent(line, input);
    };
    
    this.autoOutdent = function(state, doc, row) {
        this.$outdent.autoOutdent(doc, row);
    };
    
    this.createWorker = function(session) {
        var worker = new WorkerClient(["ace"], "worker-coffee.js", "ace/mode/coffee_worker", "Worker");
        worker.attachToDocument(session.getDocument());
        
        worker.on("error", function(e) {
            session.setAnnotations([e.data]);
        });
        
        worker.on("ok", function(e) {
            session.clearAnnotations();
        });
        
        return worker;
    };

}).call(Mode.prototype);

exports.Mode = Mode;

});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Satoshi Murakami <murky.satyr AT gmail DOT com>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/coffee_highlight_rules', ['require', 'exports', 'module' , 'ace/lib/lang', 'ace/lib/oop', 'ace/mode/text_highlight_rules'], function(require, exports, module) {
"use strict";

    var lang = require("../lib/lang");
    var oop = require("../lib/oop");
    var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;
    
    oop.inherits(CoffeeHighlightRules, TextHighlightRules);

    function CoffeeHighlightRules() {
        var identifier = "[$A-Za-z_\\x7f-\\uffff][$\\w\\x7f-\\uffff]*";
        var stringfill = {
            token : "string",
            merge : true,
            regex : ".+"
        };

        var keywords = lang.arrayToMap((
            "this|throw|then|try|typeof|super|switch|return|break|by)|continue|" +
            "catch|class|in|instanceof|is|isnt|if|else|extends|for|forown|" +
            "finally|function|while|when|new|no|not|delete|debugger|do|loop|of|off|" +
            "or|on|unless|until|and|yes").split("|")
        );
        
        var langConstant = lang.arrayToMap((
            "true|false|null|undefined").split("|")
        );
        
        var illegal = lang.arrayToMap((
            "case|const|default|function|var|void|with|enum|export|implements|" +
            "interface|let|package|private|protected|public|static|yield|" +
            "__hasProp|extends|slice|bind|indexOf").split("|")
        );
        
        var supportClass = lang.arrayToMap((
            "Array|Boolean|Date|Function|Number|Object|RegExp|ReferenceError|" +
            "RangeError|String|SyntaxError|Error|EvalError|TypeError|URIError").split("|")
        );
        
        var supportFunction = lang.arrayToMap((
            "Math|JSON|isNaN|isFinite|parseInt|parseFloat|encodeURI|" +
            "encodeURIComponent|decodeURI|decodeURIComponent|RangeError|String|" +
            "SyntaxError|Error|EvalError|TypeError|URIError").split("|")
        );

        this.$rules = {
            start : [
                {
                    token : "identifier",
                    regex : "(?:(?:\\.|::)\\s*)" + identifier
                }, {
                    token : "variable",
                    regex : "@(?:" + identifier + ")?"
                }, {
                    token: function(value) {
                        if (keywords.hasOwnProperty(value))
                            return "keyword";
                        else if (langConstant.hasOwnProperty(value))
                            return "constant.language";
                        else if (illegal.hasOwnProperty(value))
                            return "invalid.illegal";
                        else if (supportClass.hasOwnProperty(value))
                            return "language.support.class";
                        else if (supportFunction.hasOwnProperty(value))
                            return "language.support.function";
                        else
                            return "identifier";
                    },
                    regex : identifier
                }, {
                    token : "constant.numeric",
                    regex : "(?:0x[\\da-fA-F]+|(?:\\d+(?:\\.\\d+)?|\\.\\d+)(?:[eE][+-]?\\d+)?)"
                }, {
                    token : "string",
                    merge : true,
                    regex : "'''",
                    next : "qdoc"
                }, {
                    token : "string",
                    merge : true,
                    regex : '"""',
                    next : "qqdoc"
                }, {
                    token : "string",
                    merge : true,
                    regex : "'",
                    next : "qstring"
                }, {
                    token : "string",
                    merge : true,
                    regex : '"',
                    next : "qqstring"
                }, {
                    token : "string",
                    merge : true,
                    regex : "`",
                    next : "js"
                }, {
                    token : "string.regex",
                    merge : true,
                    regex : "///",
                    next : "heregex"
                }, {
                    token : "string.regex",
                    regex : "/(?!\\s)[^[/\\n\\\\]*(?: (?:\\\\.|\\[[^\\]\\n\\\\]*(?:\\\\.[^\\]\\n\\\\]*)*\\])[^[/\\n\\\\]*)*/[imgy]{0,4}(?!\\w)"
                }, {
                    token : "comment",
                    merge : true,
                    regex : "###(?!#)",
                    next : "comment"
                }, {
                    token : "comment",
                    regex : "#.*"
                }, {
                    token : "punctuation.operator",
                    regex : "\\?|\\:|\\,|\\."
                }, {
                    token : "keyword.operator",
                    regex : "(?:[\\-=]>|[-+*/%<>&|^!?=]=|>>>=?|\\-\\-|\\+\\+|::|&&=|\\|\\|=|<<=|>>=|\\?\\.|\\.{2,3}|\\!)"
                }, {
                    token : "paren.lparen",
                    regex : "[({[]"
                }, {
                    token : "paren.rparen",
                    regex : "[\\]})]"
                }, {
                    token : "text",
                    regex : "\\s+"
                }],
            
            qdoc : [{
                token : "string",
                regex : ".*?'''",
                next : "start"
            }, stringfill],
            
            qqdoc : [{
                token : "string",
                regex : '.*?"""',
                next : "start"
            }, stringfill],
            
            qstring : [{
                token : "string",
                regex : "[^\\\\']*(?:\\\\.[^\\\\']*)*'",
                merge : true,
                next : "start"
            }, stringfill],
            
            qqstring : [{
                token : "string",
                regex : '[^\\\\"]*(?:\\\\.[^\\\\"]*)*"',
                merge : true,
                next : "start"
            }, stringfill],
            
            js : [{
                token : "string",
                merge : true,
                regex : "[^\\\\`]*(?:\\\\.[^\\\\`]*)*`",
                next : "start"
            }, stringfill],
            
            heregex : [{
                token : "string.regex",
                regex : '.*?///[imgy]{0,4}',
                next : "start"
            }, {
                token : "comment.regex",
                regex : "\\s+(?:#.*)?"
            }, {
                token : "string.regex",
                merge : true,
                regex : "\\S+"
            }],
            
            comment : [{
                token : "comment",
                regex : '.*?###',
                next : "start"
            }, {
                token : "comment",
                merge : true,
                regex : ".+"
            }]
        };
    }

    exports.CoffeeHighlightRules = CoffeeHighlightRules;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/matching_brace_outdent', ['require', 'exports', 'module' , 'ace/range'], function(require, exports, module) {
"use strict";

var Range = require("../range").Range;

var MatchingBraceOutdent = function() {};

(function() {

    this.checkOutdent = function(line, input) {
        if (! /^\s+$/.test(line))
            return false;

        return /^\s*\}/.test(input);
    };

    this.autoOutdent = function(doc, row) {
        var line = doc.getLine(row);
        var match = line.match(/^(\s*\})/);

        if (!match) return 0;

        var column = match[1].length;
        var openBracePos = doc.findMatchingBracket({row: row, column: column});

        if (!openBracePos || openBracePos.row == row) return 0;

        var indent = this.$getIndent(doc.getLine(openBracePos.row));
        doc.replace(new Range(row, 0, row, column-1), indent);
    };

    this.$getIndent = function(line) {
        var match = line.match(/^(\s+)/);
        if (match) {
            return match[1];
        }

        return "";
    };

}).call(MatchingBraceOutdent.prototype);

exports.MatchingBraceOutdent = MatchingBraceOutdent;
});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/folding/pythonic', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/mode/folding/fold_mode'], function(require, exports, module) {
"use strict";

var oop = require("../../lib/oop");
var BaseFoldMode = require("./fold_mode").FoldMode;

var FoldMode = exports.FoldMode = function(markers) {
    this.foldingStartMarker = new RegExp("(?:([\\[{])|(" + markers + "))(?:\\s*)(?:#.*)?$");
};
oop.inherits(FoldMode, BaseFoldMode);

(function() {

    this.getFoldWidgetRange = function(session, foldStyle, row) {
        var line = session.getLine(row);
        var match = line.match(this.foldingStartMarker);
        if (match) {
            if (match[1])
                return this.openingBracketBlock(session, match[1], row, match.index);
            if (match[2])
                return this.indentationBlock(session, row, match.index + match[2].length);
            return this.indentationBlock(session, row);
        }
    }

}).call(FoldMode.prototype);

});/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/mode/folding/fold_mode', ['require', 'exports', 'module' , 'ace/range'], function(require, exports, module) {
"use strict";

var Range = require("../../range").Range;

var FoldMode = exports.FoldMode = function() {};

(function() {

    this.foldingStartMarker = null;
    this.foldingStopMarker = null;

    // must return "" if there's no fold, to enable caching
    this.getFoldWidget = function(session, foldStyle, row) {
        var line = session.getLine(row);
        if (this.foldingStartMarker.test(line))
            return "start";
        if (foldStyle == "markbeginend"
                && this.foldingStopMarker
                && this.foldingStopMarker.test(line))
            return "end";
        return "";
    };
    
    this.getFoldWidgetRange = function(session, foldStyle, row) {
        return null;
    };

    this.indentationBlock = function(session, row, column) {
        var re = /^\s*/;
        var startRow = row;
        var endRow = row;
        var line = session.getLine(row);
        var startColumn = column || line.length;
        var startLevel = line.match(re)[0].length;
        var maxRow = session.getLength()
        
        while (++row < maxRow) {
            line = session.getLine(row);
            var level = line.match(re)[0].length;

            if (level == line.length)
                continue;

            if (level <= startLevel)
                break;

            endRow = row;
        }

        if (endRow > startRow) {
            var endColumn = session.getLine(endRow).length;
            return new Range(startRow, startColumn, endRow, endColumn);
        }
    };

    this.openingBracketBlock = function(session, bracket, row, column) {
        var start = {row: row, column: column + 1};
        var end = session.$findClosingBracket(bracket, start);
        if (!end)
            return;

        var fw = session.foldWidgets[end.row];
        if (fw == null)
            fw = this.getFoldWidget(session, end.row);

        if (fw == "start") {
            end.row --;
            end.column = session.getLine(end.row).length;
        }
        return Range.fromPoints(start, end);
    };

}).call(FoldMode.prototype);

});
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Ajax.org Code Editor (ACE).
 *
 * The Initial Developer of the Original Code is
 * Ajax.org B.V.
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *      Fabian Jakobs <fabian AT ajax DOT org>
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 or later (the "GPL"), or
 * the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
 * in which case the provisions of the GPL or the LGPL are applicable instead
 * of those above. If you wish to allow use of your version of this file only
 * under the terms of either the GPL or the LGPL, and not to allow others to
 * use your version of this file under the terms of the MPL, indicate your
 * decision by deleting the provisions above and replace them with the notice
 * and other provisions required by the GPL or the LGPL. If you do not delete
 * the provisions above, a recipient may use your version of this file under
 * the terms of any one of the MPL, the GPL or the LGPL.
 *
 * ***** END LICENSE BLOCK ***** */

define('ace/worker/worker_client', ['require', 'exports', 'module' , 'ace/lib/oop', 'ace/lib/event_emitter'], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var EventEmitter = require("../lib/event_emitter").EventEmitter;

var WorkerClient = function(topLevelNamespaces, packagedJs, mod, classname) {

    this.changeListener = this.changeListener.bind(this);

    if (module.packaged) {
        var base = this.$guessBasePath();
        this.$worker = new Worker(base + packagedJs);
    }
    else {
        var workerUrl = this.$normalizePath(require.nameToUrl("ace/worker/worker", null, "_"));
        this.$worker = new Worker(workerUrl);

        var tlns = {};
        for (var i=0; i<topLevelNamespaces.length; i++) {
            var ns = topLevelNamespaces[i];
            var path = this.$normalizePath(require.nameToUrl(ns, null, "_").replace(/.js$/, ""));

            tlns[ns] = path;
        }
    }

    this.$worker.postMessage({
        init : true,
        tlns: tlns,
        module: mod,
        classname: classname
    });

    this.callbackId = 1;
    this.callbacks = {};

    var _self = this;
    this.$worker.onerror = function(e) {
        window.console && console.log && console.log(e);
        throw e;
    };
    this.$worker.onmessage = function(e) {
        var msg = e.data;
        switch(msg.type) {
            case "log":
                window.console && console.log && console.log(msg.data);
                break;

            case "event":
                _self._emit(msg.name, {data: msg.data});
                break;

            case "call":
                var callback = _self.callbacks[msg.id];
                if (callback) {
                    callback(msg.data);
                    delete _self.callbacks[msg.id];
                }
                break;
        }
    };
};

(function(){

    oop.implement(this, EventEmitter);

    this.$normalizePath = function(path) {
        path = path.replace(/^[a-z]+:\/\/[^\/]+\//, ""); // Remove domain name and rebuild it
        path = location.protocol + "//" + location.host
            // paths starting with a slash are relative to the root (host)
            + (path.charAt(0) == "/" ? "" : location.pathname.replace(/\/[^\/]*$/, ""))
            + "/" + path.replace(/^[\/]+/, "");
        return path;
    };

    this.$guessBasePath = function() {
        if (require.aceBaseUrl)
            return require.aceBaseUrl;

        var scripts = document.getElementsByTagName("script");
        for (var i=0; i<scripts.length; i++) {
            var script = scripts[i];

            var base = script.getAttribute("data-ace-base");
            if (base)
                return base.replace(/\/*$/, "/");

            var src = script.src || script.getAttribute("src");
            if (!src) {
                continue;
            }
            var m = src.match(/^(?:(.*\/)ace\.js|(.*\/)ace(-uncompressed)?(-noconflict)?\.js)(?:\?|$)/);
            if (m)
                return m[1] || m[2];
        }
        return "";
    };

    this.terminate = function() {
        this._emit("terminate", {});
        this.$worker.terminate();
        this.$worker = null;
        this.$doc.removeEventListener("change", this.changeListener);
        this.$doc = null;
    };

    this.send = function(cmd, args) {
        this.$worker.postMessage({command: cmd, args: args});
    };

    this.call = function(cmd, args, callback) {
        if (callback) {
            var id = this.callbackId++;
            this.callbacks[id] = callback;
            args.push(id);
        }
        this.send(cmd, args);
    };

    this.emit = function(event, data) {
        try {
            // firefox refuses to clone objects which have function properties
            // TODO: cleanup event
            this.$worker.postMessage({event: event, data: {data: data.data}});
        }
        catch(ex) {}
    };

    this.attachToDocument = function(doc) {
        if(this.$doc)
            this.terminate();

        this.$doc = doc;
        this.call("setValue", [doc.getValue()]);
        doc.on("change", this.changeListener);
    };

    this.changeListener = function(e) {
        e.range = {
            start: e.data.range.start,
            end: e.data.range.end
        };
        this.emit("change", e);
    };

}).call(WorkerClient.prototype);

exports.WorkerClient = WorkerClient;

});
;
            (function() {
                window.require(["ace/ace"], function(a) {
                    if (!window.ace)
                        window.ace = {};
                    for (var key in a) if (a.hasOwnProperty(key))
                        ace[key] = a[key];
                });
            })();
        