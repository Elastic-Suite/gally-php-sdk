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

use Gally\Sdk\Entity\AbstractEntity;

/**
 * Abstract bulk entity repository.
 */
abstract class AbstractBulkRepository extends AbstractRepository
{
    protected const BATCH_SIZE = 100;

    private array $currentBatch = [];
    private int $currentBatchSize = 0;

    public function addEntityToBulk(AbstractEntity $entity): void
    {
        $this->currentBatch[] = $entity->__toJson();
        ++$this->currentBatchSize;
        if ($this->currentBatchSize >= self::BATCH_SIZE) {
            $this->runBulk();
        }
    }

    public function runBulk(): void
    {
        if ($this->currentBatchSize) {
            $rawEntities = $this->client->post("/{$this->getEntityCode()}/bulk", $this->currentBatch);
            foreach ($rawEntities as $rawEntity) {
                $entity = $this->buildEntityObject($rawEntity);
                $this->saveInCache($entity);
            }
            $this->currentBatch = [];
            $this->currentBatchSize = 0;
        }
    }
}
