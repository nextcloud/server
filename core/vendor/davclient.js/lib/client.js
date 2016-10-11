if (typeof dav == 'undefined') { dav = {}; };

dav._XML_CHAR_MAP = {
    '<': '&lt;',
    '>': '&gt;',
    '&': '&amp;',
    '"': '&quot;',
    "'": '&apos;'
};

dav._escapeXml = function(s) {
    return s.replace(/[<>&"']/g, function (ch) {
        return dav._XML_CHAR_MAP[ch];
    });
};

dav.Client = function(options) {
    var i;
    for(i in options) {
        this[i] = options[i];
    }

};

dav.Client.prototype = {

    baseUrl : null,

    userName : null,

    password : null,


    xmlNamespaces : {
        'DAV:' : 'd'
    },

    /**
     * Generates a propFind request.
     *
     * @param {string} url Url to do the propfind request on
     * @param {Array} properties List of properties to retrieve.
     * @param {Object} [headers] headers
     * @return {Promise}
     */
    propFind : function(url, properties, depth, headers) {

        if(typeof depth == "undefined") {
            depth = 0;
        }

        headers = headers || {};

        headers['Depth'] = depth;
        headers['Content-Type'] = 'application/xml; charset=utf-8';

        var body =
            '<?xml version="1.0"?>\n' +
            '<d:propfind ';
        var namespace;
        for (namespace in this.xmlNamespaces) {
            body += ' xmlns:' + this.xmlNamespaces[namespace] + '="' + namespace + '"';
        }
        body += '>\n' +
            '  <d:prop>\n';

        for(var ii in properties) {

            var property = this.parseClarkNotation(properties[ii]);
            if (this.xmlNamespaces[property.namespace]) {
                body+='    <' + this.xmlNamespaces[property.namespace] + ':' + property.name + ' />\n';
            } else {
                body+='    <x:' + property.name + ' xmlns:x="' + property.namespace + '" />\n';
            }

        }
        body+='  </d:prop>\n';
        body+='</d:propfind>';

        return this.request('PROPFIND', url, headers, body).then(
            function(result) {

                if (depth===0) {
                    return {
                        status: result.status,
                        body: result.body[0],
                        xhr: result.xhr
                    };
                } else {
                    return {
                        status: result.status,
                        body: result.body,
                        xhr: result.xhr
                    };
                }

            }.bind(this)
        );

    },

    /**
     * Generates a propPatch request.
     *
     * @param {string} url Url to do the proppatch request on
     * @param {Array} properties List of properties to store.
     * @param {Object} [headers] headers
     * @return {Promise}
     */
    propPatch : function(url, properties, headers) {
        headers = headers || {};

        headers['Content-Type'] = 'application/xml; charset=utf-8';

        var body =
            '<?xml version="1.0"?>\n' +
            '<d:propertyupdate ';
        var namespace;
        for (namespace in this.xmlNamespaces) {
            body += ' xmlns:' + this.xmlNamespaces[namespace] + '="' + namespace + '"';
        }
        body += '>\n' +
            '  <d:set>\n' +
            '   <d:prop>\n';

        for(var ii in properties) {

            var property = this.parseClarkNotation(ii);
            var propName;
            var propValue = properties[ii];
            if (this.xmlNamespaces[property.namespace]) {
                propName = this.xmlNamespaces[property.namespace] + ':' + property.name;
            } else {
                propName = 'x:' + property.name + ' xmlns:x="' + property.namespace + '"';
            }
            body += '      <' + propName + '>' + dav._escapeXml(propValue) + '</' + propName + '>\n';
        }
        body+='    </d:prop>\n';
        body+='  </d:set>\n';
        body+='</d:propertyupdate>';

        return this.request('PROPPATCH', url, headers, body).then(
            function(result) {
                return {
                    status: result.status,
                    body: result.body,
                    xhr: result.xhr
                };
            }.bind(this)
        );

    },

    /**
     * Performs a HTTP request, and returns a Promise
     *
     * @param {string} method HTTP method
     * @param {string} url Relative or absolute url
     * @param {Object} headers HTTP headers as an object.
     * @param {string} body HTTP request body.
     * @return {Promise}
     */
    request : function(method, url, headers, body) {

        var self = this;
        var xhr = this.xhrProvider();
        headers = headers || {};
        
        if (this.userName) {
            headers['Authorization'] = 'Basic ' + btoa(this.userName + ':' + this.password);
            // xhr.open(method, this.resolveUrl(url), true, this.userName, this.password);
        }
        xhr.open(method, this.resolveUrl(url), true);
        var ii;
        for(ii in headers) {
            xhr.setRequestHeader(ii, headers[ii]);
        }

        // Work around for edge
        if (body === undefined) {
            xhr.send();
        } else {
            xhr.send(body);
        }

        return new Promise(function(fulfill, reject) {

            xhr.onreadystatechange = function() {

                if (xhr.readyState !== 4) {
                    return;
                }

                var resultBody = xhr.response;
                if (xhr.status === 207) {
                    resultBody = self.parseMultiStatus(xhr.response);
                }

                fulfill({
                    body: resultBody,
                    status: xhr.status,
                    xhr: xhr
                });

            };

            xhr.ontimeout = function() {

                reject(new Error('Timeout exceeded'));

            };

        });

    },

    /**
     * Returns an XMLHttpRequest object.
     *
     * This is in its own method, so it can be easily overridden.
     *
     * @return {XMLHttpRequest}
     */
    xhrProvider : function() {

        return new XMLHttpRequest();

    },

    /**
     * Parses a property node.
     *
     * Either returns a string if the node only contains text, or returns an
     * array of non-text subnodes.
     *
     * @param {Object} propNode node to parse
     * @return {string|Array} text content as string or array of subnodes, excluding text nodes
     */
    _parsePropNode: function(propNode) {
        var content = null;
        if (propNode.childNodes && propNode.childNodes.length > 0) {
            var subNodes = [];
            // filter out text nodes
            for (var j = 0; j < propNode.childNodes.length; j++) {
                var node = propNode.childNodes[j];
                if (node.nodeType === 1) {
                    subNodes.push(node);
                }
            }
            if (subNodes.length) {
                content = subNodes;
            }
        }

        return content || propNode.textContent || propNode.text || '';
    },

    /**
     * Parses a multi-status response body.
     *
     * @param {string} xmlBody
     * @param {Array}
     */
    parseMultiStatus : function(xmlBody) {

        var parser = new DOMParser();
        var doc = parser.parseFromString(xmlBody, "application/xml");

        var resolver = function(foo) {
            var ii;
            for(ii in this.xmlNamespaces) {
                if (this.xmlNamespaces[ii] === foo) {
                    return ii;
                }
            }
        }.bind(this);

        var responseIterator = doc.evaluate('/d:multistatus/d:response', doc, resolver, XPathResult.ANY_TYPE, null);

        var result = [];
        var responseNode = responseIterator.iterateNext();

        while(responseNode) {

            var response = {
                href : null,
                propStat : []
            };

            response.href = doc.evaluate('string(d:href)', responseNode, resolver, XPathResult.ANY_TYPE, null).stringValue;

            var propStatIterator = doc.evaluate('d:propstat', responseNode, resolver, XPathResult.ANY_TYPE, null);
            var propStatNode = propStatIterator.iterateNext();

            while(propStatNode) {

                var propStat = {
                    status : doc.evaluate('string(d:status)', propStatNode, resolver, XPathResult.ANY_TYPE, null).stringValue,
                    properties : [],
                };

                var propIterator = doc.evaluate('d:prop/*', propStatNode, resolver, XPathResult.ANY_TYPE, null);

                var propNode = propIterator.iterateNext();
                while(propNode) {
                    var content = this._parsePropNode(propNode);
                    propStat.properties['{' + propNode.namespaceURI + '}' + propNode.localName] = content;
                    propNode = propIterator.iterateNext();

                }
                response.propStat.push(propStat);
                propStatNode = propStatIterator.iterateNext();


            }

            result.push(response);
            responseNode = responseIterator.iterateNext();

        }

        return result;

    },

    /**
     * Takes a relative url, and maps it to an absolute url, using the baseUrl
     *
     * @param {string} url
     * @return {string}
     */
    resolveUrl : function(url) {

        // Note: this is rudamentary.. not sure yet if it handles every case.
        if (/^https?:\/\//i.test(url)) {
            // absolute
            return url;
        }

        var baseParts = this.parseUrl(this.baseUrl);
        if (url.charAt('/')) {
            // Url starts with a slash
            return baseParts.root + url;
        }

        // Url does not start with a slash, we need grab the base url right up until the last slash.
        var newUrl = baseParts.root + '/';
        if (baseParts.path.lastIndexOf('/')!==-1) {
            newUrl = newUrl = baseParts.path.subString(0, baseParts.path.lastIndexOf('/')) + '/';
        }
        newUrl+=url;
        return url;

    },

    /**
     * Parses a url and returns its individual components.
     *
     * @param {String} url
     * @return {Object}
     */
    parseUrl : function(url) {

         var parts = url.match(/^(?:([A-Za-z]+):)?(\/{0,3})([0-9.\-A-Za-z]+)(?::(\d+))?(?:\/([^?#]*))?(?:\?([^#]*))?(?:#(.*))?$/);
         var result = {
             url : parts[0],
             scheme : parts[1],
             host : parts[3],
             port : parts[4],
             path : parts[5],
             query : parts[6],
             fragment : parts[7],
         };
         result.root =
            result.scheme + '://' +
            result.host +
            (result.port ? ':' + result.port : '');

         return result;

    },

    parseClarkNotation : function(propertyName) {

        var result = propertyName.match(/^{([^}]+)}(.*)$/);
        if (!result) {
            return;
        }

        return {
            name : result[2],
            namespace : result[1]
        };

    }

};

