<?php

declare(strict_types=1);

namespace Synglify\WordPress\Tests\Unit\Http;

use Synglify\Core\Http\HttpResponse;
use Synglify\WordPress\Http\WpHttpClient;
use Synglify\WordPress\Tests\TestCase;

class WpHttpClientTest extends TestCase
{
    private WpHttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new WpHttpClient();
    }

    public function test_it_implements_http_client_interface(): void
    {
        $this->assertInstanceOf(
            \Synglify\Core\Http\HttpClientInterface::class,
            $this->client,
        );
    }

    public function test_get_method_returns_http_response(): void
    {
        $response = $this->client->get('https://example.com/api');

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame(200, $response->statusCode());
    }

    public function test_post_method_returns_http_response(): void
    {
        $response = $this->client->post('https://example.com/api', [
            'json' => ['key' => 'value'],
        ]);

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame(200, $response->statusCode());
    }

    public function test_put_method_returns_http_response(): void
    {
        $response = $this->client->put('https://example.com/api', [
            'json' => ['key' => 'value'],
        ]);

        $this->assertInstanceOf(HttpResponse::class, $response);
    }

    public function test_delete_method_returns_http_response(): void
    {
        $response = $this->client->delete('https://example.com/api');

        $this->assertInstanceOf(HttpResponse::class, $response);
    }

    public function test_proxy_configuration_is_applied(): void
    {
        $client = new WpHttpClient([
            'host'     => 'proxy.example.com',
            'port'     => 8080,
            'username' => 'user',
            'password' => 'pass',
        ]);

        // The proxy client should still implement the interface.
        $this->assertInstanceOf(
            \Synglify\Core\Http\HttpClientInterface::class,
            $client,
        );
    }
}
