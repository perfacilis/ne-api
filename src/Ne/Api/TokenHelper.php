<?php

namespace Ne\Api;

use Exception;

/**
 * @author Roy Arisse <support@perfacilis.com>
 * @copyright (c) 2022, Perfacilis
 */
class TokenHelper
{
    public function __construct(string $username, string $private_key)
    {
        $this->username = $username;
        $this->private_key = $private_key;
    }

    /**
     * Retrieve new or cached token for given unique identifier.
     *
     * @param string $identifier Unique identifier for this session
     * @param int $lifetime Optional, in seconds, default 1800 or 30 minutes
     * @return string Authentication token
     */
    public function getToken(string $identifier, int $lifetime = 1800): string
    {
        // Try getting tocken for local cache, if it's not expired
        $token = $this->token_cache[$identifier] ?? [];
        if ($token && $token['expires'] > time()) {
            return $token['token'];
        }

        $fields = [
            'login' => $this->username,
            'nonce' => $identifier,
            'lifetime' => $lifetime
        ];

        // Initialize API client without token, so we can get one
        $client = new Client('');
        $token = $client->request(Client::POST, Client::ENDPOINT_AUTH, $fields, [
            'Signature: ' . $this->getSignature($fields)
        ]);

        // Invalid token response
        if (!isset($token['token'])) {
            throw new Exception(sprintf(
                'Error: Invalid token response: %s',
                json_encode($token)
            ));
        }

        // Update cache for later use
        $this->token_cache[$identifier] = $token;

        return $token['token'];
    }

    /**
     * Cached tokens
     */
    private $token_cache = [];

    /**
     * Username
     */
    private $username = '';

    /**
     * Private key
     */
    private $private_key = '';

    private function getSignature(array $fields): string
    {
        $payload = json_encode($fields);
        $signature = '';

        openssl_sign(
            $payload,
            $signature,
            $this->private_key,
            OPENSSL_ALGO_SHA512
        );

        return base64_encode($signature);
    }
}
