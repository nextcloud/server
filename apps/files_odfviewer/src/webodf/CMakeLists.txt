# WebODF is mostly a JavaScript project. CMake needs to know about the C++ parts
project (WebODF C CXX)
# version 2.8.2 is needed to have support for zip files in external projects
cmake_minimum_required(VERSION 2.8.2)

# At this point, the version number that is used throughout is defined
set(WEBODF_VERSION 0.3.0)

# This makefile 'compiles' WebODF using various tools, instruments the code and
# builds and packages programs that use WebODF.

# Find installed dependencies
find_package(Qt4 4.7.0 COMPONENTS QtCore QtGui QtXml QtNetwork QtWebKit)
if (NOT QT4_FOUND)
  message(WARNING "Qt4 with modules QtCore QtGui QtXml QtNetwork QtWebKit was not found. qtjsruntime will no be built.")
endif (NOT QT4_FOUND)

# java runtime is needed for Closure Compiler
find_package(Java COMPONENTS Runtime)

if (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)
  message(FATAL_ERROR "Compiling in the source directortory is not supported. Use for example 'mkdir build; cd build; cmake ..'.")
endif (CMAKE_SOURCE_DIR STREQUAL CMAKE_BINARY_DIR)

# Tools must be obtained to work with:
include (ExternalProject)

if(Java_JAVA_EXECUTABLE)
    # Closure Compiler
    ExternalProject_Add(
        ClosureCompiler
        URL "http://closure-compiler.googlecode.com/files/compiler-20120305.tar.gz"
        URL_MD5 513344df6f18bfa00b17f034cabf897d
        CONFIGURE_COMMAND ""
        BUILD_COMMAND ""
        INSTALL_COMMAND ""
    )
    set(CLOSURE_JAR ${CMAKE_BINARY_DIR}/ClosureCompiler-prefix/src/ClosureCompiler/compiler.jar)
endif(Java_JAVA_EXECUTABLE)

# Rhino
if(Java_JAVA_EXECUTABLE)
    ExternalProject_Add(
        Rhino
        URL "http://ftp.mozilla.org/pub/js/rhino1_7R3.zip"
        URL_MD5 99d94103662a8d0b571e247a77432ac5
        CONFIGURE_COMMAND ""
        BUILD_COMMAND ""
        INSTALL_COMMAND ""
    )
    set(RHINO ${CMAKE_BINARY_DIR}/Rhino-prefix/src/Rhino/js.jar)
endif(Java_JAVA_EXECUTABLE)

# JSDoc
ExternalProject_Add(
    JsDoc
    URL "http://jsdoc-toolkit.googlecode.com/files/jsdoc_toolkit-2.4.0.zip"
    URL_MD5 a8f78f5ecd24b54501147b2af341a231
    CONFIGURE_COMMAND ""
    BUILD_COMMAND ""
    INSTALL_COMMAND ""
)
set(JSDOCDIR ${CMAKE_BINARY_DIR}/JsDoc-prefix/src/JsDoc/jsdoc-toolkit)

# Node.JS
ExternalProject_Add(
    NodeJS
    URL "http://nodejs.org/dist/v0.6.15/node-v0.6.15.tar.gz"
    URL_MD5 852cfb1ed8125a4cdba456446d869d19
    CONFIGURE_COMMAND "./configure"
    BUILD_IN_SOURCE 1
    INSTALL_COMMAND ""
)
set(NODE ${CMAKE_BINARY_DIR}/NodeJS-prefix/src/NodeJS/out/Release/node)

# JSCoverage
ExternalProject_Add(
    JSCoverage
    URL "http://siliconforks.com/jscoverage/download/jscoverage-0.5.1.tar.bz2"
    URL_MD5 a70d79a6759367fbcc0bcc18d6866ff3
    PATCH_COMMAND patch -p0 < ${CMAKE_CURRENT_SOURCE_DIR}/JSCoverage.patch
    CONFIGURE_COMMAND "./configure"
    BUILD_IN_SOURCE 1
    INSTALL_COMMAND ""
)
set(JSCOVERAGE ${CMAKE_BINARY_DIR}/JSCoverage-prefix/src/JSCoverage/jscoverage)

# Android
if (NOT ANDROID_SDK_DIR)
  find_path(ANDROID_SDK_DIR platform-tools/aapt)
endif(NOT ANDROID_SDK_DIR)
if (NOT ANT)
  find_file(ANT NAMES ant ant.exe /usr/bin /usr/local/bin)
endif(NOT ANT)



set(LIBJSFILES lib/packages.js lib/runtime.js lib/core/Base64.js
    lib/core/RawDeflate.js lib/core/ByteArray.js
    lib/core/ByteArrayWriter.js lib/core/RawInflate.js
    lib/core/Cursor.js lib/core/UnitTester.js
    lib/core/PointWalker.js lib/core/Async.js
    lib/core/Zip.js

    lib/xmldom/LSSerializerFilter.js lib/xmldom/LSSerializer.js
    lib/xmldom/RelaxNGParser.js lib/xmldom/RelaxNG.js
    lib/xmldom/RelaxNG2.js lib/xmldom/OperationalTransformInterface.js
    lib/xmldom/OperationalTransformDOM.js
    lib/xmldom/XPath.js

    lib/odf/StyleInfo.js lib/odf/Style2CSS.js
    lib/odf/FontLoader.js lib/odf/OdfContainer.js
    lib/odf/Formatting.js lib/odf/OdfCanvas.js

    lib/gui/PresenterUI.js lib/gui/Caret.js
    lib/gui/SelectionMover.js lib/gui/XMLEdit.js

    lib/manifest.js
)

set(HTML5UIFILES
  app/app.js app/controller/Files.js app/model/FileSystem.js
  app/views/FileDetail.js app/views/FilesList.js app/views/OdfView.js
  app/views/Viewport.js sencha-touch.css sencha-touch.js
  app/store/FileStore.js
  ZoomOut.png ZoomIn.png go-previous.png go-next.png
  zoom-fit-width.png zoom-fit-best.png zoom-fit-height.png
)

add_subdirectory(webodf)
add_subdirectory(programs)

# package webodf
set(WEBODFZIP webodf-${WEBODF_VERSION}.zip)
set(WEBODFZIP_FILES
  ${CMAKE_BINARY_DIR}/webodf/webodf-debug.js
  ${CMAKE_BINARY_DIR}/webodf/webodf.js
  ${CMAKE_SOURCE_DIR}/webodf/webodf.css
)
add_custom_command(
    OUTPUT ${WEBODFZIP}
    # zip using javascript code running in node.js
    COMMAND ${NODE} ARGS webodf/lib/runtime.js packwebodf.js
        ${CMAKE_BINARY_DIR}/${WEBODFZIP}
#input files
        ${WEBODFZIP_FILES}
#output files
        webodf-debug.js
        webodf.js
        webodf.css
    DEPENDS NodeJS
        packwebodf.js
        ${WEBODFZIP_FILES}
        webodf-debug.js
        webodf.js
    WORKING_DIRECTORY ${CMAKE_SOURCE_DIR}
)
add_custom_target(zip ALL DEPENDS ${WEBODFZIP})

# vim:expandtab
