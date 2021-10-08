<?php

namespace ThemeHouse\Topics\Service\Thread;

use ThemeHouse\Topics\Entity\Topic;
use ThemeHouse\Topics\XF\Entity\Thread;
use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;

/**
 * Class TopicChanger
 * @package ThemeHouse\Topics\Service\Thread
 */
class TopicChanger extends AbstractService
{
    /**
     * @var Thread
     */
    protected $thread;

    /**
     * @var bool
     */
    protected $alert = false;
    /**
     * @var string
     */
    protected $alertReason = '';

    /**
     * @var bool
     */
    protected $redirect = false;
    /**
     * @var int
     */
    protected $redirectLength = 0;

    /**
     * @var null
     */
    protected $prefixId = null;

    /**
     * @var array
     */
    protected $extraSetup = [];

    /**
     * TopicChanger constructor.
     * @param App $app
     * @param Thread $thread
     */
    public function __construct(App $app, Thread $thread)
    {
        parent::__construct($app);

        $this->thread = $thread;
    }

    /**
     * @return Thread
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * @param $alert
     * @param null $reason
     */
    public function setSendAlert($alert, $reason = null)
    {
        $this->alert = (bool)$alert;
        if ($reason !== null) {
            $this->alertReason = $reason;
        }
    }

    /**
     * @param $redirect
     * @param null $length
     */
    public function setRedirect($redirect, $length = null)
    {
        $this->redirect = (bool)$redirect;
        if ($length !== null) {
            $this->redirectLength = intval($length);
        }
    }

    /**
     * @param $prefixId
     */
    public function setPrefix($prefixId)
    {
        $this->prefixId = ($prefixId === null ? $prefixId : intval($prefixId));
    }

    /**
     * @param callable $extra
     */
    public function addExtraSetup(callable $extra)
    {
        $this->extraSetup[] = $extra;
    }

    /**
     * @param Topic $topic
     * @param array $additionalTopicIds
     * @return bool
     * @throws PrintableException
     */
    public function changeTopics(Topic $topic = null, array $additionalTopicIds = [])
    {
        $actor = \XF::visitor();

        $thread = $this->thread;

        $nodeChanged = ($topic && $thread->node_id !== $topic->node_id);
        $topicChanged = (($thread->topic_id && !$topic) || ($topic && $thread->topic_id != $topic->topic_id));

        foreach ($this->extraSetup as $extra) {
            call_user_func($extra, $thread, $topic);
        }

        if (!$topic) {
            $thread->topic_id = 0;
        } else {
            $thread->topic_id = $topic->topic_id;
        }
        if ($this->prefixId !== null) {
            $thread->prefix_id = $this->prefixId;
        }

        $thread->set('additional_topic_ids', $additionalTopicIds);

        if ($nodeChanged) {
            /** @var \XF\Service\Thread\Mover $mover */
            $mover = $this->service('XF:Thread\Mover', $thread);
            $mover->move($topic->Forum);
        } else {
            $thread->save(true, false);
        }

        if ($topicChanged && $thread->discussion_state == 'visible' && $this->alert && $thread->user_id != $actor->user_id && $thread->discussion_type != 'redirect') {
            /** @var \XF\Repository\Thread $threadRepo */
            $threadRepo = $this->repository('XF:Thread');
            $threadRepo->sendModeratorActionAlert($thread, 'move', $this->alertReason);
        }

        return $topicChanged;
    }
}
