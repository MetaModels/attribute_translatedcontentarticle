<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2022 The MetaModels team.
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
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\Table;

use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\Session;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ArticleContent
{
    /**
     * The database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The ArticleContent constructor.
     *
     * @param Connection|null $connection
     */
    public function __construct(Connection $connection = null)
    {
        if (null === $connection) {
            // @codingStandardsIgnoreStart
            @trigger_error(
                'Connection is missing. It has to be passed in the constructor. Fallback will be dropped.',
                E_USER_DEPRECATED
            );
            // @codingStandardsIgnoreEnd
            $connection = System::getContainer()->get('database_connection');
        }
        $this->connection = $connection;
    }

    /**
     * Return the "toggle visibility" button
     *
     * @return string The icon url with all information.
     */
    public function toggleIcon()
    {
        $controller = new \tl_content();

        return call_user_func_array([$controller, 'toggleIcon'], func_get_args());
    }

    /**
     * Save Data Container.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     */
    public function save(DataContainer $dataContainer)
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
            ->execute();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param string        $insertId      The id of the new entry.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws \RuntimeException If a parameter is missing.
     *
     * @SuppressWarnings(PHPMD)
     */
    public function updateCopyData(string $insertId, DataContainer $dataContainer)
    {
        $pid    = Input::get('mid');
        $ptable = Input::get('ptable');
        $slot   = Input::get('slot');
        $lang   = Input::get('lang');

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode = 'Could not update row because one of the data are missing. ';
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
            ->execute();
    }

    /**
     * Update the data from copies and set the context like pid, parent table, slot.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     *
     * @throws \RuntimeException If a parameter is missing.
     */
    public function updateCutData(DataContainer $dataContainer)
    {
        $pid      = Input::get('mid');
        $ptable   = Input::get('ptable');
        $slot     = Input::get('slot');
        $lang     = Input::get('lang');
        $insertId = $dataContainer->id;

        if (empty($pid) || empty($ptable) || empty($slot)) {
            $errorCode = 'Could not update row because one of the data are missing. ';
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
            ->execute();
    }

    /**
     * Check permissions to edit table tl_content.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function checkPermission()
    {
        /** @var SessionInterface $objSession */
        $objSession = System::getContainer()->get('session');

        // Prevent deleting referenced elements (see #4898)
        if (\Input::get('act') == 'deleteAll') {
            $objCes = $this->connection
                ->createQueryBuilder()
                ->select('t.cteAlias')
                ->from('tl_content', 't')
                ->where('t.ptable=\'tl_article\' OR t.ptable=\'\'')
                ->andWhere('t.type=\'alias\'')
                ->execute();

            $session                   = $objSession->all();
            $session['CURRENT']['IDS'] = \array_diff($session['CURRENT']['IDS'], $objCes->fetchEach('cteAlias'));
            $objSession->replace($session);
        }

        if (BackendUser::getInstance()->isAdmin) {
            return;
        }

        $strParentTable = Input::get('ptable');
        $strParentTable = preg_replace('#[^A-Za-z0-9_]#', '', $strParentTable);

        // Check the current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow paste
                break;
            case '':
            case 'create':
            case 'select':
                // Check access to the article
                if (!$this->checkAccessToElement(CURRENT_ID, $strParentTable, true)) {
                    Backend::redirect('contao?act=error');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                // Check access to the parent element if a content element is moved
                if ((Input::get('act') == 'cutAll'
                     || Input::get('act') == 'copyAll')
                    && !$this->checkAccessToElement(\Input::get('pid'), $strParentTable)) {
                    $this->redirect('contao?act=error');
                }

                $objCes = $this->connection
                    ->createQueryBuilder()
                    ->select('t.id')
                    ->from('tl_content', 't')
                    ->where('t.ptable=:parentTable')
                    ->andWhere('t.pid=:currentId')
                    ->setParameter('parentTable', $strParentTable)
                    ->setParameter('currentId', CURRENT_ID)
                    ->execute();

                $session                   = Session::getInstance()->getData();
                $session['CURRENT']['IDS'] = array_intersect(
                    $session['CURRENT']['IDS'],
                    $objCes->fetchEach('id')
                );
                $objSession->replace($session);
                break;

            case 'cut':
            case 'copy':
                // Check access to the parent element if a content element is moved
                if (!$this->checkAccessToElement(Input::get('pid'), $strParentTable)) {
                    Backend::redirect('contao?act=error');
                }
            // NO BREAK STATEMENT HERE
            default:
                // Check access to the content element
                if (!$this->checkAccessToElement(Input::get('id'), $strParentTable)) {
                    Backend::redirect('contao?act=error');
                }
                break;
        }
    }

    /**
     * Check access to a particular content element.
     *
     * @param int   $accessId Check ID.
     *
     * @param array $ptable   Parent Table.
     *
     * @param bool  $blnIsPid Is the ID a PID.
     *
     * @return bool
     */
    protected function checkAccessToElement(int $accessId, array $ptable, bool $blnIsPid = false)
    {
        $strScript = Environment::get('script');

        // Workaround for missing ptable when called via Page/File Picker
        if ($strScript != 'contao/page.php' && $strScript != 'contao/file.php') {
            if ($blnIsPid) {
                $objContent = $this->connection
                    ->createQueryBuilder()
                    ->select(1)
                    ->from($ptable, 't')
                    ->where('t.id=:id')
                    ->setParameter('id', $accessId)
                    ->setMaxResults(1)
                    ->execute();
            } else {
                $objContent = $this->connection
                    ->createQueryBuilder()
                    ->select(1)
                    ->from('tl_content', 't')
                    ->where('t.id=:id')
                    ->andWhere('t.ptable=:ptable')
                    ->setParameter('id', $accessId)
                    ->setParameter('ptable', $ptable)
                    ->setMaxResults(1)
                    ->execute();
            }
        }

        // Invalid ID
        if ($objContent->numRows < 1) {
            System::log('Invalid content element ID ' . $accessId, __METHOD__, TL_ERROR);

            return false;
        }

        return true;
    }

    /**
     * Main Language Content.
     *
     * @param DataContainer $dataContainer The DC Driver.
     *
     * @return void
     */
    public function addMainLangContent(DataContainer $dataContainer)
    {
        $factory = System::getContainer()->get('metamodels.factory');
        /** @var \MetaModels\IFactory $factory */
        $objMetaModel = $factory->getMetaModel($dataContainer->parentTable);

        $intId           = $dataContainer->id;
        $strParentTable  = $dataContainer->parentTable;
        $strSlot         = Input::get('slot');
        $strLanguage     = Input::get('lang');
        $strMainLanguage = $objMetaModel->getFallbackLanguage();

        // To DO Message::addError übersetzen
        if ($strLanguage == $strMainLanguage) {
            Message::addError('Hauptsprache kann nicht in die Hauptsprache kopiert werden.');
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
            ->execute();

        $counter = 0;

        while ($row = $objContent->fetch(\PDO::FETCH_ASSOC)) {
            $arrContent            = $row;
            $arrContent['mm_lang'] = $strLanguage;
            unset($arrContent['id']);

            $this->connection
                ->createQueryBuilder()
                ->insert('tl_content')
                ->setParameters($arrContent)
                ->execute();

            $counter++;
        }

        // TO DO Message::addError übersetzen
        Message::addInfo(sprintf('%s Element(e) kopiert', $counter));
        Controller::redirect(\System::getReferer());
    }
}
