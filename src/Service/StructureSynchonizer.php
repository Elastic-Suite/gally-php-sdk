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
use Gally\Sdk\Entity\AbstractEntity;
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Entity\SourceFieldOption;
use Gally\Sdk\Repository\AbstractRepository;
use Gally\Sdk\Repository\CatalogRepository;
use Gally\Sdk\Repository\LocalizedCatalogRepository;
use Gally\Sdk\Repository\MetadataRepository;
use Gally\Sdk\Repository\SourceFieldOptionRepository;
use Gally\Sdk\Repository\SourceFieldRepository;

/**
 * Synchronize gally catalogs structure with ecommerce data.
 */
class StructureSynchonizer
{
    private CatalogRepository $catalogRepository;
    private LocalizedCatalogRepository $localizedCatalogRepository;
    private MetadataRepository $metadataRepository;
    private SourceFieldRepository $sourceFieldRepository;
    private SourceFieldOptionRepository $sourceFieldOptionRepository;

    public function __construct(Configuration $configuration, ?TokenCacheManagerInterface $tokenCacheManager = null)
    {
        $client = new Client($configuration, $tokenCacheManager);
        $this->catalogRepository = new CatalogRepository($client);
        $this->localizedCatalogRepository = new LocalizedCatalogRepository($client, $this->catalogRepository);
        $this->metadataRepository = new MetadataRepository($client);
        $this->sourceFieldRepository = new SourceFieldRepository($client, $this->metadataRepository);
        $this->sourceFieldOptionRepository = new SourceFieldOptionRepository($client, $this->sourceFieldRepository);
    }

    /**
     * @param iterable<LocalizedCatalog> $localizedCatalogs
     */
    public function syncAllLocalizedCatalogs(iterable $localizedCatalogs, bool $clean = false, bool $dryRun = true): void
    {
        $existingCatalogs = $this->catalogRepository->findAll();
        $existingLocalizedCatalogs = $this->localizedCatalogRepository->findAll();

        /** @var LocalizedCatalog $localizedCatalog */
        foreach ($localizedCatalogs as $localizedCatalog) {
            $this->syncLocalizedCatalog($localizedCatalog, true);
            unset($existingLocalizedCatalogs[$this->localizedCatalogRepository->getIdentity($localizedCatalog)]);
            unset($existingCatalogs[$this->catalogRepository->getIdentity($localizedCatalog->getCatalog())]);
        }

        if ($clean) {
            foreach ($existingLocalizedCatalogs as $localizedCatalog) {
                if (!$dryRun) {
                    $this->localizedCatalogRepository->delete($localizedCatalog);
                }
            }

            foreach ($existingCatalogs as $catalog) {
                if (!$dryRun) {
                    $this->catalogRepository->delete($catalog);
                }
            }

            echo \sprintf("  Delete %d localized catalog(s)\n", \count($existingLocalizedCatalogs));
            echo \sprintf("  Delete %d catalog(s)\n", \count($existingCatalogs));
            echo "\n";
        }
    }

    public function syncLocalizedCatalog(LocalizedCatalog $localizedCatalog, bool $isFullContext = false): LocalizedCatalog
    {
        if (!$isFullContext) {
            $this->fetchEntityUri(
                $localizedCatalog->getCatalog(),
                $this->catalogRepository,
                ['code' => $localizedCatalog->getCatalog()->getCode()]
            );
            $this->catalogRepository->createOrUpdate($localizedCatalog->getCatalog());

            $this->fetchEntityUri(
                $localizedCatalog,
                $this->localizedCatalogRepository,
                ['code' => $localizedCatalog->getCode()]
            );
        }

        if (!$localizedCatalog->getCatalog()->getUri()) {
            $this->catalogRepository->createOrUpdate($localizedCatalog->getCatalog());
        }

        return $this->localizedCatalogRepository->createOrUpdate($localizedCatalog);
    }

    /**
     * @param iterable<SourceField> $sourceFields
     */
    public function syncAllSourceFields(iterable $sourceFields, bool $clean = false, bool $dryRun = true): void
    {
        $existingMetadatas = $this->metadataRepository->findAll();
        $existingSourceFields = $this->sourceFieldRepository->findAll();
        $this->localizedCatalogRepository->findAll();

        foreach ($sourceFields as $sourceField) {
            $identity = $this->sourceFieldRepository->getIdentity($sourceField);
            $existingSourceField = $existingSourceFields[$identity] ?? null;
            if (!$existingSourceField?->isSystem()) {
                $this->syncSourceField($sourceField, true);
            }
            unset($existingSourceFields[$identity]);
            unset($existingMetadatas[$this->metadataRepository->getIdentity($sourceField->getMetadata())]);
        }

        $this->sourceFieldRepository->runBulk();

        if ($clean) {
            foreach ($existingSourceFields as $sourceField) {
                if ($sourceField->isSystem()) {
                    unset($existingSourceFields[$this->sourceFieldRepository->getIdentity($sourceField)]);
                    continue;
                }
                if (!$dryRun) {
                    $this->sourceFieldRepository->delete($sourceField);
                }
            }

            /** @var Metadata[] $nonSystemExistingMetadata */
            $nonSystemExistingMetadata = [];
            /** @var Metadata $metadata */
            foreach ($existingMetadatas as $metadata) {
                if (!\in_array($metadata->getEntity(), ['product', 'category'], true)) {
                    $nonSystemExistingMetadata[] = $metadata;
                }
            }
            foreach ($nonSystemExistingMetadata as $metadata) {
                if (!$dryRun) {
                    $this->metadataRepository->delete($metadata);
                }
            }

            echo \sprintf("  Delete %d source field(s)\n", \count($existingSourceFields));
            echo \sprintf("  Delete %d metadata\n", \count($nonSystemExistingMetadata));
            echo "\n";
        }
    }

