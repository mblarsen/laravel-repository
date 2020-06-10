<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;

interface ResourceContext
{
    /**
     * An associative array where keys are properties and values are queries.
     */
    public function filters(): array;

    /**
     * Get the current page
     */
    public function page(): int;

    /**
     * Determine whether a full or paginated query is requested.
     */
    public function paginate(): bool;

    /**
     * Number of entities per page.
     */
    public function perPage(): int;

    /**
     * A tuple withe [column, direction], eg. [created_at, desc]
     *
     * @return [string,'asc'|'desc'|null]
     */
    public function sortBy(): array;

    /**
     * Convert the context to an array. Optionally pass in a guard name to
     * specify where to get the user from.
     *
     * @param string|null $guard The name of a guard. E.g. 'api'.
     * @return array<string,mixed>
     */
    public function toArray($guard = null): array;

    /**
     * Get the current user if any. Optionally pass in a guard name to
     * specify where to get the user from.
     *
     * @param string|null $guard The name of a guard. E.g. 'api'.
     */
    public function user($guard = null): ?Model;

    /**
     * An array of relationships to include in the query.
     */
    public function with(): array;
}
