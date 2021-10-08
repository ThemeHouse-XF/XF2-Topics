<?php

namespace ThemeHouse\Topics\XF\Searcher;

use XF\Mvc\Entity\Finder;

/**
 * Class User
 * @package ThemeHouse\Topics\XF\Searcher
 */
class User extends XFCP_User
{
    /**
     * @param Finder $finder
     */
    public function thTopicsApplyCriteriaToExistingFinder(Finder $finder)
    {
        $this->applyCriteria($finder, $this->filteredCriteria);
    }
}
