<?php

namespace ThemeHouse\Topics\Entity;

use XF\Entity\Phrase;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null media_gallery_filter_id
 * @property int display_order
 * @property bool active
 * @property array criteria
 *
 * GETTERS
 * @property \XF\Phrase|string title
 *
 * RELATIONS
 * @property \XF\Entity\Phrase MasterTitle
 */
class MediaGalleryFilter extends Entity
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_th_topics_media_gallery_filter';
        $structure->shortName = 'ThemeHouse\Topics:MediaGalleryFilter';
        $structure->primaryKey = 'media_gallery_filter_id';
        $structure->columns = [
            'media_gallery_filter_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'display_order' => ['type' => self::UINT, 'default' => 1],
            'active' => ['type' => self::BOOL, 'default' => true],
            'style_id' => ['type' => self::UINT, 'default' => 0],
            'criteria' => ['type' => self::JSON_ARRAY, 'default' => []],
        ];
        $structure->getters = [
            'title' => true,
        ];
        $structure->relations = [
            'MasterTitle' => [
                'entity' => 'XF:Phrase',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['language_id', '=', 0],
                    ['title', '=', 'media_gallery_filter.', '$media_gallery_filter_id']
                ]
            ],
        ];

        return $structure;
    }

    /**
     * @return \XF\Phrase|string
     */
    public function getTitle()
    {
        $mediaGalleryFilterPhrase = \XF::phrase('media_gallery_filter.' . $this->media_gallery_filter_id);
        return $mediaGalleryFilterPhrase->render('html', ['nameOnInvalid' => false]) ?: '';
    }

    /**
     * @return \XF\Entity\Phrase|Entity
     */
    public function getMasterPhrase()
    {
        $phrase = $this->MasterTitle;
        if (!$phrase) {
            /** @var Phrase $phrase */
            $phrase = $this->_em->create('XF:Phrase');
            $phrase->title = $this->_getDeferredValue(function () {
                return 'media_gallery_filter.' . $this->media_gallery_filter_id;
            });
            $phrase->language_id = 0;
            $phrase->addon_id = '';
        }

        return $phrase;
    }

    /**
     * @return \ThemeHouse\Topics\Repository\MediaGalleryFilter|\XF\Mvc\Entity\Repository
     */
    protected function getMediaGalleryFilterRepo()
    {
        return $this->repository('ThemeHouse\Topics:MediaGalleryFilter');
    }
}
