var InstallPage = require('../pages/install.page.js');
var Screenshot = require('../helper/screenshot.js');

describe('Installation', function() {
  var params = browser.params;
  var installPage;
  
  beforeEach(function() {
    isAngularSite(false);
    installPage = new InstallPage(params.baseUrl);
    installPage.get();
  });

  it('should load the install page with logo', function() {
    expect(installPage.installField.getAttribute('name')).toEqual("install");
    expect(installPage.installField.getAttribute('value')).toEqual('true');

    expect(element(by.css('.logo'))).toBeDefined();
    browser.takeScreenshot().then(function (png) {
      new Screenshot(png, 'InstallPage.png');
    });
  });
  
  it('should not show any warnings or errors', function() {
    if (installPage.warningField.isDisplayed()) {
      installPage.warningField.getText().then(function(text) {
        display.log(text);
      });
    }
    expect(installPage.warningField.isDisplayed()).toBeFalsy();
  });
  
  it('should show more config after clicking the advanced config link ', function() {
    // TODO: Check not displayed in a proper way
    // expect(installPage.dataDirectoryConfig.isDisplayed()).toBeFalsy();
    // expect(installPage.dbConfig.isDisplayed()).toBeFalsy();

    installPage.advancedConfigLink.click();
    
    expect(installPage.dataDirectoryConfig.isDisplayed()).toBeTruthy();
    expect(installPage.dbConfig.isDisplayed()).toBeTruthy();
    
    browser.takeScreenshot().then(function (png) {
      new Screenshot(png, 'InstallConfig.png');
    });
  });
  
  it('should install as admin with sqlite', function() {
    installPage.fillAdminAccount(params.login.user, params.login.password);
    
    browser.takeScreenshot().then(function (png) {
      new Screenshot(png, 'Credentials.png');
    });
    
    installPage.installButton.click().then(function() {
      expect(browser.getCurrentUrl()).toContain('index.php/apps/files/');
      browser.takeScreenshot().then(function (png) {
        new Screenshot(png, 'InstallFinished.png');
      });
    });
  });
  
});