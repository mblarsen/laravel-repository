<?php

namespace Mblarsen\LaravelRepository\Traits;

use Exception;
use Illuminate\Database\Eloquent\Model;

trait WrapsInResource
{
    /** @var string */
    protected $resource;

    /** @var string */
    protected $resource_collection;

    public function setResource(string $resource, string $resource_collection = null)
    {
        $this->resource = $resource;
        $this->resource_collection = $resource_collection;
    }

    public function wrapInResource($value)
    {
        if (!$this->resource) {
            throw new Exception("You must first set a resource class");
        }

        if ($value instanceof Model) {
            $resource_class = $this->resource;
            return $resource_class::make($value);
        }

        if ($this->resource_collection) {
            $resource_class = $this->resource_collection;
            return $resource_class::make($value);
        }

        $resource_class = $this->resource;

        return $resource_class::collection($value);
    }
}
