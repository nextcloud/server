#ifndef ODFVIEW_H
#define ODFVIEW_H

#include <QtWebKit/QWebView>
#include <QtNetwork/QNetworkAccessManager>
class NativeIO;

class OdfView : public QWebView {
Q_OBJECT
public:
    OdfView(QWidget* parent = 0);
    ~OdfView();
    QString currentFile() { return curFile; }

public slots:
    bool loadFile(const QString &fileName);

private slots:
    void slotLoadFinished(bool ok);
    void slotInitWindowObjects();

private:
    bool loaded;
    QString curFile;
    QNetworkAccessManager* networkaccessmanager;
    NativeIO* nativeio;
};

#endif // ODFVIEW_H
