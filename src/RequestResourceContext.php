<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RequestResourceContext implements ResourceContext
{
    /** @var Request */
    protected $request;

    /** @var array */
    protected $keys = [
        'filters' => 'filters',
        'page' => 'page',
        'per_page' => 'per_page',
        'sort_by' => 'sort_by',
        'sort_order' => 'sort_order',
        'with' => 'with',
    ];

    public function __construct()
    {
        $this->request = request();
    }

    public function filters(): array
    {
        return $this->get('filters', []);
    }

    public function page(): int
    {
        return $this->get('page', 1);
    }

    public function perPage(): int
    {
        return $this->get('per_page', 15);
    }

    public function paginate(): bool
    {
        return $this->has('page');
    }

    public function sortBy(): array
    {
        return [
            $this->get('sort_by', null),
            $this->get('sort_order', null),
        ];
    }

    public function user(): ?Model
    {
        return $this->request->user();
    }

    public function with(): array
    {
        return $this->has('with')
            ? Arr::wrap($this->get('with'))
            : [];
    }

    public function mapKeys(array $keys)
    {
        $this->keys = array_merge($this->keys, $keys);

        return $this;
    }

    /**
     * Convert the request context to an array
     */
    public function toArray(): array
    {
        [$sort_by, $sort_order] = $this->sortBy();
        return [
            'filters' => $this->filters(),
            'page' => $this->get('page'),
            'paginate' => $this->paginate(),
            'per_page' => $this->get('per_page'),
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'user' => $this->user(),
            'with' => $this->with(),
        ];
    }

    protected function has($key)
    {
        return $this->request->has($this->keys[$key]);
    }

    protected function get($key, $default = null)
    {
        return $this->request->get($this->keys[$key], $default);
    }
}
