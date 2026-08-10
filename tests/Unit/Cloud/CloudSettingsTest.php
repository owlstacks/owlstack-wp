<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Tests\Unit\Cloud;

use Owlstack\WordPress\Cloud\CloudSettings;
use Owlstack\WordPress\Cloud\CloudTokenService;
use Owlstack\WordPress\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CloudSettingsTest extends TestCase
{
    private CloudTokenService $tokens;
    private CloudSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['owlstack_test_options'] = [];
        $GLOBALS['owlstack_test_users']   = [1, 7];
        $this->tokens   = new CloudTokenService();
        $this->settings = new CloudSettings($this->tokens);
    }

    private function setOption(string $key, mixed $value): void
    {
        $data = $this->tokens->all();
        $data[$key] = $value;
        $this->tokens->save($data);
    }

    public function testDefaultPolicyIsHonor(): void
    {
        $this->assertSame('honor', $this->settings->statusPolicy());
    }

    public function testInvalidPolicyFallsBackToHonor(): void
    {
        $this->setOption('post_status_policy', 'pending');

        $this->assertSame('honor', $this->settings->statusPolicy());
    }

    #[DataProvider('statusMatrix')]
    public function testResolveStatusMatrix(string $policy, string $requested, string $expected): void
    {
        $this->setOption('post_status_policy', $policy);

        $this->assertSame($expected, $this->settings->resolveStatus($requested));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function statusMatrix(): array
    {
        return [
            'draft policy, publish requested'   => ['draft', 'publish', 'draft'],
            'draft policy, draft requested'     => ['draft', 'draft', 'draft'],
            'publish policy, publish requested' => ['publish', 'publish', 'publish'],
            'publish policy, draft requested'   => ['publish', 'draft', 'publish'],
            'honor policy, publish requested'   => ['honor', 'publish', 'publish'],
            'honor policy, draft requested'     => ['honor', 'draft', 'draft'],
        ];
    }

    public function testUnknownRequestedStatusResolvesToPublish(): void
    {
        $this->setOption('post_status_policy', 'honor');

        $this->assertSame('publish', $this->settings->resolveStatus('private'));
    }

    public function testDefaultAuthorFallsBackToTokenCreator(): void
    {
        $this->tokens->generate(7);

        $this->assertSame(7, $this->settings->defaultAuthor());
    }

    public function testDefaultAuthorPrefersConfiguredUser(): void
    {
        $this->tokens->generate(7);
        $this->setOption('default_author', 1);

        $this->assertSame(1, $this->settings->defaultAuthor());
    }

    public function testDeletedAuthorFallsBack(): void
    {
        $this->tokens->generate(7);
        $this->setOption('default_author', 99);

        $this->assertSame(7, $this->settings->defaultAuthor());
    }

    public function testNoValidAuthorReturnsZero(): void
    {
        $this->assertSame(0, $this->settings->defaultAuthor());
    }

    public function testPostTypeFallsBackToPost(): void
    {
        $this->setOption('post_type', 'nonexistent_type');

        $this->assertSame('post', $this->settings->postType());
    }

    public function testUpdatePersistsValidValues(): void
    {
        $this->settings->update('draft', 7, 'page');

        $this->assertSame('draft', $this->settings->statusPolicy());
        $this->assertSame(7, $this->settings->defaultAuthor());
        $this->assertSame('page', $this->settings->postType());
    }

    public function testUpdateRejectsInvalidValues(): void
    {
        $this->settings->update('pending', 99, 'nonexistent_type');

        $this->assertSame('honor', $this->settings->statusPolicy());
        $this->assertSame(0, $this->settings->defaultAuthor());
        $this->assertSame('post', $this->settings->postType());
    }
}
