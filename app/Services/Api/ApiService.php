<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

/**
 * Generic base class for consuming external HTTP APIs.
 * All external API services should extend this class.
 */
abstract class ApiService
{
    protected string $baseUrl;
    protected array $defaultParams = [];
    protected int $timeout = 30;

    /**
     * Perform a GET request to the API.
     */
    protected function get(string $endpoint, array $params = []): Response
    {
        $allParams = array_merge($this->defaultParams, $params);

        return Http::timeout($this->timeout)
            ->get($this->baseUrl . $endpoint, $allParams);
    }

    /**
     * Perform a POST request to the API.
     */
    protected function post(string $endpoint, array $data = []): Response
    {
        return Http::timeout($this->timeout)
            ->post($this->baseUrl . $endpoint, $data);
    }
}
