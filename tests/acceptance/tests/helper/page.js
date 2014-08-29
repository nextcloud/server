(function() {

  
  var Page = function() {

  };

  Page.prototype.moveMouseTo = function(locator) {
    var ele = element(locator);
    return browser.actions().mouseMove(ele).perform();
  }

  module.exports = Page;
})();
