<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Events extends Resource
{
    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function create(array $event): array
    {
        return $this->request('POST', '/events', $event);
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function createMany(array $events): array
    {
        return $this->request('POST', '/events', array_values($events));
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function list(array $query = []): array
    {
        return $this->request('GET', '/events', query: $query);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $eventId): array
    {
        return $this->request('GET', '/events/' . $this->encodePath($eventId));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $eventId): array
    {
        return $this->request('DELETE', '/events/' . $this->encodePath($eventId));
    }

    /**
     * @param array<int, string> $ids
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function deleteMany(array $ids): array
    {
        return $this->request('POST', '/events/delete', ['ids' => array_values($ids)]);
    }
}
