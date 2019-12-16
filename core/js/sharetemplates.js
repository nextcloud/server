(function() {
  var template = Handlebars.template, templates = OC.Share.Templates = OC.Share.Templates || {};
templates['sharedialoglinkshareview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "<ul class=\"shareWithList\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.nolinkShares : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":1},"end":{"line":17,"column":8}}})) != null ? stack1 : "")
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.linkShares : depth0),{"name":"each","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":18,"column":1},"end":{"line":36,"column":10}}})) != null ? stack1 : "")
    + "</ul>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.newShareId || (depth0 != null ? depth0.newShareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareId","hash":{},"data":data,"loc":{"start":{"line":4,"column":21},"end":{"line":4,"column":35}}}) : helper)))
    + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\">"
    + alias4(((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareLabel","hash":{},"data":data,"loc":{"start":{"line":6,"column":26},"end":{"line":6,"column":43}}}) : helper)))
    + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-add new-share has-tooltip "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":60},"end":{"line":9,"column":92}}})) != null ? stack1 : "")
    + "\" title=\""
    + alias4(((helper = (helper = helpers.newShareTitle || (depth0 != null ? depth0.newShareTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareTitle","hash":{},"data":data,"loc":{"start":{"line":9,"column":101},"end":{"line":9,"column":118}}}) : helper)))
    + "\"></a>\n					<span class=\"icon icon-loading-small "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":10,"column":42},"end":{"line":10,"column":82}}})) != null ? stack1 : "")
    + "\"></span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":11,"column":5},"end":{"line":13,"column":12}}})) != null ? stack1 : "")
    + "				</div>\n			</span>\n		</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"5":function(container,depth0,helpers,partials,data) {
    var stack1, helper;

  return "						"
    + ((stack1 = ((helper = (helper = helpers.pendingPopoverMenu || (depth0 != null ? depth0.pendingPopoverMenu : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"pendingPopoverMenu","hash":{},"data":data,"loc":{"start":{"line":12,"column":6},"end":{"line":12,"column":30}}}) : helper))) != null ? stack1 : "")
    + "\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":19,"column":21},"end":{"line":19,"column":28}}}) : helper)))
    + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\" title=\""
    + alias4(((helper = (helper = helpers.linkShareCreationDate || (depth0 != null ? depth0.linkShareCreationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"linkShareCreationDate","hash":{},"data":data,"loc":{"start":{"line":21,"column":33},"end":{"line":21,"column":58}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.linkShareLabel || (depth0 != null ? depth0.linkShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"linkShareLabel","hash":{},"data":data,"loc":{"start":{"line":21,"column":60},"end":{"line":21,"column":78}}}) : helper)))
    + "</span>\n			\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"clipboard-button icon icon-clippy has-tooltip\" data-clipboard-text=\""
    + alias4(((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLinkURL","hash":{},"data":data,"loc":{"start":{"line":24,"column":91},"end":{"line":24,"column":107}}}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.copyLabel || (depth0 != null ? depth0.copyLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"copyLabel","hash":{},"data":data,"loc":{"start":{"line":24,"column":116},"end":{"line":24,"column":129}}}) : helper)))
    + "\"></a>\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-more "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":26,"column":39},"end":{"line":26,"column":71}}})) != null ? stack1 : "")
    + "\"></a>\n					<span class=\"icon icon-loading-small "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":27,"column":42},"end":{"line":27,"column":82}}})) != null ? stack1 : "")
    + "\"></span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.program(8, data, 0),"data":data,"loc":{"start":{"line":28,"column":5},"end":{"line":32,"column":12}}})) != null ? stack1 : "")
    + "				</div>\n			</span>\n		</li>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, helper;

  return "						"
    + ((stack1 = ((helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"popoverMenu","hash":{},"data":data,"loc":{"start":{"line":31,"column":6},"end":{"line":31,"column":23}}}) : helper))) != null ? stack1 : "")
    + "\n";
},"10":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.noSharingPlaceholder : depth0),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":39,"column":0},"end":{"line":39,"column":161}}})) != null ? stack1 : "")
    + "\n";
},"11":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<input id=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":39,"column":49},"end":{"line":39,"column":56}}}) : helper)))
    + "\" class=\"shareWithField\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.noSharingPlaceholder || (depth0 != null ? depth0.noSharingPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"noSharingPlaceholder","hash":{},"data":data,"loc":{"start":{"line":39,"column":106},"end":{"line":39,"column":130}}}) : helper)))
    + "\" disabled=\"disabled\" />";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.shareAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(10, data, 0),"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":40,"column":7}}})) != null ? stack1 : "");
},"useData":true});
templates['sharedialoglinkshareview_popover_menu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadRValue || (depth0 != null ? depth0.publicUploadRValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRValue","hash":{},"data":data,"loc":{"start":{"line":12,"column":52},"end":{"line":12,"column":74}}}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-r-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":12,"column":113},"end":{"line":12,"column":120}}}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadRChecked || (depth0 != null ? depth0.publicUploadRChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRChecked","hash":{},"data":data,"loc":{"start":{"line":12,"column":154},"end":{"line":12,"column":180}}}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-r-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":13,"column":50},"end":{"line":13,"column":57}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadRLabel || (depth0 != null ? depth0.publicUploadRLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRLabel","hash":{},"data":data,"loc":{"start":{"line":13,"column":59},"end":{"line":13,"column":81}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadRWValue || (depth0 != null ? depth0.publicUploadRWValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWValue","hash":{},"data":data,"loc":{"start":{"line":19,"column":52},"end":{"line":19,"column":75}}}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-rw-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":19,"column":115},"end":{"line":19,"column":122}}}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadRWChecked || (depth0 != null ? depth0.publicUploadRWChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWChecked","hash":{},"data":data,"loc":{"start":{"line":19,"column":156},"end":{"line":19,"column":183}}}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-rw-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":20,"column":51},"end":{"line":20,"column":58}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadRWLabel || (depth0 != null ? depth0.publicUploadRWLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWLabel","hash":{},"data":data,"loc":{"start":{"line":20,"column":60},"end":{"line":20,"column":83}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadWValue || (depth0 != null ? depth0.publicUploadWValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWValue","hash":{},"data":data,"loc":{"start":{"line":26,"column":52},"end":{"line":26,"column":74}}}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-w-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":26,"column":113},"end":{"line":26,"column":120}}}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadWChecked || (depth0 != null ? depth0.publicUploadWChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWChecked","hash":{},"data":data,"loc":{"start":{"line":26,"column":154},"end":{"line":26,"column":180}}}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-w-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":27,"column":50},"end":{"line":27,"column":57}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadWLabel || (depth0 != null ? depth0.publicUploadWLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWLabel","hash":{},"data":data,"loc":{"start":{"line":27,"column":59},"end":{"line":27,"column":81}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li id=\"allowPublicEditingWrapper\">\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"allowPublicEditing\" id=\"sharingDialogAllowPublicEditing-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":35,"column":90},"end":{"line":35,"column":97}}}) : helper)))
    + "\" class=\"checkbox publicEditingCheckbox\" "
    + ((stack1 = ((helper = (helper = helpers.publicEditingChecked || (depth0 != null ? depth0.publicEditingChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicEditingChecked","hash":{},"data":data,"loc":{"start":{"line":35,"column":138},"end":{"line":35,"column":164}}}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicEditing-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":36,"column":49},"end":{"line":36,"column":56}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicEditingLabel || (depth0 != null ? depth0.publicEditingLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicEditingLabel","hash":{},"data":data,"loc":{"start":{"line":36,"column":58},"end":{"line":36,"column":80}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"5":function(container,depth0,helpers,partials,data) {
    return "checked=\"checked\"";
},"7":function(container,depth0,helpers,partials,data) {
    return "disabled=\"disabled\"";
},"9":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"11":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"shareOption menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"passwordByTalk\" id=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":66,"column":69},"end":{"line":66,"column":76}}}) : helper)))
    + "\" class=\"checkbox passwordByTalkCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":67,"column":5},"end":{"line":67,"column":56}}})) != null ? stack1 : "")
    + " />\n					<label for=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":68,"column":32},"end":{"line":68,"column":39}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data,"loc":{"start":{"line":68,"column":41},"end":{"line":68,"column":64}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"13":function(container,depth0,helpers,partials,data) {
    return "datepicker";
},"15":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"expireDate","hash":{},"data":data,"loc":{"start":{"line":84,"column":77},"end":{"line":84,"column":91}}}) : helper)));
},"17":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"defaultExpireDate","hash":{},"data":data,"loc":{"start":{"line":84,"column":99},"end":{"line":84,"column":120}}}) : helper)));
},"19":function(container,depth0,helpers,partials,data) {
    return "readonly";
},"21":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<a href=\"#\" class=\"menuitem pop-up\" data-url=\""
    + alias4(((helper = (helper = helpers.url || (depth0 != null ? depth0.url : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"url","hash":{},"data":data,"loc":{"start":{"line":104,"column":50},"end":{"line":104,"column":57}}}) : helper)))
    + "\" data-window=\""
    + alias4(((helper = (helper = helpers.newWindow || (depth0 != null ? depth0.newWindow : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newWindow","hash":{},"data":data,"loc":{"start":{"line":104,"column":72},"end":{"line":104,"column":85}}}) : helper)))
    + "\">\n					<span class=\"icon "
    + alias4(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconClass","hash":{},"data":data,"loc":{"start":{"line":105,"column":23},"end":{"line":105,"column":36}}}) : helper)))
    + "\"></span>\n					<span>"
    + alias4(((helper = (helper = helpers.label || (depth0 != null ? depth0.label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data,"loc":{"start":{"line":106,"column":11},"end":{"line":106,"column":20}}}) : helper)))
    + "</span>\n				</a>\n			</li>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"popovermenu menu\">\n	<ul>\n		<li class=\"hidden linkTextMenu\">\n			<span class=\"menuitem icon-link-text\">\n				<input id=\"linkText-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":5,"column":24},"end":{"line":5,"column":31}}}) : helper)))
    + "\" class=\"linkText\" type=\"text\" readonly=\"readonly\" value=\""
    + alias4(((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLinkURL","hash":{},"data":data,"loc":{"start":{"line":5,"column":89},"end":{"line":5,"column":105}}}) : helper)))
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.publicUpload : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":2},"end":{"line":30,"column":9}}})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.publicEditing : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":31,"column":2},"end":{"line":39,"column":9}}})) != null ? stack1 : "")
    + "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"hideDownload\" id=\"sharingDialogHideDownload-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":43,"column":78},"end":{"line":43,"column":85}}}) : helper)))
    + "\" class=\"checkbox hideDownloadCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hideDownload : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":44,"column":5},"end":{"line":44,"column":49}}})) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogHideDownload-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":45,"column":43},"end":{"line":45,"column":50}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.hideDownloadLabel || (depth0 != null ? depth0.hideDownloadLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"hideDownloadLabel","hash":{},"data":data,"loc":{"start":{"line":45,"column":52},"end":{"line":45,"column":73}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<input type=\"checkbox\" name=\"showPassword\" id=\"showPassword-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":50,"column":65},"end":{"line":50,"column":72}}}) : helper)))
    + "\" class=\"checkbox showPasswordCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":51,"column":5},"end":{"line":51,"column":50}}})) != null ? stack1 : "")
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordEnforced : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":51,"column":51},"end":{"line":51,"column":103}}})) != null ? stack1 : "")
    + " value=\"1\" />\n					<label for=\"showPassword-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":52,"column":30},"end":{"line":52,"column":37}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.enablePasswordLabel || (depth0 != null ? depth0.enablePasswordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enablePasswordLabel","hash":{},"data":data,"loc":{"start":{"line":52,"column":39},"end":{"line":52,"column":62}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":55,"column":14},"end":{"line":55,"column":56}}})) != null ? stack1 : "")
    + " linkPassMenu\">\n				<span class=\"menuitem icon-share-pass\">\n					<input id=\"linkPassText-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":57,"column":29},"end":{"line":57,"column":36}}}) : helper)))
    + "\" class=\"linkPassText\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data,"loc":{"start":{"line":57,"column":88},"end":{"line":57,"column":111}}}) : helper)))
    + "\" autocomplete=\"new-password\" />\n					<input type=\"submit\" class=\"icon-confirm share-pass-submit\" value=\"\" />\n					<span class=\"icon icon-loading-small hidden\"></span>\n				</span>\n			</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPasswordByTalkCheckBox : depth0),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":62,"column":2},"end":{"line":71,"column":9}}})) != null ? stack1 : "")
    + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":74,"column":26},"end":{"line":74,"column":33}}}) : helper)))
    + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\"\n				"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":75,"column":4},"end":{"line":75,"column":49}}})) != null ? stack1 : "")
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":75,"column":50},"end":{"line":75,"column":104}}})) != null ? stack1 : "")
    + " />\n				<label for=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":76,"column":27},"end":{"line":76,"column":34}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expireDateLabel","hash":{},"data":data,"loc":{"start":{"line":76,"column":36},"end":{"line":76,"column":55}}}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":79,"column":13},"end":{"line":79,"column":55}}})) != null ? stack1 : "")
    + "\">\n			<span class=\"menuitem icon-expiredate expirationDateContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":80,"column":65},"end":{"line":80,"column":72}}}) : helper)))
    + "\">\n				<label for=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":81,"column":37},"end":{"line":81,"column":44}}}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDate","hash":{},"data":data,"loc":{"start":{"line":81,"column":77},"end":{"line":81,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationLabel","hash":{},"data":data,"loc":{"start":{"line":81,"column":97},"end":{"line":81,"column":116}}}) : helper)))
    + "</label>\n				<!-- do not use the datepicker if enforced -->\n				<input id=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":83,"column":36},"end":{"line":83,"column":43}}}) : helper)))
    + "\" class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"unless","hash":{},"fn":container.program(13, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":83,"column":52},"end":{"line":83,"column":105}}})) != null ? stack1 : "")
    + "\" type=\"text\"\n					placeholder=\""
    + alias4(((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDatePlaceholder","hash":{},"data":data,"loc":{"start":{"line":84,"column":18},"end":{"line":84,"column":47}}}) : helper)))
    + "\" value=\""
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(15, data, 0),"inverse":container.program(17, data, 0),"data":data,"loc":{"start":{"line":84,"column":56},"end":{"line":84,"column":127}}})) != null ? stack1 : "")
    + "\"\n					data-max-date=\""
    + alias4(((helper = (helper = helpers.maxDate || (depth0 != null ? depth0.maxDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"maxDate","hash":{},"data":data,"loc":{"start":{"line":85,"column":20},"end":{"line":85,"column":31}}}) : helper)))
    + "\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"if","hash":{},"fn":container.program(19, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":85,"column":33},"end":{"line":85,"column":76}}})) != null ? stack1 : "")
    + " />\n			</span>\n			</li>\n		<li>\n			<a href=\"#\" class=\"share-add\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-edit\"></span>\n				<span>"
    + alias4(((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addNoteLabel","hash":{},"data":data,"loc":{"start":{"line":92,"column":10},"end":{"line":92,"column":26}}}) : helper)))
    + "</span>\n				<input type=\"button\" class=\"share-note-delete icon-delete "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":93,"column":62},"end":{"line":93,"column":98}}})) != null ? stack1 : "")
    + "\">\n			</a>\n		</li>\n		<li class=\"share-note-form share-note-link "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":96,"column":45},"end":{"line":96,"column":81}}})) != null ? stack1 : "")
    + "\">\n			<span class=\"menuitem icon-note\">\n				<textarea class=\"share-note\">"
    + alias4(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareNote","hash":{},"data":data,"loc":{"start":{"line":98,"column":33},"end":{"line":98,"column":46}}}) : helper)))
    + "</textarea>\n				<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":99,"column":86},"end":{"line":99,"column":97}}}) : helper)))
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.social : depth0),{"name":"each","hash":{},"fn":container.program(21, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":102,"column":2},"end":{"line":109,"column":11}}})) != null ? stack1 : "")
    + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>"
    + alias4(((helper = (helper = helpers.unshareLinkLabel || (depth0 != null ? depth0.unshareLinkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLinkLabel","hash":{},"data":data,"loc":{"start":{"line":111,"column":122},"end":{"line":111,"column":142}}}) : helper)))
    + "</span></a>\n		</li>\n		<li>\n			<a href=\"#\" class=\"new-share\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-add\"></span>\n				<span>"
    + alias4(((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareLabel","hash":{},"data":data,"loc":{"start":{"line":117,"column":10},"end":{"line":117,"column":27}}}) : helper)))
    + "</span>\n			</a>\n		</li>\n	</ul>\n</div>\n";
},"useData":true});
templates['sharedialoglinkshareview_popover_menu_pending'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"menuitem icon-info\">\n					<p>"
    + alias4(((helper = (helper = helpers.enforcedPasswordLabel || (depth0 != null ? depth0.enforcedPasswordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enforcedPasswordLabel","hash":{},"data":data,"loc":{"start":{"line":6,"column":8},"end":{"line":6,"column":33}}}) : helper)))
    + "</p>\n				</span>\n			</li>\n			<li class=\"linkPassMenu\">\n				<span class=\"menuitem\">\n					<form autocomplete=\"off\" class=\"enforcedPassForm\">\n						<input id=\"enforcedPassText\" required class=\"enforcedPassText\" type=\"password\"\n							placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data,"loc":{"start":{"line":13,"column":20},"end":{"line":13,"column":43}}}) : helper)))
    + "\" autocomplete=\"enforcedPassText\" minlength=\""
    + alias4(((helper = (helper = helpers.minPasswordLength || (depth0 != null ? depth0.minPasswordLength : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"minPasswordLength","hash":{},"data":data,"loc":{"start":{"line":13,"column":88},"end":{"line":13,"column":109}}}) : helper)))
    + "\" />\n						<input type=\"submit\" value=\" \" class=\"primary icon-checkmark-white\">\n					</form>\n				</span>\n			</li>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<div class=\"popovermenu open menu pending\">\n	<ul>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isPasswordEnforced : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":2},"end":{"line":18,"column":9}}})) != null ? stack1 : "")
    + "	</ul>\n</div>\n";
},"useData":true});
templates['sharedialogresharerinfoview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div class=\"share-note\">"
    + container.escapeExpression(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"shareNote","hash":{},"data":data,"loc":{"start":{"line":5,"column":44},"end":{"line":5,"column":57}}}) : helper)))
    + "</div>";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<span class=\"reshare\">\n	<div class=\"avatar\" data-userName=\""
    + alias4(((helper = (helper = helpers.reshareOwner || (depth0 != null ? depth0.reshareOwner : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"reshareOwner","hash":{},"data":data,"loc":{"start":{"line":2,"column":36},"end":{"line":2,"column":52}}}) : helper)))
    + "\"></div>\n	"
    + alias4(((helper = (helper = helpers.sharedByText || (depth0 != null ? depth0.sharedByText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharedByText","hash":{},"data":data,"loc":{"start":{"line":3,"column":1},"end":{"line":3,"column":17}}}) : helper)))
    + "\n</span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasShareNote : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":0},"end":{"line":5,"column":70}}})) != null ? stack1 : "")
    + "\n";
},"useData":true});
templates['sharedialogshareelistview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isShareWithCurrentUser : depth0),{"name":"unless","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":8},"end":{"line":21,"column":12}}})) != null ? stack1 : "");
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":4,"column":21},"end":{"line":4,"column":32}}}) : helper)))
    + "\" data-share-type=\""
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data,"loc":{"start":{"line":4,"column":51},"end":{"line":4,"column":64}}}) : helper)))
    + "\" data-share-with=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data,"loc":{"start":{"line":4,"column":83},"end":{"line":4,"column":96}}}) : helper)))
    + "\">\n			<div class=\"avatar "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.modSeed : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":22},"end":{"line":5,"column":64}}})) != null ? stack1 : "")
    + "\" data-username=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data,"loc":{"start":{"line":5,"column":81},"end":{"line":5,"column":94}}}) : helper)))
    + "\" data-avatar=\""
    + alias4(((helper = (helper = helpers.shareWithAvatar || (depth0 != null ? depth0.shareWithAvatar : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithAvatar","hash":{},"data":data,"loc":{"start":{"line":5,"column":109},"end":{"line":5,"column":128}}}) : helper)))
    + "\" data-displayname=\""
    + alias4(((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithDisplayName","hash":{},"data":data,"loc":{"start":{"line":5,"column":148},"end":{"line":5,"column":172}}}) : helper)))
    + "\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.modSeed : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":5,"column":174},"end":{"line":5,"column":235}}})) != null ? stack1 : "")
    + "></div>\n			<span class=\"username\" title=\""
    + alias4(((helper = (helper = helpers.shareWithTitle || (depth0 != null ? depth0.shareWithTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithTitle","hash":{},"data":data,"loc":{"start":{"line":6,"column":33},"end":{"line":6,"column":51}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithDisplayName","hash":{},"data":data,"loc":{"start":{"line":6,"column":53},"end":{"line":6,"column":77}}}) : helper)))
    + "</span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.canUpdateShareSettings : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":7,"column":3},"end":{"line":19,"column":10}}})) != null ? stack1 : "")
    + "		</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "imageplaceholderseed";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "data-seed=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data,"loc":{"start":{"line":5,"column":200},"end":{"line":5,"column":213}}}) : helper)))
    + " "
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data,"loc":{"start":{"line":5,"column":214},"end":{"line":5,"column":227}}}) : helper)))
    + "\"";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "			<span class=\"sharingOptionsGroup\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.editPermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":9,"column":4},"end":{"line":14,"column":11}}})) != null ? stack1 : "")
    + "				<div tabindex=\"0\" class=\"share-menu\"><span class=\"icon icon-more\"></span>\n					"
    + ((stack1 = ((helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"popoverMenu","hash":{},"data":data,"loc":{"start":{"line":16,"column":5},"end":{"line":16,"column":22}}}) : helper))) != null ? stack1 : "")
    + "\n				</div>\n			</span>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "					<span>\n						<input id=\"canEdit-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":11,"column":25},"end":{"line":11,"column":32}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":11,"column":33},"end":{"line":11,"column":44}}}) : helper)))
    + "\" type=\"checkbox\" name=\"edit\" class=\"permissions checkbox\" />\n						<label for=\"canEdit-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":12,"column":26},"end":{"line":12,"column":33}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":12,"column":34},"end":{"line":12,"column":45}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.canEditLabel || (depth0 != null ? depth0.canEditLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"canEditLabel","hash":{},"data":data,"loc":{"start":{"line":12,"column":47},"end":{"line":12,"column":63}}}) : helper)))
    + "</label>\n					</span>\n";
},"10":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":24,"column":21},"end":{"line":24,"column":32}}}) : helper)))
    + "\" data-share-type=\""
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data,"loc":{"start":{"line":24,"column":51},"end":{"line":24,"column":64}}}) : helper)))
    + "\">\n			<div class=\"avatar\" data-username=\""
    + alias4(((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiator","hash":{},"data":data,"loc":{"start":{"line":25,"column":38},"end":{"line":25,"column":56}}}) : helper)))
    + "\"></div>\n			<span class=\"has-tooltip username\" title=\""
    + alias4(((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiator","hash":{},"data":data,"loc":{"start":{"line":26,"column":45},"end":{"line":26,"column":63}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.shareInitiatorText || (depth0 != null ? depth0.shareInitiatorText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiatorText","hash":{},"data":data,"loc":{"start":{"line":26,"column":65},"end":{"line":26,"column":87}}}) : helper)))
    + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLabel","hash":{},"data":data,"loc":{"start":{"line":28,"column":147},"end":{"line":28,"column":163}}}) : helper)))
    + "</span></a>\n			</span>\n		</li>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "<ul id=\"shareWithList\" class=\"shareWithList\">\n"
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.sharees : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":1},"end":{"line":22,"column":10}}})) != null ? stack1 : "")
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.linkReshares : depth0),{"name":"each","hash":{},"fn":container.program(10, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":23,"column":1},"end":{"line":31,"column":10}}})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['sharedialogshareelistview_popover_menu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return " "
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.sharePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":29},"end":{"line":10,"column":22}}})) != null ? stack1 : "")
    + " ";
},"2":function(container,depth0,helpers,partials,data) {
    var stack1;

  return " "
    + ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":61},"end":{"line":10,"column":14}}})) != null ? stack1 : "")
    + " ";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n			<li>\n				<span class=\"menuitem\">\n					<input id=\"canShare-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":6,"column":25},"end":{"line":6,"column":32}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":6,"column":33},"end":{"line":6,"column":44}}}) : helper)))
    + "\" type=\"checkbox\" name=\"share\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasSharePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":6,"column":104},"end":{"line":6,"column":154}}})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.sharePermission || (depth0 != null ? depth0.sharePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharePermission","hash":{},"data":data,"loc":{"start":{"line":6,"column":173},"end":{"line":6,"column":192}}}) : helper)))
    + "\" />\n					<label for=\"canShare-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":7,"column":26},"end":{"line":7,"column":33}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":7,"column":34},"end":{"line":7,"column":45}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.canShareLabel || (depth0 != null ? depth0.canShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"canShareLabel","hash":{},"data":data,"loc":{"start":{"line":7,"column":47},"end":{"line":7,"column":64}}}) : helper)))
    + "</label>\n				</span>\n				</li>\n			";
},"4":function(container,depth0,helpers,partials,data) {
    return "checked=\"checked\"";
},"6":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.createPermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":12,"column":3},"end":{"line":19,"column":21}}})) != null ? stack1 : "")
    + "\n			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.updatePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(10, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":20,"column":3},"end":{"line":27,"column":22}}})) != null ? stack1 : "")
    + "\n			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.deletePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(13, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":28,"column":3},"end":{"line":35,"column":22}}})) != null ? stack1 : "")
    + "\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":12,"column":35},"end":{"line":19,"column":14}}})) != null ? stack1 : "");
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canCreate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":15,"column":27},"end":{"line":15,"column":34}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":15,"column":35},"end":{"line":15,"column":46}}}) : helper)))
    + "\" type=\"checkbox\" name=\"create\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasCreatePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":15,"column":107},"end":{"line":15,"column":158}}})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.createPermission || (depth0 != null ? depth0.createPermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"createPermission","hash":{},"data":data,"loc":{"start":{"line":15,"column":177},"end":{"line":15,"column":197}}}) : helper)))
    + "\"/>\n						<label for=\"canCreate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":16,"column":28},"end":{"line":16,"column":35}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":16,"column":36},"end":{"line":16,"column":47}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.createPermissionLabel || (depth0 != null ? depth0.createPermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"createPermissionLabel","hash":{},"data":data,"loc":{"start":{"line":16,"column":49},"end":{"line":16,"column":74}}}) : helper)))
    + "</label>\n					</span>\n				</li>\n			";
},"10":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":20,"column":35},"end":{"line":27,"column":15}}})) != null ? stack1 : "");
},"11":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canUpdate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":23,"column":27},"end":{"line":23,"column":34}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":23,"column":35},"end":{"line":23,"column":46}}}) : helper)))
    + "\" type=\"checkbox\" name=\"update\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasUpdatePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":23,"column":107},"end":{"line":23,"column":158}}})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.updatePermission || (depth0 != null ? depth0.updatePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"updatePermission","hash":{},"data":data,"loc":{"start":{"line":23,"column":177},"end":{"line":23,"column":197}}}) : helper)))
    + "\"/>\n						<label for=\"canUpdate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":24,"column":28},"end":{"line":24,"column":35}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":24,"column":36},"end":{"line":24,"column":47}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.updatePermissionLabel || (depth0 != null ? depth0.updatePermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"updatePermissionLabel","hash":{},"data":data,"loc":{"start":{"line":24,"column":49},"end":{"line":24,"column":74}}}) : helper)))
    + "</label>\n					</span>\n				</li>\n				";
},"13":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(14, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":28,"column":35},"end":{"line":35,"column":15}}})) != null ? stack1 : "");
},"14":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canDelete-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":31,"column":27},"end":{"line":31,"column":34}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":31,"column":35},"end":{"line":31,"column":46}}}) : helper)))
    + "\" type=\"checkbox\" name=\"delete\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasDeletePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":31,"column":107},"end":{"line":31,"column":158}}})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.deletePermission || (depth0 != null ? depth0.deletePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deletePermission","hash":{},"data":data,"loc":{"start":{"line":31,"column":177},"end":{"line":31,"column":197}}}) : helper)))
    + "\"/>\n						<label for=\"canDelete-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":32,"column":28},"end":{"line":32,"column":35}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":32,"column":36},"end":{"line":32,"column":47}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.deletePermissionLabel || (depth0 != null ? depth0.deletePermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deletePermissionLabel","hash":{},"data":data,"loc":{"start":{"line":32,"column":49},"end":{"line":32,"column":74}}}) : helper)))
    + "</label>\n					</span>\n				</li>\n				";
},"16":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasCreatePermission : depth0),{"name":"if","hash":{},"fn":container.program(17, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":38,"column":3},"end":{"line":45,"column":10}}})) != null ? stack1 : "")
    + "			<li>\n				<span class=\"menuitem\">\n					<input id=\"password-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":48,"column":25},"end":{"line":48,"column":32}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":48,"column":33},"end":{"line":48,"column":44}}}) : helper)))
    + "\" type=\"checkbox\" name=\"password\" class=\"password checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":48,"column":104},"end":{"line":48,"column":149}}})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(19, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":48,"column":149},"end":{"line":48,"column":234}}})) != null ? stack1 : "")
    + "\" />\n					<label for=\"password-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":49,"column":26},"end":{"line":49,"column":33}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":49,"column":34},"end":{"line":49,"column":45}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordLabel","hash":{},"data":data,"loc":{"start":{"line":49,"column":47},"end":{"line":49,"column":64}}}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li class=\"passwordMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":52,"column":27},"end":{"line":52,"column":34}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":52,"column":35},"end":{"line":52,"column":46}}}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":52,"column":47},"end":{"line":52,"column":89}}})) != null ? stack1 : "")
    + "\">\n				<span class=\"passwordContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":53,"column":35},"end":{"line":53,"column":42}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":53,"column":43},"end":{"line":53,"column":54}}}) : helper)))
    + " icon-passwordmail menuitem\">\n					<label for=\"passwordField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":54,"column":31},"end":{"line":54,"column":38}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":54,"column":39},"end":{"line":54,"column":50}}}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"password","hash":{},"data":data,"loc":{"start":{"line":54,"column":83},"end":{"line":54,"column":95}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordLabel","hash":{},"data":data,"loc":{"start":{"line":54,"column":97},"end":{"line":54,"column":114}}}) : helper)))
    + "</label>\n					<input id=\"passwordField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":55,"column":30},"end":{"line":55,"column":37}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":55,"column":38},"end":{"line":55,"column":49}}}) : helper)))
    + "\" class=\"passwordField\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data,"loc":{"start":{"line":55,"column":102},"end":{"line":55,"column":125}}}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordValue","hash":{},"data":data,"loc":{"start":{"line":55,"column":134},"end":{"line":55,"column":151}}}) : helper)))
    + "\" autocomplete=\"new-password\" />\n					<span class=\"icon-loading-small hidden\"></span>\n				</span>\n			</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isTalkEnabled : depth0),{"name":"if","hash":{},"fn":container.program(24, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":59,"column":3},"end":{"line":73,"column":11}}})) != null ? stack1 : "");
},"17":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"secureDrop-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":41,"column":28},"end":{"line":41,"column":35}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":41,"column":36},"end":{"line":41,"column":47}}}) : helper)))
    + "\" type=\"checkbox\" name=\"secureDrop\" class=\"checkbox secureDrop\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.secureDropMode : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":41,"column":111},"end":{"line":41,"column":157}}})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.readPermission || (depth0 != null ? depth0.readPermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"readPermission","hash":{},"data":data,"loc":{"start":{"line":41,"column":176},"end":{"line":41,"column":194}}}) : helper)))
    + "\"/>\n						<label for=\"secureDrop-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":42,"column":29},"end":{"line":42,"column":36}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":42,"column":37},"end":{"line":42,"column":48}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.secureDropLabel || (depth0 != null ? depth0.secureDropLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"secureDropLabel","hash":{},"data":data,"loc":{"start":{"line":42,"column":50},"end":{"line":42,"column":69}}}) : helper)))
    + "</label>\n					</span>\n				</li>\n";
},"19":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isPasswordForMailSharesRequired : depth0),{"name":"if","hash":{},"fn":container.program(20, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":48,"column":170},"end":{"line":48,"column":227}}})) != null ? stack1 : "");
},"20":function(container,depth0,helpers,partials,data) {
    return "disabled=\"\"";
},"22":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"24":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":62,"column":32},"end":{"line":62,"column":39}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":62,"column":40},"end":{"line":62,"column":51}}}) : helper)))
    + "\" type=\"checkbox\" name=\"passwordByTalk\" class=\"passwordByTalk checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":62,"column":123},"end":{"line":62,"column":174}}})) != null ? stack1 : "")
    + " />\n						<label for=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":63,"column":33},"end":{"line":63,"column":40}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":63,"column":41},"end":{"line":63,"column":52}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data,"loc":{"start":{"line":63,"column":54},"end":{"line":63,"column":77}}}) : helper)))
    + "</label>\n					</span>\n				</li>\n				<li class=\"passwordByTalkMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":66,"column":34},"end":{"line":66,"column":41}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":66,"column":42},"end":{"line":66,"column":53}}}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":66,"column":54},"end":{"line":66,"column":102}}})) != null ? stack1 : "")
    + "\">\n					<span class=\"passwordByTalkContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":67,"column":42},"end":{"line":67,"column":49}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":67,"column":50},"end":{"line":67,"column":61}}}) : helper)))
    + " icon-passwordtalk menuitem\">\n						<label for=\"passwordByTalkField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":68,"column":38},"end":{"line":68,"column":45}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":68,"column":46},"end":{"line":68,"column":57}}}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"password","hash":{},"data":data,"loc":{"start":{"line":68,"column":90},"end":{"line":68,"column":102}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data,"loc":{"start":{"line":68,"column":104},"end":{"line":68,"column":127}}}) : helper)))
    + "</label>\n						<input id=\"passwordByTalkField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":69,"column":37},"end":{"line":69,"column":44}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":69,"column":45},"end":{"line":69,"column":56}}}) : helper)))
    + "\" class=\"passwordField\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordByTalkPlaceholder || (depth0 != null ? depth0.passwordByTalkPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkPlaceholder","hash":{},"data":data,"loc":{"start":{"line":69,"column":109},"end":{"line":69,"column":138}}}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordValue","hash":{},"data":data,"loc":{"start":{"line":69,"column":147},"end":{"line":69,"column":164}}}) : helper)))
    + "\" autocomplete=\"new-password\" />\n						<span class=\"icon-loading-small hidden\"></span>\n					</span>\n				</li>\n";
},"26":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"expireDate","hash":{},"data":data,"loc":{"start":{"line":84,"column":160},"end":{"line":84,"column":174}}}) : helper)));
},"28":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"defaultExpireDate","hash":{},"data":data,"loc":{"start":{"line":84,"column":182},"end":{"line":84,"column":203}}}) : helper)));
},"30":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<a href=\"#\" class=\"share-add\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<span class=\"icon icon-edit\"></span>\n					<span>"
    + alias4(((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addNoteLabel","hash":{},"data":data,"loc":{"start":{"line":92,"column":11},"end":{"line":92,"column":27}}}) : helper)))
    + "</span>\n					<input type=\"button\" class=\"share-note-delete icon-delete "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":93,"column":63},"end":{"line":93,"column":99}}})) != null ? stack1 : "")
    + "\">\n				</a>\n			</li>\n			<li class=\"share-note-form "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":96,"column":30},"end":{"line":96,"column":66}}})) != null ? stack1 : "")
    + "\">\n				<span class=\"menuitem icon-note\">\n					<textarea class=\"share-note\">"
    + alias4(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareNote","hash":{},"data":data,"loc":{"start":{"line":98,"column":34},"end":{"line":98,"column":47}}}) : helper)))
    + "</textarea>\n					<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":99,"column":87},"end":{"line":99,"column":98}}}) : helper)))
    + "\" />\n				</span>\n			</li>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"popovermenu bubble hidden menu\">\n	<ul>\n		"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isResharingAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":3,"column":2},"end":{"line":10,"column":30}}})) != null ? stack1 : "")
    + "\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isFolder : depth0),{"name":"if","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":11,"column":2},"end":{"line":36,"column":10}}})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isMailShare : depth0),{"name":"if","hash":{},"fn":container.program(16, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":37,"column":2},"end":{"line":74,"column":10}}})) != null ? stack1 : "")
    + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":77,"column":26},"end":{"line":77,"column":33}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":77,"column":34},"end":{"line":77,"column":45}}}) : helper)))
    + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":77,"column":113},"end":{"line":77,"column":158}}})) != null ? stack1 : "")
    + "\" />\n				<label for=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":78,"column":27},"end":{"line":78,"column":34}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":78,"column":35},"end":{"line":78,"column":46}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expireDateLabel","hash":{},"data":data,"loc":{"start":{"line":78,"column":48},"end":{"line":78,"column":67}}}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"expirationDateMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":81,"column":32},"end":{"line":81,"column":39}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":81,"column":40},"end":{"line":81,"column":51}}}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":81,"column":52},"end":{"line":81,"column":94}}})) != null ? stack1 : "")
    + "\">\n			<span class=\"expirationDateContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":82,"column":40},"end":{"line":82,"column":47}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":82,"column":48},"end":{"line":82,"column":59}}}) : helper)))
    + " icon-expiredate menuitem\">\n				<label for=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":83,"column":37},"end":{"line":83,"column":44}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":83,"column":45},"end":{"line":83,"column":56}}}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDate","hash":{},"data":data,"loc":{"start":{"line":83,"column":89},"end":{"line":83,"column":107}}}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationLabel","hash":{},"data":data,"loc":{"start":{"line":83,"column":109},"end":{"line":83,"column":128}}}) : helper)))
    + "</label>\n				<input id=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":84,"column":36},"end":{"line":84,"column":43}}}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data,"loc":{"start":{"line":84,"column":44},"end":{"line":84,"column":55}}}) : helper)))
    + "\" class=\"datepicker\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDatePlaceholder","hash":{},"data":data,"loc":{"start":{"line":84,"column":101},"end":{"line":84,"column":130}}}) : helper)))
    + "\" value=\""
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(26, data, 0),"inverse":container.program(28, data, 0),"data":data,"loc":{"start":{"line":84,"column":139},"end":{"line":84,"column":210}}})) != null ? stack1 : "")
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isNoteAvailable : depth0),{"name":"if","hash":{},"fn":container.program(30, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":87,"column":2},"end":{"line":102,"column":9}}})) != null ? stack1 : "")
    + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>"
    + alias4(((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLabel","hash":{},"data":data,"loc":{"start":{"line":104,"column":122},"end":{"line":104,"column":138}}}) : helper)))
    + "</span></a>\n		</li>\n	</ul>\n</div>\n";
},"useData":true});
templates['sharedialogview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "	<label for=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":3,"column":23},"end":{"line":3,"column":30}}}) : helper)))
    + "\" class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.shareLabel || (depth0 != null ? depth0.shareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLabel","hash":{},"data":data,"loc":{"start":{"line":3,"column":56},"end":{"line":3,"column":70}}}) : helper)))
    + "</label>\n	<div class=\"oneline\">\n		<input id=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":5,"column":23},"end":{"line":5,"column":30}}}) : helper)))
    + "\" class=\"shareWithField\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.sharePlaceholder || (depth0 != null ? depth0.sharePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharePlaceholder","hash":{},"data":data,"loc":{"start":{"line":5,"column":80},"end":{"line":5,"column":100}}}) : helper)))
    + "\" />\n		<span class=\"shareWithLoading icon-loading-small hidden\"></span>\n		<span class=\"shareWithConfirm icon icon-confirm\"></span>\n	</div>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<div class=\"resharerInfoView subView\"></div>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isSharingAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":2,"column":0},"end":{"line":9,"column":7}}})) != null ? stack1 : "")
    + "<div class=\"linkShareView subView\"></div>\n<div class=\"shareeListView subView\"></div>\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
},"useData":true});
})();