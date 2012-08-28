#ifndef ODFPAGE_H
#define ODFPAGE_H

#include <QtWebKit/QWebPage>
#include <QtCore/QDebug>

class OdfPage : public QWebPage {
public:
    OdfPage(QObject* parent) :QWebPage(parent) {}
    void javaScriptConsoleMessage(const QString& message, int lineNumber, const QString & sourceID) {
        qDebug() << sourceID << ":" << lineNumber << ":" << message;
    }
};

#endif // ODFPAGE_H
