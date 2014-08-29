(function() {  
  var FirstRunWizardPage = function(baseUrl) {    
    this.firstRunWizardId = by.id('firstrunwizard');
    this.firstRunWizard = element(this.firstRunWizardId);      
    this.closeLink = element(by.id('cboxOverlay'));
  };
  
  FirstRunWizardPage.prototype.waitForDisplay = function() {
    browser.wait(function() {
      console.log(by.id('closeWizard'));
      return by.id('closeWizard');
      // return by.id('firstrunwizard');
    }, 8000);
  };
  
  FirstRunWizardPage.prototype.isFirstRunWizardPage = function() {
    this.waitForDisplay();
    return !!this.firstRunWizardId;
  };
  
  FirstRunWizardPage.prototype.waitForClose = function() {
    browser.wait(function () {
      return element(by.id('cboxOverlay')).isDisplayed().then(function(displayed) {
        return !displayed; // Do a little Promise/Boolean dance here, since wait will resolve promises.
      });
    }, 3000, 'firstrunwizard should dissappear');
  }
  
  FirstRunWizardPage.prototype.close = function() {
    browser.executeScript('$("#closeWizard").click();');
    browser.executeScript('$("#cboxOverlay").click();');
    this.waitForClose();
  };
  
  module.exports = FirstRunWizardPage;
})();