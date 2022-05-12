# NE DistriService API Client

Basic PHP example code to get you started with NE DistriService's API.
You'll need an account to use this API.

See https://orders.ne.nl/apidoc/ for instructions on how to get set up.
The documentation page has code examples too.

Use our Contact form to get an account or request access:
https://www.ne.nl/contact/

## Installation

```bash
composer require ne/api
```

## Basic usage

```php
require 'vendor/autoload.php';

use Ne\Api\TokenHelper;
use Ne\Api\Client;

// Private key, copied from API management page
$private_key = '-----BEGIN PRIVATE KEY-----
[...]
-----END PRIVATE KEY-----';

// Generate JWT
$helper = new TokenHelper('your@email.addr', $private_key);
$token = $helper->getToken('nonce123');

// Make request using JWT
$client = new Client($token);
$resp = $client->get(Client::ENDPOINT_PING, []);

var_dump($resp);
```
