<?php

namespace ThemeHouse\Topics\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

/**
 * Class TopicGroup
 * @package ThemeHouse\Topics\Admin\Controller
 */
class TopicGroup extends AbstractController
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionIndex(ParameterBag $params)
    {
        if ($params['topic_group_id']) {
            $topicGroup = $this->assertTopicGroupExists($params['topic_group_id']);
            return $this->redirect($this->buildLink('topic-groups/edit', $topicGroup));
        }

        $topicGroupRepo = $this->getTopicGroupRepo();
        $topicGroupList = $topicGroupRepo->findTopicGroupsForList()->fetch();
        $topicGroups = $topicGroupList;

        $options = $this->em()->find('XF:Option', 'thtopics_groupMultiSelect');

        $viewParams = [
            'topicGroups' => $topicGroups,
            'totalTopicGroups' => $topicGroups->count(),
            'options' => [$options],
        ];
        return $this->view('ThemeHouse\Topics:TopicGroup\Listing', 'thtopics_topic_group_list', $viewParams);
    }

    /**
     * @param string $id
     *
     * @return \ThemeHouse\Topics\Entity\TopicGroup|\XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertTopicGroupExists($id)
    {
        return $this->assertRecordExists('ThemeHouse\Topics:TopicGroup', $id);
    }

    /**
     * @return \ThemeHouse\Topics\Repository\TopicGroup|\XF\Mvc\Entity\Repository
     */
    protected function getTopicGroupRepo()
    {
        return $this->repository('ThemeHouse\Topics:TopicGroup');
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $topicGroup = $this->assertTopicGroupExists($params['topic_group_id']);
        return $this->topicGroupAddEdit($topicGroup);
    }

    /**
     * @param \ThemeHouse\Topics\Entity\TopicGroup $topicGroup
     * @return \XF\Mvc\Reply\View
     */
    protected function topicGroupAddEdit(\ThemeHouse\Topics\Entity\TopicGroup $topicGroup)
    {
        $topicOptions = $this->getTopicOptions();

        $viewParams = [
            'topicGroup' => $topicGroup,
            'topicOptions' => $topicOptions,
        ];
        return $this->view('ThemeHouse\Topics:TopicGroup\Edit', 'thtopics_topic_group_edit', $viewParams);
    }

    /**
     * @return array
     */
    protected function getTopicOptions()
    {
        $topicRepo = $this->getTopicRepo();

        $topics = $topicRepo->getTopics();
        $choices = [];

        $choices[] = [
            'value' => 0,
            'label' => \XF::phrase('(none)'),
        ];

        foreach ($topics as $topic) {
            $choices[] = [
                'value' => $topic->topic_id,
                'label' => $topic->title,
            ];
        }

        return $choices;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic|\XF\Mvc\Entity\Repository
     */
    protected function getTopicRepo()
    {
        return $this->repository('ThemeHouse\Topics:Topic');
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var \ThemeHouse\Topics\Entity\TopicGroup $topicGroup */
        $topicGroup = $this->em()->create('ThemeHouse\Topics:TopicGroup');
        return $this->topicGroupAddEdit($topicGroup);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws \XF\PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params['topic_group_id']) {
            $topicGroup = $this->assertTopicGroupExists($params['topic_group_id']);
        } else {
            $topicGroup = $this->em()->create('ThemeHouse\Topics:TopicGroup');
        }

        $this->topicGroupSaveProcess($topicGroup)->run();

        $topicIds = array_filter($this->filter('topic_ids', 'array-int'));

        $removeTopicIds = array_diff($topicGroup->topic_ids, $topicIds);
        $addTopicIds = array_diff($topicIds, $topicGroup->topic_ids);

        $db = $this->app()->db();
        if ($addTopicIds) {
            $db->update(
                'xf_th_topics_topic',
                ['topic_group_id' => $topicGroup->topic_group_id],
                'topic_id IN (' . $db->quote($addTopicIds) . ')'
            );
        }
        if ($removeTopicIds) {
            $db->update(
                'xf_th_topics_topic',
                ['topic_group_id' => 0],
                'topic_id IN (' . $db->quote($removeTopicIds) . ')'
            );
        }

        return $this->redirect($this->buildLink('topic-groups') . $this->buildLinkHash($topicGroup->topic_group_id));
    }

    /**
     * @param \ThemeHouse\Topics\Entity\TopicGroup $topicGroup
     * @return \XF\Mvc\FormAction
     */
    protected function topicGroupSaveProcess(\ThemeHouse\Topics\Entity\TopicGroup $topicGroup)
    {
        $form = $this->formAction();

        $input = $this->filter([
            'title' => 'str',
        ]);

        $form->basicEntitySave($topicGroup, $input);

        return $form;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        $topicGroup = $this->assertTopicGroupExists($params['topic_group_id']);
        if (!$topicGroup->preDelete()) {
            return $this->error($topicGroup->getErrors());
        }

        if ($this->isPost()) {
            $topicGroup->delete();
            return $this->redirect($this->buildLink('topic-groups'));
        } else {
            $viewParams = [
                'topicGroup' => $topicGroup,
            ];
            return $this->view('ThemeHouse\Topics:TopicGroup\Delete', 'thtopics_topic_group_delete', $viewParams);
        }
    }

    /**
     * @param $action
     * @param ParameterBag $params
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('thtopics');
    }
}
