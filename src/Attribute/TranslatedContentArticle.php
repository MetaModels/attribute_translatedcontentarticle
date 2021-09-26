<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Marc Reimann <reimann@mediendepot-ruhr.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */


namespace MetaModels\AttributeTranslatedContentArticleBundle\Attribute;

use Contao\Controller;
use Contao\System;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\IMetaModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This is the AttributeTranslatedContentArticle class for handling article fields.
 */
class TranslatedContentArticle extends TranslatedReference
{

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Array of Call Ids.
     *
     * @var array
     */
    private static $arrCallIds = [];

    /**
     * Create a new instance.
     *
     * @param IMetaModel               $objMetaModel The MetaModel instance this attribute belongs to.
     * @param array                    $arrData      The attribute information array.
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        IMetaModel $objMetaModel,
        $arrData = [],
        EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($objMetaModel, $arrData);

        if (null === $eventDispatcher) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @\trigger_error(
                'Event dispatcher is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $eventDispatcher = System::getContainer()->get('event_dispatcher');
        }

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueTable()
    {
        // Needed to fake implement ITranslate.
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef              = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'translatedcontentarticle';

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($arrIds)
    {
        // Needed to fake implement BaseComplex.
    }

    /**
     * {@inheritdoc}
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }


    /**
     * {@inheritdoc}
     */
    public function searchForInLanguages($strPattern, $arrLanguages = array())
    {
        // Needed to fake implement ITranslate.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD)
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        // Generate only for frontend (speeds up the backend a little)
        if (TL_MODE == 'BE') {
            return [];
        }

        $strTable            = $this->getMetaModel()->getTableName();
        $strColumn           = $this->getColName();
        $strLanguage         = $this->getMetaModel()->isTranslated() ? $strLangCode : '-';
        $strFallbackLanguage = $this->getMetaModel()->isTranslated()
            ? $this->getMetaModel()->getFallbackLanguage()
            : '-';
        $arrData             = [];

        foreach ($arrIds as $intId) {
            // Continue if it's a recursive call
            $strCallId = $strTable . '_' . $strColumn . '_' . $strLanguage . '_' . $intId;
            if (isset(static::$arrCallIds[$strCallId])) {
                $arrData[$intId]['value'] = sprintf('RECURSION: %s', $strCallId);
                continue;
            }
            static::$arrCallIds[$strCallId] = true;

            $objContent         = \ContentModel::findPublishedByPidAndTable($intId, $strTable);
            $arrContent         = [];
            $arrContentFallback = [];

            if ($objContent !== null) {
                while ($objContent->next()) {
                    if ($objContent->mm_slot == $strColumn && $objContent->mm_lang == $strLanguage) {
                        $arrContent[] = Controller::getContentElement($objContent->current());
                    } elseif ($objContent->mm_slot == $strColumn
                        && $strLanguage != $strFallbackLanguage
                        && $objContent->mm_lang == $strFallbackLanguage
                    ) {
                        $arrContentFallback[] = Controller::getContentElement($objContent->current());
                    }
                }
            }

            if (!empty($arrContent)) {
                $arrData[$intId]['value'] = $arrContent;
            } elseif (!empty($arrContentFallback)) {
                $arrData[$intId]['value'] = $arrContentFallback;
            } else {
                $arrData[$intId]['value'] = [];
            }

            unset(static::$arrCallIds[$strCallId]);
        }

        return $arrData;
    }
}
