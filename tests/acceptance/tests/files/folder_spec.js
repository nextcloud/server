var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');


// ============================ FOLDERS ============================================================== //
// =================================================================================================== //

describe('Folders', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should create a new folder', function() {
    filesPage.createNewFolder('testFolder');
    expect(filesPage.listFiles()).toContain('testFolder');
  });

  it('should not create new folder if foldername already exists', function() {
    filesPage.createNewFolder('testFolder');
    var warning = by.css('.tipsy-inner');
    expect(filesPage.alertWarning.isDisplayed()).toBeTruthy();
  });

  it('should delete a folder', function() {
    filesPage.get(); // TODO: reload cause warning alerts don't disappear
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.deleteFile('testFolder');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('testFolder');
  });
});

// ============================== SUB FOLDERS ======================================================== //
// =================================================================================================== //

describe('Subfolders', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });


  it('should go into folder and create subfolder', function() {
    var folder = 'hasSubFolder';
    filesPage.createNewFolder(folder);
    filesPage.goInToFolder(folder);
    filesPage.createNewFolder('SubFolder');
    filesPage.createNewFolder('SubFolder2');
    expect(filesPage.listFiles()).toContain('SubFolder', 'SubFolder2');
  });  

  it('should rename a subfolder', function() {
    filesPage.renameFile('SubFolder2', 'NewSubFolder');
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    expect(filesPage.listFiles()).toContain('NewSubFolder');
  });

  it('should delete a subfolder', function() {
    filesPage.deleteFile('SubFolder');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('SubFolder');
  });

  it('should delete a folder containing a subfolder', function() {
    filesPage.get();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.deleteFile('hasSubFolder');
    browser.sleep(800);
    expect(filesPage.listFiles()).not.toContain('hasSubFolder');
  });
});