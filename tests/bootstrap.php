<?php

/**
 * WordPress function stubs for unit testing.
 *
 * Defines minimal WordPress function stubs so that unit tests can run
 * without a full WordPress environment. Each stub is only defined if
 * the function doesn't already exist (so integration tests with a real
 * WP bootstrap still work).
 *
 * @package Owlstack\WordPress\Tests
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (! function_exists('wp_remote_request')) {
    function wp_remote_request(string $url, array $args = []): array
    {
        return [
            'headers'  => [],
            'body'     => '{}',
            'response' => [
                'code'    => 200,
                'message' => 'OK',
            ],
            'cookies'  => [],
        ];
    }
}

if (! function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code(array $response): int
    {
        return $response['response']['code'] ?? 200;
    }
}

if (! function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body(array $response): string
    {
        return $response['body'] ?? '';
    }
}

if (! function_exists('wp_remote_retrieve_headers')) {
    function wp_remote_retrieve_headers(array $response): array
    {
        return $response['headers'] ?? [];
    }
}

if (! function_exists('is_wp_error')) {
    function is_wp_error(mixed $thing): bool
    {
        return false;
    }
}

if (! function_exists('do_action')) {
    function do_action(string $hookName, mixed ...$args): void
    {
        // No-op stub.
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hookName, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}

if (! function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        return $default;
    }
}

if (! function_exists('update_option')) {
    function update_option(string $option, mixed $value, string|bool $autoload = 'yes'): bool
    {
        return true;
    }
}

if (! function_exists('delete_option')) {
    function delete_option(string $option): bool
    {
        return true;
    }
}

if (! function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string
    {
        return 'test-salt-key-for-unit-tests-only-32chars!!';
    }
}

if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string
    {
        return trim(strip_tags($str));
    }
}

if (! function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}

if (! function_exists('absint')) {
    function absint(mixed $maybeint): int
    {
        return abs((int) $maybeint);
    }
}

if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('wp_generate_password')) {
    function wp_generate_password(int $length = 12, bool $specialChars = true): string
    {
        return substr(bin2hex(random_bytes($length)), 0, $length);
    }
}

if (! function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        return json_encode($data, $options, $depth);
    }
}
