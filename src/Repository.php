<?php

namespace Mblarsen\LaravelRepository;

use BadMethodCallException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mblarsen\LaravelRepository\Traits\Filters;
use Mblarsen\LaravelRepository\Traits\IncludesRelations;
use Mblarsen\LaravelRepository\Traits\Sorts;
use Mblarsen\LaravelRepository\Traits\WrapsInResource;

/**
 * @method allQuery($query = null): Builder
 * @method allResources($query = null): \Illuminate\Http\Resources\Json\ResourceCollection
 * @method createQuery(array $data): JsonBuilder
 * @method createResource(array $data): JsonResource
 * @method findQuery($id, $query = null): Builder
 * @method findResource($id, $query = null): JsonResource
 * @method listQuery($column = null, $query = null): Builder
 * @method updateQuery(Model $model, array $data): Builder
 * @method updateResource(Model $model, array $data): JsonResource
 */
class Repository
{
    use Filters;
    use IncludesRelations;
    use Sorts;
    use WrapsInResource;

    /** @var string */
    protected $model;

    /** @var ResourceContext $resource_context */
    protected $resource_context;

    /** @var string|callable $list_column */
    protected $default_list_column;

    /** @var bool $only_query */
    protected $only_query = false;

    public function __construct(ResourceContext $resource_context)
    {
        $this->resource_context = $resource_context;
        $this->register();
    }

    protected function register()
    {
        // Allows you to set default_list_column
    }

    /**
     * Creates a new repository for a model.
     *
     * @param string $model model name
     * @param array|ResourceContext $context
     */
    public static function for(string $model, $context = null): self
    {
        /** @var Repository $repository */
        $repository = resolve(static::class);
        $repository->setModel($model);
        if ($context) {
            $repository->setContext(
                is_array($context)
                    ? ArrayResourceContext::create($context)
                    : $context
            );
        }
        return $repository;
    }

    public function __call($name, $arguments)
    {
        if ($this->canWrapInResource($name)) {
            return $this->wrapInResource(
                call_user_func_array([&$this, Str::before($name, 'Resource')], $arguments)
            );
        }

        if ($this->canReturnAsQuery($name)) {
            try {
                $this->only_query = true;
                return call_user_func_array([&$this, Str::before($name, 'Query')], $arguments);
            } finally {
                $this->only_query = false;
            }
        }

        throw new BadMethodCallException();
    }

    private function canReturnAsQuery($name)
    {
        return Str::endsWith($name, 'Query') &&
            in_array(
                Str::before($name, 'Query'),
                ['all', 'find', 'list']
            );
    }

    private function canWrapInResource($name)
    {
        return (Str::endsWith($name, 'Resource') ||
            Str::endsWith($name, 'Resources')) &&
            in_array(
                Str::before($name, 'Resource'),
                ['all', 'find', 'create', 'update', 'destroy']
            );
    }

    /**
     * Get the currenct resource context
     */
    public function getContext(): ResourceContext
    {
        return $this->resource_context;
    }

    /**
     * Set or replace the resource context
     */
    public function setContext(ResourceContext $resource_context, bool $set_allowed_with = false)
    {
        $this->resource_context = $resource_context;

        if ($set_allowed_with) {
            $this->setAllowedWith($resource_context->with());
        }

        return $this;
    }

    /**
     * Set the model
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Return all models based on resource context and query
     *
     * The ResourceContext determines if the result is a Collection or a
     * LengthAwarePaginator.
     *
     * @return LengthAwarePaginator|Collection|Builder
     */
    public function all($query = null)
    {
        $query = $this->modelQuery($query);

        $this
            ->validateQurey($query)
            ->applyWith($query)
            ->applySort($query)
            ->applyFilters($query);

        if ($this->only_query) {
            return $query;
        }

        return $this->execute($query);
    }

    /**
     * Execute query
     *
     * @param Builder $query
     * @return LengthAwarePaginator|Collection
     */
    private function execute($query)
    {
        $page = $this->resource_context->page();
        $per_page = $this->resource_context->perPage();
        $should_paginate = $this->resource_context->paginate();

        return $should_paginate
            ? $query->paginate($per_page, ['*'], 'page', $page)
            : $query->get();
    }

    /**
     * Produces a result suitable for selects, lists, and autocomplete. All
     * entries that has a 'value' and a 'label' key.
     *
     * Note: if a callable is used the mapping is performed in memory, while a
     * string is done in the database layer.
     *
     * @param callable|string $column
     * @param Builder $query
     * @return Collection|Builder|LengthAwarePaginator
     */
    public function list($column = null, $query = null)
    {
        $query = $this->modelQuery($query);

        $column = $column
            ?: $this->default_list_column
            ?: $this->default_sort_by;

        if (is_string($column)) {
            $query->select([$query->getModel()->getKeyName() . " AS value", "$column AS label"]);
            return $this->all($query);
        }

        $mapper = function (Model $model) use ($column) {
            return [
                'value' => $model->getKey(),
                'label' => $column($model)
            ];
        };

        if (is_callable($column)) {
            $all = $this->all($query);

            if ($all instanceof Builder) {
                return $all;
            }
            if ($all instanceof Collection) {
                return $all->map($mapper);
            }
            if ($all instanceof LengthAwarePaginator) {
                $items = collect($all->items())->map($mapper)->toArray();
                $all = new PaginationLengthAwarePaginator(
                    $items,
                    $all->total(),
                    $all->perPage(),
                    $all->currentPage()
                );
                return $all;
            }
        }

        throw new InvalidArgumentException("'column' should be a string or callable");
    }

    /**
     * @return Model|Builder
     */
    public function find($id, $query = null)
    {
        $query = $this->modelQuery($query);

        $this
            ->validateQurey($query)
            ->applyWith($query);

        $query->whereId($id);

        if ($this->only_query) {
            return $query;
        }

        return $query->firstOrFail();
    }

    public function create(array $data): Model
    {
        return $this->model::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model;
    }

    public function destroy(Model $model)
    {
        $model->delete();
    }

    protected function modelQuery($query = null)
    {
        return $query ?? $this->model::query();
    }

    protected function validateQurey(Builder $query)
    {
        $model = $this->model;
        $query_model = get_class($query->getModel());

        if ($model !== $query_model) {
            throw new InvalidArgumentException("The input query and model does not match");
        }

        return $this;
    }
}
