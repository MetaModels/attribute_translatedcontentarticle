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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */


namespace MetaModels\AttributeTranslatedContentArticleBundle\Attribute;

use Contao\System;
use MetaModels\Attribute\BaseSimple;
use MetaModels\Attribute\ITranslated;
use MetaModels\IMetaModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This is the AttributeTranslatedContentArticle class for handling article fields.
 */
class TranslatedContentArticle extends BaseSimple implements ITranslated
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
     * SearchForInLanguage
     *
     * @param string $strPattern
     * @param array  $arrLanguages
     *
     * @return string[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function searchForInLanguages($strPattern, $arrLanguages = array())
    {
        // Needed to fake implement ITranslate.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDataType()
    {
        return 'varchar(255) NOT NULL default \'\'';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef              = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'MetaModelAttributeArticle';

        return $arrFieldDef;
    }

    /**
     * SetTranslatedDataFor.
     *
     * @param array  $arrValues   DataArray.
     * @param string $strLangCode LanguageCode.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }

    /**
     * GetTranslatedDataFor.
     *
     * @param $arrIds
     * @param $strLangCode
     *
     * @return mixed[]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
        $strFallbackLanguage = $this->getMetaModel()->isTranslated() ?
            $this->getMetaModel()->getFallbackLanguage() : '-';
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
                    if ($objContent->mm_slot == $strColumn
                        && $objContent->mm_lang == $strLanguage
                    ) {
                        $arrContent[] = $this->getContentElement($objContent->current());
                    } elseif ($objContent->mm_slot == $strColumn
                              && $strLanguage != $strFallbackLanguage
                              && $objContent->mm_lang == $strFallbackLanguage
                    ) {
                        $arrContentFallback[] = $this->getContentElement($objContent->current());
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

    /**
     * UnsetValueFor - Needed to fake implement ITranslate.
     *
     * @param $arrIds
     * @param $strLangCode
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        // Needed to fake implement ITranslate.
    }

    /**
     * {@inheritDoc}
     */
    private function getContentElement($objContent)
    {
        return \Controller::getContentElement($objContent);
    }
}
