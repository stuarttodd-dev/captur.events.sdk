<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Streams extends Resource
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $stream, array $query = []): array
    {
        return $this->request('GET', '/streams/' . $this->encodePath($stream), query: $query);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $stream): array
    {
        return $this->request('DELETE', '/streams/' . $this->encodePath($stream));
    }
}
