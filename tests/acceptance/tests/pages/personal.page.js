(function() {  
  var PersonalPage = function(baseUrl) {
    this.baseUrl = baseUrl;
    this.path = 'index.php/settings/personal';
    this.url = baseUrl + this.path;
    
    this.passwordForm = element(by.css('form#passwordform'));
    this.oldPasswordInput = element(by.id('pass1'));
    this.newPasswordInput = element(by.id('pass2'));
    this.newPasswordButton = element(by.id('passwordbutton'));

    this.passwordChanged = element(by.id('passwordchanged'));
    this.passwordError = element(by.id('passworderror'));
    
    this.displaynameForm = element(by.id('displaynameform'));
    this.displaynameInput = this.displaynameForm.element(by.id('displayName'));
    
  };

  PersonalPage.prototype.get = function() {
    browser.get(this.url);
  };
  
  PersonalPage.prototype.isUserPage = function() {
    return browser.driver.getCurrentUrl() == this.url;
  };
  
  PersonalPage.prototype.ensurePersonalPage = function() {
    // console.log(this.isUserPage());
    // if (! this.isUserPage()) {
    //   display.log('Warning: Auto loading UserPage');
    //   this.get();
    // }
  };
  
  PersonalPage.prototype.changePassword = function(oldPass, newPass) {
    this.ensurePersonalPage();
    this.oldPasswordInput.sendKeys(oldPass);
    this.newPasswordInput.sendKeys(newPass);
    this.newPasswordButton.click();

    // result need some time to display
    var changed = this.passwordChanged;
    var error = this.passwordError;   
    var ready = false;
    browser.wait(function () {
      changed.isDisplayed().then(function(c) {
        error.isDisplayed().then(function(e) {
          ready = c || e;
        });
      });
      return ready;
    }, 8000, 'personal password change result not displayed');
  };
  
  module.exports = PersonalPage;
})();