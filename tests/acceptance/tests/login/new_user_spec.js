var LoginPage = require('../pages/login.page.js');
var UserPage = require('../pages/user.page.js');
var Screenshot = require('../helper/screenshot.js');

describe('New User', function() {
  var params = browser.params;
  var loginPage;
  
  beforeEach(function() {
    isAngularSite(false);
    loginPage = new LoginPage(params.baseUrl);
    browser.manage().deleteAllCookies(); // logout the hard way
    loginPage.get();
  });
  
  it('should login as admin and create a new user ', function() {
    loginPage.login(params.login.user, params.login.password);
    userPage = new UserPage(params.baseUrl);
    userPage.get();
    userPage.createNewUser('demo', 'demo');
    userPage.get();
    expect(userPage.listUser()).toContain('demo');
  });
  
  it('should login with a new user', function() {    
    loginPage.login('demo', 'demo');
    
    expect(browser.getCurrentUrl()).toContain('index.php/apps/files/');
    browser.takeScreenshot().then(function (png) {
        new Screenshot(png, 'LoginAsNewUser.png');
    });
  });
  
  it('should login as admin and delete new user', function() {    
    // Cleanup prev test
    loginPage.login(params.login.user, params.login.password);
    userPage.get();
    userPage.deleteUser('demo');
    userPage.get();
    expect(userPage.listUser()).not.toContain('demo');
  });
  
});