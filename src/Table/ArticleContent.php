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
 * @author     Marc Reimann <reimann@mediendepot-ruhr.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\Table;

use Contao\BackendUser;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use MetaModels\Factory;
use MetaModels\ITranslatedMetaModel;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ArticleContent
{
    /**
     * The database connection.
     *
     * @var Connection
     */
    private Connection $connection;

    /**
     * Symfony session object.
     *
     * @var Session
     */
    private Session $session;

    /**
     * MetaModels factory.
     *
     * @var Factory
     */
    private Factory $factory;

    /**
     * The translator interface.
     *
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * The ArticleContent constructor.
     *
     * @param Connection|null $connection          The connection.
     * @param Session|null $session                The session.
     * @param Factory|null $factory                The factory.
     * @param TranslatorInterface|null $translator The translator interface.
     */
    public function __construct(
        Connection $connection = null,
        Session $session = null,
        Factory $factory = null,
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

        if (null === $session) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Not passing an "Session" is deprecated.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $session = System::getContainer()->get('session');
            assert($session instanceof Session);
        }
        $this->session = $session;

        if (null === $factory) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Not passing an "Factory" is deprecated.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $factory = System::getContainer()->get('metamodels.factory');
            assert($factory instanceof Factory);
        }
        $this->factory = $factory;

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
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @return string The icon url with all information.
     */
    public function toggleIcon(): string
    {
        $controller = new \tl_content();

        return \call_user_func_array([$controller, 'toggleIcon'], \func_get_args());
    }

    /**
     * Save Data Container.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     * @throws Exception
     */
    public function save(DataContainer $dataContainer): void
    {
        $lang = \Input::get('lang');
        if (empty($lang)) {
            $lang = '';
        }

        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.mm_slot', ':slot')
            ->set('t.mm_lang', ':lang')
            ->where('t.id=:id')
            ->setParameter('slot', Input::get('slot'))
            ->setParameter('lang', $lang)
            ->setParameter('id', $dataContainer->id)
            ->executeQuery();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param string        $insertId      The id of the new entry.
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception If a parameter is missing.
     * @SuppressWarnings(PHPMD)
     */
    public function updateCopyData(string $insertId, DataContainer $dataContainer): void
    {
        $pid    = Input::get('mid');
        $ptable = Input::get('ptable');
        $slot   = Input::get('slot');
        $lang   = Input::get('lang');

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode  = 'Could not update row because one of the data are missing. ';
            $errorCode .= 'Insert ID: %s, Pid: %s, Parent table: %s, Slot: %s, Lang: %s';
            throw new \RuntimeException(
                \sprintf(
                    $errorCode,
                    $insertId,
                    $pid,
                    $ptable,
                    $slot,
                    $lang
                )
            );
        }

        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.pid', ':pid')
            ->set('t.ptable', ':ptable')
            ->set('t.mm_slot', ':slot')
            ->set('t.mm_lang', ':lang')
            ->where('t.id=:id')
            ->setParameter('pid', $pid)
            ->setParameter('ptable', $ptable)
            ->setParameter('slot', $slot)
            ->setParameter('lang', $lang)
            ->setParameter('id', $insertId)
            ->executeQuery();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception If a parameter is missing.
     */
    public function updateCutData(DataContainer $dataContainer): void
    {
        $pid      = Input::get('mid');
        $ptable   = Input::get('ptable');
        $slot     = Input::get('slot');
        $lang     = Input::get('lang');
        $insertId = $dataContainer->id;

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode  = 'Could not update row because one of the data are missing. ';
            $errorCode .= 'Insert ID: %s, Pid: %s, Parent table: %s, Slot: %s, Lang: %s';
            throw new \RuntimeException(
                \sprintf(
                    $errorCode,
                    $insertId,
                    $pid,
                    $ptable,
                    $slot,
                    $lang
                )
            );
        }

        $this->connection
            ->createQueryBuilder()
            ->update('tl_content', 't')
            ->set('t.pid', ':pid')
            ->set('t.ptable', ':ptable')
            ->set('t.mm_slot', ':slot')
            ->set('t.mm_lang', ':lang')
            ->where('t.id=:id')
            ->setParameter('pid', $pid)
            ->setParameter('ptable', $ptable)
            ->setParameter('slot', $slot)
            ->setParameter('lang', $lang)
            ->setParameter('id', $insertId)
            ->executeQuery();
    }

    /**
     * Check permissions to edit table tl_content.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    public function checkPermission(DataContainer $dataContainer): void
    {
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        if (BackendUser::getInstance()->isAdmin) {
            return;
        }

        $strParentTable = Input::get('ptable');
        $strParentTable = \preg_replace('#[^A-Za-z0-9_]#', '', $strParentTable);

        // Check the current action.
        switch (Input::get('act')) {
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                /** @psalm-suppress UndefinedMagicPropertyFetch */
                $objCes = $this->connection
                    ->createQueryBuilder()
                    ->select('t.id')
                    ->from('tl_content', 't')
                    ->where('t.ptable=:parentTable')
                    ->andWhere('t.pid=:currentId')
                    ->setParameter('parentTable', $strParentTable)
                    ->setParameter('currentId', $dataContainer->currentPid)
                    ->executeQuery();

                $contaoBeSession = $this->session->getBag('contao_backend');
                assert($contaoBeSession instanceof AttributeBagInterface);
                $contaoBeSession->set(
                    'CURRENT',
                    \array_diff($contaoBeSession->get('CURRENT') ?? [], $objCes->fetchFirstColumn())
                );
                break;
            case 'paste':
            case '':
            case 'create':
            case 'select':
            case 'cut':
            case 'copy':
            default:
        }
    }

    /**
     * Main language content.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws Exception
     *
     * @deprecated The method does not work correctly and is too complex to implement.
     */
    public function addMainLangContent(DataContainer $dataContainer): void
    {
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        $objMetaModel = $this->factory->getMetaModel($dataContainer->parentTable);

        $intId           = $dataContainer->id;
        $ptable          = $dataContainer->parentTable;
        $strSlot         = Input::get('slot');
        $strLanguage     = Input::get('lang');

        if ($objMetaModel instanceof ITranslatedMetaModel) {
            $strMainLanguage = $objMetaModel->getMainLanguage();
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $strMainLanguage = $objMetaModel->getFallbackLanguage();
        }

        // Check if same language.
        if ($strLanguage === $strMainLanguage) {
            Message::addError($this->translator->trans('ERR.copy_same_language', [], 'contao_default'));
            Controller::redirect(\System::getReferer());

            return;
        }

        $objContent = $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('tl_content', 't')
            ->where('t.pid=:id')
            ->andWhere('t.ptable=:ptable')
            ->andWhere('t.mm_slot=:slot')
            ->andWhere('t.mm_lang=:lang')
            ->setParameter('id', $intId)
            ->setParameter('ptable', $ptable)
            ->setParameter('slot', $strSlot)
            ->setParameter('lang', $strMainLanguage)
            ->executeQuery();

        $counter = 0;

        while ($row = $objContent->fetchAllAssociative()) {
            $arrContent            = $row;
            $arrContent['mm_lang'] = $strLanguage;
            unset($arrContent['id']);

            $this->connection
                ->createQueryBuilder()
                ->insert('tl_content')
                ->setParameters($arrContent)
                ->executeQuery();

            $counter++;
        }

        Message::addInfo($this->translator->trans('MSC.copy_elements', [0 => $counter], 'contao_default'));
        Controller::redirect(\System::getReferer());
    }
}
