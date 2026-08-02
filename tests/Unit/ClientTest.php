<?php

declare(strict_types=1);

use Captur\Client;
use Captur\Exceptions\ApiException;
use Captur\Exceptions\AuthenticationException;
use Captur\Exceptions\AuthorizationException;
use Captur\Exceptions\ConflictException;
use Captur\Exceptions\NotFoundException;
use Captur\Exceptions\RateLimitException;
use Captur\Exceptions\ValidationException;
use Captur\Webhook;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * @param list<Response|ConnectException> $responses
 * @param list<array<string, mixed>> $history
 */
function makeClient(array $responses, array &$history = []): Client
{
    $history = [];
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new Client(
        apiKey: 'evt_test_abc',
        baseUrl: 'https://captur.events',
        version: 'v1',
        http: new GuzzleClient(['handler' => $stack]),
    );
}

function errorResponse(int $status, array $error = [], string $raw = ''): Response
{
    if ($raw !== '') {
        return new Response($status, [], $raw);
    }

    return new Response($status, [], json_encode(['error' => $error], JSON_THROW_ON_ERROR));
}

it('constructs a client with the default http client', function (): void {
    $client = new Client(apiKey: 'evt_test_abc');

    expect($client->events())->toBeInstanceOf(Captur\Resources\Events::class);
});

