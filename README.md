# captur.events PHP SDK

Official-style PHP client for the [captur.events](https://captur.events) event ingestion API.

**Base URL:** `https://captur.events` · **Default version:** `v1`  
**Docs:** [Introduction](https://captur.events/docs/introduction) · [OpenAPI](https://captur.events/developers/openapi.json)

## Install

```bash
composer require captur/events
```

Requires PHP 8.3+.

## Authentication

Create a project in the dashboard and copy a **test** (`evt_test_…`) or **live** (`evt_live_…`) API key.

```bash
export CAPTUR_API_KEY=evt_test_...
```

Every request sends `Authorization: Bearer <key>`.

## Quick start

```php
use Captur\Client;

$client = new Client(apiKey: getenv('CAPTUR_API_KEY'));

// 1. Register an event type (once per project)
$client->eventTypes()->create([
    'name' => 'vehicle.entered',
    'description' => 'Vehicle entered a car park',
]);

// 2. Ingest an event
$event = $client->events()->create([
    'type' => 'vehicle.entered',
    'stream' => 'vehicle:AB12XYZ',
    'data' => [
        'car_park_id' => 'teeside-central',
        'barrier' => 'north',
    ],
]);

echo $event['id'];
```

Optional client options:

```php
$client = new Client(
    apiKey: getenv('CAPTUR_API_KEY'),
    baseUrl: 'https://captur.events', // default host
    version: 'v1',                    // default API version
);
```

## Events

### Single ingest

```php
$event = $client->events()->create([
    'type' => 'vehicle.entered',
    'stream' => 'vehicle:AB12XYZ',
    'occurred_at' => '2026-08-01T08:45:00Z',
    'idempotency_key' => 'entry-AB12XYZ-2026-08-01T08:45',
    'data' => ['car_park_id' => 'teeside-central'],
]);
```

### Batch ingest

`POST` a JSON array (max 500). Response shape: `{ "events": [ … ] }`.

```php
$result = $client->events()->createMany([
    ['type' => 'vehicle.entered', 'stream' => 'vehicle:AB12XYZ', 'data' => []],
    ['type' => 'vehicle.exited', 'stream' => 'vehicle:AB12XYZ', 'data' => []],
]);
```

### List, get, delete

```php
$client->events()->list([
    'type' => 'vehicle.entered',
    'stream' => 'vehicle:AB12XYZ',
    'from' => '2026-08-01T00:00:00Z',
    'to' => '2026-08-02T00:00:00Z',
]);

$client->events()->get('01KYY7S1ZGJ1DF8XG4X1RFHQ1S');
$client->events()->delete('01KYY7S1ZGJ1DF8XG4X1RFHQ1S');
$client->events()->deleteMany(['01A…', '01B…']);
```

### Pagination (list events)

Use **either** offset (`page`) **or** cursor (`cursor` / `starting_after`) — not both.

**Offset pagination** (`page` + `limit`):

```php
$page = 1;

do {
    $result = $client->events()->list([
        'type' => 'vehicle.entered',
        'limit' => 100,
        'page' => $page,
    ]);

    foreach ($result['data'] as $event) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor pagination** (preferred for large histories / continuous sync):

```php
$cursor = null;

do {
    $query = ['limit' => 100];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->events()->list($query);

    foreach ($result['data'] as $event) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

Or continue after a known event id:

```php
$result = $client->events()->list([
    'limit' => 100,
    'starting_after' => '01J9X2K8M3N4P5Q6R7S8T9V0W1',
]);
```

## Event types

```php
$client->eventTypes()->list();
$client->eventTypes()->get('vehicle.entered');
$client->eventTypes()->update('vehicle.entered', [
    'description' => 'Updated description',
]);
$client->eventTypes()->delete('vehicle.entered');
```

### Pagination (list event types)

**Offset:**

```php
$page = 1;

do {
    $result = $client->eventTypes()->list([
        'limit' => 50,
        'page' => $page,
    ]);

    foreach ($result['data'] as $eventType) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = ['limit' => 50];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->eventTypes()->list($query);

    foreach ($result['data'] as $eventType) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

## Streams

```php
$client->streams()->get('vehicle:AB12XYZ');
$client->streams()->delete('vehicle:AB12XYZ');
```

### Pagination (read stream)

Stream reads return events under `events` (not `data`).

**Offset:**

```php
$page = 1;

do {
    $result = $client->streams()->get('vehicle:AB12XYZ', [
        'limit' => 100,
        'page' => $page,
    ]);

    foreach ($result['events'] as $event) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = ['limit' => 100];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->streams()->get('vehicle:AB12XYZ', $query);

    foreach ($result['events'] as $event) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

Or continue after a known event id:

```php
$result = $client->streams()->get('vehicle:AB12XYZ', [
    'limit' => 100,
    'starting_after' => '01J9X2K8M3N4P5Q6R7S8T9V0W1',
]);
```

## Session reports

```php
$client->reports()->sessions([
    'start_type' => 'session.started',
    'end_type' => 'session.ended',
    'from' => '2026-08-01T00:00:00Z',
    'to' => '2026-08-02T00:00:00Z',
]);
```

### Pagination (session reports)

**Offset:**

```php
$page = 1;

do {
    $result = $client->reports()->sessions([
        'start_type' => 'session.started',
        'end_type' => 'session.ended',
        'from' => '2026-08-01T00:00:00Z',
        'to' => '2026-08-02T00:00:00Z',
        'limit' => 50,
        'page' => $page,
    ]);

    foreach ($result['data'] as $session) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = [
        'start_type' => 'session.started',
        'end_type' => 'session.ended',
        'from' => '2026-08-01T00:00:00Z',
        'to' => '2026-08-02T00:00:00Z',
        'limit' => 50,
    ];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->reports()->sessions($query);

    foreach ($result['data'] as $session) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

## Webhooks

```php
$client->webhooks()->create([
    'url' => 'https://example.com/hooks/captur',
    'event_types' => ['vehicle.entered'],
]);

$client->webhooks()->list();
$client->webhooks()->get('wh_…');
$client->webhooks()->update('wh_…', ['enabled' => false]);
$client->webhooks()->delete('wh_…');
```

Test inbox helpers: `testInbox()`, `upsertTestInbox()`, `deleteTestInbox()`, `sendTestInboxSample()`.

### Pagination (list webhooks)

**Offset:**

```php
$page = 1;

do {
    $result = $client->webhooks()->list([
        'limit' => 50,
        'page' => $page,
    ]);

    foreach ($result['data'] as $webhook) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = ['limit' => 50];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->webhooks()->list($query);

    foreach ($result['data'] as $webhook) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

## Alerts

```php
$client->alerts()->list();
$client->alerts()->create(['name' => 'High dwell']);
$client->alerts()->get('al_…');
$client->alerts()->update('al_…', ['name' => 'Updated']);
$client->alerts()->delete('al_…');
```

### Pagination (list alerts)

**Offset:**

```php
$page = 1;

do {
    $result = $client->alerts()->list([
        'limit' => 50,
        'page' => $page,
    ]);

    foreach ($result['data'] as $alert) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = ['limit' => 50];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->alerts()->list($query);

    foreach ($result['data'] as $alert) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

## Projections

```php
$client->projections()->list();
$client->projections()->create(['name' => 'occupancy']);
$client->projections()->get('pr_…');
$client->projections()->update('pr_…', ['name' => 'Updated']);
$client->projections()->delete('pr_…');
$client->projections()->replay('pr_…');
```

### Pagination (list projections)

**Offset:**

```php
$page = 1;

do {
    $result = $client->projections()->list([
        'limit' => 50,
        'page' => $page,
    ]);

    foreach ($result['data'] as $projection) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $page++;
} while ($hasMore);
```

**Cursor:**

```php
$cursor = null;

do {
    $query = ['limit' => 50];
    if ($cursor !== null) {
        $query['cursor'] = $cursor;
    }

    $result = $client->projections()->list($query);

    foreach ($result['data'] as $projection) {
        // …
    }

    $hasMore = $result['meta']['has_more'] ?? false;
    $cursor = $result['meta']['next_cursor'] ?? null;
} while ($hasMore && $cursor !== null);
```

## Verify webhook signatures

captur signs deliveries with HMAC-SHA256 over the **exact raw body** (`X-Captur-Signature`).

```php
use Captur\Webhook;

$payload = $request->getContent(); // raw body — required
$signature = $request->header('X-Captur-Signature');
$secret = getenv('CAPTUR_WEBHOOK_SECRET');

if (! Webhook::verify($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
```

## Errors

Non-2xx responses throw typed exceptions under `Captur\Exceptions\`:

| Status | Exception |
|--------|-----------|
| 401 | `AuthenticationException` |
| 403 | `AuthorizationException` |
| 404 | `NotFoundException` |
| 409 | `ConflictException` |
| 422 | `ValidationException` |
| 429 | `RateLimitException` |
| other | `ApiException` |

```php
use Captur\Exceptions\CapturException;

try {
    $client->events()->create([/* … */]);
} catch (CapturException $e) {
    echo $e->getMessage(); // human message
    echo $e->code();       // e.g. invalid_api_key
    echo $e->type();       // e.g. authentication_error
    echo $e->status();     // HTTP status
}
```

## Development

```bash
composer install
composer tests
composer test:coverage   # requires pcov or xdebug; enforces 100%
composer standards:check
```

Docker:

```bash
docker compose build
docker compose up -d
docker exec php-composer-package composer install
docker exec php-composer-package composer tests
```

CI runs on push/PR to `main`: standards check plus tests with a 100% coverage gate.

## Links

- [Product docs](https://captur.events/docs/introduction)
- [Send your first event](https://captur.events/docs/send-your-first-event)
- [Verify signatures](https://captur.events/docs/verify-signatures)
- [OpenAPI JSON](https://captur.events/developers/openapi.json)
