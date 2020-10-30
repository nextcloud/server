CHANGELOG
=========

5.0.0
-----

 * Removed support for passing `null` as 1st (`$id`) argument of `Section::get()` method, pass a valid child section identifier instead.

4.4.0
-----

 * Deprecated passing `null` as 1st (`$id`) argument of `Section::get()` method, pass a valid child section identifier instead.

3.4.0
-----

 * added the `Stopwatch::reset()` method
 * allowed to measure sub-millisecond times by introducing an argument to the
   constructor of `Stopwatch`
