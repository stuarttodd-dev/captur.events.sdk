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
    'limit' => 50,
]);

$client->events()->get('01KYY7S1ZGJ1DF8XG4X1RFHQ1S');
$client->events()->delete('01KYY7S1ZGJ1DF8XG4X1RFHQ1S');
$client->events()->deleteMany(['01A…', '01B…']);
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

## Streams

```php
$client->streams()->get('vehicle:AB12XYZ');
$client->streams()->delete('vehicle:AB12XYZ');
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

## Webhooks, alerts, projections

```php
$client->webhooks()->create([
    'url' => 'https://example.com/hooks/captur',
    'event_types' => ['vehicle.entered'],
]);

$client->alerts()->list();
$client->projections()->list();
```

Test inbox helpers: `testInbox()`, `upsertTestInbox()`, `deleteTestInbox()`, `sendTestInboxSample()`.

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
