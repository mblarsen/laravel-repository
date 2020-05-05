<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

final class ArrayResourceContext implements ResourceContext
{
    /** @var array $context_keys */
    protected static $context_keys = [
        'filters',
        'page',
        'per_page',
        'sort_by',
        'sort_order',
        'user',
        'with',
    ];

    /** @var array */
    protected $context;

    public static function create(array $context = []): self
    {
        return new self($context);
    }

    public function __construct(array $context = [])
    {
        $context = $this->validateContext($context);

        /** @var array */
        $this->context = $context;
    }

    public function validateContext(array $context): array
    {
        $valid_keys = self::$context_keys;
        return Arr::only(
            $context,
            array_intersect(
                $valid_keys,
                array_keys($context)
            )
        );
    }

    public function validateKey(string $key): string
    {
        [$head] = $this->splitKey($key);

        if (!in_array($head, self::$context_keys)) {
            throw new InvalidArgumentException("Invalid key '$head'");
        }

        return $key;
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
            $this->get('sort_by', null),
            $this->get('sort_order', 'asc'),
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

    public function merge(array $values)
    {
        $values = $this->validateContext($values);

        $this->context = array_merge_recursive($this->context, $values);

        return $this;
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);

        return data_get($this->context, $key, $default);
    }

    public function set(string $key, $value)
    {
        $this->validateKey($key);

        [$head, $tail] = $this->splitKey($key);

        if ($tail) {
            $this->context[$head][$tail] = $value;
        } else {
            $this->context[$head] = $value;
        }


        return $this;
    }

    public function exclude(array $keys)
    {
        $this->context = Arr::except($this->context, $keys);

        return $this;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->context,
            [
                'paginate' => $this->paginate(),
                'user' => $this->user()
            ]
        );
    }

    private function splitKey($key)
    {
        $dos_position = strpos($key, '.');
        $head = $dos_position !== false
            ? substr($key, 0, $dos_position)
            : $key;
        $tail = $dos_position !== false
            ? substr($key, $dos_position + 1)
            : null;
        return [$head, $tail];
    }
}
