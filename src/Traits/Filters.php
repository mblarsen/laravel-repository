<?php

namespace Mblarsen\LaravelRepository\Traits;

use Illuminate\Support\Str;

trait Filters
{
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

        $multi_column = is_string($original) && strpos($original, '|') !== false;

        if ($initial && $multi_column) {
            $columns = explode('|', $original);
            $this->orWhereMany($query, $columns, $value);
        } elseif (count($path) === 1) {
            $key = $path[0];
            $this->whereLike($query, $key, $value);
        } elseif (count($path) === 2) {
            $relation_name = Str::camel($path[0]);
            $key = $path[1];
            $query->whereHas($relation_name, function ($query) use (
                $key,
                $value
            ) {
                $this->whereLike($query, $key, $value);
            });
        } elseif (count($path) > 2) {
            $relation_name = Str::camel(array_shift($path));
            $query->whereHas($relation_name, function ($query) use (
                $path,
                $value
            ) {
                $this->applyFiltersRecursively($query, $path, $value);
            });
        }
    }

    private function orWhereMany($query, $columns, $value)
    {
        $query->where(function ($query) use ($columns, $value) {
            foreach ($columns as $key) {
                $query->orWhere(function ($query) use ($key, $value) {
                    $this->applyFiltersRecursively(
                        $query,
                        $key,
                        $value,
                    );
                });
            }
        });
    }

    private function whereLike($query, $key, $value, $method = 'where')
    {
        $has_concat = strpos($key, '+') !== false;
        if ($has_concat) {
            $column_list = str_replace('+', ',', $key);
            $raw = "CONCAT_WS(' ', {$column_list}) LIKE '%{$value}%'";
            $raw_method = $method . 'Raw';
            $query->$raw_method($raw);
        } else {
            $query->$method($key, 'LIKE', "%${value}%");
        }
    }
}

