<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2024 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedContentArticle
 * @author     Andreas Dziemba <adziemba@web.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\Widgets;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Widget\AbstractWidget;
use ContaoCommunityAlliance\DcGeneral\Data\MultiLanguageDataProviderInterface;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ArticleWidget
 *
 * @package MetaModels\AttributeTranslatedContentArticleBundle\Widgets
 */
class ContentArticleWidget extends AbstractWidget
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
    protected $subTemplate = 'widget_translatedcontentarticle';

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
     * @var Connection
     */
    private Connection $connection;

    /**
     * The contao input.
     *
     * @var Adapter|Input
     */
    private Adapter|Input $input;

    /**
     * Compat layer.
     *
     * @var \ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat|null
     */

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
        Adapter $input = null,
        TranslatorInterface $translator = null
    ) {
        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
            assert($connection instanceof Connection);
        }
        $this->connection = $connection;

        if (null === $input) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Input adapter is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $input = System::getContainer()->get('contao.framework')?->getAdapter(Input::class);
            assert($input instanceof Adapter);
        }
        $this->input = $input;

        if (null ===  $translator) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Translator is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd

            $translator = System::getContainer()->get('translator');
            assert($translator instanceof TranslatorInterface);
        }
        $this->translator = $translator;

        parent::__construct($arrAttributes, $dcCompat);

        $currentID        = $this->input->get('id');
        $this->hasEmptyId = empty($currentID);
    }

    /**
     * Set an object property
     *
     * @param string $strKey   The property name.
     * @param mixed  $varValue The property value.
     *
     * @return void
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'subTemplate':
                $this->subTemplate = $varValue;
                break;
            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }

    /**
     * Return an object property
     *
     * @param string $strKey The property name.
     *
     * @return string The property value
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'subTemplate':
                return $this->subTemplate;
            default:
        }

        return parent::__get($strKey);
    }

    /**
     * Check whether an object property exists
     *
     * @param string $strKey The property name.
     *
     * @return boolean True if the property exists
     */
    public function __isset($strKey)
    {
        switch ($strKey) {
            case 'subTemplate':
                return isset($this->subTemplate);
            default:
                return parent::__get($strKey);
        }
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
        // Retrieve current language.
        $currentLang = '';
        if (Environment::get('isAjaxRequest')) {
            $currentLang = Input::post('lang');
        } else {
            $dataProvider = $this->getEnvironment()->getDataProvider();
            if ($dataProvider instanceof MultiLanguageDataProviderInterface) {
                $currentLang = $dataProvider->getCurrentLanguage();
            }
        }

        $rootTable    = $this->getRootMetaModelTable($this->strTable);
        $requestToken = System::getContainer()->get('contao.csrf.token_manager')?->getDefaultTokenValue();

        $strQuery = \http_build_query([
                                         'do'          => 'metamodel_' . ($rootTable ?: 'table_not_found'),
                                         'table'       => 'tl_content',
                                         'ptable'      => $this->strTable,
                                         'id'          => $this->currentRecord,
                                         'mid'         => $this->currentRecord,
                                         'slot'        => $this->strName,
                                         'lang'        => $currentLang,
                                         'popup'       => 1,
                                         'nb'          => 1,
                                         'langSupport' => 1,
                                         'rt'          => $requestToken,
                                     ]);

        $contentElements =
            $this->getContentTypesByRecordId($this->currentRecord, $rootTable, $this->strName, $currentLang);

        $content = (new ContaoBackendViewTemplate($this->subTemplate))
            ->setTranslator($this->getEnvironment()->getTranslator())
            ->set('name', $this->strName)
            ->set('id', $this->strId)
            ->set('label', $this->label)
            ->set('readonly', $this->readonly)
            ->set('hasEmptyId', $this->hasEmptyId)
            ->set('link', 'contao?' . $strQuery)
            ->set('elements', $contentElements)
            ->set('lang', $currentLang)
            ->parse();

        return !Environment::get('isAjaxRequest') ? '<div>' . $content . '</div>' : $content;
    }

    /**
     * Get the RootMetaModelTable.
     *
     * @param string $tableName Table name to Check.
     *
     * @return bool|string Returns RootMetaModelTable.
     *
     * @throws \Exception Throws an Exception.
     */
    public function getRootMetaModelTable(string $tableName)
    {
        $tables = [];

        $statement = $this->connection
            ->createQueryBuilder()
            ->select('t.tableName, d.renderType, d.ptable')
            ->from('tl_metamodel', 't')
            ->leftJoin('t', 'tl_metamodel_dca', 'd', '(t.id=d.pid)')
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            $tables[$row['tableName']] = [
                'renderType' => $row['renderType'],
                'ptable'     => $row['ptable'],
            ];
        }

        $getTable = function ($tableName) use (&$getTable, $tables) {
            if (!isset($tables[$tableName])) {
                return false;
            }

            $arrTable = $tables[$tableName];

            switch ($arrTable['renderType']) {
                case 'standalone':
                    return $tableName;

                case 'ctable':
                    return $getTable($arrTable['ptable']);

                default:
                    throw new \Exception('Unexpected case: ' . $arrTable['renderType']);
            }
        };

        return $getTable($tableName);
    }


    /**
     * Retrieve all content elements of this item as parent.
     *
     * @param int|null    $recordId    The record id.
     * @param string      $ptableName  The name of parent tab.
     * @param string      $slotName    The name of slot.
     * @param string|null $currentLang The current language.
     *
     * @return array Returns array with content elements.
     */
    public function getContentTypesByRecordId(
        ?int $recordId,
        string $ptableName,
        string $slotName,
        ?string $currentLang
    ): array {
        $contentElements = [];

        if (empty($recordId) || empty($ptableName)) {
            return $contentElements;
        }

        $statement = $this->connection
            ->createQueryBuilder()
            ->select('t.type, t.invisible, t.start, t.stop')
            ->from('tl_content', 't')
            ->where('t.pid=:pid')
            ->andWhere('t.ptable=:ptable')
            ->andWhere('t.mm_slot=:slot')
            ->andWhere('t.mm_lang=:lang')
            ->orderBy('t.sorting')
            ->setParameter('pid', $recordId)
            ->setParameter('ptable', $ptableName)
            ->setParameter('slot', $slotName)
            ->setParameter('lang', $currentLang)
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            $contentElements[] = [
                'name'        => $this->translator->trans('CTE.' . $row['type'] . '.0', [], 'contao_default'),
                'isInvisible' => $row['invisible']
                                 || ($row['start'] && $row['start'] > time())
                                 || ($row['stop'] && $row['stop'] <= time())
            ];
        }

        return $contentElements;
    }
}
