<?php

declare(strict_types=1);

namespace Captur\Http;

use Captur\Exceptions\ApiException;
use Captur\Exceptions\AuthenticationException;
use Captur\Exceptions\AuthorizationException;
use Captur\Exceptions\CapturException;
use Captur\Exceptions\ConflictException;
use Captur\Exceptions\NotFoundException;
use Captur\Exceptions\RateLimitException;
use Captur\Exceptions\ValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class Transporter
{
    public function __construct(
        private readonly GuzzleClient $http,
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @param array<mixed>|null $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
    ): array {
        $options = [
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($body !== null) {
            try {
                $options['body'] = json_encode($body, JSON_THROW_ON_ERROR);
            } catch (JsonException $jsonException) {
                throw new ApiException(
                    message: 'Failed to encode request body as JSON.',
                    previous: $jsonException,
                );
            }
        }

        try {
            $response = $this->http->request(
                $method,
                $this->url($path),
                $options,
            );
        } catch (GuzzleException $guzzleException) {
            throw new ApiException(
                message: $guzzleException->getMessage(),
                previous: $guzzleException,
            );
        }

        return $this->decode($response);
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CapturException
     */
    private function decode(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status >= 400) {
            throw $this->toException($response);
        }

        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new ApiException(
                message: 'Failed to decode API response as JSON.',
                status: $status,
                previous: $jsonException,
            );
        }

        if (! is_array($decoded)) {
            throw new ApiException(
                message: 'Unexpected API response shape.',
                status: $status,
            );
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @throws CapturException
     */
    private function toException(ResponseInterface $response): CapturException
    {
        $status = $response->getStatusCode();
        $parsed = $this->parseErrorBody((string) $response->getBody());

        $args = [
            'message' => $parsed['message'],
            'errorCode' => $parsed['code'],
            'errorType' => $parsed['type'],
            'parameter' => $parsed['parameter'],
            'status' => $status,
        ];

        return match ($status) {
            401 => new AuthenticationException(...$args),
            403 => new AuthorizationException(...$args),
            404 => new NotFoundException(...$args),
            409 => new ConflictException(...$args),
            422 => new ValidationException(...$args),
            429 => new RateLimitException(...$args),
            default => new ApiException(...$args),
        };
    }

    /**
     * @return array{message: string, code: ?string, type: ?string, parameter: ?string}
     */
    private function parseErrorBody(string $raw): array
    {
        $defaults = [
            'message' => 'An unexpected API error occurred.',
            'code' => null,
            'type' => null,
            'parameter' => null,
        ];

        $error = $this->decodeErrorObject($raw);
        if ($error === null) {
            if ($raw !== '') {
                $defaults['message'] = $raw;
            }

            return $defaults;
        }

        return [
            'message' => $this->stringField($error, 'message') ?? $defaults['message'],
            'code' => $this->stringField($error, 'code'),
            'type' => $this->stringField($error, 'type'),
            'parameter' => $this->stringField($error, 'parameter'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeErrorObject(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['error']) || ! is_array($payload['error'])) {
            return null;
        }

        /** @var array<string, mixed> $error */
        $error = $payload['error'];

        return $error;
    }

    /**
     * @param array<string, mixed> $error
     */
    private function stringField(array $error, string $key): ?string
    {
        $value = $error[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
