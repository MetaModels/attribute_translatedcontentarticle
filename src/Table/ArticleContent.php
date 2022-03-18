<?php

/**
 * This file is part of MetaModels/attribute_translatedcontentarticle.
 *
 * (c) 2012-2021 The MetaModels team.
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
 * @copyright  2012-2021 The MetaModels team.
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ArticleContent
{
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

        Database::getInstance()
                ->prepare('UPDATE tl_content SET mm_slot=?, mm_lang=? WHERE id=?')
                ->execute(Input::get('slot'), $lang, $dataContainer->id);
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

        Database::getInstance()
                ->prepare('UPDATE tl_content SET pid=?, ptable=?, mm_slot=?, mm_lang=? WHERE id=?')
                ->execute(
                    $pid,
                    $ptable,
                    $slot,
                    $lang,
                    $insertId
                );
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

        Database::getInstance()
                ->prepare('UPDATE tl_content SET pid=?, ptable=?, mm_slot=?, mm_lang=? WHERE id=?')
                ->execute(
                    $pid,
                    $ptable,
                    $slot,
                    $lang,
                    $insertId
                );
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
            $objCes = Database::getInstance()
                              ->prepare("SELECT cteAlias 
                                    FROM tl_content 
                                    WHERE (ptable='tl_article' OR ptable='') 
                                      AND type='alias'")
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
                if ((Input::get('act') == 'cutAll' ||
                        Input::get('act') == 'copyAll') &&
                    !$this->checkAccessToElement(\Input::get('pid'), $strParentTable)) {
                    $this->redirect('contao?act=error');
                }

                $objCes = Database::getInstance()
                                  ->prepare('SELECT id FROM tl_content WHERE ptable=? AND pid=?')
                                  ->execute($strParentTable, CURRENT_ID);

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
     * @param string $ptable   Parent Table.
     *
     * @param bool  $blnIsPid Is the ID a PID.
     *
     * @return bool
     */
    protected function checkAccessToElement(int $accessId, string $ptable, bool $blnIsPid = false)
    {
        $strScript = Environment::get('script');

        // Workaround for missing ptable when called via Page/File Picker
        if ($strScript != 'contao/page.php' && $strScript != 'contao/file.php') {
            if ($blnIsPid) {
                $objContent = Database::getInstance()
                                      ->prepare('SELECT 1 FROM `' . $ptable . '` WHERE id=?')
                                      ->limit(1)
                                      ->execute($accessId);
            } else {
                $objContent = Database::getInstance()
                                      ->prepare('SELECT 1 FROM tl_content WHERE id=? AND ptable=?')
                                      ->limit(1)
                                      ->execute($accessId, $ptable);
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

        $objContent = Database::getInstance()
                              ->prepare('SELECT * FROM tl_content WHERE pid=? AND ptable=? AND mm_slot=? AND mm_lang=?')
                              ->execute($intId, $strParentTable, $strSlot, $strMainLanguage);

        $counter = 0;
        while ($objContent->next()) {
            $arrContent            = $objContent->row();
            $arrContent['mm_lang'] = $strLanguage;
            unset($arrContent['id']);

            Database::getInstance()
                    ->prepare('INSERT INTO tl_content %s')
                    ->set($arrContent)
                    ->execute();
            $counter++;
        }

        // TO DO Message::addError übersetzen
        Message::addInfo(sprintf('%s Element(e) kopiert', $counter));
        Controller::redirect(\System::getReferer());
    }
}
