# Changelog

All notable changes to `laravel-repository` will be documented in this file.

## next

## 0.12.0

-   feat: you can change the default key names used in
    `RequestResourceContext` using `mapKeys()`. See example in [README customizing section](README.md#customizing-requestresourcecontext).

## 0.11.0

-   feat: allow exact match instead of LIKE using `!` after property name. See
    updated [README filtering section](README.md#filtering).
-   bugfix: the set_allowed_with failed for when a plain array was passed as
    context.

## 0.10.0

-   refactor: the `toArray()` function will return the original value of `page`
    and `per_page`. While the output no longer truely reflects what is used in
    the Repository class it makes it much more useful working with. E.g if want
    to modify a ResourceContext you can convert it to an ArrayContext. Often you
    don't care about the pagination details, but prior to this version you would
    have to always exclude `page` otherwise a non-page request would become
    paged.

## 0.9.0

-   feat: support relations in piped filters E.g user search
    `address.email|address.phone`.

## 0.8.1

-   bugfix: `set()` would create too many levels of array

## 0.8.0

-   feat: 1st argument to `setContext()` can be an array to get ArrayResourceContext
-   feat: add 2nd argument to `setContext()` so automatically setAllowedWith
    equal to whatever is set in the context.
-   refactor: `ArrayResourceContext::merge()` is now recursive
-   feat: add `get()` and `set()` to ArrayResourceContext that respects valid keys
-   feat: add toArray to ArrayResourceContext + ResourceContext as well
-   refactor: only use known values when creating an array context

## 0.7.0

-   feat: similar to `Resource` methods you can use `Query` to get the query
    builder instead of the result.
-   feat: list() called without column parameter will attempt to use default sort by.
-   refactor: eliminate WITH_ALL and WITH_NONE constants. Just use `[]` and `[*]`
    instead. This the Laravel way.

## 0.6.0

-   feat: pass array to `Repository::for()` without the need to specify `ArrayResourceContext`

## 0.5.0

-   feat: add `default_list_column`
-   feat: add `register()` method for sub-classes

## 0.4.0

-   feat: support for JsonResource (see [README resources section](README.md#resources))

## 0.3.0

-   feat: add [`list()`](README.md#list)
-   fix: correctly deal with depper nested filters
-   docs: add CONTRIBUTING text

## 0.2.0

-   fix: throw exception if input query doesn't match model
-   chore: run test using MySQL in travis to make sure `CONCAT_WS` is tested as
    it is not supported by SQLite.

## 0.1.0

-   Initial release
