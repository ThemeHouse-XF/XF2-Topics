<?php

namespace ThemeHouse\Topics\XFMG\ControllerPlugin;

use XF\Entity\User;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Reply\View;
use XFMG\Entity\Category;

/**
 * Class MediaList
 * @package ThemeHouse\Topics\XFMG\ControllerPlugin
 */
class MediaList extends XFCP_MediaList
{
    /**
     * @return array
     */
    public function getFilterInput()
    {
        $filters = parent::getFilterInput();

        $input = $this->filter([
            'thtopics_filters' => 'array',
        ]);

        if (!empty($input['thtopics_filters'])) {
            $mediaGalleryFilterRepo = $this->getThTopicsMediaGalleryFilterRepo();
            $mediaGalleryFilters = $mediaGalleryFilterRepo->findMediaGalleryFiltersForList()->fetch();

            foreach ($mediaGalleryFilters as $mediaGalleryFilterId => $mediaGalleryFilter) {
                if (!empty($input['thtopics_filters'][$mediaGalleryFilterId])) {
                    $filters['thtopics_filters'][$mediaGalleryFilterId] = true;
                }
            }
        }

        return $filters;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\MediaGalleryFilter|\XF\Mvc\Entity\Repository
     */
    protected function getThTopicsMediaGalleryFilterRepo()
    {
        return $this->repository('ThemeHouse\Topics:MediaGalleryFilter');
    }

    /**
     * @param Finder $finder
     * @param array $filters
     */
    public function applyFilters(Finder $finder, array $filters)
    {
        parent::applyFilters($finder, $filters);

        if (!empty($filters['thtopics_filters'])) {
            $mediaGalleryFilterRepo = $this->getThTopicsMediaGalleryFilterRepo();
            $mediaGalleryFilters = $mediaGalleryFilterRepo->findMediaGalleryFiltersForList()->fetch();

            foreach ($mediaGalleryFilters as $mediaGalleryFilterId => $mediaGalleryFilter) {
                /** @var \ThemeHouse\Topics\XF\Searcher\User $searcher */
                $searcher = $this->searcher('XF:User', $mediaGalleryFilter->criteria);
                $searcher->thTopicsApplyCriteriaToExistingFinder($finder);
                if ($mediaGalleryFilter->style_id) {
                    $this->controller->setViewOption('style_id', $mediaGalleryFilter->style_id);
                }
            }
        }
    }

    /**
     * @param Category|null $category
     * @param User|null $user
     * @return View
     */
    public function actionFilters(Category $category = null, User $user = null)
    {
        $reply = parent::actionFilters($category, $user);

        if ($reply instanceof View) {
            $mediaGalleryFilterRepo = $this->getThTopicsMediaGalleryFilterRepo();
            $mediaGalleryFilters = $mediaGalleryFilterRepo->findMediaGalleryFiltersForList()->fetch();

            $reply->setParam('thtopics_mediaGalleryFilters', $mediaGalleryFilters);
        }

        return $reply;
    }
}
