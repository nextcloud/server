var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');

// ============================ SEARCH =============================================================== //
// =================================================================================================== //

describe('Search', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should search files by name', function() {
    filesPage.createNewTxtFile('searchFile');
    filesPage.createNewFolder('searchFolder');
    filesPage.searchInput.click();
    filesPage.searchInput.sendKeys('search');
    expect(filesPage.listSelctedFiles()).toContain('searchFile', 'searchFolder');
    filesPage.deleteFile('searchFile.txt');
    filesPage.deleteFile('searchFolder');
  });
});