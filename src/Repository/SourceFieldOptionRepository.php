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
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\Entity\SourceFieldOption;

/**
 * Source field option repository.
 */
class SourceFieldOptionRepository extends AbstractBulkRepository
{
    public function __construct(
        Client $client,
        private SourceFieldRepository $sourceFieldRepository,
    ) {
        parent::__construct($client);
    }

    public function getEntityCode(): string
    {
        return SourceFieldOption::getEntityCode();
    }

    public function getIdentity(AbstractEntity $entity): string
    {
        if (!$entity instanceof SourceFieldOption) {
            throw new \InvalidArgumentException(sprintf('Entity %s not managed by this repository.', $entity::class));
        }

        return $entity->getSourceField()->getCode() . '_' . $entity->getCode();
    }

    protected function buildEntityObject(array $rawEntity): AbstractEntity
    {
        /** @var SourceField $sourceField */
        $sourceField = $this->sourceFieldRepository->findByUri($rawEntity['sourceField']);

        return new SourceFieldOption(
            $sourceField,
            $rawEntity['code'],
            $rawEntity['position'],
            $rawEntity['defaultLabel'],
            $rawEntity['labels'],
            $rawEntity['id'] ?? null,
        );
    }
}
