CHANGELOG
=========

5.1.0
-----

 * added fluent configuration of options using `OptionResolver::define()`
 * added `setInfo()` and `getInfo()` methods
 * updated the signature of method `OptionsResolver::setDeprecated()` to `OptionsResolver::setDeprecation(string $option, string $package, string $version, $message)`
 * deprecated `OptionsResolverIntrospector::getDeprecationMessage()`, use `OptionsResolverIntrospector::getDeprecation()` instead

5.0.0
-----

 * added argument `$triggerDeprecation` to `OptionsResolver::offsetGet()`

4.3.0
-----

 * added `OptionsResolver::addNormalizer` method

4.2.0
-----

 * added support for nested options definition
 * added `setDeprecated` and `isDeprecated` methods

3.4.0
-----

 * added `OptionsResolverIntrospector` to inspect options definitions inside an `OptionsResolver` instance
 * added array of types support in allowed types (e.g int[])

2.6.0
-----

 * deprecated OptionsResolverInterface
 * [BC BREAK] removed "array" type hint from OptionsResolverInterface methods
   setRequired(), setAllowedValues(), addAllowedValues(), setAllowedTypes() and
   addAllowedTypes()
 * added OptionsResolver::setDefault()
 * added OptionsResolver::hasDefault()
 * added OptionsResolver::setNormalizer()
 * added OptionsResolver::isRequired()
 * added OptionsResolver::getRequiredOptions()
 * added OptionsResolver::isMissing()
 * added OptionsResolver::getMissingOptions()
 * added OptionsResolver::setDefined()
 * added OptionsResolver::isDefined()
 * added OptionsResolver::getDefinedOptions()
 * added OptionsResolver::remove()
 * added OptionsResolver::clear()
 * deprecated OptionsResolver::replaceDefaults()
 * deprecated OptionsResolver::setOptional() in favor of setDefined()
 * deprecated OptionsResolver::isKnown() in favor of isDefined()
 * [BC BREAK] OptionsResolver::isRequired() returns true now if a required
   option has a default value set
 * [BC BREAK] merged Options into OptionsResolver and turned Options into an
   interface
 * deprecated Options::overload() (now in OptionsResolver)
 * deprecated Options::set() (now in OptionsResolver)
 * deprecated Options::get() (now in OptionsResolver)
 * deprecated Options::has() (now in OptionsResolver)
 * deprecated Options::replace() (now in OptionsResolver)
 * [BC BREAK] Options::get() (now in OptionsResolver) can only be used within
   lazy option/normalizer closures now
 * [BC BREAK] removed Traversable interface from Options since using within
   lazy option/normalizer closures resulted in exceptions
 * [BC BREAK] removed Options::all() since using within lazy option/normalizer
   closures resulted in exceptions
 * [BC BREAK] OptionDefinitionException now extends LogicException instead of
   RuntimeException
 * [BC BREAK] normalizers are not executed anymore for unset options
 * normalizers are executed after validating the options now
 * [BC BREAK] an UndefinedOptionsException is now thrown instead of an
   InvalidOptionsException when non-existing options are passed
