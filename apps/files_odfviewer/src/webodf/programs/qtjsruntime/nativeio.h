#ifndef NATIVEIO_H
#define NATIVEIO_H

#include <QtCore/QFile>
#include <QtCore/QDir>
#include <QtCore/QMap>

class QWebPage;

// class that exposes filesystem to web environment
class NativeIO : public QObject {
Q_OBJECT
private:
    QWebPage* webpage;
    QString errstr;
    const QDir runtimedir;
    const QDir cwd;
    const QMap<QString, QFile::Permissions> pathPermissions;
public:
    typedef QMap<QString, QFile::Permissions> PathMap;
    PathMap v;
    NativeIO(QObject* parent, const QDir& runtimedir, const QDir& cwd,
             const PathMap& pathPermissions = PathMap());
public slots:
    /**
     * Return the last error.
     */
    QString error() {
        return errstr;
    }
    QString readFileSync(const QString& path, const QString& encoding);
    QString read(const QString& path, int offset, int length);
    void writeFile(const QString& path, const QString& data);
    void unlink(const QString& path);
    int getFileSize(const QString& path);
    void exit(int exitcode);
    QString currentDirectory() const;
    QStringList libraryPaths() const;
};

#endif
