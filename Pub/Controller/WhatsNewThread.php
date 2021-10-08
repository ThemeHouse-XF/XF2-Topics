<?php

namespace ThemeHouse\Topics\Pub\Controller;

use ThemeHouse\Topics\XF\FindNew\Thread;
use XF\FindNew\AbstractHandler;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractWhatsNewFindType;

/**
 * Class WhatsNewThread
 * @package ThemeHouse\Topics\Pub\Controller
 */
class WhatsNewThread extends AbstractWhatsNewFindType
{
    /**
     * @param ParameterBag $params
     * @return \XF\Mvc\Reply\Redirect
     */
    public function actionIndex(ParameterBag $params)
    {
        if (!$this->options()->thtopics_showNewThreadsPage) {
            $this->setSectionContext('whatsNew');
            return $this->notFound();
        }

        return parent::actionIndex($params);
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        return 'thread';
    }

    /**
     * @param AbstractHandler $handler
     * @param array $filters
     * @return \XF\Mvc\Reply\Redirect
     */
    protected function triggerNewFindNewAction(AbstractHandler $handler, array $filters)
    {
        /** @var Thread $handler */
        $handler->setOrderBy('post_date', 'DESC');
        $handler->setRoute('whats-new/threads');

        return parent::triggerNewFindNewAction($handler, $filters);
    }
}
