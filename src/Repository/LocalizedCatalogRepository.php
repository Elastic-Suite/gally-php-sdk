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

namespace Gally\Sdk\Repository;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Entity\AbstractEntity;
use Gally\Sdk\Entity\Catalog;
use Gally\Sdk\Entity\LocalizedCatalog;

/**
 * Localized Catalog repository.
 *
 * @method LocalizedCatalog findByIdentity(LocalizedCatalog $entity)
 * @method LocalizedCatalog createOrUpdate(LocalizedCatalog $entity)
 */
class LocalizedCatalogRepository extends AbstractRepository
{
    public function __construct(
        Client $client,
        private CatalogRepository $catalogRepository)
    {
        parent::__construct($client);
    }

    public function getEntityCode(): string
    {
        return LocalizedCatalog::getEntityCode();
    }

    public function getIdentity(AbstractEntity $entity): string
    {
        if (!$entity instanceof LocalizedCatalog) {
            throw new \InvalidArgumentException(sprintf('Entity %s not managed by this repository.', $entity::class));
        }

        return $entity->getCode();
    }

    protected function buildEntityObject(array $rawEntity): AbstractEntity
    {
        /** @var Catalog $catalog */
        $catalog = $this->catalogRepository->findByUri($rawEntity['catalog']);

        return new LocalizedCatalog(
            $catalog,
            $rawEntity['code'],
            $rawEntity['name'],
            $rawEntity['locale'],
            $rawEntity['currency'],
            $rawEntity['id'] ?? null,
        );
    }
}
