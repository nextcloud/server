/**
 * translate a string
 * @param app the id of the app for which to translate the string
 * @param text the string to translate
 * @return string
 */
function t(app,text){
	if( !( app in t.cache )){
		
		$.post( OC.filePath('core','ajax','translations.php'), {'app': app}, function(jsondata){
			t.cache[app] = jsondata.data;
		});

		// Bad answer ...
		if( !( app in t.cache )){
			t.cache[app] = [];
		}
	}
	if( typeof( t.cache[app][text] ) !== 'undefined' ){
		return t.cache[app][text];
	}
	else{
		return text;
	}
}
t.cache={};

OC={
	webroot:oc_webroot,
	coreApps:['files','admin','log','search','settings','core'],
	/**
	 * get an absolute url to a file in an appen
	 * @param app the id of the app the file belongs to
	 * @param file the file path relative to the app folder
	 * @return string
	 */
	linkTo:function(app,file){
		return OC.filePath(app,'',file);
	},
	/**
	 * get the absolute url for a file in an app
	 * @param app the id of the app
	 * @param type the type of the file to link to (e.g. css,img,ajax.template)
	 * @param file the filename
	 * @return string
	 */
	filePath:function(app,type,file){
		var isCore=OC.coreApps.indexOf(app)!=-1;
		app+='/';
		var link=OC.webroot+'/';
		if(!isCore){
			link+='apps/';
		}
		link+=app;
		if(type){
			link+=type+'/'
		}
		link+=file;
		return link;
	},
	/**
	 * get the absolute path to an image file
	 * @param app the app id to which the image belongs
	 * @param file the name of the image file
	 * @return string
	 * 
	 * if no extention is given for the image, it will automatically decide between .png and .svg based on what the browser supports
	 */ 
	imagePath:function(app,file){
		if(file.indexOf('.')==-1){//if no extention is given, use png or svg depending on browser support
			file+=(SVGSupport())?'.svg':'.png'
		}
		return OC.filePath(app,'img',file);
	},
	/**
	 * load a script for the server and load it
	 * @param app the app id to which the script belongs
	 * @param script the filename of the script
	 * @param ready event handeler to be called when the script is loaded
	 * 
	 * if the script is already loaded, the event handeler will be called directly
	 */
	addScript:function(app,script,ready){
		var path=OC.filePath(app,'js',script+'.js');
		if(OC.addStyle.loaded.indexOf(path)==-1){
			OC.addStyle.loaded.push(path);
			if(ready){
				$.getScript(path,ready);
			}else{
				$.getScript(path);
			}
		}else{
			if(ready){
				ready();
			}
		}
	},
	/**
	 * load a css file and load it
	 * @param app the app id to which the css style belongs
	 * @param style the filename of the css file
	 */
	addStyle:function(app,style){
		var path=OC.filePath(app,'css',style+'.css');
		if(OC.addScript.loaded.indexOf(path)==-1){
			OC.addScript.loaded.push(path);
			var style=$('<link rel="stylesheet" type="text/css" href="'+path+'"/>');
			$('head').append(style);
		}
	},
	/**
	 * do a search query and display the results
	 * @param query the search query
	 */
	search:function(query){
		if(query){
			OC.addStyle('search','results');
			$.getJSON(OC.filePath('search','ajax','search.php')+'?query='+encodeURIComponent(query), function(results){
				OC.search.lastResults=results;
				OC.search.showResults(results);
			});
		}
	}
}
OC.search.customResults={};
OC.search.currentResult=-1;
OC.search.lastQuery='';
OC.search.lastResults={};
OC.addStyle.loaded=[];
OC.addScript.loaded=[];

/**
 * implement Array.filter for browsers without native support
 */
if (!Array.prototype.filter) {
	Array.prototype.filter = function(fun /*, thisp*/) {
		var len = this.length >>> 0;
		if (typeof fun != "function")
			throw new TypeError();
		
		var res = [];
		var thisp = arguments[1];
		for (var i = 0; i < len; i++) {
			if (i in this) {
				var val = this[i]; // in case fun mutates this
				if (fun.call(thisp, val, i, this))
					res.push(val);
			}
		}
		return res;
	}
}
/**
 * implement Array.indexOf for browsers without native support
 */
if (!Array.prototype.indexOf){
	Array.prototype.indexOf = function(elt /*, from*/)
	{
		var len = this.length;
		
		var from = Number(arguments[1]) || 0;
		from = (from < 0)
		? Math.ceil(from)
		: Math.floor(from);
		if (from < 0)
			from += len;
		
		for (; from < len; from++)
		{
			if (from in this &&
				this[from] === elt)
				return from;
		}
		return -1;
	};
}

