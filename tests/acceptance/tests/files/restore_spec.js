var LoginPage = require('../pages/login.page.js');
var FilesPage = require('../pages/files.page.js');

// ============================ RESTORE FOLDERS ====================================================== //
// =================================================================================================== //

describe('Restore Folders', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });


  it('should restore a emtpy folder that has been deleted', function() {
    filesPage.createNewFolder('Empty');
    filesPage.deleteFile('Empty');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return filesPage.listFiles();
    }, 5000);  
    filesPage.restoreFile(0);
    filesPage.get();
  

    expect(filesPage.listFiles()).toContain('Empty');
    filesPage.deleteFile('Empty');
  });

  it('should restore a folder including special characters', function() {
    filesPage.createNewFolder('Sp€c!@l FölD€r');
    filesPage.deleteFile('Sp€c!@l FölD€r');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);

    filesPage.restoreFile(0);
    filesPage.get();

    expect(filesPage.listFiles()).toContain('Sp€c!@l FölD€r');
    filesPage.deleteFile('Sp€c!@l FölD€r');
  });

  it('should restore a non empty folder that has been deleted', function() {
    filesPage.createNewFolder('nonEmpty');
    filesPage.createSubFolder('nonEmpty', 'Subfolder');
    filesPage.createNewTxtFile('TextFile');
    filesPage.get();
    filesPage.deleteFile('nonEmpty');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(0);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('nonEmpty');
  });

  it('should restore a folder whose name is currently in use', function() {
    
    // create and delete non empty folder
    filesPage.createNewFolder('sameFolderName');
    filesPage.deleteFile('sameFolderName');
    filesPage.createNewFolder('sameFolderName');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(0);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('sameFolderName (Wiederhergestellt)'); //for german ownclouds
    filesPage.deleteFile('sameFolderName');
    filesPage.deleteFile('sameFolderName (Wiederhergestellt)');
  });

  it('should restore a sub folder when the root folder has been deleted separately', function() {
    filesPage.getSubFolder('nonEmpty', 'Subfolder');
    filesPage.createNewTxtFile('IsInSub');
    filesPage.getFolder('nonEmpty');
    filesPage.deleteFile('Subfolder');
    filesPage.get()
    filesPage.deleteFile('nonEmpty');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(1);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('Subfolder');
  });
});


// ============================ RESTORE FOLDERS ====================================================== //
// =================================================================================================== //

describe('Restore Files', function() {
  var params = browser.params;
  var filesPage;
  
  beforeEach(function() {
    isAngularSite(false);
    filesPage = new FilesPage(params.baseUrl);
    filesPage.getAsUser(params.login.user, params.login.password);
  });

  it('should restore a file thas has been deleted', function() {
    filesPage.createNewTxtFile('restoreMe');
    filesPage.deleteFile('restoreMe.txt');
    filesPage.trashbinButton.click();
        browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(0);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('restoreMe');
    filesPage.deleteFile('restoreMe.txt');
  });

  it('should restore a file including special characters', function() {
    filesPage.createNewTxtFile('Sp€c!@L RésTör€');
    filesPage.deleteFile('Sp€c!@L RésTör€.txt');
    filesPage.trashbinButton.click();
        browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(0);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('Sp€c!@L RésTör€');
    filesPage.deleteFile('Sp€c!@L RésTör€.txt');
  });

  it('should restore a file whose name is currently in use', function() {
    filesPage.createNewTxtFile('sameFileName');
    filesPage.deleteFile('sameFileName.txt');
    filesPage.createNewTxtFile('sameFileName');
    filesPage.trashbinButton.click();
    browser.wait(function() {
      return(filesPage.listFiles());
    }, 3000);
    filesPage.restoreFile(0);
    filesPage.get();
    expect(filesPage.listFiles()).toContain('sameFileName (Wiederhergestellt)'); //for german ownclouds
    filesPage.deleteFile('sameFileName.txt');
    filesPage.deleteFile('sameFileName (Wiederhergestellt).txt');
  });
});