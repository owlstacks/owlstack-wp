<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Tests\Unit\Http;

use Owlstack\WordPress\Http\WpHttpClient;
use Owlstack\WordPress\Tests\TestCase;

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
            \Owlstack\Core\Http\Contracts\HttpClientInterface::class,
            $this->client,
        );
    }

    public function test_get_method_returns_array(): void
    {
        $response = $this->client->get('https://example.com/api');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertSame(200, $response['status']);
    }

    public function test_post_method_returns_array(): void
    {
        $response = $this->client->post('https://example.com/api', [
            'json' => ['key' => 'value'],
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertSame(200, $response['status']);
    }

    public function test_put_method_returns_array(): void
    {
        $response = $this->client->put('https://example.com/api', [
            'json' => ['key' => 'value'],
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }

    public function test_delete_method_returns_array(): void
    {
        $response = $this->client->delete('https://example.com/api');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
    }
}