/**
 * check if the browser support svg images
 */
function SVGSupport() {
	return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1") || document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Shape", "1.0");
}

/**
 * prototypal inharitence functions
 * 
 * usage:
 * MySubObject=object(MyObject)
 */
function object(o) {
	function F() {}
    F.prototype = o;
	return new F();
}

$(document).ready(function(){
	if(!SVGSupport()){//replace all svg images with png images for browser that dont support svg
		$('img.svg').each(function(index,element){
			element=$(element);
			var src=element.attr('src');
			element.attr('src',src.substr(0,src.length-3)+'png');
		});
	};
	$('form.searchbox').submit(function(event){
		event.preventDefault();
	})
	$('#searchbox').keyup(function(event){
		if(event.keyCode==13){//enter
			if(OC.search.currentResult>-1){
				var result=$('#searchresults tr.result a')[OC.search.currentResult];
				$(result).click();
			}
		}else if(event.keyCode==38){//up
			if(OC.search.currentResult>0){
				OC.search.currentResult--;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode==40){//down
			if(OC.search.lastResults.length>OC.search.currentResult+1){
				OC.search.currentResult++;
				OC.search.renderCurrent();
			}
		}else if(event.keyCode==27){//esc
			OC.search.hide();
		}else{
			var query=$('#searchbox').val();
			if(OC.search.lastQuery!=query){
				OC.search.lastQuery=query;
				OC.search.currentResult=-1;
				if(query.length>2){
					OC.search(query);
				}else{
					if(OC.search.hide){
						OC.search.hide();
					}
				}
			}
		}
	});
	
	$('#pass2').showPassword();
});




/*
*	@name							Show Password
*	@descripton						
*	@version						1.3
*	@requires						Jquery 1.5
*
*	@author							Jan Jarfalk
*	@author-email					jan.jarfalk@unwrongest.com
*	@author-website					http://www.unwrongest.com
*
*	@special-thanks					Michel Gratton
*
*	@licens							MIT License - http://www.opensource.org/licenses/mit-license.php
*/
(function($){
     $.fn.extend({
         showPassword: function(c) {	
            
            // Setup callback object
			var callback 	= {'fn':null,'args':{}}
				callback.fn = c;
			
			// Clones passwords and turn the clones into text inputs
			var cloneElement = function( element ) {
				
				var $element = $(element);
					
				$clone = $("<input />");
					
				// Name added for JQuery Validation compatibility
				// Element name is required to avoid script warning.
				$clone.attr({
					'type'		:	'text',
					'class'		:	$element.attr('class'),
					'style'		:	$element.attr('style'),
					'size'		:	$element.attr('size'),
					'name'		:	$element.attr('name')+'-clone',
					'tabindex' 	:	$element.attr('tabindex')
				});
					
				return $clone;
			
			};
			
			// Transfers values between two elements
			var update = function(a,b){
				b.val(a.val());
			};
			
			// Shows a or b depending on checkbox
			var setState = function( checkbox, a, b ){
			
				if(checkbox.is(':checked')){
					update(a,b);
					b.show();
					a.hide();
				} else {
					update(b,a);
					b.hide();
					a.show();
				}
				
			};
            
            return this.each(function() {
            	
            	var $input					= $(this),
            		$checkbox 				= $($input.data('typetoggle'));
            	
            	// Create clone
				var $clone = cloneElement($input);
					$clone.insertAfter($input);
				
				// Set callback arguments
            	if(callback.fn){	
            		callback.args.input		= $input;
            		callback.args.checkbox	= $checkbox;
					callback.args.clone 	= $clone;
            	}
				

				
				$checkbox.bind('click', function() {
					setState( $checkbox, $input, $clone );
				});
				
				$input.bind('keyup', function() {
					update( $input, $clone )
				});
				
				$clone.bind('keyup', function(){ 
					update( $clone, $input );
					
					// Added for JQuery Validation compatibility
					// This will trigger validation if it's ON for keyup event
					$input.trigger('keyup');
					
				});
				
				// Added for JQuery Validation compatibility
				// This will trigger validation if it's ON for blur event
				$clone.bind('blur', function() { $input.trigger('focusout'); });
				
				setState( $checkbox, $input, $clone );
				
				if( callback.fn ){
					callback.fn( callback.args );
				}

            });
        }
    });
})(jQuery);
