<?php

declare(strict_types=1);

namespace Captur;

use Captur\Http\Transporter;
use Captur\Resources\Alerts;
use Captur\Resources\Events;
use Captur\Resources\EventTypes;
use Captur\Resources\Projections;
use Captur\Resources\Reports;
use Captur\Resources\Streams;
use Captur\Resources\Webhooks;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    private const string DEFAULT_BASE_URL = 'https://captur.events';

    private const string DEFAULT_VERSION = 'v1';

    private readonly Transporter $transporter;

    public function __construct(
        string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        string $version = self::DEFAULT_VERSION,
        ?GuzzleClient $http = null,
    ) {
        $this->transporter = new Transporter(
            http: $http ?? new GuzzleClient(),
            apiKey: $apiKey,
            baseUrl: $this->resolveBaseUrl($baseUrl, $version),
        );
    }

    public function eventTypes(): EventTypes
    {
        return new EventTypes($this->transporter);
    }

    public function events(): Events
    {
        return new Events($this->transporter);
    }

    public function streams(): Streams
    {
        return new Streams($this->transporter);
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks($this->transporter);
    }

    public function alerts(): Alerts
    {
        return new Alerts($this->transporter);
    }

    public function projections(): Projections
    {
        return new Projections($this->transporter);
    }

    public function reports(): Reports
    {
        return new Reports($this->transporter);
    }

    private function resolveBaseUrl(string $baseUrl, string $version): string
    {
        return rtrim($baseUrl, '/') . '/' . trim($version, '/');
    }
}
