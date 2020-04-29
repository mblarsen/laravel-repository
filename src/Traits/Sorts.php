<?php

namespace Mblarsen\LaravelRepository\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Sorts
{
    /** @var string $default_sort_by */
    protected $default_sort_by;

    /** @var string $default_sort_order */
    protected $default_sort_order = 'asc';

    public function setDefaultSort(string $by, string $order = 'asc')
    {
        $this->default_sort_by = $by;
        $this->default_sort_order = $order;

        return $this;
    }

    private function applySort($query)
    {
        [$by, $order] = $this->resource_context->sortBy();

        $by = $by ?: $this->default_sort_by;
        $order = $order ?: $this->default_sort_order;

        if (!$by) {
            return $this;
        }

        if (strpos($by, '.') === false) {
            $query->orderBy($by, $order);
        } else {
            $this->applyRelationSort($query, $by, $order);
        }

        return $this;
    }

    private function applyRelationSort($query, $by, $order)
    {
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

    private function getJoinKeys(Relation $relation): array
    {
        if ($relation instanceof BelongsTo) {
            return [
                $relation->getQualifiedForeignKeyName(),
                $relation->getQualifiedOwnerKeyName(),
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
            'Relation type ' . class_basename($relation) . ' is not supported',
        );
    }
}
