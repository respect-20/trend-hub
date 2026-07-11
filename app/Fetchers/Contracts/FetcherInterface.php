<?php

namespace App\Fetchers\Contracts;

use App\Fetchers\NormalizedPost;

interface FetcherInterface
{
    public function source(): string;

    /**
     * @return NormalizedPost[]
     */
    public function fetch(): array;
}
