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
use Gally\Sdk\Entity\Index;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;

/**
 * Indexer manager service.
 */
class IndexOperation
{
    private const INDEX_DOCUMENT_ENTITY_CODE = 'index_documents';

    protected Client $client;

    public function __construct(
        Configuration $configuration,
        string $environment,
    ) {
        $this->client = new Client($configuration, $environment);
    }

    public function createIndex(Metadata $metadata, LocalizedCatalog $localizedCatalog): Index
    {
        $index = new Index($metadata, $localizedCatalog);

        $indexRawData = $this->client->post(Index::getEntityCode(), $index->__toJson());
        $index->setId($indexRawData['name']);

        return $index;
    }

    public function getIndexByName(Metadata $metadata, LocalizedCatalog $localizedCatalog): Index
    {
        $rawIndicesList = $this->client->get(Index::getEntityCode());

        foreach ($rawIndicesList as $rawIndex) {
            if (
                $rawIndex['entityType'] === $metadata->getEntity()
                && $rawIndex['localizedCatalog'] === '/localized_catalogs/' . $localizedCatalog->getId()
                && 'live' === $rawIndex['status']
            ) {
                $index = new Index($metadata, $localizedCatalog);
                $index->setId($rawIndex['name']);

                return $index;
            }
        }

        throw new \LogicException("Index for entity {$metadata->getEntity()} and localizedCatalog {$localizedCatalog->getCode()} does not exist yet. Make sure everything is reindexed.");
    }

    public function refreshIndex(Index $index): void
    {
        $this->client->put(sprintf('%s/%s/%s', Index::getEntityCode(), 'refresh', $index->getId()));
    }

    public function installIndex(Index $index): void
    {
        $this->client->put(sprintf('%s/%s/%s', Index::getEntityCode(), 'install', $index->getId()));
    }

    public function executeBulk(Index $index, array $documents): void
    {
        $this->client->post(
            self::INDEX_DOCUMENT_ENTITY_CODE,
            ['indexName' => $index->getId(), 'documents' => $documents]
        );
    }
}
