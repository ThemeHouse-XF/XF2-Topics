<?php

namespace ThemeHouse\Topics\XF\Finder;

use ThemeHouse\Topics\Entity\Topic;
use ThemeHouse\Topics\XF\Entity\Forum;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\FinderExpression;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Finder
 */
class Thread extends XFCP_Thread
{
    /**
     * @param bool $force
     * @return $this
     */
    public function filterByTopicsAndForums($force = false)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $forumFilters = \XF::visitor()->getForumFilters();
        /** @noinspection PhpUndefinedMethodInspection */
        $topicFilters = \XF::visitor()->getTopicFilters();

        if ($force || $forumFilters || $topicFilters) {
            /** @var \ThemeHouse\Topics\XF\Finder\Thread $threadFinder */
            $this->inTopicsAndForums($forumFilters, $topicFilters);
        }

        return $this;
    }

    /**
     * @param array $nodeIds
     * @param array $topicIds
     * @param array $limits
     * @return $this
     */
    public function inTopicsAndForums(array $nodeIds = [], array $topicIds = [], array $limits = [])
    {
        $limits = array_replace([
            'visibility' => true,
            'allowOwnPending' => false
        ], $limits);

        if ($nodeIds) {
            $this->inOneOfForums($nodeIds);
        }

        if ($topicIds) {
            $this->inTopics($topicIds);
        }

        if ($limits['visibility'] && $nodeIds) {
            $this->applyVisibilityChecksWithNodeIds($nodeIds, $limits['allowOwnPending']);
        }

        return $this;
    }

    /**
     * @param array $nodeIds
     * @return $this
     */
    public function inOneOfForums(array $nodeIds)
    {
        if (!empty($nodeIds)) {
            $whereOr = [];
            foreach ($nodeIds as $nodeId) {
                $whereOr[] = ['node_id', $nodeId];
            }

            if ($whereOr) {
                $this->whereOr($whereOr);
            }
        }

        return $this;
    }

    /**
     * @param array $topicIds
     * @return $this
     */
    public function inTopics(array $topicIds)
    {
        $logicalOperator = \XF::options()->thtopics_topicFilterLogicalOperator;

        if (!empty($topicIds)) {
            if ($logicalOperator === 'or' || $logicalOperator === 'andxor') {
                $this->inOneOfTopics($topicIds);

                if ($logicalOperator === 'andxor' && count($topicIds) > 1) {
                    $this->applyTopicMatchesOrder($topicIds);
                }
            } else {
                foreach ($topicIds as $topicId) {
                    $this->whereSql(
                        "`xf_thread`.topic_id = {$topicId} OR FIND_IN_SET({$topicId}, `xf_thread`.additional_topic_ids)"
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @param array $topicIds
     * @return $this
     */
    public function inOneOfTopics(array $topicIds)
    {
        if (!empty($topicIds)) {
            $query = [];
            foreach ($topicIds as $topicId) {
                $query[] = "(`xf_thread`.topic_id = {$topicId} OR FIND_IN_SET({$topicId}, `xf_thread`.additional_topic_ids))";
            }
            $this->whereSql(join('OR', $query));
        }

        return $this;
    }

    /**
     * @param array $topicIds
     * @return $this
     */
    public function applyTopicMatchesOrder(array $topicIds)
    {
        $newOrder = null;
        if (!empty($topicIds)) {
            $sum = [];
            foreach ($topicIds as $topicId) {
                $sum[] = "(`xf_thread`.topic_id = {$topicId} OR FIND_IN_SET({$topicId}, `xf_thread`.additional_topic_ids))";
            }
            $newOrder = new FinderExpression('(' . join('+', $sum) . ')');
        }

        if (!$newOrder) {
            return $this;
        }

        if ($this->order) {
            $existingOrder = $this->order;
            $this->resetOrder();
            $this->order($newOrder, 'DESC');
            foreach ($existingOrder as $order) {
                if (is_array($order)) {
                    $direction = $order[1] ?: 'ASC';
                    $order = $order[0];
                } else {
                    $orderParts = explode(' ', $order);
                    $direction = array_pop($orderParts);
                    $order = implode(' ', $orderParts);
                }
                $this->order(new FinderExpression($order), $direction);
            }
        } else {
            $this->order($newOrder, 'DESC');
        }

        return $this;
    }

    /**
     * @param array $nodeIds
     * @param bool $allowOwnPending
     * @return $this
     */
    public function applyVisibilityChecksWithNodeIds(array $nodeIds, $allowOwnPending = false)
    {
        $visitor = \XF::visitor();

        /** @var Finder $forumFinder */
        $forumFinder = \XF::em()->getFinder('XF:Forum')
            ->with('Node', true)
            ->with('Node.Permissions|' . $visitor->permission_combination_id);

        if ($nodeIds) {
            $forumFinder->where('node_id', $nodeIds);
        }

        /** @var AbstractCollection $forums */
        $forums = $forumFinder->fetch();

        if (!$forums->count() && !empty($nodeIds)) {
            $nodeIds = [];
            return $this->applyVisibilityChecksWithNodeIds($nodeIds, $allowOwnPending);
        }

        $deletionLog = false;

        foreach ($forums as $nodeId => $forum) {
            /** @var Forum $forum */
            if (!$forum->canView()) {
                unset($forums[$nodeId]);
                continue;
            }
            $forumViewableState = 'v';
            if ($forum->canViewDeletedThreads()) {
                $deletionLog = true;
                $forumViewableState .= 'd';
            }
            if ($forum->canViewModeratedThreads()) {
                $forumViewableState .= 'm';
            }
            $viewableStates[$forumViewableState][] = $forum->node_id;
        }

        if ($deletionLog) {
            $this->with('DeletionLog');
        }

        $conditions = [];

        if (!empty($viewableStates['v'])) {
            $conditions[] = [
                'discussion_state' => 'visible',
                'node_id' => $viewableStates['v'],
            ];
            if ($visitor->user_id && $allowOwnPending) {
                $conditions[] = [
                    'discussion_state' => 'moderated',
                    'user_id' => $visitor->user_id,
                    'node_id' => $viewableStates['v'],
                ];
            }
        }

        if (!empty($viewableStates['vd'])) {
            $conditions[] = [
                'discussion_state',
                ['visible', 'deleted'],
                'node_id',
                $viewableStates['vd'],
            ];
            if ($visitor->user_id && $allowOwnPending) {
                $conditions[] = [
                    'discussion_state' => 'moderated',
                    'user_id' => $visitor->user_id,
                    'node_id' => $viewableStates['vd'],
                ];
            }
        }

        if (!empty($viewableStates['vdm'])) {
            $conditions[] = [
                'discussion_state' => ['visible', 'deleted', 'moderated'],
                'node_id' => $viewableStates['vdm'],
            ];
        }

        if (!empty($viewableStates['vm'])) {
            $conditions[] = [
                'discussion_state' => ['visible', 'moderated'],
                'node_id' => $viewableStates['vm'],
            ];
        }

        $this->whereOr($conditions);

        $viewOthers = [];
        $notViewOthers = [];
        foreach ($forums as $forum) {
            if ($visitor->hasNodePermission($forum->node_id, 'viewOthers')) {
                $viewOthers[] = $forum->node_id;
            } else {
                $notViewOthers[] = $forum->node_id;
            }
        }

        if ($notViewOthers) {
            if ($visitor->user_id) {
                if ($viewOthers) {
                    $this->whereOr([
                        [
                            'user_id' => $visitor->user_id,
                            'node_id' => $notViewOthers,
                        ],
                        [
                            'node_id' => $viewOthers,
                        ],
                    ]);
                } else {
                    $this->where('user_id', $visitor->user_id);
                }
            } else {
                if ($viewOthers) {
                    $this->where('node_id', $viewOthers);
                } else {
                    $this->whereSql('1=0'); // force false immediately
                }
            }
        }

        return $this;
    }

    /**
     * @param Topic $topic
     * @param bool $allowOwnPending
     * @return $this
     */
    public function applyVisibilityChecksInTopic(Topic $topic, $allowOwnPending = false)
    {
        $conditions = [];
        $viewableStates = ['visible'];
        $visitor = \XF::visitor();

        if ($topic->node_id) {
            /** @var \XF\Entity\Forum $forum */
            $forum = $topic->Forum;
            if ($forum->canViewDeletedThreads()) {
                $viewableStates[] = 'deleted';

                $this->with('DeletionLog');
            }

            if ($forum->canViewModeratedThreads()) {
                $viewableStates[] = 'moderated';
            } else {
                if ($visitor->user_id && $allowOwnPending) {
                    $conditions[] = [
                        'discussion_state' => 'moderated',
                        'user_id' => $visitor->user_id
                    ];
                }
            }
        }


        $conditions[] = ['discussion_state', $viewableStates];

        $this->whereOr($conditions);

        if ($topic->node_id) {
            if (!$visitor->hasNodePermission($topic->node_id, 'viewOthers')) {
                if ($visitor->user_id) {
                    $this->where('user_id', $visitor->user_id);
                } else {
                    $this->whereSql('1=0'); // force false immediately
                }
            }
        }

        return $this;
    }
}
