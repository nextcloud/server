(function() {  
  var InstallPage = function(baseUrl) {
    this.baseUrl = baseUrl;
    
    this.installField = element(by.name('install'));
    this.warningField = element(by.css('.warning'));
    
    this.adminAccount = element(by.id('adminaccount'));
    this.adminInput = this.adminAccount.element(by.id('adminlogin'));
    this.passwordInput = this.adminAccount.element(by.id('adminpass'));
    this.installButton = element(by.css('form .buttons input[type="submit"]'));
  
    this.advancedConfigLink = element(by.id('showAdvanced'));
    this.dataDirectoryConfig = element(by.id('datadirContent'));
    this.dbConfig = element(by.id('databaseBackend'));
  };

  InstallPage.prototype.get = function() {
    browser.get(this.baseUrl);
  };
  
  InstallPage.prototype.isInstallPage = function() {
    return !!this.installField;
  };
  
  InstallPage.prototype.fillAdminAccount = function(user, pass) {
    this.adminInput.sendKeys(user);
    this.passwordInput.sendKeys(pass);
  };
  
  InstallPage.prototype.isAdvancedConfigOpen = function() {
    return this.databaseBackend.isDisplayed() && this.dbConfig.isDisplayed();
  };
  
  InstallPage.prototype.openAdvancedConfig = function() {
    if (! this.isAdvancedConfigOpen()) {
      this.advancedConfigLink.click();
    }
  };
  
  InstallPage.prototype.closeAdvancedConfig = function() {
    if (this.isAdvancedConfigOpen()) {
      this.advancedConfigLink.click();
    }
  };
  
  InstallPage.prototype.configDatabase = function(dbConfig) {
    
  };
  
  module.exports = InstallPage;
})();