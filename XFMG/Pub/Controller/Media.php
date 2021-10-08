<?php

namespace ThemeHouse\Topics\XFMG\Pub\Controller;

use ThemeHouse\Topics\Repository\MediaGalleryFilter;
use ThemeHouse\Topics\XF\Searcher\User;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Class Media
 * @package ThemeHouse\Topics\XFMG\Pub\Controller
 */
class Media extends XFCP_Media
{
    /**
     * @param ParameterBag $params
     * @return View
     */
    public function actionView(ParameterBag $params)
    {
        $reply = parent::actionView($params);

        if ($reply instanceof View && $reply->getParam('mediaItem')) {
            $mediaItem = $reply->getParam('mediaItem');

            /** @var MediaGalleryFilter $mediaGalleryFilterRepo */
            $mediaGalleryFilterRepo = $this->repository('ThemeHouse\Topics:MediaGalleryFilter');
            $mediaGalleryFilters = $mediaGalleryFilterRepo->findMediaGalleryFiltersForList()->fetch();

            foreach ($mediaGalleryFilters as $mediaGalleryFilterId => $mediaGalleryFilter) {
                if ($mediaGalleryFilter->style_id) {
                    /** @var User $searcher */
                    $searcher = $this->searcher('XF:User', $mediaGalleryFilter->criteria);
                    $finder = \XF::em()->getFinder('XF:User')->where('user_id', $mediaItem->user_id);
                    $searcher->thTopicsApplyCriteriaToExistingFinder($finder);
                    if ($finder->fetch()->count()) {
                        $this->setViewOption('style_id', $mediaGalleryFilter->style_id);
                    }
                }
            }
        }

        return $reply;
    }
}
