<?php

namespace ThemeHouse\Topics\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Entity\Option;
use XF\Entity\OptionGroup;
use XF\Mvc\ParameterBag;
use XF\PrintableException;

/**
 * Class Topic
 * @package ThemeHouse\Topics\Admin\Controller
 */
class Topic extends AbstractController
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionIndex()
    {
        $topics = $this->getTopicRepo()->getTopicsForList(false);

        $options = [
            $this->em()->find('XF:Option', 'thtopics_enableTopics'),
        ];

        $viewParams = [
            'topics' => $topics,
            'options' => $options,
        ];

        return $this->view('ThemeHouse\Topics:Topic\List', 'thtopics_topic_list', $viewParams);
    }

    /**
     * @return \ThemeHouse\Topics\Repository\Topic
     */
    protected function getTopicRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:Topic');
    }

    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionAdd()
    {
        /** @var \ThemeHouse\Topics\Entity\Topic $topic */
        $topic = $this->em()->create('ThemeHouse\Topics:Topic');

        $topic['topic_group_id'] = $this->filter('topic_group_id', 'int');

        return $this->topicAddEdit($topic);
    }

    /**
     * @param \ThemeHouse\Topics\Entity\Topic $topic
     * @return \XF\Mvc\Reply\View
     */
    protected function topicAddEdit(\ThemeHouse\Topics\Entity\Topic $topic)
    {
        $viewParams = [
            'topic' => $topic,
            'nodeOptions' => $this->getNodeOptions(),
            'topicOptions' => $this->getTopicOptions(),
            'topicGroupOptions' => $this->getTopicGroupOptions(),
        ];

        return $this->view('ThemeHouse\Topics:Topic\Edit', 'thtopics_topic_edit', $viewParams);
    }

    /**
     * @return array
     */
    protected function getNodeOptions()
    {
        /** @var \XF\Repository\Node $nodeRepo */
        $nodeRepo = \XF::repository('XF:Node');

        $choices = $nodeRepo->getNodeOptionsData(true, 'Forum');

        return $choices;
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
     * @return array
     */
    protected function getTopicGroupOptions()
    {
        $topicGroupRepo = $this->getTopicGroupRepo();

        $topicGroups = $topicGroupRepo->getTopicGroups();
        $choices = [];

        $choices[] = [
            'value' => 0,
            'label' => \XF::phrase('(none)'),
        ];

        foreach ($topicGroups as $topicGroup) {
            $choices[] = [
                'value' => $topicGroup->topic_group_id,
                'label' => $topicGroup->title,
            ];
        }

        return $choices;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\TopicGroup
     */
    protected function getTopicGroupRepo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->repository('ThemeHouse\Topics:TopicGroup');
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionEdit(ParameterBag $params)
    {
        $topic = $this->assertTopicExists($params['topic_id']);

        return $this->topicAddEdit($topic);
    }

    /**
     * @param string $id
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return \ThemeHouse\Topics\Entity\Topic
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertTopicExists($id, $with = null, $phraseKey = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->assertRecordExists('ThemeHouse\Topics:Topic', $id, $with, $phraseKey);
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Mvc\Reply\Exception
     * @throws PrintableException
     */
    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        /** @noinspection PhpUndefinedFieldInspection */
        if ($params->topic_id) {
            /** @var \ThemeHouse\Topics\Entity\Topic $topic */
            /** @noinspection PhpUndefinedFieldInspection */
            $topic = $this->assertTopicExists($params->topic_id);
        } else {
            $topic = $this->em()->create('ThemeHouse\Topics:Topic');
        }

        $this->topicSaveProcess($topic)->run();

        $redirect = $this->buildLink('topics');

        return $this->redirect($redirect);
    }

    /**
     * @param \ThemeHouse\Topics\Entity\Topic $topic
     * @return \XF\Mvc\FormAction|\XF\Mvc\Reply\Exception
     * @throws PrintableException
     */
    protected function topicSaveProcess(\ThemeHouse\Topics\Entity\Topic $topic)
    {
        $form = $this->formAction();

        $input = $this->filter([
            'title' => 'str',
            'description' => 'str',
            'background_color' => 'str',
            'extra_class' => 'str',
            'additional_selectable' => 'bool',
            'node_id' => 'uint',
            'topic_group_id' => 'uint',
        ]);

        if (empty($input['background_color'])) {
            throw new PrintableException(\XF::phrase('thtopics_topics_require_background_color'));
        }

        $oldNode = $topic->node_id;
        $newNode = $input['node_id'];

        if ($oldNode && !$newNode) {
            $db = $this->app()->db();
            $threadCount = $db->fetchOne('
                SELECT COUNT(*)
                FROM xf_thread
                WHERE topic_id = ?
            ', $topic->topic_id);
            if ($threadCount) {
                throw new PrintableException(
                    \XF::phrase('thtopics_move_threads_before_unassociating_topic_from_forum')
                );
            }
        }

        $form->basicEntitySave($topic, $input);

        /* Move threads to new forum alongside topic */
        $form->complete(function () use ($oldNode, $newNode, $topic) {
            if ($oldNode && $newNode) {
                $db = $this->app()->db();
                $db->update('xf_thread', ['node_id' => $newNode], "topic_id = {$topic->topic_id}");

                $forums = $this->finder('XF:Forum')
                    ->where('node_id', '=', [$oldNode, $newNode])
                    ->fetch();

                foreach ($forums as $forum) {
                    /** @var \XF\Entity\Forum $forum */
                    $forum->rebuildCounters();
                    $forum->save();
                }
            }
        });

        /* Apply topic to all threads in node that don't have a topic yet */
        $form->complete(function () use ($newNode, $topic) {
            if ($newNode) {
                $db = $this->app()->db();

                $db->update('xf_thread', ['topic_id' => $topic->topic_id], "node_id = {$newNode} && !topic_id");
            }
        });

        return $form;
    }

    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws PrintableException
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionDelete(ParameterBag $params)
    {
        /** @var \ThemeHouse\Topics\Entity\Topic $topic */
        /** @noinspection PhpUndefinedFieldInspection */
        $topic = $this->assertTopicExists($params->topic_id);

        if ($this->isPost()) {
            $topic->delete();

            return $this->redirect($this->buildLink('topics'));
        } else {
            $viewParams = [
                'topic' => $topic
            ];

            return $this->view('ThemeHouse\Topics:Topic\Delete', 'thtopics_topic_delete', $viewParams);
        }
    }

    /**
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionTools()
    {
        $this->setSectionContext('thtopics_tools');
        $this->assertAdminPermission('rebuildCache');

        if ($this->isPost()) {
            $job = $this->filter('job', 'str');
            $options = $this->filter('options', 'array');

            $runner = $this->app->job($job, null, $options);
            if ($runner && $runner->canTriggerByChoice()) {
                $uniqueId = 'Rebuild' . $job;
                $id = $this->app->jobManager()->enqueueUnique(
                    $uniqueId,
                    $job,
                    $options
                );

                $reply = $this->redirect(
                    $this->buildLink('tools/run-job', null, [
                        'only_id' => $id,
                        '_xfRedirect' => $this->buildLink('topics/tools', null, ['success' => 1])
                    ])
                );
                $reply->setPageParam('skipManualJobRun', true);
                return $reply;
            } else {
                return $this->error(\XF::phrase('this_cache_could_not_be_rebuilt'), 500);
            }
        } else {
            /** @var \XF\Repository\Node $nodeRepo */
            $nodeRepo = \XF::repository('XF:Node');
            $nodes = $nodeRepo->getFullNodeList();
            $nodeTree = $nodeRepo->createNodeTree($nodes);

            /** @var \XF\Repository\ThreadPrefix $prefixRepo */
            $prefixRepo = \XF::repository('XF:ThreadPrefix');
            $prefixes = $prefixRepo->findPrefixesForList();

            $viewParams = [
                'success' => $this->filter('success', 'bool'),
                'hasStoppedManualJobs' => $this->app->jobManager()->hasStoppedManualJobs(),
                'nodeTree' => $nodeTree,
                'topics' => $this->getTopicRepo()->getTopics(),
                'prefixes' => $prefixes
            ];
            return $this->view('ThemeHouse\Topics:Topic\Tools', 'thtopics_topic_tools', $viewParams);
        }
    }

    /**
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionOptions()
    {
        $this->setSectionContext('thtopics_options');
        /** @var OptionGroup $group */
        $group = $this->assertOptionGroupExists('thtopics');

        if ($group->AddOn && !$group->AddOn->active) {
            return $this->error(\XF::phrase('option_group_belongs_to_disabled_addon', [
                'addon' => $group->AddOn->title,
                'link' => $this->buildLink('add-ons')
            ]));
        }

        $optionRepo = $this->getOptionRepo();

        $viewParams = [
            'group' => $group,
            'groups' => $optionRepo->findOptionGroupList()->fetch(),
            'canAdd' => $optionRepo->canAddOption()
        ];
        return $this->view('XF:Option\Listing', 'option_list', $viewParams);
    }

    /**
     * @param string $groupId
     * @param array|string|null $with
     * @param null|string $phraseKey
     *
     * @return OptionGroup|\XF\Mvc\Entity\Entity
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function assertOptionGroupExists($groupId, $with = null, $phraseKey = null)
    {
        return $this->assertRecordExists('XF:OptionGroup', $groupId, $with, $phraseKey);
    }

    /**
     * @return \XF\Mvc\Entity\Repository|\XF\Repository\Option
     */
    protected function getOptionRepo()
    {
        return $this->repository('XF:Option');
    }

    /**
     * @return \XF\Mvc\Reply\Redirect|\XF\Mvc\Reply\View
     * @throws PrintableException
     */
    public function actionLayout()
    {
        /** @var Option $option */
        $option = \XF::em()->find('XF:Option', 'thtopic_topicListData');

        if ($this->isPost()) {
            /** @var \XF\Entity\Option $option */
            $option->option_value = $this->filter('value', 'str');
            $option->save();

            return $this->redirect($this->buildLink('topics/layout'), \XF::phrase('changes_saved'));
        }

        $widgetFinder = $this->finder('XF:Widget');
        $widgets = $widgetFinder->where('definition_id', 'thtopics_thread_filter')->fetch();

        $widgetLimits = [];
        if ($widgets) {
            foreach ($widgets as $widget) {
                if (!empty($widget->options['visible_filter_topics'])) {
                    $widgetLimits[] = $widget->options['visible_filter_topics'];
                }
            }
        }
        $widgetLimits = implode(',', array_filter($widgetLimits));

        $topicRepo = $this->getTopicRepo();

        $options[] = $this->em()->find('XF:Option', 'thtopics_visibleFilterTopics');
        $options[] = $this->em()->find('XF:Option', 'thtopics_visibleFilterTopicsVertical');

        $totalWidgets = count($widgets);

        $viewParams = [
            'topics' => $topicRepo->getTopicsForList(false),
            'data' => json_decode($option->option_value),
            'options' => $options,
            'widgetLimits' => $widgetLimits,

            'widgets' => $widgets,
            'totalWidgets' => $totalWidgets,
        ];

        return $this->view('ThemeHouse\Topics:Topic\Layout', 'thtopics_topics_layout', $viewParams);
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
