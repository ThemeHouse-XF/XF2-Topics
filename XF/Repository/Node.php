<?php

namespace ThemeHouse\Topics\XF\Repository;

/**
 * Class Node
 * @package ThemeHouse\Topics\XF\Repository
 */
class Node extends XFCP_Node
{
    /**
     * @return array
     */
    public function rebuildTopicsCache()
    {
        $cache = $this->getTopicsCacheData();
        \XF::registry()->set('thTopicsNodes', $cache);
        return $cache;
    }

    /**
     * @return array
     */
    public function getTopicsCacheData()
    {
        $nodes = $this->finder('XF:Node')->order('lft')->fetchColumns('node_id', 'parent_node_id');
        $output = [];
        $nodeTree = [];
        foreach ($nodes as $node) {
            $nodeOutput = [
                'id' => $node['node_id'],
                'children' => []
            ];
            if ($node['parent_node_id']) {
                $output[$node['parent_node_id']]['children'][] = $nodeOutput;
                $children = count($output[$node['parent_node_id']]['children']);
                $output[$node['node_id']] =& $output[$node['parent_node_id']]['children'][$children - 1];
            } else {
                $nodeTree[] = $nodeOutput;
                $nodeCount = count($nodeTree);
                $output[$node['node_id']] =& $nodeTree[$nodeCount - 1];
            }
        }

        return $nodeTree;
    }
}
