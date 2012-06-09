/**
 * Small executable that loads a javascript or html page from local a URI.
 * If the URI ends in .js it will be run in QtScript engine, otherwise, it will
 * be assumed to be a webpage that will be opened in
 */
#include "pagerunner.h"
#include <QtGui/QApplication>
int
main(int argc, char** argv) {
    if (argc < 2) {
        QTextStream err(stderr);
        err << "Usage: " << argv[0] << " [--export-pdf pdffile] "
               "[--export-png pngfile] html/javascripfile [arguments]\n";
        return 1;
    }
    QApplication app(argc, argv);
    app.setApplicationName(argv[0]);
    PageRunner p(QCoreApplication::arguments().mid(1));
    return app.exec();
}
