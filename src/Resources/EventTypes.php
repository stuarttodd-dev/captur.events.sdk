<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class EventTypes extends Resource
{
    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function list(): array
    {
        return $this->request('GET', '/event-types');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function create(array $payload): array
    {
        return $this->request('POST', '/event-types', $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $eventType): array
    {
        return $this->request('GET', '/event-types/' . $this->encodePath($eventType));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function update(string $eventType, array $payload): array
    {
        return $this->request('PATCH', '/event-types/' . $this->encodePath($eventType), $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $eventType): array
    {
        return $this->request('DELETE', '/event-types/' . $this->encodePath($eventType));
    }
}
