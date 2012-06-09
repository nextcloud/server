include(${QT_USE_FILE})
include_directories(${CMAKE_CURRENT_BINARY_DIR} ${QT_QTCORE_INCLUDE_DIR})
QT4_WRAP_CPP(NATIVEQTCLIENT_MOC
    odfview.h
    ../qtjsruntime/nativeio.h
    ../qtjsruntime/nam.h
)
file(WRITE ${CMAKE_CURRENT_BINARY_DIR}/application.qrc
        "<RCC><qresource prefix='/'>")
foreach(FILE ${HTML5UIFILES} ${LIBJSFILES} index.html scripts.js webodf.css)
    file(APPEND ${CMAKE_CURRENT_BINARY_DIR}/application.qrc
        "<file>www/${FILE}</file>\n")
endforeach(FILE ${HTML5UIFILES})
file(APPEND ${CMAKE_CURRENT_BINARY_DIR}/application.qrc
        "</qresource></RCC>")
COPY_FILES(NATIVEDEPS ${CMAKE_SOURCE_DIR}/programs/touchui
    ${CMAKE_CURRENT_BINARY_DIR}/www ${HTML5UIFILES})
COPY_FILES(NATIVEDEPS ${CMAKE_CURRENT_SOURCE_DIR}
    ${CMAKE_CURRENT_BINARY_DIR}/www scripts.js)
COPY_FILES(NATIVEDEPS ${CMAKE_SOURCE_DIR}/programs/touchui
    ${CMAKE_CURRENT_BINARY_DIR}/www index.html)
COPY_FILES(NATIVEDEPS ${CMAKE_SOURCE_DIR}/webodf
    ${CMAKE_CURRENT_BINARY_DIR}/www webodf.css)
COPY_FILES(NATIVEDEPS ${CMAKE_SOURCE_DIR}/webodf
    ${CMAKE_CURRENT_BINARY_DIR}/www ${LIBJSFILES})
QT4_ADD_RESOURCES(NATIVEQTCLIENT_RES
    ${CMAKE_CURRENT_BINARY_DIR}/application.qrc)

add_custom_target(nativeQtClientDepencencies ALL DEPENDS ${NATIVEDEPS})

add_executable(nativeQtClient
    main.cpp
    odfview.cpp
    ../qtjsruntime/nativeio.cpp
    odfpage.cpp ${NATIVEQTCLIENT_MOC} ${NATIVEQTCLIENT_UI}
    ${NATIVEQTCLIENT_RES})

target_link_libraries(nativeQtClient ${QT_LIBRARIES})
add_dependencies(nativeQtClient nativeQtClientDepencencies)
