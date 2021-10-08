<?php

namespace ThemeHouse\Topics\XF\Admin\Controller;

use XF\Mvc\Reply\View;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Admin\Controller
 */
class Thread extends XFCP_Thread
{
    /**
     * @return View
     * @throws \XF\Mvc\Reply\Exception
     */
    public function actionBatchUpdateConfirm()
    {
        $reply = parent::actionBatchUpdateConfirm();

        if ($reply instanceof View) {
            /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
            $topicRepo = \XF::em()->getRepository('ThemeHouse\Topics:Topic');
            $topics = $topicRepo->getTopics();

            $reply->setParam('topics', $topics);
        }

        return $reply;
    }
}
