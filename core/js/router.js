OC.router_base_url = OC.webroot + '/index.php',
OC.Router = {
	// register your ajax requests to load after the loading of the routes
	// has finished. otherwise you face problems with race conditions
	registerLoadedCallback: function(callback){
		this.routes_request.done(callback);
	},
	routes_request: $.ajax(OC.router_base_url + '/core/routes.json', {
		dataType: 'json',
		success: function(jsondata) {
			if (jsondata.status === 'success') {
				OC.Router.routes = jsondata.data;
			}
		}
	}),
	generate:function(name, opt_params) {
		if (!('routes' in this)) {
			if(this.routes_request.state() != 'resolved') {
				console.warn('To avoid race conditions, please register a callback');// wait
			}
		}
		if (!(name in this.routes)) {
			throw new Error('The route "' + name + '" does not exist.');
		}
		var route = this.routes[name];
		var params = opt_params || {};
		var unusedParams = $.extend(true, {}, params);
		var url = '';
		var optional = true;
		$(route.tokens).each(function(i, token) {
			if ('text' === token[0]) {
			    url = token[1] + url;
			    optional = false;

			    return;
			}

			if ('variable' === token[0]) {
			    if (false === optional || !(token[3] in route.defaults)
				    || ((token[3] in params) && params[token[3]] != route.defaults[token[3]])) {
				var value;
				if (token[3] in params) {
				    value = params[token[3]];
				    delete unusedParams[token[3]];
				} else if (token[3] in route.defaults) {
				    value = route.defaults[token[3]];
				} else if (optional) {
				    return;
				} else {
				    throw new Error('The route "' + name + '" requires the parameter "' + token[3] + '".');
				}

				var empty = true === value || false === value || '' === value;

				if (!empty || !optional) {
				    url = token[1] + encodeURIComponent(value).replace(/%2F/g, '/') + url;
				}

				optional = false;
			    }

			    return;
			}

			throw new Error('The token type "' + token[0] + '" is not supported.');
		});
		if (url === '') {
			url = '/';
		}

		unusedParams = $.param(unusedParams);
		if (unusedParams.length > 0) {
			url += '?'+unusedParams;
		}

		return OC.router_base_url + url;
	}
};
