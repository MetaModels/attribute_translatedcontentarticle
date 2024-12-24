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
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcontentarticle/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedContentArticleBundle\FileUsage;

use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\Validator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use InspiredMinds\ContaoFileUsage\Provider\FileUsageProviderInterface;
use InspiredMinds\ContaoFileUsage\Result\ResultInterface;
use InspiredMinds\ContaoFileUsage\Result\ResultsCollection;
use MetaModels\AttributeTranslatedContentArticleBundle\Attribute\TranslatedContentArticle;
use MetaModels\CoreBundle\FileUsage\MetaModelsTranslatedMultipleResult;
use MetaModels\CoreBundle\FileUsage\MetaModelsTranslatedSingleResult;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function preg_match_all;
use function preg_quote;
use function str_replace;
use function urldecode;

/**
 * This class supports the Contao extension 'file usage'.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class FileUsageProvider implements FileUsageProviderInterface
{
    // phpcs:disable
    private const INSERT_TAG_PATTERN = '~{{(file|picture|figure)::([a-f0-9]{8}-[a-f0-9]{4}-1[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})(([|?])[^}]+)?}}~';
    // phpcs:enable

    private string $pathPattern = '~(href|src)\s*=\s*"(__contao_upload_path__/.+?)([?"])~';

    private string $refererId = '';

    public function __construct(
        private readonly IFactory $factory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly Connection $connection,
        private readonly string $csrfTokenName,
        string $uploadPath,
    ) {
        $this->pathPattern = str_replace('__contao_upload_path__', preg_quote($uploadPath, '~'), $this->pathPattern);
    }

    public function find(): ResultsCollection
    {
        $this->refererId = $this->requestStack->getCurrentRequest()?->attributes->get('_contao_referer_id') ?? '';

        $allTables = $this->factory->collectNames();

        $collection = new ResultsCollection();
        foreach ($allTables as $table) {
            $collection->mergeCollection($this->processTable($table));
        }

        return $collection;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function processTable(string $table): ResultsCollection
    {
        $collection = new ResultsCollection();
        $metaModel  = $this->factory->getMetaModel($table);
        assert($metaModel instanceof IMetaModel);

        Controller::loadDataContainer('tl_content');
        $fields = $GLOBALS['TL_DCA']['tl_content']['fields'] ?? [];

        $attributes = [];
        foreach ($metaModel->getAttributes() as $attribute) {
            if (!$attribute instanceof TranslatedContentArticle) {
                continue;
            }
            $attributes[] = $attribute->getColName();
        }
        if ([] === $attributes) {
            return $collection;
        }

        $results = $this->connection->createQueryBuilder()
            ->select('t.*')
            ->from('tl_content', 't')
            ->where('t.ptable=:ptable')
            ->andWhere('t.mm_slot IN (:slots)')
            ->setParameter('ptable', $table)
            ->setParameter('slots', $attributes, ArrayParameterType::STRING)
            ->executeQuery();

        if (!$results instanceof Result) {
            return $collection;
        }

        foreach ($results->iterateAssociative() as $result) {
            foreach ($fields as $field => $config) {
                if (empty($fieldContent = $result[$field])) {
                    continue;
                }
                $attributeName = $result['mm_slot'];
                $itemId        = $result['pid'];
                $language      = $result['mm_lang'];

                // Check file columns.
                if ('fileTree' === ($config['inputType'] ?? '')) {
                    $collection->mergeCollection(
                        $this->extractFromFileTree(
                            $config['eval'],
                            $fieldContent,
                            $table,
                            $attributeName,
                            $itemId,
                            $language
                        )
                    );
                    continue;
                }

                // Check all other columns.
                preg_match_all(self::INSERT_TAG_PATTERN, $fieldContent, $matches);
                foreach ($matches[2] ?? [] as $uuid) {
                    $collection->addResult(
                        $uuid,
                        $this->createFileResult($table, $attributeName, $itemId, $language, false)
                    );
                }

                if (
                    '' !== $this->pathPattern
                    && false !== preg_match_all($this->pathPattern, $fieldContent, $matches)
                ) {
                    foreach ($matches[2] ?? [] as $path) {
                        $file = FilesModel::findByPath(urldecode($path));

                        if (null === $file || null === $file->uuid) {
                            continue;
                        }

                        $collection->addResult(
                            StringUtil::binToUuid($file->uuid),
                            $this->createFileResult($table, $attributeName, $itemId, $language, false)
                        );
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * @param array{multiple?: bool, orderField?: string, ...} $eval
     * @param string                                           $fieldContent
     * @param string                                           $table
     * @param string                                           $attributeName
     * @param string                                           $itemId
     * @param string                                           $language
     *
     * @return ResultsCollection
     */
    public function extractFromFileTree(
        array $eval,
        string $fieldContent,
        string $table,
        string $attributeName,
        string $itemId,
        string $language
    ): ResultsCollection {
        $collection = new ResultsCollection();
        if ($eval['multiple'] ?? false) {
            $uuids = StringUtil::deserialize($fieldContent, true);
            $collection->mergeCollection(
                $this->addMultipleFileReferences($uuids, $table, $attributeName, $itemId, $language)
            );
            if (false !== ($orderField = ($eval['orderField'] ?? false))) {
                $uuids = StringUtil::deserialize($orderField, true);
                $collection->mergeCollection(
                    $this->addMultipleFileReferences($uuids, $table, $attributeName, $itemId, $language)
                );
            }

            return $collection;
        }

        $uuid = $fieldContent;
        if (Validator::isBinaryUuid($uuid)) {
            $uuid = StringUtil::binToUuid($uuid);
        }
        if (Validator::isStringUuid($uuid)) {
            $collection->addResult($uuid, $this->createFileResult($table, $attributeName, $itemId, $language, false));
        }

        return $collection;
    }

    private function addMultipleFileReferences(
        array $fileUuids,
        string $tableName,
        string $attributeName,
        string $itemId,
        string $language,
    ): ResultsCollection {
        $collection = new ResultsCollection();
        foreach ($fileUuids as $uuid) {
            $collection->addResult(
                $uuid,
                $this->createFileResult($tableName, $attributeName, $itemId, $language, true)
            );
            // Also add children, if the reference is a folder.
            $file = FilesModel::findByUuid($uuid);
            if (null !== $file && 'folder' === $file->type) {
                $files = FilesModel::findByPid($uuid);
                if (null === $files) {
                    continue;
                }
                assert($files instanceof Collection);
                foreach ($files as $child) {
                    $collection->addResult(
                        StringUtil::binToUuid($child->uuid),
                        $this->createFileResult($tableName, $attributeName, $itemId, $language, true)
                    );
                }
            }
        }

        return $collection;
    }

    private function createFileResult(
        string $tableName,
        string $attributeName,
        string $itemId,
        string $language,
        bool $isMultiple
    ): ResultInterface {
        if ($isMultiple) {
            return new MetaModelsTranslatedMultipleResult(
                $tableName,
                $attributeName,
                $itemId,
                $language,
                $this->urlGenerator->generate(
                    'metamodels.metamodel',
                    [
                        'tableName' => $tableName,
                        'act'       => 'edit',
                        'id'        => ModelId::fromValues($tableName, $itemId)->getSerialized(),
                        'language'  => $language,
                        'ref'       => $this->refererId,
                        'rt'        => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
                    ]
                )
            );
        }

        return new MetaModelsTranslatedSingleResult(
            $tableName,
            $attributeName,
            $itemId,
            $language,
            $this->urlGenerator->generate(
                'metamodels.metamodel',
                [
                    'tableName' => $tableName,
                    'act'       => 'edit',
                    'id'        => ModelId::fromValues($tableName, $itemId)->getSerialized(),
                    'language'  => $language,
                    'ref'       => $this->refererId,
                    'rt'        => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
                ]
            )
        );
    }
}
