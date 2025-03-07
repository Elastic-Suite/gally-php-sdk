<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Gally Team <elasticsuite@smile.fr>
 * @copyright 2024-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\Sdk\Service;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Client\TokenCacheManagerInterface;
use Gally\Sdk\Entity\Index;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Repository\CatalogRepository;
use Gally\Sdk\Repository\LocalizedCatalogRepository;

/**
 * Indexer manager service.
 */
class IndexOperation
{
    private const INDEX_DOCUMENT_ENTITY_CODE = 'index_documents';

    protected Client $client;
    protected LocalizedCatalogRepository $localizedCatalogRepository;

    public function __construct(Configuration $configuration, ?TokenCacheManagerInterface $tokenCacheManager = null)
    {
        $this->client = new Client($configuration, $tokenCacheManager);
        $catalogRepository = new CatalogRepository($this->client);
        $this->localizedCatalogRepository = new LocalizedCatalogRepository($this->client, $catalogRepository);
    }

    public function createIndex(Metadata $metadata, LocalizedCatalog $localizedCatalog): Index
    {
        $index = new Index($metadata, $localizedCatalog);

        $indexRawData = $this->client->post(Index::getEntityCode(), $index->__toJson());
        $index->setName($indexRawData['name']);

        return $index;
    }

    public function getIndexByName(Metadata $metadata, LocalizedCatalog $localizedCatalog): Index
    {
        $rawIndicesList = $this->client->get(Index::getEntityCode());

        if (!$localizedCatalog->getUri()) {
            $existingLocalizedCatalogs = $this->localizedCatalogRepository->findBy(['code' => $localizedCatalog->getCode()]);
            if (1 !== \count($existingLocalizedCatalogs)) {
                throw new \LogicException("Can't find localized catalog with code '{$localizedCatalog->getCode()}', make sure you catalog structure has been sync with Gally.");
            }
            $localizedCatalog->setUri(reset($existingLocalizedCatalogs)->getUri());
        }

        foreach ($rawIndicesList['hydra:member'] ?? [] as $rawIndex) {
            if (
                $rawIndex['entityType'] === $metadata->getEntity()
                && $rawIndex['localizedCatalog'] === $localizedCatalog->getUri()
                && 'live' === $rawIndex['status']
            ) {
                $index = new Index($metadata, $localizedCatalog);
                $index->setName($rawIndex['name']);

                return $index;
            }
        }

        throw new \LogicException("Index for entity {$metadata->getEntity()} and localizedCatalog {$localizedCatalog->getCode()} does not exist yet. Make sure everything is reindexed.");
    }

    public function refreshIndex(Index|string $index): void
    {
        $this->client->put(
            \sprintf('%s/%s/%s', Index::getEntityCode(), 'refresh', \is_string($index) ? $index : $index->getName())
        );
    }

    public function installIndex(Index|string $index): void
    {
        $this->client->put(
            \sprintf('%s/%s/%s', Index::getEntityCode(), 'install', \is_string($index) ? $index : $index->getName())
        );
    }

    public function executeBulk(Index|string $index, array $documents): void
    {
        $this->client->post(
            self::INDEX_DOCUMENT_ENTITY_CODE,
            ['indexName' => \is_string($index) ? $index : $index->getName(), 'documents' => $documents]
        );
    }
}
