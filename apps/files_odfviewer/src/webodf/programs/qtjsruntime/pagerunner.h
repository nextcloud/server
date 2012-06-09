#ifndef PAGERUNNER_H
#define PAGERUNNER_H

#include <QtCore/QTextStream>
#include <QtCore/QTime>
#include <QtWebKit/QWebPage>

class NAM;
class NativeIO;

class PageRunner : public QWebPage {
Q_OBJECT
private:
    QUrl url;
    NAM* nam;
    QTextStream out;
    QTextStream err;
    bool changed;
    QWidget* const view;
    QTime time;
    bool scriptMode;
    NativeIO* nativeio;
    QString exportpdf;
    QString exportpng;
    bool sawJSError;
public:
    PageRunner(const QStringList& args);
    ~PageRunner();
private slots:
    void finished(bool ok);
    void noteChange() {
        changed = true;
    }
    void reallyFinished();
    void slotInitWindowObjects();
    bool shouldInterruptJavaScript() {
        changed = true;
        return false;
    }
private:
    void javaScriptConsoleMessage(const QString& message, int lineNumber,
            const QString& sourceID) {
        changed = true;
        if (scriptMode) {
            err << message << endl;
        } else {
            err << sourceID << ":" << lineNumber << " " << message << endl;
        }
        sawJSError = true;
    }
    void javaScriptAlert(QWebFrame* /*frame*/, const QString& msg) {
        changed = true;
        err << "ALERT: " << msg << endl;
    }
    bool javaScriptPrompt(QWebFrame*, const QString&, const QString&, QString*){
        changed = true;
        return false;
    }
    void renderToFile(const QString& filename);
    void printToFile(const QString& filename);
    // overload because default impl was causing a crash
    QString userAgentForUrl(const QUrl&) const {
        return QString();
    }
    QMap<QString, QString> parseArguments(const QStringList& args);
};

#endif
