# Changelog

All notable changes to `laravel-repository` will be documented in this file

## next

-   feat: pass array to `Repository::for()` without the need to specify `ArrayResourceContext`

## 0.5.0

-   feat: add `default_list_column`
-   feat: add `register()` method for sub-classes

## 0.4.0

-   feat: support for JsonResource (see [README resources section](README.md#resources)

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
