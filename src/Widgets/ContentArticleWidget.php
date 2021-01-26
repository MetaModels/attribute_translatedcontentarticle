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

namespace MetaModels\AttributeTranslatedContentArticleBundle\Widgets;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Input;
use Contao\System;
use Contao\Widget;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use Doctrine\DBAL\Connection;

/**
 * Class ArticleWidget
 *
 * @package MetaModels\AttributeTranslatedContentArticleBundle\Widgets
 */
class ContentArticleWidget extends Widget
{

    /**
     * Submit user input.
     *
     * @var boolean
     */
    protected $blnSubmitInput = false;

    /**
     * Add a for attribute.
     *
     * @var boolean
     */
    protected $blnForAttribute = false;

    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'be_widget';

    /**
     * Flag if the current entry has an id.
     *
     * @var bool
     */
    protected $hasEmptyId = false;

    /**
     * The language of the current context. If no language support is needed or not set use '-'.
     *
     * @var string
     */
    protected $lang = '-';

    /**
     * The database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The contao input.
     *
     * @var \Contao\CoreBundle\Framework\Adapter|Input
     */
    private $input;

    /**
     * Check if we have an id, if not set a flag.
     * After this check call the parent constructor.
     *
     * @inheritDoc
     */
    public function __construct(
        $arrAttributes = null,
        DcCompat $dcCompat = null,
        Connection $connection = null,
        Adapter $input = null
    ) {
        $this->connection = ($connection ?? System::getContainer()->get('database_connection'));
        $this->input      = (
            $input ?? System::getContainer()->get('contao.framework')->getAdapter(Input::class)
        );

        parent::__construct($arrAttributes);

        $currentID        = $this->input->get('id');
        $this->hasEmptyId = empty($currentID);
    }

    /**
     * Generate the widget and return it as string.
     *
     * @return string Generated String.
     *
     * @throws \Exception Throws Exceptions.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function generate()
    {
        // Update the language.
        $currentLang = $GLOBALS['TL_LANGUAGE'];
        $this->lang  = ($currentLang) ?: '-';

        if (!empty($GLOBALS['TL_LANG']['MSC']['edit'])) {
            $edit = $GLOBALS['TL_LANG']['MSC']['edit'];
        } else {
            $edit = 'Edit';
        }

        // If we have no id, we get some trouble with the modal. So we disabled the button.
        if ($this->hasEmptyId) {
            return sprintf(
                '<p class="tl_help tl_tip">%s</p>' .
                '<button type="button" name="%s" class="tl_submit" disabled>%s</button>',
                $GLOBALS['TL_LANG']['attribute_contentarticle']['missing_id'],
                $this->name,
                $edit
            );
        }

        $strQuery = http_build_query([
            'do'     => 'metamodel_' . $this->getRootMetaModelTable($this->strTable) ?: 'table_not_found',
            'table'  => 'tl_content',
            'ptable' => $this->strTable,
            'id'     => $this->currentRecord,
            'slot'   => $this->strName,
            'lang'   => $this->lang,
            'popup'  => 1,
            'nb'     => 1,
            'rt'     => REQUEST_TOKEN,
        ]);

        return sprintf(
            '<div><p><a href="%s" class="tl_submit" onclick="%s">%s</a></p></div>',
            'contao?' . $strQuery,
            'Backend.openModalIframe({width:850,title:\'' . $this->strLabel . '\',url:this.href});return false',
            $edit
        );
    }

    /**
     * Get the RootMetaModelTable.
     *
     * @param string $strTable Table name to Check.
     *
     * @return bool|string Returns RootMetaModelTable.
     *
     * @throws \Exception Throws an Exception.
     */
    private function getRootMetaModelTable($strTable)
    {
        $arrTables = [];
        $objTables = \Database::getInstance()->execute('
                SELECT tableName, d.renderType, d.ptable
                FROM tl_metamodel AS m
                JOIN tl_metamodel_dca AS d
                ON m.id = d.pid
            ');

        while ($objTables->next()) {
            $arrTables[$objTables->tableName] = [
                'renderType' => $objTables->renderType,
                'ptable'     => $objTables->ptable,
            ];
        }

        $getTable = function ($strTable) use (&$getTable, $arrTables) {
            if (!isset($arrTables[$strTable])) {
                return false;
            }

            $arrTable = $arrTables[$strTable];

            switch ($arrTable['renderType']) {
                case 'standalone':
                    return $strTable;

                case 'ctable':
                    return $getTable($arrTable['ptable']);

                default:
                    throw new \Exception('Unexpected case: ' . $arrTable['renderType']);
            }
        };

        return $getTable($strTable);
    }
}
