# laravel-repository

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mblarsen/laravel-repository.svg)](https://packagist.org/packages/mblarsen/laravel-repository)
[![Build Status](https://scrutinizer-ci.com/g/mblarsen/laravel-repository/badges/build.png?b=master)](https://scrutinizer-ci.com/g/mblarsen/laravel-repository/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/mblarsen/laravel-repository/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mblarsen/laravel-repository/?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/mblarsen/laravel-repository.svg)](https://scrutinizer-ci.com/g/mblarsen/laravel-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/mblarsen/laravel-repository.svg)](https://packagist.org/packages/mblarsen/laravel-repository)

> Beefed up query-builder and repository to reduce boilerplate and keep your controllers lean

The goal of this repository implementation:

1. Separate controller code from query code
2. avoid boilerplate for paged, filtered, and sorted resources
3. let you be in control of the query for special cases

Practically the repository class is a mix between a query builder and a
repository.

Features:

-   Get started with zero config. [See basic examples](#basic-examples)
-   The usual suspects: `all`, `find`, `create`, `update`, `destroy`.
-   Plus `allResources`, `findResource`, and so on to wrap in resources.
-   `list()` function useful for `<select>` and autocompletes.
-   Front-end and user driven. The request is the context for what gets included:
    -   filter query on model and relations
    -   include relations (all blocked by default)
    -   deal with paging transparently
    -   order models by their props or their relations props (with no custom SQL)

This package includes one interfaces and three classes for you to build on:

-   `Repository` class, to use as is or extend for your model needs.
-   `ResourceContext` interface, provides data to the repository.
-   `RequestResourceContext`, draws data from the incoming Request object.
-   `ArrayResourceContext`, you provide the data. Good for testing.

## Installation

You can install the package via composer:

```bash
composer require mblarsen/laravel-repository
```

The repository relies on a `ResourceContext` to provide it with the necessary
values to be able to sort, filter, paginate and so on. This is handled
automatically, but it means that you should let Laravel's depency injection
provide a repository for you. This is espesically powerfull if you extende the
base repository class.

The default context that is used is the `RequestResourceContext` which is
automatically injected into the repository. This is espesically useful when you
are building public or private APIs to serve a front-end. However, another
implementation is provided that lets you provide the control to a higher
degree. This is the `ArrayResourceContext`. This implementation is useful for
testing or for when you build a traditional Laravel application using Blade
views.

The following examples a biased toward use of the `RequestResourceContext`.

### Basic examples

The base repository knows nothing of your models, so unless you sub-class the
repository, you must specify what model you are querying.

```php
// Using Laravel's resolve() helper
$repository = resolve(Repository::class)->setModel(Post::class);
// Using static factory
$repository = Repository::for(Post::class);
```

When used in controllers it is recommended letting Laravel do the work for you:

```php
public function index(Repository $repository)
{
    return $repository->setModel(Post::class)->all();
}
```

... or in case of a custom repository:

```php
public function index(PostRepository $repository)
{
    return $repository->all();
}
```

The result is now automatically **sorted**, **filtered**, and **paginated** according to the request.

Example requests that you'll be able to provide now:

```html
/posts?sort_by=created_at&page=2&filters[title]=laravel
```

That is:

-   `/posts`, request posts
-   `sort_by=created_at`, sort by created_at
-   `sort_by=desc`, sort in descending order (default: `asc`)
-   `page=2`, paginated result, request page 2 (default: `null` meaing not paginated)
-   `filters[title]=laravel` search for title in the posts name

You can also filter by relationships. E.g. `filters[address.zip]=1227`.

Since relations are disallowed by default nothing requests to include are
ignored. But once we set that up your will be able to request relations as well:

-   `with[]=ads&with[]=comments`, will include the relations `ads` and `comments`.

### Repository features

You can define the behaviour of the repository using a chained API. Be sure to
check the [full API](#api) below.

#### Control what relations to include

Query: `with` or `with[]`

The repository will include any relations specified in `with` based on what is
allowed. That ensures that the client cannot request data that you have not
allowed.

```php
$repository->setAllowedWith(['comments']);
```

Some times you want to include certain relations by default. In addition to
doing that on the model directly the Laravel way you also have the option to
set which on the repository. This allows you to control the list of relation
per action in the controller.

```php
$relation->setDefaultWith(['comments']);
```

_Aside: if you are building a public api (for your SPA or 3rd party) it is
recommended that you wrap the result a `JsonResource`. This will give you
control of what properties are exposed and will allow you to transform the data
further. [See resources section](#resources)._

#### Filtering

Query: `filter[key]=value`

This package provides a search like functionality through its filters. Under
the hood it uses `LIKE`, ie. `%value%`.

The key doesn't have to be properties on the main model. It can be relation
properties as well. Here are some examples:

```php
// Search on model property
title=cra
// Seacch on relation property
address.city=mass
```

You can combine properties in a search:

```php
// Search in full name
first_name+middle_name+last_name=cra
```

And lastly you can choose to search for the same value in multiple properties:

```php
// Search in both title, name, and email
title|name|email=cra
```

A different way to filter is to provider a query builder to `all()` and
`find()`. See examples in [the API](#api).

#### Transforming `RequestResourceContext`

You cannot modify the request context directly, but you have the option to
convert it to an `ArrayResourceContext`.

```php
public function index(UserRepository $repository)
{
    $repository->setContext(
        ArrayResourceContext::create(
            $repository->getContext()->toArray()
        )
            ->merge([
                'filters' => ['status' => 'approved']
            ])
            ->exclude(['page'])
    );
}
```

Note that `toArray()` returns a full context. Including the defaults for `page`
and `per_page`. Use `exclude()` remove them from the `ArrayResourceContext`.

### Custom repositories

Many of your models will likely not need as custom (sub-classed) repository.
But often your core models have more logic associated with them. In that case
it is advised do extend the base repository.

All the properties except the `model` can be omitted. Well, you can omitted the
model too but that is kind of pointless.

Disclaimer: the example's purpose is to demo the flexibility not and isn't very
real world.

```php
class PostRepository extends Repository
{
    // We serve you Posts
    protected $model = Post::class;

    // The client can request to include the following relations
    protected $allowed_with = ['ads', 'comments'];

    // However, we will include these automatically
    protected $default_with = ['comments'];

    // We change the default sort key to created_at ...
    protected $default_sort_by = 'created_at';

    // ... in descending order
    protected $default_sort_order = 'desc';

    // We override modelQuery to ensure only
    // published posts will be returned
    protected function modelQuery($query = null)
    {
        // It is perfectly okay to not invoke the parent.
        // It simly defaults to an empty query of the current
        // model. These are identical:
        //
        // $query = parent::modelQuery($query);
        // $query = $query ?: Post::query()

        $query = parent::modelQuery($query);
        $query->whereNotNul('published_at');

        return $query;
    }
}
```

You can achieve the same with the base repository, but of course then you would
have to repeat the setup every time:

```php
public function index(Repository $repository)
{
    $only_published = Post::query()->whereNotNul('published_at');

    $repository
        ->setModel(Post::class)
        ->setDefaulSort('created_at', 'desc')
        ->setAllowedWith(['ads', 'comments'])
        ->setDefaultWith(['comments'])
        ;

    return $repository->all($only_published);
}
```

Versus:

```php
public function index(PostRepository $repository)
{
    return $repository->all();
}
```

### Resources

If you are building a public api (for your SPA or 3rd party) it is recommended
that you wrap the result a `JsonResource`. This will give you control of what
properties are exposed and will allow you to transform the data further.

Doing is really easy.

```php
// using base repository
$repository->setResource(UserResource::class);

// or extending
protected $resource = UserResource::class;
```

Then you just use any of the API methods prepending `Resource` or `Resources`
as shown here:

```php
$repository->allResources();
$repository->findResource(3);
$repository->createResource(['name' => 'foo']);
$repository->updateResource($user, ['name' => 'foo']);
```

If you implement a collection resource you can set that as the second arguments
to the `setResource` method call:

```php
$repository->setResource(UserResource::class, UserResourceCollection::class);

// or in class
protected $resource_collection = UserResourceCollection::class
```

_Note: `list()` doesn't support resources. It didn't make any sense to me. You
are welcome to change my mind._

### Testing

When it comes to testing the `ArrayResourceContext` comes in handy. It lets you
pass in context values directly.

```php
$repository = Repository::for(User::class, ArrayResourceContext::create([
    'filters' => [
        'status' => 'approved',
    ]
]));

// or shorter

$repository = Repository::for(User::class, [
    'filters' => [
        'status' => 'approved',
    ]
]);

```

## API

-   [`all($query = null)`](#all)
-   [`list(string|callabel $column, $query = null)`](#list)
-   [`find($id, $query = null)`](#find)
-   [`create(array $data)`](#create)
-   [`update(Model $model, array $data)`](#update)
-   [`destroy(Model $model)`](#destroy)
-   [`setContext(ResourceContext|array $resource_context)`](setContext)
-   [`setModel(string $model)`](setModel)
-   [`setAllowedWith(array $allowed)`](#setAllowedWith)
-   [`setDefaultSort(string $by, string $order = 'asc')`](#setDefaulSort)
-   [`setDefaultWith(array $with)`](#setDefaultWith)

When extending the base repository you may want to check out these additional
functions:

-   [`modelQuery($query = null)`](#modelQuery)
-   [`register()`](#register)

### `all($query = null)`

### `allQuery($query = null)`

### `allResources($query = null)`

<a name="all"></a>

Return all models given the current resource context.

```php
public function index(UserRepository $user_repository)
{
    // To get only users with status 'pending'
    $query = User::where('status', 'pending');
    $users = $user_repository->all($query);

    // Of course in this simple case we could have achived the same using a
    // filter: GET /users?filters[status]=pending
}
```

Use with `Query` suffix to return as a query builder.

Use with `Resources` suffix to return as a resource collection.

### `list(string|callabel $column = null, $query = null)`

### `listQuery(string|callabel $column = null, $query = null)`

<a name="list"></a>

Produces a result suitable for selects, lists, and autocomplete. All entries
that has a 'value' and a 'label' key.

If `$column` is omitted the default sort by is used. In many cases they'll be
the same anyway.

Use with `Query` suffix to return as a query builder.

Note: if a callable is used the mapping is performed in memory, while a string
is done in the database layer. Also note that when using the listQuery variant
the callable is not reflected.

### `find($id, $query = null)`

### `findQuery($id, $query = null)`

### `findResource($id, $query = null)`

<a name="find"></a>

Gets a single model. You can further narrow down the result by providing a
start query. See example in `all()`

Use with `Query` suffix to return as a query builder.

Use with `Resource` suffix to return as a resource.

### `create(array $data)`

### `createResource(array $data)`

<a name="create"></a>

Typical crUd. Use with `Resource` suffix to return as a resource.

### `update(Model $model, array $data)`

<a name="update"></a>

Typical crUd.

Use with `Resource` suffix to return as a resource.

### `destroy(Model $model)`

<a name="destroy"></a>

Typical cruD.

### `setContext(ResourceContext|array $resource_context)`

<a name="setContext"></a>

This method lets you set or change the context after the repository is created.

### `setModel(string $model)`

<a name="setModel"></a>

See example code.

### `setAllowedWith(array $allowed)`

<a name="setAllowedWith"></a>

See example code.

### `setDefaultSort(string $by, string $order = 'asc')`

<a name="setDefaultSort"></a>

### `setDefaultWith(array $with)`

<a name="setDefaultWith"></a>

See example code.

### `modelQuery($query = null)`

<a name="modelQuery"></a>

See example code.

### `register()`

<a name="register"></a>

Called when the repository is created. This is useful setting up this
`default_list_column` function for a sub-classed repository.

```php
protected function register()
{
    $this->default_list_column = function ($model) {
        return $model->full_name;
    };
}
```

See [test case](tests/ExtensionTest.php).

### `interface ResourceContext`

<a name="ResourceContext"></a>

See [ResourceContext](src/ResourceContext.php) implementation.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed
recently.

## Security

If you discover any security related issues, please email m19n@pm.me instead of
using the issue tracker.

## Prior work

-   [Spatie QueryBuildir](https://github.com/spatie/laravel-query-builder)
-   [bosnadev/repository](https://github.com/bosnadev/repository)

## Credits

-   [Michael Bøcker-Larsen](https://github.com/mblarsen)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

Please see [DEVELOPING](.github/DEVELOPING.md) for help getting started.

## Contributors ✨

Thanks goes to these wonderful people ([emoji key](https://allcontributors.org/docs/en/emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tr>
    <td align="center"><a href="https://www.codeboutique.com"><img src="https://avatars0.githubusercontent.com/u/247048?v=4" width="100px;" alt=""/><br /><sub><b>Michael Bøcker-Larsen</b></sub></a><br /><a href="https://github.com/mblarsen/laravel-repository/commits?author=mblarsen" title="Code">💻</a> <a href="https://github.com/mblarsen/laravel-repository/commits?author=mblarsen" title="Documentation">📖</a> <a href="#maintenance-mblarsen" title="Maintenance">🚧</a></td>
  </tr>
</table>

<!-- markdownlint-enable -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors](https://github.com/all-contributors/all-contributors) specification. Contributions of any kind welcome!