it('uses a custom api version in the request url', function (): void {
    $history = [];
    $mock = new MockHandler([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $client = new Client(
        apiKey: 'evt_test_abc',
        baseUrl: 'https://captur.events/',
        version: '/v2/',
        http: new GuzzleClient(['handler' => $stack]),
    );

    $client->events()->list();

    expect((string) $history[0]['request']->getUri())
        ->toBe('https://captur.events/v2/events');
});

it('sends bearer auth and creates an event', function (): void {
    $history = [];
    $client = makeClient([
        new Response(202, [], json_encode([
            'id' => '01KYY7S1ZGJ1DF8XG4X1RFHQ1S',
            'type' => 'vehicle.entered',
            'stream' => 'vehicle:AB12XYZ',
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $event = $client->events()->create([
        'type' => 'vehicle.entered',
        'stream' => 'vehicle:AB12XYZ',
        'data' => ['car_park_id' => 'teeside-central'],
    ]);

    expect($event['id'])->toBe('01KYY7S1ZGJ1DF8XG4X1RFHQ1S')
        ->and($history)->toHaveCount(1);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://captur.events/v1/events')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer evt_test_abc')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'type' => 'vehicle.entered',
            'stream' => 'vehicle:AB12XYZ',
            'data' => ['car_park_id' => 'teeside-central'],
        ]);
});

it('posts a JSON array for batch createMany', function (): void {
    $history = [];
    $client = makeClient([
        new Response(202, [], json_encode([
            'events' => [
                ['id' => '01A'],
                ['id' => '01B'],
            ],
        ], JSON_THROW_ON_ERROR)),
    ], $history);

    $result = $client->events()->createMany([
        ['type' => 'vehicle.entered', 'stream' => 'vehicle:A'],
        ['type' => 'vehicle.exited', 'stream' => 'vehicle:A'],
    ]);

    expect($result['events'])->toHaveCount(2);

    $body = (string) $history[0]['request']->getBody();
    expect($body)->toStartWith('[')
        ->and(json_decode($body, true))->toHaveCount(2);
});

it('lists gets and deletes events', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => '01A'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => 2], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->events()->list([
        'type' => 'vehicle.entered',
        'stream' => 'vehicle:AB12XYZ',
        'limit' => 10,
    ]);
    $client->events()->get('01A');
    $client->events()->delete('01A');
    $client->events()->deleteMany(['01A', '01B']);

    expect($history[0]['request']->getUri()->getQuery())->toContain('type=vehicle.entered')
        ->and((string) $history[1]['request']->getUri())->toBe('https://captur.events/v1/events/01A')
        ->and($history[2]['request']->getMethod())->toBe('DELETE')
        ->and($history[3]['request']->getMethod())->toBe('POST')
        ->and((string) $history[3]['request']->getUri())->toBe('https://captur.events/v1/events/delete')
        ->and(json_decode((string) $history[3]['request']->getBody(), true))->toBe([
            'ids' => ['01A', '01B'],
        ]);
});

it('covers all event type endpoints', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(201, [], json_encode(['name' => 'vehicle.entered'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['name' => 'vehicle.entered'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['name' => 'vehicle.entered'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->eventTypes()->list();
    $client->eventTypes()->create([
        'name' => 'vehicle.entered',
        'description' => 'Vehicle entered a car park',
    ]);
    $client->eventTypes()->get('vehicle.entered');
    $client->eventTypes()->update('vehicle.entered', ['description' => 'Updated']);
    $client->eventTypes()->delete('vehicle.entered');

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[1]['request']->getMethod())->toBe('POST')
        ->and($history[2]['request']->getMethod())->toBe('GET')
        ->and($history[3]['request']->getMethod())->toBe('PATCH')
        ->and($history[4]['request']->getMethod())->toBe('DELETE')
        ->and((string) $history[2]['request']->getUri())
        ->toBe('https://captur.events/v1/event-types/vehicle.entered');
});

it('reads and deletes a stream', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->streams()->get('vehicle:AB12XYZ');
    $client->streams()->delete('vehicle:AB12XYZ');

    expect((string) $history[0]['request']->getUri())
        ->toBe('https://captur.events/v1/streams/vehicle%3AAB12XYZ')
        ->and($history[1]['request']->getMethod())->toBe('DELETE');
});

it('covers all webhook endpoints including test inbox', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(201, [], json_encode(['id' => 'wh_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'wh_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'wh_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['email' => 'test@example.com'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['email' => 'test@example.com'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['sent' => true], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->webhooks()->list();
    $client->webhooks()->create(['url' => 'https://example.com/hooks/captur']);
    $client->webhooks()->get('wh_1');
    $client->webhooks()->update('wh_1', ['enabled' => false]);
    $client->webhooks()->delete('wh_1');
    $client->webhooks()->testInbox();
    $client->webhooks()->upsertTestInbox(['email' => 'test@example.com']);
    $client->webhooks()->deleteTestInbox();
    $client->webhooks()->sendTestInboxSample();

    expect($history)->toHaveCount(9)
        ->and((string) $history[5]['request']->getUri())
        ->toBe('https://captur.events/v1/webhooks/test-inbox')
        ->and($history[6]['request']->getMethod())->toBe('PUT')
        ->and($history[8]['request']->getMethod())->toBe('POST');
});

it('covers all alert endpoints', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(201, [], json_encode(['id' => 'al_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'al_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'al_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->alerts()->list();
    $client->alerts()->create(['name' => 'High dwell']);
    $client->alerts()->get('al_1');
    $client->alerts()->update('al_1', ['name' => 'Updated']);
    $client->alerts()->delete('al_1');

    expect($history[3]['request']->getMethod())->toBe('PUT')
        ->and((string) $history[2]['request']->getUri())->toBe('https://captur.events/v1/alerts/al_1');
});

it('covers all projection endpoints including replay', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
        new Response(201, [], json_encode(['id' => 'pr_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'pr_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['id' => 'pr_1'], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['deleted' => true], JSON_THROW_ON_ERROR)),
        new Response(200, [], json_encode(['replayed' => true], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->projections()->list();
    $client->projections()->create(['name' => 'occupancy']);
    $client->projections()->get('pr_1');
    $client->projections()->update('pr_1', ['name' => 'Updated']);
    $client->projections()->delete('pr_1');
    $client->projections()->replay('pr_1');

    expect($history[3]['request']->getMethod())->toBe('PATCH')
        ->and((string) $history[5]['request']->getUri())
        ->toBe('https://captur.events/v1/projections/pr_1/replay');
});

it('fetches session reports', function (): void {
    $history = [];
    $client = makeClient([
        new Response(200, [], json_encode(['data' => []], JSON_THROW_ON_ERROR)),
    ], $history);

    $client->reports()->sessions([
        'start_type' => 'session.started',
        'end_type' => 'session.ended',
        'from' => '2026-08-01T00:00:00Z',
        'to' => '2026-08-02T00:00:00Z',
    ]);

    expect((string) $history[0]['request']->getUri())
        ->toStartWith('https://captur.events/v1/reports/sessions?');
});

it('returns an empty array for empty success bodies', function (): void {
    $client = makeClient([new Response(204)]);

    expect($client->events()->delete('01A'))->toBe([]);
});

it('maps authentication validation and rate limit errors', function (): void {
    $client = makeClient([
        errorResponse(401, [
            'type' => 'authentication_error',
            'code' => 'invalid_api_key',
            'message' => 'Invalid API key.',
        ]),
        errorResponse(422, [
            'type' => 'validation_error',
            'code' => 'unknown_event_type',
            'message' => 'Unknown event type.',
            'parameter' => 'type',
        ]),
        errorResponse(429, [
            'type' => 'rate_limit_error',
            'code' => 'monthly_quota_exceeded',
            'message' => 'Monthly quota exceeded.',
        ]),
    ]);

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (AuthenticationException $authenticationException) {
        expect($authenticationException->getMessage())->toBe('Invalid API key.')
            ->and($authenticationException->code())->toBe('invalid_api_key')
            ->and($authenticationException->type())->toBe('authentication_error')
            ->and($authenticationException->status())->toBe(401);
    }

    try {
        $client->events()->create(['type' => 'missing.type', 'stream' => 'x']);
        expect(false)->toBeTrue();
    } catch (ValidationException $validationException) {
        expect($validationException->code())->toBe('unknown_event_type')
            ->and($validationException->type())->toBe('validation_error')
            ->and($validationException->parameter())->toBe('type')
            ->and($validationException->status())->toBe(422);
    }

    try {
        $client->events()->create(['type' => 'vehicle.entered', 'stream' => 'x']);
        expect(false)->toBeTrue();
    } catch (RateLimitException $rateLimitException) {
        expect($rateLimitException->code())->toBe('monthly_quota_exceeded')
            ->and($rateLimitException->status())->toBe(429);
    }
});

it('maps authorization not found conflict and generic api errors', function (): void {
    $client = makeClient([
        errorResponse(403, [
            'type' => 'authorization_error',
            'code' => 'feature_not_available',
            'message' => 'Feature not available.',
        ]),
        errorResponse(404, [
            'type' => 'not_found',
            'code' => 'event_not_found',
            'message' => 'Event not found.',
        ]),
        errorResponse(409, [
            'type' => 'conflict_error',
            'code' => 'expected_version_mismatch',
            'message' => 'Expected version mismatch.',
        ]),
        errorResponse(500, [
            'type' => 'api_error',
            'code' => 'internal_error',
            'message' => 'Boom.',
        ]),
    ]);

    expect(fn (): array => $client->alerts()->list())->toThrow(AuthorizationException::class)
        ->and(fn (): array => $client->events()->get('missing'))->toThrow(NotFoundException::class)
        ->and(fn (): array => $client->events()->create(['type' => 'a.b', 'stream' => 's']))
        ->toThrow(ConflictException::class)
        ->and(fn (): array => $client->events()->list())->toThrow(ApiException::class);
});

it('handles malformed and incomplete error bodies', function (): void {
    $client = makeClient([
        new Response(500),
        errorResponse(500, raw: 'not-json'),
        new Response(500, [], json_encode(['ok' => true], JSON_THROW_ON_ERROR)),
        new Response(500, [], json_encode(['error' => 'string'], JSON_THROW_ON_ERROR)),
        new Response(500, [], json_encode([
            'error' => [
                'message' => 123,
                'code' => null,
                'type' => ['nested'],
                'parameter' => false,
            ],
        ], JSON_THROW_ON_ERROR)),
        new Response(500, [], json_encode([
            'error' => [],
        ], JSON_THROW_ON_ERROR)),
    ]);

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('An unexpected API error occurred.')
            ->and($apiException->code())->toBeNull()
            ->and($apiException->parameter())->toBeNull();
    }

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('not-json');
    }

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('{"ok":true}');
    }

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('{"error":"string"}');
    }

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('An unexpected API error occurred.')
            ->and($apiException->code())->toBeNull()
            ->and($apiException->type())->toBeNull()
            ->and($apiException->parameter())->toBeNull();
    }

    try {
        $client->events()->list();
        expect(false)->toBeTrue();
    } catch (ApiException $apiException) {
        expect($apiException->getMessage())->toBe('An unexpected API error occurred.');
    }
});

it('fails when the response body is invalid json or not an object', function (): void {
    $client = makeClient([
        new Response(200, [], '{'),
        new Response(200, [], '123'),
    ]);

    expect(fn (): array => $client->events()->list())->toThrow(ApiException::class, 'Failed to decode API response as JSON.')
        ->and(fn (): array => $client->events()->list())->toThrow(ApiException::class, 'Unexpected API response shape.');
});

it('wraps guzzle transport failures', function (): void {
    $client = makeClient([
        new ConnectException('Could not connect', new Request('GET', '/events')),
    ]);

    expect(fn (): array => $client->events()->list())
        ->toThrow(ApiException::class, 'Could not connect');
});

it('fails when the request body cannot be json encoded', function (): void {
    $client = makeClient([]);
    $resource = fopen('php://memory', 'r');

    expect(fn (): array => $client->events()->create(['bad' => $resource]))
        ->toThrow(ApiException::class, 'Failed to encode request body as JSON.');

    if (is_resource($resource)) {
        fclose($resource);
    }
});

it('verifies valid webhook signatures', function (): void {
    $payload = '{"id":"01ABC","type":"vehicle.entered"}';
    $secret = 'whsec_test_secret';
    $signature = hash_hmac('sha256', $payload, $secret);

    expect(Webhook::verify($payload, $signature, $secret))->toBeTrue();
});

it('rejects invalid or missing webhook signatures', function (): void {
    $payload = '{"id":"01ABC"}';
    $secret = 'whsec_test_secret';

    expect(Webhook::verify($payload, 'bad', $secret))->toBeFalse()
        ->and(Webhook::verify($payload, null, $secret))->toBeFalse()
        ->and(Webhook::verify($payload, '', $secret))->toBeFalse()
        ->and(Webhook::verify('', 'abc', $secret))->toBeFalse()
        ->and(Webhook::verify($payload, 'abc', ''))->toBeFalse();
});
