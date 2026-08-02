<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;
use Captur\Http\Transporter;

abstract class Resource
{
    public function __construct(
        protected readonly Transporter $transporter,
    ) {
    }

    /**
     * @param array<mixed>|null $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    protected function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
    ): array {
        return $this->transporter->request($method, $path, $body, $query);
    }

    protected function encodePath(string $value): string
    {
        return rawurlencode($value);
    }
}
