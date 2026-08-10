<?php

declare(strict_types=1);

namespace Owlstack\WordPress\Cloud;

defined( 'ABSPATH' ) || exit;

/**
 * Site-level settings for content pushed by OwlStack Cloud.
 *
 * Stored in the same `owlstack_cloud` option as the token data
 * (separate from `owlstack_settings`, whose sanitizer strips unknown keys).
 */
class CloudSettings
{
    public const POLICY_DRAFT   = 'draft';
    public const POLICY_PUBLISH = 'publish';
    public const POLICY_HONOR   = 'honor';

    public const POLICIES = [self::POLICY_DRAFT, self::POLICY_PUBLISH, self::POLICY_HONOR];

    public function __construct(
        private readonly CloudTokenService $tokens,
    ) {
    }

    /**
     * How incoming content is statused: force draft, force publish,
     * or honor the status OwlStack Cloud requests.
     */
    public function statusPolicy(): string
    {
        $policy = $this->tokens->all()['post_status_policy'] ?? self::POLICY_HONOR;

        return in_array($policy, self::POLICIES, true) ? $policy : self::POLICY_HONOR;
    }

    /**
     * Resolve the final post status for a requested status.
     */
    public function resolveStatus(string $requested): string
    {
        $requested = $requested === self::POLICY_DRAFT ? self::POLICY_DRAFT : self::POLICY_PUBLISH;

        return match ($this->statusPolicy()) {
            self::POLICY_DRAFT   => self::POLICY_DRAFT,
            self::POLICY_PUBLISH => self::POLICY_PUBLISH,
            default              => $requested,
        };
    }

    /**
     * WP user ID that authors posts created by OwlStack Cloud.
     * Falls back to the user who generated the token.
     */
    public function defaultAuthor(): int
    {
        $data = $this->tokens->all();

        $author = (int) ($data['default_author'] ?? 0);
        if ($author > 0 && get_userdata($author) !== false) {
            return $author;
        }

        $creator = (int) ($data['created_by'] ?? 0);
        if ($creator > 0 && get_userdata($creator) !== false) {
            return $creator;
        }

        return 0;
    }

    /**
     * Post type for posts created by OwlStack Cloud.
     */
    public function postType(): string
    {
        $type = $this->tokens->all()['post_type'] ?? 'post';

        return is_string($type) && $type !== '' && post_type_exists($type) ? $type : 'post';
    }

    /**
     * Persist settings from the admin form.
     */
    public function update(string $statusPolicy, int $defaultAuthor, string $postType): void
    {
        $data = $this->tokens->all();

        $data['post_status_policy'] = in_array($statusPolicy, self::POLICIES, true) ? $statusPolicy : self::POLICY_HONOR;
        $data['default_author']     = $defaultAuthor > 0 && get_userdata($defaultAuthor) !== false ? $defaultAuthor : 0;
        $data['post_type']          = post_type_exists($postType) ? $postType : 'post';

        $this->tokens->save($data);
    }
}
