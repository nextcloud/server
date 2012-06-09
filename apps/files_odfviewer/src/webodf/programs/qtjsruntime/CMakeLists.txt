include(${QT_USE_FILE})
QT4_WRAP_CPP(QTJSRUNTIME_MOC nam.h pagerunner.h nativeio.h)
add_executable(qtjsruntime qtjsruntime.cpp pagerunner.cpp nativeio.cpp ${QTJSRUNTIME_MOC})
target_link_libraries(qtjsruntime ${QT_LIBRARIES})
