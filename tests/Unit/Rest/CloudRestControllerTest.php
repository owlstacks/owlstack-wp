<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Tests\Unit\Rest;

use Owlstack\WordPress\Cloud\CloudTokenService;
use Owlstack\WordPress\Rest\CloudRestController;
use Owlstack\WordPress\Tests\TestCase;

class CloudRestControllerTest extends TestCase
{
    private CloudTokenService $tokens;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['owlstack_test_options'] = [];
        $GLOBALS['owlstack_test_posts']   = [];
        $GLOBALS['owlstack_test_meta']    = [];
        $GLOBALS['owlstack_test_users']   = [1];
        $this->tokens = new CloudTokenService();
    }

    private function request(array $params = [], array $headers = [], string $body = ''): \WP_REST_Request
    {
        return new \WP_REST_Request($params, $headers, $body);
    }

    // ── checkToken ───────────────────────────────────────────────────────

    public function testCheckTokenFailsWhenNotPaired(): void
    {
        $result = CloudRestController::checkToken($this->request());

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_not_paired', $result->get_error_code());
        $this->assertSame(403, $result->get_error_data()['status']);
    }

    public function testCheckTokenRejectsWrongToken(): void
    {
        $this->tokens->generate(1);

        $result = CloudRestController::checkToken(
            $this->request([], ['X-Owlstack-Token' => 'owlstk_' . str_repeat('0', 64)]),
        );

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_invalid_token', $result->get_error_code());
        $this->assertSame(401, $result->get_error_data()['status']);
    }

    public function testCheckTokenRejectsMissingHeader(): void
    {
        $this->tokens->generate(1);

        $result = CloudRestController::checkToken($this->request());

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_invalid_token', $result->get_error_code());
    }

    public function testCheckTokenAcceptsValidTokenAndRecordsUse(): void
    {
        $token = $this->tokens->generate(1);

        $result = CloudRestController::checkToken(
            $this->request([], ['X-Owlstack-Token' => $token]),
        );

        $this->assertTrue($result);
        $this->assertIsInt($this->tokens->info()['last_used_at']);
    }

    // ── createPost ───────────────────────────────────────────────────────

    public function testCreatePostHonorsRequestedStatus(): void
    {
        $this->tokens->generate(1);

        $response = CloudRestController::createPost($this->request([
            'title'   => 'Hello',
            'content' => '<p>Body</p>',
            'status'  => 'draft',
        ]));

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(201, $response->get_status());
        $this->assertSame('draft', $response->get_data()['status']);
    }

    public function testCreatePostPolicyOverridesRequestedStatus(): void
    {
        $this->tokens->generate(1);
        $data = $this->tokens->all();
        $data['post_status_policy'] = 'draft';
        $this->tokens->save($data);

        $response = CloudRestController::createPost($this->request([
            'title'   => 'Hello',
            'content' => '<p>Body</p>',
            'status'  => 'publish',
        ]));

        $this->assertSame('draft', $response->get_data()['status']);
    }

    public function testCreatePostSanitizesHostileContent(): void
    {
        $this->tokens->generate(1);

        $response = CloudRestController::createPost($this->request([
            'title'   => 'Hello',
            'content' => '<p>Safe</p><script>alert(1)</script>',
            'status'  => 'publish',
        ]));

        $postId = $response->get_data()['id'];
        $stored = $GLOBALS['owlstack_test_posts'][$postId]->post_content;

        $this->assertStringContainsString('<p>Safe</p>', $stored);
        $this->assertStringNotContainsString('<script>', $stored);
    }

    public function testCreatePostMarksPostAsCloudCreated(): void
    {
        $this->tokens->generate(1);

        $response = CloudRestController::createPost($this->request([
            'title'   => 'Hello',
            'content' => 'Body',
            'status'  => 'publish',
        ]));

        $postId = $response->get_data()['id'];

        $this->assertSame(1, $GLOBALS['owlstack_test_meta'][$postId]['_owlstack_cloud']);
    }

    public function testCreatePostFailsWithoutValidAuthor(): void
    {
        $this->tokens->generate(42); // User 42 does not exist.

        $result = CloudRestController::createPost($this->request([
            'title'   => 'Hello',
            'content' => 'Body',
            'status'  => 'publish',
        ]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_no_author', $result->get_error_code());
    }

    // ── deletePost ───────────────────────────────────────────────────────

    public function testDeletePostReturns404ForMissingPost(): void
    {
        $this->tokens->generate(1);

        $result = CloudRestController::deletePost($this->request(['id' => 999]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_post_not_found', $result->get_error_code());
    }

    public function testDeletePostRefusesForeignPosts(): void
    {
        $this->tokens->generate(1);
        $postId = wp_insert_post(['post_title' => 'Not ours', 'post_status' => 'publish']);

        $result = CloudRestController::deletePost($this->request(['id' => $postId]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('owlstack_forbidden_post', $result->get_error_code());
        $this->assertSame(403, $result->get_error_data()['status']);
    }

    public function testDeletePostTrashesCloudPosts(): void
    {
        $this->tokens->generate(1);
        $postId = wp_insert_post([
            'post_title'  => 'Ours',
            'post_status' => 'publish',
            'meta_input'  => ['_owlstack_cloud' => 1],
        ]);

        $result = CloudRestController::deletePost($this->request(['id' => $postId]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $this->assertTrue($result->get_data()['deleted']);
        $this->assertSame('trash', $GLOBALS['owlstack_test_posts'][$postId]->post_status);
    }

    public function testDeletePostForceDeletes(): void
    {
        $this->tokens->generate(1);
        $postId = wp_insert_post([
            'post_title'  => 'Ours',
            'post_status' => 'publish',
            'meta_input'  => ['_owlstack_cloud' => 1],
        ]);

        $result = CloudRestController::deletePost($this->request(['id' => $postId, 'force' => true]));

        $this->assertTrue($result->get_data()['deleted']);
        $this->assertArrayNotHasKey($postId, $GLOBALS['owlstack_test_posts']);
    }

    // ── siteInfo ─────────────────────────────────────────────────────────

    public function testSiteInfoExposesConnectionMetadata(): void
    {
        $response = CloudRestController::siteInfo();

        $data = $response->get_data();

        $this->assertSame('Test Site', $data['name']);
        $this->assertSame('https://example.test', $data['url']);
        $this->assertSame('honor', $data['post_status_policy']);
        $this->assertSame('post', $data['default_post_type']);
    }
}
