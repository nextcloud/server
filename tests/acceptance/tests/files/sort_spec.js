var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');

// ============================ SORT ================================================================= //
// =================================================================================================== //

describe('Sort', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('shloud sort files by name', function() {
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("documents"))).toBeTruthy;
    filesPage.nameSortArrow.click();
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("ownCloudUserManual.pdf"))).toBeTruthy;
  });

  it('should sort files by size', function() {
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("documents"))).toBeTruthy;
    filesPage.sizeSortArrow.click();
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("music"))).toBeTruthy;
  });

  it('should sort files by modified date', function() {
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("documents"))).toBeTruthy;
    filesPage.createNewTxtFile('newText')
    filesPage.modifiedSortArrow.click();
    expect(filesPage.firstListElem == element(filesPage.fileListElemId("newText.txt"))).toBeTruthy;
  });
});