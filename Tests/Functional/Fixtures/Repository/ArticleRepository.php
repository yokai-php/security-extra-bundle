<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Repository;

use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\Article;

class ArticleRepository
{
    /**
     * @param Article $article
     *
     * @return bool
     */
    public function isPublishable(Article $article)
    {
        return $article->status === 'draft';
    }
}
