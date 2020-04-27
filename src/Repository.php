<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as PaginationLengthAwarePaginator;
use InvalidArgumentException;
use Mblarsen\LaravelRepository\Traits\Filters;
use Mblarsen\LaravelRepository\Traits\IncludesRelations;
use Mblarsen\LaravelRepository\Traits\Sorts;

class Repository
{
    use Filters;
    use IncludesRelations;
    use Sorts;

    const WITH_ALLOW_ALL = ['*'];
    const WITH_ALLOW_NONE = [];

    /** @var string */
    protected $model;

    /** @var ResourceContext $resource_context */
    protected $resource_context;

    /** @var array $allowed_with */
    protected $allowed_with = self::WITH_ALLOW_NONE;

    /** @var array $default_with */
    protected $default_with = [];

    /** @var string $default_sort_by */
    protected $default_sort_by;

    /** @var string $default_sort_order */
    protected $default_sort_order = 'asc';

    public function __construct(ResourceContext $resource_context)
    {
        $this->resource_context = $resource_context;
    }

    /**
     * Creates a new repository for a model.
     */
    public static function for(string $model, ResourceContext $context = null): self
    {
        $repository = resolve(static::class);
        $repository->setModel($model);
        if ($context) {
            $repository->setContext($context);
        }
        return $repository;
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
    public function setContext(ResourceContext $resource_context)
    {
        $this->resource_context = $resource_context;

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
     * Paginator.
     *
     * @return LengthAwarePaginator|Collection
     */
    public function all($query = null)
    {
        $query = $this->modelQuery($query);

        return $this
            ->validateQurey($query)
            ->applyWith($query)
            ->applySort($query)
            ->applyFilters($query)
            ->paginate($query);
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
     * @return Collection
     */
    public function list($column = null, $query = null)
    {
        $query = $this->modelQuery($query);

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

            if ($all instanceof Collection) {
                $all = $all->map($mapper);
            } elseif ($all instanceof LengthAwarePaginator) {
                $items = collect($all->items())->map($mapper)->toArray();
                $all = new PaginationLengthAwarePaginator(
                    $items,
                    $all->total(),
                    $all->perPage(),
                    $all->currentPage()
                );
            }

            return $all;
        }

        throw new InvalidArgumentException("'column' should be a string or callable");
    }

    public function find($id, $query = null): Model
    {
        $query = $this->modelQuery($query);

        $this
            ->validateQurey($query)
            ->applyWith($query);

        $query->whereId($id);

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

    /**
     * @param Builder $query
     * @return LengthAwarePaginator|Collection
     */
    private function paginate($query)
    {
        $page = $this->resource_context->page();
        $per_page = $this->resource_context->perPage();
        $should_paginate = $this->resource_context->paginate();

        return $should_paginate
            ? $query->paginate($per_page, ['*'], 'page', $page)
            : $query->get();
    }
}
