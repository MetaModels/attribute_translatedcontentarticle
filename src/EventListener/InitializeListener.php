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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\EventListener;

use Contao\Input;
use Contao\CoreBundle\Routing\ScopeMatcher;
use MetaModels\ViewCombination\ViewCombination;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class InitializeListener
 *
 * @package MetaModels\AttributeTranslatedContentArticleBundle\EventListener
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InitializeListener
{
    /**
     * The TokenStorageInterface.
     *
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * The AuthenticationTrustResolverInterface.
     *
     * @var AuthenticationTrustResolverInterface
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    private AuthenticationTrustResolverInterface $authenticationTrustResolver;

    /**
     * The ScopeMatcher.
     *
     * @var ScopeMatcher
     */
    private ScopeMatcher $scopeMatcher;

    /**
     * The ViewCombination.
     *
     * @var ViewCombination
     */
    private ViewCombination $viewCombination;

    /**
     * Constructor.
     *
     * @param TokenStorageInterface                $tokenStorage                The token storage.
     * @param AuthenticationTrustResolverInterface $authenticationTrustResolver The authentication resolver.
     * @param ScopeMatcher                         $scopeMatcher                The scope matche.
     * @param ViewCombination                      $viewCombination             The view combination.
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationTrustResolverInterface $authenticationTrustResolver,
        ScopeMatcher $scopeMatcher,
        ViewCombination $viewCombination
    ) {
        $this->tokenStorage                = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->scopeMatcher                = $scopeMatcher;
        $this->viewCombination             = $viewCombination;
    }

    /**
     * Replaces the current session data with the stored session data.
     *
     * @param RequestEvent $event The event.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->scopeMatcher->isBackendMainRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        /** @psalm-suppress DeprecatedMethod */
        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $localMenu = &$GLOBALS['BE_MOD'];
        $this->addBackendModules($localMenu);
    }

    /**
     * Add the modules to the backend sections.
     *
     * @param array $localMenu Reference to the global array.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addBackendModules(&$localMenu)
    {
        $strModule      = Input::get('do');
        $strTable       = Input::get('table');
        $blnLangSupport = Input::get('langSupport');

        if (\str_starts_with($strModule, 'metamodel_') && $strTable === 'tl_content' && $blnLangSupport === '1') {
            $needsToBeAdded = true;
            foreach ($GLOBALS['BE_MOD'] as $key => $mod) {
                if (isset($mod[$strModule])) {
                    $localMenu[$key][$strModule]['tables'][] = 'tl_content';
                    $localMenu[$key][$strModule]['callback'] = null;
                    $needsToBeAdded                          = false;
                    break;
                }
            }
            if ($needsToBeAdded) {
                $localMenu['content'][$strModule]['tables'][]           = 'tl_content';
                $localMenu['content'][$strModule]['callback']           = null;
                $localMenu['content'][$strModule]['addMainLangContent'] = [
                    'MetaModels\\AttributeTranslatedContentArticleBundle\\Table\\ArticleContent',
                    'addMainLangContent'
                ];
            }
        }
    }
}
