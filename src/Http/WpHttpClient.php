<?php

declare(strict_types=1);

namespace Synglify\WordPress\Http;

use Synglify\Core\Exceptions\SynglifyException;
use Synglify\Core\Http\Contracts\HttpClientInterface;

/**
 * WordPress HTTP API implementation of HttpClientInterface.
 *
 * Uses wp_remote_get(), wp_remote_post(), and wp_remote_request()
 * instead of cURL, respecting WordPress proxy and SSL settings.
 */
class WpHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly int $timeout = 30,
    ) {
    }

    public function get(string $url, array $options = []): array
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): array
    {
        return $this->request('POST', $url, $options);
    }

    public function put(string $url, array $options = []): array
    {
        return $this->request('PUT', $url, $options);
    }

    public function delete(string $url, array $options = []): array
    {
        return $this->request('DELETE', $url, $options);
    }

    /**
     * Execute an HTTP request using the WordPress HTTP API.
     *
     * @param string $method  HTTP method.
     * @param string $url     Request URL.
     * @param array  $options Request options (headers, json, body, form_params, multipart, query).
     * @return array{status: int, headers: array, body: string}
     *
     * @throws SynglifyException On WP_Error or request failure.
     */
    private function request(string $method, string $url, array $options = []): array
    {
        // Append query parameters to URL.
        if (isset($options['query'])) {
            $url .= '?' . http_build_query($options['query']);
        }

        $args = [
            'method'  => $method,
            'timeout' => $this->timeout,
            'headers' => $options['headers'] ?? [],
        ];

        // Set request body based on option type.
        if (isset($options['multipart'])) {
            $boundary = wp_generate_password(24, false);
            $args['headers']['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
            $args['body'] = $this->buildMultipartBody($options['multipart'], $boundary);
        } elseif (isset($options['json'])) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($options['json']);
        } elseif (isset($options['body'])) {
            $args['body'] = $options['body'];
        } elseif (isset($options['form_params'])) {
            $args['body'] = $options['form_params'];
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new SynglifyException(
                'HTTP request failed: ' . $response->get_error_message()
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $rawHeaders = wp_remote_retrieve_headers($response);

        // Normalize headers to array format matching core's HttpClient.
        $headers = [];
        if ($rawHeaders instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary || is_iterable($rawHeaders)) {
            foreach ($rawHeaders as $key => $value) {
                $headers[strtolower($key)][] = $value;
            }
        }

        return [
            'status'  => (int) $statusCode,
            'headers' => $headers,
            'body'    => $body,
        ];
    }

    /**
     * Build a multipart/form-data body string.
     *
     * @param array  $parts    Multipart field definitions.
     * @param string $boundary The boundary string.
     */
    private function buildMultipartBody(array $parts, string $boundary): string
    {
        $body = '';

        foreach ($parts as $part) {
            $name = $part['name'];
            $contents = $part['contents'];

            $body .= "--{$boundary}\r\n";

            if (isset($part['filename'])) {
                $mimeType = $part['headers']['Content-Type']
                    ?? $part['content_type']
                    ?? 'application/octet-stream';

                $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$part['filename']}\"\r\n";
                $body .= "Content-Type: {$mimeType}\r\n\r\n";
            } else {
                $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            }

            $body .= $contents . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        return $body;
    }
}
