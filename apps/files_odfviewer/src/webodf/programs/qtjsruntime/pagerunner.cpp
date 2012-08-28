#include "pagerunner.h"

#include "nam.h"
#include "nativeio.h"
#include <QtCore/QFileInfo>
#include <QtCore/QTemporaryFile>
#include <QtCore/QTimer>
#include <QtCore/QCoreApplication>
#include <QtGui/QPainter>
#include <QtGui/QPrinter>
#include <QtWebKit/QWebFrame>
#include <QtCore/QDebug>

QByteArray getRuntimeBindings() {
    return
    "if (typeof(runtime) !== 'undefined') {"
    "    runtime.readFileSync = function (path, encoding) {"
    "        return nativeio.readFileSync(path, encoding);"
    "    };"
    "    runtime.read = function (path, offset, length, callback) {"
    "        var data = nativeio.read(path, offset, length);"
    "        data = runtime.byteArrayFromString(data, 'binary');"
    "        callback(nativeio.error()||null, data);"
    "    };"
    "    runtime.writeFile = function (path, data, callback) {"
    "        data = runtime.byteArrayToString(data, 'binary');"
    "        nativeio.writeFile(path, data);"
    "        callback(nativeio.error()||null);"
    "    };"
    "    runtime.deleteFile = function (path, callback) {"
    "        nativeio.unlink(path);"
    "        callback(nativeio.error()||null);"
    "    };"
    "    runtime.getFileSize = function (path, callback) {"
    "        callback(nativeio.getFileSize(path));"
    "    };"
    "    runtime.exit = function (exitCode) {"
    "        nativeio.exit(exitCode);"
    "    };"
    "    runtime.currentDirectory = function () {"
    "        return nativeio.currentDirectory();"
    "    };"
    "}";
}

PageRunner::PageRunner(const QStringList& args)
    : QWebPage(0),
      out(stdout),
      err(stderr),
      view(new QWidget()) {

    QMap<QString, QString> settings = parseArguments(args);
    QStringList arguments = args.mid(settings.size() * 2);
    exportpdf = settings.value("export-pdf");
    exportpng = settings.value("export-png");
    url = QUrl(arguments[0]);
    nativeio = new NativeIO(this, QFileInfo(arguments[0]).dir(),
                            QDir::current());
    if (url.scheme() == "file" || url.isRelative()) {
        QFileInfo info(url.toLocalFile());
        if (!info.isReadable() || !info.isFile()) {
            QTextStream err(stderr);
            err << "Cannot read file '" + url.toString() + "'.\n";
            qApp->exit(1);
        }
    }
    nam = new NAM(this, QUrl(url).host(), QUrl(url).port());

    setNetworkAccessManager(nam);
    connect(this, SIGNAL(loadFinished(bool)), this, SLOT(finished(bool)));
    connect(mainFrame(), SIGNAL(javaScriptWindowObjectCleared()),
            this, SLOT(slotInitWindowObjects()));
    sawJSError = false;

    setView(view);
    scriptMode = arguments[0].endsWith(".js");
    if (scriptMode) {
        QByteArray html = "'" + arguments[0].toUtf8().replace('\'', "\\'")
                + "'";
        for (int i = 1; i < arguments.length(); ++i) {
            html += ",'" + arguments[i].toUtf8().replace('\'', "\\'") + "'";
        }
        html = "<html>"
                "<head><base href=\".\"></base><title></title>"
                "<script>var arguments=[" + html + "];</script>"
                "<script src=\"" + arguments[0].toUtf8() + "\"></script>";
        if (arguments[0].endsWith("runtime.js")) {
            // add runtime modification
            html += "<script>" + getRuntimeBindings() +
                        "    runtime.libraryPaths = function () {"
                        "        /* convert to javascript array */"
                        "        var p = nativeio.libraryPaths(),"
                        "            a = [], i;"
                        "        for (i in p) { a[i] = p[i]; }"
                        "        return a;"
                        "    };</script>";
        }
        html += "</head><body></body></html>\n";
        QTemporaryFile tmp("XXXXXX.html");
        tmp.setAutoRemove(true);
        tmp.open();
        tmp.write(html);
        tmp.close();
        mainFrame()->load(tmp.fileName());
    } else {
        // Make the url absolute. If it is not done here, QWebFrame will do
        // it, and it will lose the query and fragment part.
        QUrl absurl;
        if (url.isRelative()) {
            absurl = QUrl::fromLocalFile(QFileInfo(url.toLocalFile()).absoluteFilePath());
            absurl.setQueryItems(url.queryItems());
            absurl.setFragment(url.fragment());
        } else {
            absurl = url;
        }
        mainFrame()->load(absurl);
    }
}
PageRunner::~PageRunner() {
    delete view;
}
void PageRunner::finished(bool ok) {
    // bind nativeio
    if (!ok) {
        qApp->exit(1);
    }
    if (!scriptMode) {
        mainFrame()->evaluateJavaScript(getRuntimeBindings());
    }

    // connect signals
    connect(this, SIGNAL(contentsChanged()), this, SLOT(noteChange()));
    connect(this, SIGNAL(downloadRequested(QNetworkRequest)),
            this, SLOT(noteChange()));
    connect(this, SIGNAL(repaintRequested(QRect)),
            this, SLOT(noteChange()));
    connect(mainFrame(), SIGNAL(pageChanged()), this, SLOT(noteChange()));
    connect(this, SIGNAL(geometryChangeRequested(QRect)),
            this, SLOT(noteChange()));
    QTimer::singleShot(150, this, SLOT(reallyFinished()));
    changed = false;
    time.start();
}
void PageRunner::reallyFinished() {
    int latency = time.restart();
    // err << latency << " " << changed << " " << nam->hasOutstandingRequests() << endl;
    if (changed || latency >= 152 || nam->hasOutstandingRequests()) {
        QTimer::singleShot(150, this, SLOT(reallyFinished()));
        changed = false;
        return;
    }
    if (!exportpdf.isEmpty() || !exportpng.isEmpty()) {
        setViewportSize(mainFrame()->contentsSize());
    }
    if (!exportpng.isEmpty()) {
        renderToFile(exportpng);
    }
    if (!exportpdf.isEmpty()) {
        printToFile(exportpdf);
    }
    qApp->exit(sawJSError);
}
QMap<QString, QString> PageRunner::parseArguments(const QStringList& args) {
    int i = 0;
    QMap<QString, QString> settings;
    while (i + 2 < args.length()) {
        if (args[i].startsWith("--")) {
            settings[args[i].mid(2)] = args[i+1];
        }
        i += 2;
    }
    return settings;
}
void PageRunner::slotInitWindowObjects() {
    mainFrame()->addToJavaScriptWindowObject("nativeio", nativeio);
}
void PageRunner::renderToFile(const QString& filename) {
    QImage pixmap(mainFrame()->contentsSize().boundedTo(QSize(10000,10000)),
                  QImage::Format_ARGB32_Premultiplied);
    QPainter painter(&pixmap);
    mainFrame()->render(&painter, QWebFrame::ContentsLayer);
    painter.end();
    pixmap.save(filename);
}
void PageRunner::printToFile(const QString& filename) {
    QPrinter printer(QPrinter::HighResolution);
    printer.setFontEmbeddingEnabled(true);
    printer.setOutputFormat(QPrinter::PdfFormat);
    printer.setOutputFileName(filename);
    mainFrame()->print(&printer);
}
