<?php

namespace Mblarsen\LaravelRepository;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class Repository
{
    const WITH_ALLOW_ALL = ['all'];
    const WITH_ALLOW_NONE = [];

    /** @var string */
    protected $model;

    /** @var ResourceContext $resource_context */
    protected $resource_context;

    /** @var array|null $allowed_with */
    protected $allowed_with = self::WITH_ALLOW_NONE;

    /** @var array $default_with */
    protected $default_with = [];

    /** @var string default_sort_by */
    protected $default_sort_by = 'name';

    /** @var string default_sort_direction */
    protected $default_sort_direction = 'asc';

    public function __construct(ResourceContext $resource_context)
    {
        $this->resource_context = $resource_context;
    }

    public static function for($model, ResourceContext $context = null): self
    {
        $repository = resolve(static::class);
        $repository->setModel($model);
        if ($context) {
            $repository->setContext($context);
        }
        return $repository;
    }

    public function getContext(): ResourceContext
    {
        return $this->resource_context;
    }

    public function setContext(ResourceContext $resource_context)
    {
        $this->resource_context = $resource_context;
    }

    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    public function setAllowedWith(array $allowed)
    {
        $this->allowed_with = $allowed;

        return $this;
    }

    public function setDefaultWith(array $with)
    {
        $this->default_with = $with;

        return $this;
    }

    public function setDefaultSort(string $by, string $direction = 'asc')
    {
        $this->default_sort_by = $by;
        $this->default_sort_direction = $direction;

        return $this;
    }

    /**
     * @return Paginator|Collection
     */
    public function all($query = null)
    {
        $query = $this->modelQuery($query);

        return $this->applyWith($query)
            ->applyOrderBy($query)
            ->applyFilters($query)
            ->paginate($query);
    }

    public function find($id, $query = null): Model
    {
        $query = $this->modelQuery($query);

        $this->applyWith($query);

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

    /**
     * @param Builder $query
     * @return Paginator|Collection
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

    private function applyOrderBy($query)
    {
        $ordering = $this->resource_context->sortBy();

        $by = $ordering['sort_by'] ?: $this->default_sort_by;
        $order = $ordering['sort_order'] ?: $this->default_sort_direction;

        if (strpos($by, '.') === false) {
            $query->orderBy($by, $order);
        } else {
            /** @var Model */
            $model = new $this->model();

            [$relation_name, $by] = explode('.', $by);

            $relation = $model->$relation_name();

            [$model_key, $relation_key, $morph_class] = $this->getJoinKeys(
                $relation,
            );

            $related = $relation->getRelated();
            $query->orderBy(
                $related
                    ::select($by)
                    ->whereColumn($relation_key, $model_key)
                    ->when($morph_class, function ($query) use ($morph_class) {
                        $query->where($morph_class, $this->model);
                    })
                    ->limit(1),
                $order,
            );
        }

        return $this;
    }

    private function getJoinKeys(Relation $relation): array
    {
        if ($relation instanceof BelongsTo) {
            return [
                $relation->getQualifiedForeignKeyName(),
                $relation->getOwnerKeyName(),
                null,
            ];
        }
        if ($relation instanceof MorphOneOrMany) {
            return [
                $relation->getQualifiedParentKeyName(),
                $relation->getQualifiedForeignKeyName(),
                $relation->getMorphType(),
            ];
        }
        if ($relation instanceof HasOneOrMany) {
            return [
                $relation->getQualifiedParentKeyName(),
                $relation->getQualifiedForeignKeyName(),
                null,
            ];
        }
        throw new Exception(
            'Relation type ' . get_class($relation) . ' is not supported',
        );
    }

    private function applyWith($query)
    {
        $requested_with = $this->resource_context->with();

        if ($this->allowed_with === self::WITH_ALLOW_ALL) {
            $with = $requested_with;
        } else {
            $with = array_intersect($this->allowed_with, $requested_with);
        }

        $with = array_unique(array_merge($with, $this->default_with));

        if (!empty($with)) {
            $query->with($with);
        }

        return $this;
    }

    private function applyFilters($query, callable $handler = null)
    {
        $filters = collect($this->resource_context->filters())
            ->filter()
            ->all();
        foreach ($filters as $filter => $value) {
            if (!$handler || $handler($query, $filter, $value) !== true) {
                $this->applyFiltersRecursively($query, $filter, $value, true);
            }
        }

        return $this;
    }

    /**
     * Let user filter by nested relations
     *
     * @param string|array $path
     * @param mixed $value
     */
    private function applyFiltersRecursively(
        $query,
        $path,
        $value,
        $initial = false
    ) {
        $original = $path;

        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $multi_column = strpos($original, '|') !== false;

        if ($initial && $multi_column) {
            $columns = explode('|', $original);
            $query->whereLike($columns, $value);
        } elseif (count($path) === 1) {
            $key = $path[0];
            $this->buildWhereLike($query, $key, $value);
        } elseif (count($path) === 2) {
            $relation_name = Str::camel($path[0]);
            $key = $path[1];
            $query->whereHas($relation_name, function ($query) use (
                $key,
                $value
            ) {
                $this->buildWhereLike($query, $key, $value);
            });
        } elseif (count($path) > 2) {
            $relation_name = Str::camel(array_shift($path));
            $query->whereHas($relation_name, function ($query) use (
                $path,
                $value
            ) {
                $this->applyFilters($query, $path, $value);
            });
        }
    }

    private function buildWhereLike($query, $key, $value)
    {
        $has_concat = strpos($key, '+') !== false;
        if ($has_concat) {
            $column_list = str_replace('+', ',', $key);
            $raw = "CONCAT_WS(' ', {$column_list}) LIKE '%{$value}%'";
            $query->whereRaw($raw);
        } else {
            $query->where($key, 'LIKE', "%${value}%");
        }
    }
}
