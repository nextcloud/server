var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');
var Screenshot = require('../helper/screenshot.js');

// ============================ TXT FILES ============================================================ //
// =================================================================================================== //

describe('Txt Files', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should create a new txt file', function() {
    filesPage.createNewTxtFile('testText');
    expect(filesPage.listFiles()).toContain('testText');
  });

  it('should not create new file if filename already exists', function() {
    filesPage.createNewTxtFile('testText');
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  it('should delete a txt file', function() {
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.deleteFile('testText.txt');
    filesPage.get();
    expect(filesPage.listFiles()).not.toContain('testText')
  });
});