    public function syncSourceField(SourceField $sourceField, bool $isFullContext = false): void
    {
        if (!$isFullContext) {
            $this->fetchEntityUri(
                $sourceField->getMetadata(),
                $this->metadataRepository,
                ['entity' => $sourceField->getMetadata()->getEntity()]
            );

            $this->fetchEntityUri(
                $sourceField,
                $this->sourceFieldRepository,
                ['metadata.entity' => $sourceField->getMetadata()->getEntity(), 'code' => $sourceField->getCode()]
            );
        }

        if (!$sourceField->getMetadata()->getUri()) {
            $this->metadataRepository->createOrUpdate($sourceField->getMetadata());
        }

        // Replace localized catalog by an instance with id.
        foreach ($sourceField->getLabels() as $label) {
            $label->setLocalizedCatalog($this->localizedCatalogRepository->findByIdentity($label->getLocalizedCatalog()));
        }

        if ($isFullContext) {
            $this->sourceFieldRepository->addEntityToBulk($sourceField);
        } else {
            $this->sourceFieldRepository->createOrUpdate($sourceField);
        }
    }

    /**
     * @param iterable<SourceFieldOption> $sourceFieldOptions
     */
    public function syncAllSourceFieldOptions(iterable $sourceFieldOptions, bool $clean = false, bool $dryRun = true): void
    {
        $this->metadataRepository->findAll();
        $this->sourceFieldRepository->findAll();
        $this->localizedCatalogRepository->findAll();
        $existingSourceFieldOptions = $this->sourceFieldOptionRepository->findAll();

        foreach ($sourceFieldOptions as $sourceFieldOption) {
            $this->syncSourceFieldOption($sourceFieldOption, true);
            unset($existingSourceFieldOptions[$this->sourceFieldOptionRepository->getIdentity($sourceFieldOption)]);
        }

        $this->sourceFieldOptionRepository->runBulk();

        if ($clean) {
            foreach ($existingSourceFieldOptions as $sourceFieldOption) {
                if (!$dryRun) {
                    $this->sourceFieldOptionRepository->delete($sourceFieldOption);
                }
            }

            echo \sprintf("  Delete %d source field option(s)\n", \count($existingSourceFieldOptions));
            echo "\n";
        }
    }

    public function syncSourceFieldOption(SourceFieldOption $sourceFieldOption, bool $isFullContext = false): void
    {
        if (!$isFullContext) {
            $this->fetchEntityUri(
                $sourceFieldOption->getSourceField(),
                $this->sourceFieldRepository,
                [
                    'entity' => $sourceFieldOption->getSourceField()->getMetadata()->getEntity(),
                    'code' => $sourceFieldOption->getSourceField()->getCode(),
                ]
            );

            $this->fetchEntityUri(
                $sourceFieldOption,
                $this->sourceFieldOptionRepository,
                ['sourceField' => $sourceFieldOption->getSourceField()->getUri(), 'code' => $sourceFieldOption->getCode()]
            );
        }

        $sourceFieldOption->setSourceField($this->sourceFieldRepository->findByIdentity($sourceFieldOption->getSourceField()));

        // Replace localized catalog by an instance with id.
        foreach ($sourceFieldOption->getLabels() as $label) {
            $label->setLocalizedCatalog($this->localizedCatalogRepository->findByIdentity($label->getLocalizedCatalog()));
        }

        if ($isFullContext) {
            $this->sourceFieldOptionRepository->addEntityToBulk($sourceFieldOption);
        } else {
            $this->sourceFieldOptionRepository->createOrUpdate($sourceFieldOption);
        }
    }

    private function fetchEntityUri(AbstractEntity $entity, AbstractRepository $repository, array $criteria): void
    {
        if (SourceFieldOption::class === $entity::class) {
            $code = $criteria['code'];
            unset($criteria['code']);
        }
        if (Metadata::class === $entity::class) {
            $entityCode = $criteria['entity'];
            unset($criteria['entity']);
        }
        $existingEntities = $repository->findBy($criteria);
        if ($existingEntities && 1 == \count($existingEntities)) {
            $existingEntity = reset($existingEntities);
            $entity->setUri($existingEntity->getUri());
        } elseif (isset($code)) {
            foreach ($existingEntities as $existingEntity) {
                if ($existingEntity->getCode() == $code) {
                    $entity->setUri($existingEntity->getUri());
                    break;
                }
            }
        } elseif (isset($entityCode)) {
            foreach ($existingEntities as $existingEntity) {
                if ($existingEntity->getEntity() == $entityCode) {
                    $entity->setUri($existingEntity->getUri());
                    break;
                }
            }
        }
    }
}
