<?php

declare(strict_types=1);

namespace Captur\Resources;

use Captur\Exceptions\CapturException;

class Webhooks extends Resource
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function list(array $query = []): array
    {
        return $this->request('GET', '/webhooks', query: $query);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function create(array $payload): array
    {
        return $this->request('POST', '/webhooks', $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function get(string $webhookId): array
    {
        return $this->request('GET', '/webhooks/' . $this->encodePath($webhookId));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function update(string $webhookId, array $payload): array
    {
        return $this->request('PATCH', '/webhooks/' . $this->encodePath($webhookId), $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function delete(string $webhookId): array
    {
        return $this->request('DELETE', '/webhooks/' . $this->encodePath($webhookId));
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function testInbox(): array
    {
        return $this->request('GET', '/webhooks/test-inbox');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function upsertTestInbox(array $payload = []): array
    {
        return $this->request('PUT', '/webhooks/test-inbox', $payload);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function deleteTestInbox(): array
    {
        return $this->request('DELETE', '/webhooks/test-inbox');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function sendTestInboxSample(array $payload = []): array
    {
        return $this->request('POST', '/webhooks/test-inbox/sample', $payload);
    }
}
