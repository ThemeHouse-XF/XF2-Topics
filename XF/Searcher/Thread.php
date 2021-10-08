<?php

namespace ThemeHouse\Topics\XF\Searcher;

use XF\Mvc\Entity\Finder;

/**
 * Class Thread
 * @package ThemeHouse\Topics\XF\Searcher
 */
class Thread extends XFCP_Thread
{
    /**
     * @return array
     */
    public function getFormData()
    {
        $formData = parent::getFormData();

        /** @var \ThemeHouse\Topics\Repository\Topic $topicRepo */
        $topicRepo = $this->em->getRepository('ThemeHouse\Topics:Topic');
        $topics = $topicRepo->getTopics();

        $formData['topics'] = $topics;

        return $formData;
    }

    /**
     * @return array
     */
    public function getFormDefaults()
    {
        $formDefaults = parent::getFormDefaults();

        $formDefaults['topic_id'] = -1;
        $formDefaults['additional_topic_ids'] = [];

        return $formDefaults;
    }

    /**
     * @param Finder $finder
     * @param $key
     * @param $value
     * @param $column
     * @param $format
     * @param $relation
     * @return bool
     */
    protected function applySpecialCriteriaValue(Finder $finder, $key, $value, $column, $format, $relation)
    {
        if ($key == 'topic_id' && $value == -1) {
            // any topic so skip condition
            return true;
        }

        return parent::applySpecialCriteriaValue($finder, $key, $value, $column, $format, $relation);
    }
}
