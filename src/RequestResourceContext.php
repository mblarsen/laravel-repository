<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RequestResourceContext implements ResourceContext
{
    /** @var Request */
    protected $request;

    public function __construct()
    {
        /** @var Request */
        $this->request = request();
    }

    public function filters(): array
    {
        return $this->request->get('filters', []);
    }

    public function page(): int
    {
        return $this->request->has('page', 1);
    }

    public function perPage(): int
    {
        return $this->request->has('per_page', 15);
    }

    public function paginate(): bool
    {
        return $this->request->has('page');
    }

    public function sortBy(): array
    {
        return [
            $this->request->get('sort_by', null),
            $this->request->get('sort_order', null),
        ];
    }

    public function user(): ?Model
    {
        return $this->request->user();
    }

    public function with(): array
    {
        return $this->request->has('with')
            ? Arr::wrap($this->request->get('with'))
            : [];
    }
}
