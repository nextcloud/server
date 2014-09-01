var LoginPage = require('../pages/login.page.js');
var UserPage = require('../pages/user.page.js');
var PersonalPage = require('../pages/personal.page.js');

describe('Change Password  - Valid Usernames', function() {
  var params = browser.params;
  var loginPage;
  var long_pass = 'newNEW123!"§$%&()=?öüß';
  var special_pass = 'special%&@/1234!-+=';
  
  beforeEach(function() {
    isAngularSite(false);
    loginPage = new LoginPage(params.baseUrl);
    browser.manage().deleteAllCookies(); // logout the hard way
    loginPage.get();
  });
  
  it('should login as admin and create a test users ', function() {
    loginPage.fillUserCredentilas(params.login.user, params.login.password);
    loginPage.loginButton.click();
    userPage = new UserPage(params.baseUrl);
    userPage.get();
    userPage.createNewUser('demo', 'password');
    userPage.get();
    expect(userPage.listUser()).toContain('demo');
  });

  it('should login test user', function() {  
    // workaround: Test needed to close firstrunwizard
    loginPage.login('demo', 'password');
    expect(browser.getCurrentUrl()).toContain('index.php/apps/files/');      
  });
        
  it('should login and change password in personal settings', function() {    
    loginPage.login('demo', 'password');
    var personalPage = new PersonalPage(params.baseUrl);
    personalPage.get();
    personalPage.changePassword('password', 'newpassword')

    expect(personalPage.passwordChanged.isDisplayed()).toBeTruthy();
    expect(personalPage.passwordError.isDisplayed()).toBeFalsy();
  });
  
  it('should login and change password to super save password in personal settings', function() {    
    loginPage.login('demo', 'newpassword');
    var personalPage = new PersonalPage(params.baseUrl);
    personalPage.get();
    personalPage.changePassword('newpassword', long_pass);
    browser.wait(function () {
        return personalPage.passwordChanged.isDisplayed();
      }, 3000);
    expect(personalPage.passwordChanged.isDisplayed()).toBeTruthy();
    expect(personalPage.passwordError.isDisplayed()).toBeFalsy();
  });
  
  it('should login and not change password with wrong old password in personal settings', function() {    
    loginPage.login('demo', long_pass);
    var personalPage = new PersonalPage(params.baseUrl);
    personalPage.get();
    personalPage.changePassword('wrongpassword', 'newpassword');
    expect(personalPage.passwordChanged.isDisplayed()).toBeFalsy();
    expect(personalPage.passwordError.isDisplayed()).toBeTruthy();
  });
  
  // %, &, @ and /
  it('should change password including specialcharacters in personal settings', function() {    
    loginPage.login('demo', long_pass);
    var personalPage = new PersonalPage(params.baseUrl);
    personalPage.get();
    personalPage.changePassword(long_pass, special_pass);
    expect(personalPage.passwordChanged.isDisplayed()).toBeTruthy();
    expect(personalPage.passwordError.isDisplayed()).toBeFalsy();
  });
 
  it('should login with password including specialcharacters', function() {    
    loginPage.login('demo', special_pass);
    expect(browser.getCurrentUrl()).toContain('index.php/apps/files/');      
  });
  
  it('should login as admin and change password for test users ', function() {
    loginPage.login(params.login.user, params.login.password);
    userPage = new UserPage(params.baseUrl);
    userPage.get();
    element(by.css('#userlist tr[data-displayname="demo"] td.password')).click().then(function() {
      element(by.css('#userlist tr[data-displayname="demo"] td.password input')).sendKeys("password");
      element(by.css('#userlist tr[data-displayname="demo"] td.password input')).sendKeys(protractor.Key.ENTER);
    });
  });
  
  it('should login with password changed by admin', function() {    
    loginPage.login('demo', 'password');
    expect(browser.getCurrentUrl()).toContain('index.php/apps/files/');      
  });
  
  it('should login as admin and delete test user', function() {    
    // Cleanup prev tests
    loginPage.login(params.login.user, params.login.password);
    userPage.get();
    userPage.deleteUser('demo');
    userPage.get();
    expect(userPage.listUser()).not.toContain('demo');
  });
  
});