<?php

namespace ThemeHouse\Topics\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

/**
 * Class MediaGalleryFilter
 * @package ThemeHouse\Topics\Repository
 */
class MediaGalleryFilter extends Repository
{
    /**
     * @return Finder
     */
    public function findMediaGalleryFiltersForList()
    {
        return $this->finder('ThemeHouse\Topics:MediaGalleryFilter')->order(
            ['display_order', 'media_gallery_filter_id']
        );
    }
}
