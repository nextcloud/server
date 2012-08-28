macro(COPY_FILES _varname _srcdir _tgtdir)
  foreach(_file ${ARGN})
    GET_FILENAME_COMPONENT(_subdir ${_file} PATH)
    FILE(MAKE_DIRECTORY ${_tgtdir}/${_subdir})
    add_custom_command(
      OUTPUT ${_tgtdir}/${_file}
      COMMAND ${CMAKE_COMMAND} ARGS -E copy_if_different
        ${_srcdir}/${_file}
        ${_tgtdir}/${_file}
      DEPENDS
        ${_srcdir}/${_file}
    )
    set(${_varname} ${${_varname}} ${_tgtdir}/${_file})
  endforeach(_file)
endmacro(COPY_FILES _directory _files)

if(QT4_FOUND)
  add_subdirectory(qtjsruntime)
  add_subdirectory(nativeQtClient)
  add_subdirectory(docnosis)
endif(QT4_FOUND)

if(ANDROID_SDK_DIR AND ANT)
  add_subdirectory(android)
endif(ANDROID_SDK_DIR AND ANT)

add_subdirectory(firefoxextension)

add_subdirectory(touchui)
add_subdirectory(playbook)
