<?php

namespace Mblarsen\LaravelRepository\Tests\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserResourceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}

