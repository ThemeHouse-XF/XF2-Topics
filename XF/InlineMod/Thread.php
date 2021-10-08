<?php

namespace ThemeHouse\Topics\XF\InlineMod;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\InlineMod
 */
class Thread extends XFCP_Thread
{
    /**
     * @return array|\XF\InlineMod\AbstractAction[]
     */
    public function getPossibleActions()
    {
        /** @var array $actions */
        $actions = parent::getPossibleActions();

        if (\XF::options()->thtopics_enableTopics) {
            $actions['thtopics_changeTopics'] = $this->getActionHandler('ThemeHouse\Topics:Thread\ChangeTopic');
        }

        return $actions;
    }
}
