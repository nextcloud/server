# Things that make developing Psalm complicated

This is a somewhat informal list that might aid others.

## Statement analysis
- **Type inference**  
  what effect do different PHP elements (function calls, if/for/foreach statements etc.) have on the types of things
- **Especially loops**  
  loops are hard to reason about - break and continue are a pain
- **Also dealing with literal strings/ints/floats**
- **Code liveness detection**  
  what effect do different PHP elements have on whether code is in scope, whether code is redundant
- **Logical assertions**  
  what effect do different PHP elements have on user-asserted logic in if conditionals, ternarys etc.
- **Generics & Templated code**  
  Figuring out how templated code should work (`@template` tags), how much it should work like it does in other languages (Hack, TypeScript etc.)

## Supporting the community
- **Supporting formal PHPDoc annotations**
- **Supporting informal PHPDoc annotations**  
  e.g. `ArrayIterator|string[]` to denote an `ArrayIterator` over strings
- **non-Composer projects**  
  e.g. WordPress

## Making Psalm fast
- **Parser-based reflection**  
  requires scanning everything necessary for analysis
- **Forking processes** (non-windows)  
  mostly handled by code borrowed from Phan, but can introduce subtle issues, also requires to think about how to make work happen in processes
- **Caching things**  
  see below

## Cache invalidation
- **Invalidating analysis results**  
  requires tracking what methods/properties are used in what other files, and invalidating those results when linked methods change
- **Partial parsing**  
  Reparsing bits of files that have changed, which is hard

## Language Server Support
- **Making Psalm fast**  
  see above
- **Handling temporary file changes**
- **Dealing with malformed PHP code**  
  When people write code, it's not always pretty as they write it. A language server needs to deal with that bad code somehow

## Fixing code with Psalter
- **Adding/replacing code**  
  Figuring out what changed, making edits that could have been made by a human
- **Minimal diffs**  
  hard to change more than you need
