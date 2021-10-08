<?php

namespace ThemeHouse\Topics\ThemeHouse\QAForums\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Class Thread
 * @package ThemeHouse\Topics\ThemeHouse\QAForums\XF\Pub\Controller
 */
class Thread extends XFCP_Thread
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Error|\XF\Mvc\Reply\Redirect|View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionThreadVotes(ParameterBag $params)
    {
        $response = parent::actionThreadVotes($params);

        if ($response instanceof View) {
            /** @var \ThemeHouse\Topics\XF\Entity\Thread $thread */
            $thread = $response->getParam('thread');

            if ($thread->topic_id) {
                $topic = $thread->Topic;
                $response->setParam('topic', $topic);
            }
        }

        return $response;
    }
}
