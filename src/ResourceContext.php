<?php

namespace Mblarsen\LaravelRepository;

use Illuminate\Database\Eloquent\Model;

interface ResourceContext
{
    public function filters(): array;
    public function page(): int;
    public function paginate(): bool;
    public function perPage(): int;
    public function sortBy(): array;
    public function toArray(): array;
    public function user(): ?Model;
    public function with(): array;
}
