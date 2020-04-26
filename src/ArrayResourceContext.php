<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final class ArrayResourceContext implements ResourceContext
{
    /** @var array */
    protected $context;

    public static function create(array $context = []): self
    {
        return new self($context);
    }

    public function __construct(array $context = [])
    {
        /** @var array */
        $this->context = $context;
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
        return (bool) $this->get('page', false);
    }

    public function sortBy(): array
    {
        return [
            'sort_by' => $this->get('sort_by', null),
            'sort_order' => $this->get('sort_order', null),
        ];
    }

    public function with(): array
    {
        return $this->get('with', []);
    }

    public function user(): ?Model
    {
        return $this->get('user');
    }

    protected function get($key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }
}
