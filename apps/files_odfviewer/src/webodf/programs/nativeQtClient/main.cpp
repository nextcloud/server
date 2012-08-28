#include "odfview.h"
#include <QtGui/QApplication>
#include <QtCore/QTimer>

int main(int argc, char *argv[]) {
    QApplication a(argc, argv);
    OdfView view;
    view.show();
    return a.exec();
}
