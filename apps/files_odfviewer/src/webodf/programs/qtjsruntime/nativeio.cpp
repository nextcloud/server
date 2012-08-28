#include "nativeio.h"
#include <QtWebKit/QWebPage>
#include <QtCore/QCoreApplication>
#include <QtCore/QTextCodec>

NativeIO::NativeIO(QObject* parent, const QDir& runtimedir_,
         const QDir& cwd_,
         const QMap<QString, QFile::Permissions>& pathPermissions_)
    :QObject(parent), runtimedir(runtimedir_), cwd(cwd_),
      pathPermissions(pathPermissions_) {
}
QString
NativeIO::readFileSync(const QString& path, const QString& encoding) {
    errstr = QString();
    QFile file(cwd.absoluteFilePath(path));
    QByteArray data;
    if (file.open(QIODevice::ReadOnly)) {
        data = file.readAll();
    }
    QString out;
    if (encoding != "binary") {
        QTextCodec *codec = QTextCodec::codecForName(encoding.toAscii());
        if (codec) {
            out = codec->toUnicode(data);
        }
    }
    if (out.length() == 0 && data.length() > 0) {
        out = QString(data.length(), 0);
        for (int i = 0; i < data.length(); ++i) {
            out[i] = data[i];
        }
    }
    return out;
}
QString
NativeIO::read(const QString& path, int offset, int length) {
    errstr = QString();
    QFile file(cwd.absoluteFilePath(path));
    QByteArray data;
    if (file.open(QIODevice::ReadOnly) && (offset == 0 || file.seek(offset))) {
        int lastLength = 0;
        do {
            lastLength = data.length();
            data += file.read(length - data.length());
        } while (data.length() < length && data.length() != lastLength);
    }
    if (length != data.length()) {
        errstr = "Not enough data: " + QString::number(length) +
                " instead of " + QString::number(data.length());
        return QString();
    }
    QString out(length, 0);
    for (int i = 0; i < length; ++i) {
        out[i] = data[i];
    }
    return out;
}
void
NativeIO::writeFile(const QString& path, const QString& data) {
    QFile file(cwd.absoluteFilePath(path));
    errstr = QString();
    if (!file.open(QIODevice::WriteOnly)) {
        errstr = "Could not open file for writing.";
        return;
    }
    int length = data.length();
    QByteArray out(length, 0);
    for (int i = 0; i < length; ++i) {
        out[i] = data[i].unicode();
    }
    if (file.write(out) != out.length()) {
        errstr = "Could not write to file.";
    }
    return;
}
void
NativeIO::unlink(const QString& path) {
    errstr = QString();
    QFile file(cwd.absoluteFilePath(path));
    if (!file.remove()) {
        errstr = "Could not delete file";
    }
}
int
NativeIO::getFileSize(const QString& path) {
    errstr = QString();
    QFile file(cwd.absoluteFilePath(path));
    if (!file.exists()) {
        errstr = "Could not determine file size.";
    }
    return file.size();
}
void
NativeIO::exit(int exitcode) {
    qApp->exit(exitcode);
}
QString
NativeIO::currentDirectory() const {
    return QDir::currentPath();
}
QStringList
NativeIO::libraryPaths() const {
    QStringList paths;
    paths << runtimedir.absolutePath() << cwd.absolutePath();
    return paths;
}
