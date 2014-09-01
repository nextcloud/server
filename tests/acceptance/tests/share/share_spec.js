var LoginPage = require('../pages/login.page.js');
var UserPage = require('../pages/user.page.js');
var FilesPage = require('../pages/files.page.js');


describe('Share', function() {
  var params = browser.params;
  var loginPage;
  var userPage
  var filesPage;

  beforeEach(function() {
    isAngularSite(false);
    loginPage = new LoginPage(params.baseUrl);
    userPage = new UserPage(params.baseUrl);
    filesPage = new FilesPage(params.baseUrl);
  });

  it('should login as admin and create 4 new users', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    // userPage.get();
    // userPage.createNewGroup('test_specGroup_1');
    userPage.get();
    // userPage.createNewGroup('test_specGroup_2');
    userPage.createNewUser('demo', 'password');
    userPage.createNewUser('demo2', 'password');
    userPage.createNewUser('demo3', 'password');
    userPage.createNewUser('demo4', 'password');
    userPage.get();
    userPage.renameDisplayName('demo2', ' display2');
    userPage.renameDisplayName('demo3', ' display3');
    userPage.renameDisplayName('demo4', ' display4');
      // setting Group to User fails cause click receives an other element
    // userPage.setUserGroup('demo2', 'test_specGroup_1');
    // userPage.setUserGroup('demo3', 'test_specGroup_1');
    // userPage.setUserGroup('demo4', 'test_specGroup_2');
    expect(userPage.listUser()).toContain('demo', 'demo2', 'demo3', 'demo4');
  });


  it('should share a folder with another user by username', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewFolder('toShare_1');
    browser.sleep(500);
    filesPage.shareFile('toShare_1', 'demo');

    loginPage.logout();
    loginPage.login('demo', 'password');
    expect(filesPage.listFiles()).toContain('toShare_1');
  });

  it('should share a folder including special characters', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewFolder('sP€c!@L');
    browser.sleep(500);
    filesPage.shareFile('sP€c!@L', 'demo');

    loginPage.logout();
    loginPage.login('demo', 'password');
    expect(filesPage.listFiles()).toContain('sP€c!@L');
  });

  it('should share a folder with 3 another user by display name', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewFolder('toShare_2');
    browser.sleep(500);
    filesPage.shareFile('toShare_2', 'display2');

    filesPage.shareWithForm.sendKeys(protractor.Key.DELETE);
    filesPage.shareWithForm.sendKeys('display3');
    browser.wait(function(){
      return filesPage.sharedWithDropdown.isDisplayed();
    }, 3000);
    filesPage.shareWithForm.sendKeys(protractor.Key.ENTER);

    filesPage.shareWithForm.sendKeys(protractor.Key.DELETE);
    filesPage.shareWithForm.sendKeys('display4');
    browser.wait(function(){
      return filesPage.sharedWithDropdown.isDisplayed();
    }, 3000);
    filesPage.shareWithForm.sendKeys(protractor.Key.ENTER);

    loginPage.logout();
    loginPage.login('demo2', 'password');
    expect(filesPage.listFiles()).toContain('toShare_2');

    loginPage.logout();
    loginPage.login('demo3', 'password');
    expect(filesPage.listFiles()).toContain('toShare_2');

    loginPage.logout();
    loginPage.login('demo4', 'password');
    expect(filesPage.listFiles()).toContain('toShare_2');
  });

  it('should grant second users CRUDS rights to their folder', function() {
    filesPage.getAsUser('demo2', 'password');
    filesPage.getFolder('toShare_2');

    //create file
    filesPage.createNewTxtFile('inSharedBySecond');
    filesPage.createNewTxtFile('toBeDeleted');
    expect(filesPage.listFiles()).toContain('inSharedBySecond' ,'toBeDeleted');

    //delete file
    filesPage.deleteFile('toBeDeleted.txt');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('toBeDeleted');
    

    //share file
    filesPage.shareFile('inSharedBySecond.txt', 'demo');

    loginPage.logout();
    loginPage.login('demo', 'password');
    filesPage.renameFile('inSharedBySecond.txt', 'renamedBySecond.txt')
    expect(filesPage.listFiles()).toContain('renamedBySecond');
    filesPage.deleteFile('renamedBySecond.txt');
  });

  it('should delete the root folder shared with a user account by another user', function() {
    filesPage.getAsUser('demo2', 'password');
    filesPage.deleteFile('toShare_2');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('toShare_2');

    loginPage.logout();
    loginPage.login(params.login.user, params.login.password);
    expect(filesPage.listFiles()).toContain('toShare_2');
  });

  it('should delete a file shared with a user, only form user if user deletes it', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewTxtFile('toDeleteByUser');
    filesPage.shareFile('toDeleteByUser.txt', 'demo');

    loginPage.logout();
    loginPage.login('demo', 'password');
    filesPage.deleteFile('toDeleteByUser.txt');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('inSharedBySecond');

    loginPage.logout();
    loginPage.login(params.login.user, params.login.password);
    expect(filesPage.listFiles()).toContain('toDeleteByUser');
    filesPage.deleteFile('toDeleteByUser.txt');
  });

  it('should delete a file in a shared folder, from all', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.getFolder('toShare_1');
    filesPage.createNewTxtFile('toDeleteFromAll');

    loginPage.logout();
    loginPage.login('demo', 'password');
    filesPage.getFolder('toShare_1');
    filesPage.deleteFile('toDeleteFromAll.txt');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('toDeleteFormAll');

    loginPage.logout();
    loginPage.login(params.login.user, params.login.password);
    filesPage.getFolder('toShare_1');
    expect(filesPage.listFiles()).not.toContain('toDeleteFromAll');
  });

  it('should delete a file shared with a user, form all if owner deletes it', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewTxtFile('toDeleteByOwner');
    filesPage.shareFile('toDeleteByOwner.txt', 'demo');

    loginPage.logout();
    loginPage.login('demo', 'password');
    expect(filesPage.listFiles()).toContain('toDeleteByOwner');

    loginPage.logout();
    loginPage.login(params.login.user, params.login.password);
    filesPage.deleteFile('toDeleteByOwner.txt');
  
    loginPage.logout();
    loginPage.login('demo', 'password');
    expect(filesPage.listFiles()).not.toContain('toDeleteByOwner');

  });

  it('should not be possible to reshare a folder, if the "re-share" option is removed', function() {
    filesPage.getAsUser(params.login.user, params.login.password);
    filesPage.createNewFolder('noReshare');
    filesPage.shareFile('noReshare', 'demo');
    filesPage.disableReshare('noReshare', 'demo');
  
    loginPage.logout();
    loginPage.login('demo', 'password');

    expect(filesPage.checkReshareability('noReshare')).toBeFalsy();
  });

});