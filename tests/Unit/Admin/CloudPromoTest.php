<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Tests\Unit\Admin;

use Owlstack\WordPress\Admin\CloudPromo;
use Owlstack\WordPress\Tests\TestCase;

class CloudPromoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['owlstack_test_user_meta']    = [];
        $GLOBALS['owlstack_test_current_user'] = 0;
    }

    public function testUrlPointsAtTheMarketingSite(): void
    {
        $this->assertStringStartsWith(CloudPromo::SITE_URL . '/register', CloudPromo::url('/register'));
    }

    public function testUrlCarriesAttributionParameters(): void
    {
        $url = CloudPromo::url('/', 'settings-card');

        $this->assertStringContainsString('utm_source=wordpress-plugin', $url);
        $this->assertStringContainsString('utm_medium=plugin-admin', $url);
        $this->assertStringContainsString('utm_campaign=cloud-connect', $url);
        $this->assertStringContainsString('utm_content=settings-card', $url);
    }

    public function testPlacementDistinguishesCallSites(): void
    {
        $this->assertStringContainsString(
            'utm_content=cloud-page',
            CloudPromo::url('/register', 'cloud-page'),
        );
    }

    public function testNotDismissedForLoggedOutUser(): void
    {
        $this->assertFalse(CloudPromo::isDismissed(0));
    }

    public function testNotDismissedByDefault(): void
    {
        $this->assertFalse(CloudPromo::isDismissed(7));
    }

    public function testDismissalIsPerUser(): void
    {
        update_user_meta(7, 'owlstack_cloud_promo_dismissed', '1');

        $this->assertTrue(CloudPromo::isDismissed(7));
        $this->assertFalse(CloudPromo::isDismissed(8));
    }

    public function testCloudPlatformCountExceedsThePluginsOwn(): void
    {
        $this->assertGreaterThan(11, CloudPromo::CLOUD_PLATFORM_COUNT);
    }
}
