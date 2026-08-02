<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Alerts extends Resource
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function list(array $query = []): array
    {
        return $this->request('GET', '/alerts', query: $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function create(array $payload): array
    {
        return $this->request('POST', '/alerts', $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $alertId): array
    {
        return $this->request('GET', '/alerts/' . $this->encodePath($alertId));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function update(string $alertId, array $payload): array
    {
        return $this->request('PUT', '/alerts/' . $this->encodePath($alertId), $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $alertId): array
    {
        return $this->request('DELETE', '/alerts/' . $this->encodePath($alertId));
    }
}
