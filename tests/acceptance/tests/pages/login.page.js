(function() {
  var LoginPage = function(baseUrl) {
    this.baseUrl = baseUrl;
    this.url = baseUrl;
    
    this.loginForm = element(by.name('login'));
    this.userInput = this.loginForm.element(by.id('user'));
    this.passwordInput = this.loginForm.element(by.id('password'));
    this.loginButton = element(by.id('submit')); 
    
    // On Page when logged in     
    this.menuButton = element(by.id('expand'));
    this.logoutButton = element(by.id('logout'));
    this.newButton = element(by.id('expandDisplayName'));
  };
  
  LoginPage.prototype.get = function() {
    browser.get(this.url);
  };
  
  LoginPage.prototype.isCurrentPage = function() {
    
    return this.loginForm.isPresent();
  };
  
  LoginPage.prototype.fillUserCredentilas = function(user, pass) {
    this.userInput.sendKeys(user);
    this.passwordInput.sendKeys(pass);
  };
  
  LoginPage.prototype.login = function(user, pass) {
    this.fillUserCredentilas(user, pass);
    this.loginButton.click();
    var button = this.newButton;
    browser.wait(function() {
      return button.isPresent();
    }, 5000, 'load files content');
  };
  
  LoginPage.prototype.logout = function() {
    this.menuButton.click();
    this.logoutButton.click();
  };
  
  module.exports = LoginPage;
})();