<?php

namespace Mblarsen\LaravelRepository\Traits;

trait IncludesRelations
{

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

    private function applyWith($query)
    {
        $requested_with = $this->resource_context->with();

        if ($this->allowed_with === ['*']) {
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
}
