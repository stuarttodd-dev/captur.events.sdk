<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Reports extends Resource
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function sessions(array $query): array
    {
        return $this->request('GET', '/reports/sessions', query: $query);
    }
}
