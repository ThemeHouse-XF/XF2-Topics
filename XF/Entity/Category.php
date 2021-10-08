<?php

namespace ThemeHouse\Topics\XF\Entity;

/**
 * Class Category
 * @package ThemeHouse\Topics\XF\Entity
 */
class Category extends XFCP_Category
{
    /**
     *
     */
    protected function _postDelete()
    {
        parent::_postDelete();

        $this->rebuildTopicsCache();
    }

    /**
     *
     */
    protected function rebuildTopicsCache()
    {
        $this->app()->jobManager()->enqueueUnique('thTopicsNodes', 'ThemeHouse\Topics:RebuildNodeTopicsCache');
    }

    /**
     *
     */
    protected function _postSave()
    {
        parent::_postSave();

        if ($this->isChanged('lft') || $this->isChanged('parent_node_id')) {
            $this->rebuildTopicsCache();
        }
    }
}
