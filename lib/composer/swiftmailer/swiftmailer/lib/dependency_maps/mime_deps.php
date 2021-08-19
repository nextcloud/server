<?php

require __DIR__.'/../mime_types.php';

Swift_DependencyContainer::getInstance()
    ->register('properties.charset')
    ->asValue('utf-8')

    ->register('email.validator')
    ->asSharedInstanceOf('Egulias\EmailValidator\EmailValidator')

    ->register('mime.idgenerator.idright')
    // As SERVER_NAME can come from the user in certain configurations, check that
    // it does not contain forbidden characters (see RFC 952 and RFC 2181). Use
    // preg_replace() instead of preg_match() to prevent DoS attacks with long host names.
    ->asValue(!empty($_SERVER['SERVER_NAME']) && '' === preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'swift.generated')

    ->register('mime.idgenerator')
    ->asSharedInstanceOf('Swift_Mime_IdGenerator')
    ->withDependencies([
        'mime.idgenerator.idright',
    ])

    ->register('mime.message')
    ->asNewInstanceOf('Swift_Mime_SimpleMessage')
    ->withDependencies([
        'mime.headerset',
        'mime.textcontentencoder',
        'cache',
        'mime.idgenerator',
        'properties.charset',
    ])

    ->register('mime.part')
    ->asNewInstanceOf('Swift_Mime_MimePart')
    ->withDependencies([
        'mime.headerset',
        'mime.textcontentencoder',
        'cache',
        'mime.idgenerator',
        'properties.charset',
    ])

    ->register('mime.attachment')
    ->asNewInstanceOf('Swift_Mime_Attachment')
    ->withDependencies([
        'mime.headerset',
        'mime.base64contentencoder',
        'cache',
        'mime.idgenerator',
    ])
    ->addConstructorValue($swift_mime_types)

    ->register('mime.embeddedfile')
    ->asNewInstanceOf('Swift_Mime_EmbeddedFile')
    ->withDependencies([
        'mime.headerset',
        'mime.base64contentencoder',
        'cache',
        'mime.idgenerator',
    ])
    ->addConstructorValue($swift_mime_types)

    ->register('mime.headerfactory')
    ->asNewInstanceOf('Swift_Mime_SimpleHeaderFactory')
    ->withDependencies([
        'mime.qpheaderencoder',
        'mime.rfc2231encoder',
        'email.validator',
        'properties.charset',
        'address.idnaddressencoder',
    ])

    ->register('mime.headerset')
    ->asNewInstanceOf('Swift_Mime_SimpleHeaderSet')
    ->withDependencies(['mime.headerfactory', 'properties.charset'])

    ->register('mime.qpheaderencoder')
    ->asNewInstanceOf('Swift_Mime_HeaderEncoder_QpHeaderEncoder')
    ->withDependencies(['mime.charstream'])

    ->register('mime.base64headerencoder')
    ->asNewInstanceOf('Swift_Mime_HeaderEncoder_Base64HeaderEncoder')
    ->withDependencies(['mime.charstream'])

    ->register('mime.charstream')
    ->asNewInstanceOf('Swift_CharacterStream_NgCharacterStream')
    ->withDependencies(['mime.characterreaderfactory', 'properties.charset'])

    ->register('mime.bytecanonicalizer')
    ->asSharedInstanceOf('Swift_StreamFilters_ByteArrayReplacementFilter')
    ->addConstructorValue([[0x0D, 0x0A], [0x0D], [0x0A]])
    ->addConstructorValue([[0x0A], [0x0A], [0x0D, 0x0A]])

    ->register('mime.characterreaderfactory')
    ->asSharedInstanceOf('Swift_CharacterReaderFactory_SimpleCharacterReaderFactory')

    ->register('mime.textcontentencoder')
    ->asAliasOf('mime.qpcontentencoder')

    ->register('mime.safeqpcontentencoder')
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_QpContentEncoder')
    ->withDependencies(['mime.charstream', 'mime.bytecanonicalizer'])

    ->register('mime.rawcontentencoder')
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_RawContentEncoder')

    ->register('mime.nativeqpcontentencoder')
    ->withDependencies(['properties.charset'])
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_NativeQpContentEncoder')

    ->register('mime.qpcontentencoder')
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_QpContentEncoderProxy')
    ->withDependencies(['mime.safeqpcontentencoder', 'mime.nativeqpcontentencoder', 'properties.charset'])

    ->register('mime.7bitcontentencoder')
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_PlainContentEncoder')
    ->addConstructorValue('7bit')
    ->addConstructorValue(true)

    ->register('mime.8bitcontentencoder')
    ->asNewInstanceOf('Swift_Mime_ContentEncoder_PlainContentEncoder')
    ->addConstructorValue('8bit')
    ->addConstructorValue(true)

    ->register('mime.base64contentencoder')
    ->asSharedInstanceOf('Swift_Mime_ContentEncoder_Base64ContentEncoder')

    ->register('mime.rfc2231encoder')
    ->asNewInstanceOf('Swift_Encoder_Rfc2231Encoder')
    ->withDependencies(['mime.charstream'])
;

unset($swift_mime_types);
