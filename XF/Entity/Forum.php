<?php

namespace ThemeHouse\Topics\XF\Entity;

/**
 * Class Forum
 * @package ThemeHouse\Topics\XF\Entity
 *
 * @property AbstractCollection|Topic[] Topics
 */
class Forum extends XFCP_Forum
{
    /**
     * @param $prefix
     * @return bool
     */
    public function isPrefixValid($prefix)
    {
        if ($prefix && !$this->node_id) {
            return true;
        }

        return parent::isPrefixValid($prefix);
    }

    /**
     *
     */
    protected function _postDelete()
    {
        parent::_postDelete();

        $this->db()->update('xf_th_topics_topic', ['node_id' => 0], 'node_id = ?', $this->node_id);

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
