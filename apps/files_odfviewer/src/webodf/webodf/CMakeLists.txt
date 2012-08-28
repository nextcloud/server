string(COMPARE EQUAL "${CMAKE_SOURCE_DIR}" "${CMAKE_CURRENT_SOURCE_DIR}"
    WRONGCMAKEDIR)
if(WRONGCMAKEDIR)
  message(FATAL_ERROR "You should point CMake to the parent directory.")
endif(WRONGCMAKEDIR)

set(TESTJSFILES tests/core/ZipTests.js
    tests/core/Base64Tests.js
    tests/core/CursorTests.js
    tests/core/PointWalkerTests.js
    tests/core/RuntimeTests.js
    tests/gui/CaretTests.js
    tests/gui/SelectionMoverTests.js
    tests/gui/XMLEditTests.js
    tests/xmldom/OperationalTransformDOMTests.js
    tests/xmldom/XPathTests.js
    tests/tests.js
)

add_custom_target(jslintcheck ALL
    COMMAND ${NODE} lib/runtime.js tools/runjslint.js
        ${LIBJSFILES} ${TESTJSFILES}
    DEPENDS NodeJS
    WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}
    SOURCES ${LIBJSFILES} ${TESTJSFILES}
)

if (Java_JAVA_EXECUTABLE)
  # nonStandardJsDocs is not used because we use @licstart @licend and @source
  set(SHARED_CLOSURE_ARGS --warning_level VERBOSE --jscomp_error accessControls --jscomp_error ambiguousFunctionDecl --jscomp_error checkRegExp --jscomp_error checkTypes --jscomp_error checkVars --jscomp_error constantProperty --jscomp_error deprecated --jscomp_error externsValidation --jscomp_error fileoverviewTags --jscomp_error globalThis --jscomp_error invalidCasts --jscomp_error missingProperties --jscomp_error strictModuleDepCheck --jscomp_error typeInvalidation --jscomp_error undefinedVars --jscomp_error unknownDefines --jscomp_error uselessCode --jscomp_error visibility --jscomp_off nonStandardJsDocs --summary_detail_level 3)

  foreach(JSFILE ${LIBJSFILES})
    set(LIB_CLOSURE_ARGS ${LIB_CLOSURE_ARGS}
        --js ${CMAKE_CURRENT_SOURCE_DIR}/${JSFILE})
  endforeach(JSFILE ${LIBJSFILES})

  foreach(JSFILE ${TESTJSFILES})
    set(TEST_CLOSURE_ARGS ${TEST_CLOSURE_ARGS}
        --js ${CMAKE_CURRENT_SOURCE_DIR}/${JSFILE})
  endforeach(JSFILE ${TESTJSFILES})

  add_custom_command(
      OUTPUT simplecompiled.js
      COMMAND ${Java_JAVA_EXECUTABLE}
      ARGS -jar ${CLOSURE_JAR}
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS} ${TEST_CLOSURE_ARGS}
         --compilation_level WHITESPACE_ONLY
         --formatting PRETTY_PRINT
         --js_output_file simplecompiled.js
      DEPENDS ClosureCompiler ${LIBJSFILES} ${TESTJSFILES}
  )

  add_custom_command(
      OUTPUT compiled.js
      COMMAND ${Java_JAVA_EXECUTABLE}
      ARGS -jar ${CLOSURE_JAR}
         --define IS_COMPILED_CODE=true
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS} ${TEST_CLOSURE_ARGS}
         --compilation_level ADVANCED_OPTIMIZATIONS
         --formatting PRETTY_PRINT
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
         --js_output_file compiled.js
      DEPENDS ClosureCompiler ${LIBJSFILES} ${TESTJSFILES} tools/externs.js
  )

  add_custom_command(
      OUTPUT ${CMAKE_CURRENT_BINARY_DIR}/webodf.js
      COMMAND ${Java_JAVA_EXECUTABLE}
      ARGS -jar ${CLOSURE_JAR}
         --define IS_COMPILED_CODE=true
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS}
         --compilation_level SIMPLE_OPTIMIZATIONS
         --formatting PRINT_INPUT_DELIMITER
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
         --js_output_file ${CMAKE_CURRENT_BINARY_DIR}/webodf.js
      DEPENDS ClosureCompiler ${LIBJSFILES} tools/externs.js
  )
  add_custom_target(webodf.js
      SOURCES ${CMAKE_CURRENT_BINARY_DIR}/webodf.js
  )

  # too hardcore for now...
  # add_custom_command(
  #     OUTPUT webodf-experimental.js
  #     COMMAND ${Java_JAVA_EXECUTABLE}
  #     ARGS -jar ${CLOSURE_JAR}
  #        --define IS_COMPILED_CODE=true
  #        ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS}
  #        --js ${CMAKE_CURRENT_SOURCE_DIR}/lib/export.js
  #        --compilation_level ADVANCED_OPTIMIZATIONS
  #        --formatting PRINT_INPUT_DELIMITER
  #        --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
  #        --js_output_file webodf-experimental.js
  #     DEPENDS ClosureCompiler ${LIBJSFILES} lib/export.js tools/externs.js
  # )

  add_custom_command(
      OUTPUT ${CMAKE_CURRENT_BINARY_DIR}/webodf-debug.js
      COMMAND ${Java_JAVA_EXECUTABLE}
      ARGS -jar ${CLOSURE_JAR}
         --define IS_COMPILED_CODE=true
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS}
         --compilation_level WHITESPACE_ONLY
         --formatting PRETTY_PRINT
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
         --js_output_file ${CMAKE_CURRENT_BINARY_DIR}/webodf-debug.js
      DEPENDS ClosureCompiler ${LIBJSFILES} tools/externs.js
  )
  add_custom_target(webodf-debug.js
     SOURCES ${CMAKE_CURRENT_BINARY_DIR}/webodf-debug.js
  )

  add_custom_command(
      OUTPUT odfedit.js
      COMMAND ${Java_JAVA_EXECUTABLE}
      ARGS -jar ${CLOSURE_JAR}
         --define IS_COMPILED_CODE=true
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS}
         --js ${CMAKE_CURRENT_SOURCE_DIR}/odfedit.js
         --compilation_level ADVANCED_OPTIMIZATIONS
         --formatting PRETTY_PRINT
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/extjsexterns.js
         --js_output_file odfedit.js
      DEPENDS ClosureCompiler ${LIBJSFILES} ${APPJSFILES} tools/externs.js tools/extjsexterns.js odfedit.js
  )

  add_custom_command(
    OUTPUT gui.js
    COMMAND ${Java_JAVA_EXECUTABLE}
    ARGS -jar ${CLOSURE_JAR}
         --define IS_COMPILED_CODE=true
         ${SHARED_CLOSURE_ARGS} ${LIB_CLOSURE_ARGS}
         --js ${CMAKE_CURRENT_SOURCE_DIR}/filelister.js
         --js ${CMAKE_CURRENT_SOURCE_DIR}/gui.js
         --compilation_level ADVANCED_OPTIMIZATIONS
         --formatting PRETTY_PRINT
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/externs.js
         --externs ${CMAKE_CURRENT_SOURCE_DIR}/tools/extjsexterns.js
         --js_output_file gui.js
    DEPENDS ClosureCompiler ${LIBJSFILES} ${APPJSFILES} tools/externs.js tools/extjsexterns.js filelister.js gui.js
  )

  add_custom_target(syntaxcheck ALL
      DEPENDS simplecompiled.js webodf.js webodf-debug.js compiled.js
  )

  add_custom_target(rhinotest
      COMMAND ${Java_JAVA_EXECUTABLE} -jar ${RHINO}
          -debug lib/runtime.js tests/tests.js
      DEPENDS Rhino
      WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}
      SOURCES ${LIBJSFILES}
  )
  add_custom_target(simplerhinotest
      COMMAND ${Java_JAVA_EXECUTABLE} -jar ${RHINO}
          ${CMAKE_CURRENT_BINARY_DIR}/simplecompiled.js
      DEPENDS Rhino simplecompiled.js
      WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/tests
  )
  add_custom_command(
      OUTPUT docs/index.html
       COMMAND ${Java_JAVA_EXECUTABLE}
        ARGS -jar ${JSDOCDIR}/jsrun.jar
            ${JSDOCDIR}/app/run.js -d=${CMAKE_CURRENT_BINARY_DIR}/docs
                -t=${JSDOCDIR}/templates/jsdoc ${LIBJSFILES}
        WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}
        DEPENDS ${LIBJSFILES} JsDoc
  )
  add_custom_target(doc DEPENDS docs/index.html)
  add_custom_target(simplenodetest ALL
      COMMAND ${NODE} ${CMAKE_CURRENT_BINARY_DIR}/simplecompiled.js
      DEPENDS NodeJS simplecompiled.js
      WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/tests
  )

endif (Java_JAVA_EXECUTABLE)

add_custom_target(nodetest ALL
    COMMAND ${NODE} lib/runtime.js tests/tests.js
    DEPENDS NodeJS
    WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}
    SOURCES ${LIBJSFILES}
)
if (QT4_FOUND)
  add_custom_target(qtjsruntimetest ALL
      COMMAND ${CMAKE_BINARY_DIR}/programs/qtjsruntime/qtjsruntime ../lib/runtime.js tests.js
      DEPENDS qtjsruntime jslintcheck
      WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/tests
      SOURCES ${LIBJSFILES}
  )
endif (QT4_FOUND)
add_custom_command(
    OUTPUT instrumented/index.html
    COMMAND ${JSCOVERAGE}
    ARGS --exclude=extjs --exclude=extjs/.git ${CMAKE_CURRENT_SOURCE_DIR}
         ${CMAKE_CURRENT_BINARY_DIR}/instrumented
    DEPENDS ${LIBJSFILES} jslintcheck JSCoverage
)
add_custom_target(instrumented ALL DEPENDS instrumented/index.html)
# vim:expandtab
