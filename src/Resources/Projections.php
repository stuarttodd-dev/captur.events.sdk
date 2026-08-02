<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Projections extends Resource
{
    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function list(): array
    {
        return $this->request('GET', '/projections');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function create(array $payload): array
    {
        return $this->request('POST', '/projections', $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $projectionId): array
    {
        return $this->request('GET', '/projections/' . $this->encodePath($projectionId));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function update(string $projectionId, array $payload): array
    {
        return $this->request('PATCH', '/projections/' . $this->encodePath($projectionId), $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $projectionId): array
    {
        return $this->request('DELETE', '/projections/' . $this->encodePath($projectionId));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function replay(string $projectionId, array $payload = []): array
    {
        return $this->request(
            'POST',
            '/projections/' . $this->encodePath($projectionId) . '/replay',
            $payload,
        );
    }
}
