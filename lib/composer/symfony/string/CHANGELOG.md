CHANGELOG
=========

5.1.0
-----

 * added the `AbstractString::reverse()` method
 * made `AbstractString::width()` follow POSIX.1-2001
 * added `LazyString` which provides memoizing stringable objects
 * The component is not marked as `@experimental` anymore
 * added the `s()` helper method to get either an `UnicodeString` or `ByteString` instance,
   depending of the input string UTF-8 compliancy
 * added `$cut` parameter to `Symfony\Component\String\AbstractString::truncate()`
 * added `AbstractString::containsAny()`
 * allow passing a string of custom characters to `ByteString::fromRandom()`

5.0.0
-----

 * added the component as experimental
