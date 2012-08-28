#include "mainwindow.h"
#include "ui_mainwindow.h"
#include "odfview.h"
#include <QtGui/QDockWidget>
#include <QtGui/QFileDialog>
#include <QtGui/QFileSystemModel>
#include <QtGui/QLineEdit>
#include <QtGui/QMdiArea>
#include <QtGui/QTreeView>
#include <QtGui/QVBoxLayout>
#include <QtCore/QDir>
#include <QtCore/QSettings>

MainWindow::MainWindow(QWidget *parent) :
    QMainWindow(parent),
    ui(new Ui::MainWindow)
{
    ui->setupUi(this);

    createActions();
    createToolBars();

    QCoreApplication::setOrganizationName("KO");
    //QCoreApplication::setOrganizationDomain("example.com");
    QCoreApplication::setApplicationName("Odf Viewer");

    QSettings settings;

    setWindowTitle(tr("Odf Viewer"));
    setUnifiedTitleAndToolBarOnMac(true);

    QStringList odfNameFilter;
    odfNameFilter << "*.odt" << "*.ods" << "*.odp";
    dirmodel = new QFileSystemModel(this);
    dirmodel->setNameFilters(odfNameFilter);
    dirmodel->setFilter(QDir::AllDirs|QDir::AllEntries|QDir::NoDotAndDotDot);
    dirview = new QTreeView(this);
    dirview->setModel(dirmodel);
    dirview->setHeaderHidden(true);
    dirview->setAnimated(true);
    for (int i = 1; i < dirmodel->columnCount(); i++) {
        dirview->setColumnHidden(i, true);
    }
    QString rootpath = settings.value("rootpath", QDir::homePath()).toString();
    dirmodel->setRootPath(rootpath);
    const QModelIndex rootindex = dirmodel->index(rootpath);
    dirview->setRootIndex(rootindex);
    QLineEdit *dirPath = new QLineEdit(rootpath, this);
    dirdock = new QDockWidget(this);
    QWidget *w = new QWidget(dirdock);
    QVBoxLayout *layout = new QVBoxLayout(w);
    dirdock->setWidget(w);
    layout->addWidget(dirPath);
    layout->addWidget(dirview);
    addDockWidget(Qt::LeftDockWidgetArea, dirdock);

    connect(dirview, SIGNAL(clicked(QModelIndex)), this, SLOT(loadOdf(QModelIndex)));
    connect(dirPath, SIGNAL(textChanged(QString)), this, SLOT(setPath(QString)));
}

MainWindow::~MainWindow()
{
    delete ui;
}

void
MainWindow::openFile(const QString& path)
{
    QMdiSubWindow* w = findMdiChild(path);
    OdfView* v = (w) ?dynamic_cast<OdfView*>(w->widget()) :0;
    if (v == 0) {
        w = ui->mdiArea->activeSubWindow();
        v = (w) ?dynamic_cast<OdfView*>(w->widget()) :0;
    }
    if (v == 0) {
        v = new OdfView(this);
        v->showMaximized();
        w = ui->mdiArea->addSubWindow(v);
        w->showMaximized();
    }
    ui->mdiArea->setActiveSubWindow(w);
    v->loadFile(path);
}

void MainWindow::changeEvent(QEvent *e)
{
    QMainWindow::changeEvent(e);
    switch (e->type()) {
    case QEvent::LanguageChange:
        ui->retranslateUi(this);
        break;
    default:
        break;
    }
}
void MainWindow::open()
{
    QString fileName = QFileDialog::getOpenFileName(this, QString(), QString(),
        tr("Office Files (*.odt *.odp *.ods)"));
    if (!fileName.isEmpty()) {
        QMdiSubWindow *existing = findMdiChild(fileName);
        if (existing) {
            ui->mdiArea->setActiveSubWindow(existing);
            return;
        }

        OdfView *child = createOdfView();
        if (child->loadFile(fileName)) {
            statusBar()->showMessage(tr("File loaded"), 2000);
            child->showMaximized();
        } else {
            child->close();
        }
    }
}
void MainWindow::createActions()
{
    //openAct = new QAction(QIcon(":/images/open.png"), tr("&Open..."), this);
    openAct = new QAction(tr("&Open..."), this);
    openAct->setShortcuts(QKeySequence::Open);
    openAct->setStatusTip(tr("Open an existing file"));
    connect(openAct, SIGNAL(triggered()), this, SLOT(open()));
}
void MainWindow::createToolBars()
{
    fileToolBar = addToolBar(tr("File"));
    fileToolBar->addAction(openAct);
}
QMdiSubWindow *MainWindow::findMdiChild(const QString &fileName)
{
    QString canonicalFilePath = QFileInfo(fileName).canonicalFilePath();

    foreach (QMdiSubWindow *window, ui->mdiArea->subWindowList()) {
        OdfView *odfView = qobject_cast<OdfView *>(window->widget());
        if (odfView->currentFile() == canonicalFilePath)
            return window;
    }
    return 0;
}

OdfView *MainWindow::createOdfView()
{
    OdfView *view = new OdfView(this);
    ui->mdiArea->addSubWindow(view);
    return view;
}

void
MainWindow::loadOdf(const QModelIndex& index) {
    if (dirmodel->isDir(index)) {
        if (dirview->isExpanded(index)) {
            dirview->collapse(index);
        } else {
            dirview->expand(index);
        }
        return;
    }
    QString path = dirmodel->filePath(index);
    path = QFileInfo(path).canonicalFilePath();
    openFile(path);
}

void MainWindow::setPath(const QString &path)
{
    dirmodel->setRootPath(path);
    const QModelIndex rootindex = dirmodel->index(path);
    dirview->setRootIndex(rootindex);
    QSettings settings;
    settings.setValue("rootpath", path);
}



