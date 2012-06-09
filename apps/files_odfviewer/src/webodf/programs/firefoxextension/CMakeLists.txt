# the files that go into the extension
set(FIREFOXEXTENSIONFILES
    bootstrap.js
    chrome.manifest
    skin/default/icon.png
    content/odf.html
    components/odfContentHandler.js
)
configure_file(${CMAKE_CURRENT_SOURCE_DIR}/install.rdf.in
    ${CMAKE_CURRENT_SOURCE_DIR}/install.rdf)
set(WEBODFXPI ${CMAKE_CURRENT_BINARY_DIR}/webodf-${WEBODF_VERSION}.xpi)
add_custom_command(
    OUTPUT ${WEBODFXPI}
    # copy the common webodf.css and webodf.js
    COMMAND ${CMAKE_COMMAND} ARGS -E copy_if_different
        ${CMAKE_SOURCE_DIR}/webodf/webodf.css
        ${CMAKE_CURRENT_SOURCE_DIR}/content/webodf.css
    COMMAND ${CMAKE_COMMAND} ARGS -E copy_if_different
        ${CMAKE_BINARY_DIR}/webodf/webodf.js
        ${CMAKE_CURRENT_SOURCE_DIR}/content/webodf.js
    # zip using javascript code running in node.js
    COMMAND ${NODE} ARGS ../../webodf/lib/runtime.js packextension.js
        ${WEBODFXPI}
        ${FIREFOXEXTENSIONFILES}
        content/webodf.js
        content/webodf.css
        install.rdf
    DEPENDS NodeJS
        packextension.js ${FIREFOXEXTENSIONFILES}
        install.rdf.in
        ${CMAKE_SOURCE_DIR}/webodf/webodf.css
        ${CMAKE_BINARY_DIR}/webodf/webodf.js
    WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}
)
add_custom_target(firefoxextension ALL DEPENDS ${WEBODFXPI})
