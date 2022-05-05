<?php

namespace Ne\Api;

use Exception;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2022, Perfacilis
 */
class Client
{
    /*
     * Request methods
     */
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const DELETE = 'DELETE';

    /**
     * Endpoints
     */
    public const BASE_URL = 'http://orders.ne.localhost/api/v1';
    public const ENDPOINT_AUTH = 'auth';
    public const ENDPOINT_PING = 'ping';
    public const ENDPOINT_ORDERS = 'orders';

    public function __construct(string $jwt)
    {
        $this->token = $jwt;
    }

    public function get(string $endpoint, array $fields): array
    {
        return $this->request(self::GET, $endpoint, $fields);
    }

    public function post(string $endpoint, array $fields): array
    {
        return $this->request(self::POST, $endpoint, $fields);
    }

    public function patch(string $endpoint, array $fields): array
    {
        return $this->request(self::PATCH, $endpoint, $fields);
    }

    public function delete(string $endpoint, array $fields): array
    {
        return $this->request(self::DELETE, $endpoint, $fields);
    }

    /**
     * Make a request
     *
     * @param string $method self::GET, self::POST, etc.
     * @param string $endpoint E.g. /api/v1/orders, full hostname will be added.
     * @param array $fields Fields to be posted
     * @param array $headers Flat array like ["Signature: 123456abcdef12346"]
     * @return array
     */
    public function request(string $method, string $endpoint, array $fields, array $headers = []): array
    {
        // Build URL for GET request
        $url = $this->getUrl($endpoint, $method === self::GET ? $fields : []);

        // Add headers, though enforce JSON
        $headers = array_merge($headers, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        // Add JWT, if set
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);

        // https://php.watch/articles/php-curl-security-hardening
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
        }

        // Add fields for non-POST requests, as JSON
        if ($method !== self::GET) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->getPayload($fields));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        $json = curl_exec($curl);
        $error = curl_error($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error) {
            throw new Exception(sprintf(
                'Error from %s: %s',
                $url,
                $error
            ));
        }

        if ($status_code < 200 || $status_code > 299) {
            throw new Exception(sprintf(
                'Error: HTTP status %d from %s: %s',
                $status_code,
                $url,
                $json
            ), $status_code);
        }

        if (!$json) {
            throw new Exception(sprintf(
                'Error: Empty result from %s.',
                $url
            ));
        }

        $output = json_decode($json, true);
        if (!$output) {
            throw new Exception(sprintf(
                "Error: Invalid JSON from %s: %s\n%s\n",
                $url,
                json_last_error_msg(),
                $json
            ));
        }

        return $output;
    }

    /**
     * Auth token in use to make requests
     */
    private $token = '';

    /**
     * Build full URL from endpoint.
     * This enforces URL to start with BASE_URL so request data doesn't leak
     * elsewhere.
     *
     * @param string $endpoint E.g. /api/v1/orders
     * @param array $fields Leave empty for non get requests
     */
    private function getUrl(string $endpoint, array $fields = [])
    {
        $url = self::BASE_URL . '/' . trim($endpoint, '/');

        if ($fields) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= http_build_query($fields);
        }

        return $url;
    }

    /**
     * JSON encode fields to payload using JWT standard.
     *
     * @param array $fields
     * @return string
     */
    private function getPayload(array $fields): string
    {
        return json_encode($fields, JSON_UNESCAPED_SLASHES);
    }
}
