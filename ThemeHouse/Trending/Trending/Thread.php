<?php

namespace ThemeHouse\Topics\ThemeHouse\Trending\Trending;

/**
 * Class Thread
 * @package ThemeHouse\Topics\ThemeHouse\Trending\Trending
 */
class Thread extends XFCP_Thread
{
    /**
     * @param $threadFinder
     * @return mixed
     */
    protected function applyFinderFilters($threadFinder)
    {
        if (\XF::options()->thtopics_topicFilterTrendingPages) {
            /** @var \ThemeHouse\Topics\XF\Finder\Thread $threadFinder */
            $threadFinder->filterByTopicsAndForums();
        }

        return parent::applyFinderFilters($threadFinder);
    }
}
