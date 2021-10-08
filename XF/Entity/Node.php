<?php

namespace ThemeHouse\Topics\XF\Entity;

/**
 * Class Node
 * @package ThemeHouse\Topics\XF\Entity
 */
class Node extends XFCP_Node
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
