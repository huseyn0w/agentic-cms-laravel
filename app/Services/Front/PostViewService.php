<?php

namespace App\Services\Front;

use App\Repositories\PostRepository;
use App\Services\BaseCrudService;

/**
 * Front-end post view service: resolves the public post page (via the inherited
 * resolveBySlug) and owns the like toggle. All data access goes through
 * PostRepository — the controller never touches it directly.
 */
class PostViewService extends BaseCrudService
{
    public function __construct(private PostRepository $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Toggle the authenticated user's like on a post, returning the repository
     * result (a localized message string, or false when not permitted).
     */
    public function like($postId, $userId)
    {
        return $this->repo->handleLike($postId, $userId);
    }

    /**
     * Related posts for the post-detail "Related posts" block (FEATURE_MATRIX
     * §1): other published posts sharing a category/tag, in the current locale.
     */
    public function related(int $postId, ?string $locale = null, int $limit = 4)
    {
        return $this->repo->getRelated($postId, $locale ?? get_current_lang(), $limit);
    }
}
