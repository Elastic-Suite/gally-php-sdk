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

namespace Gally\Sdk\Entity;

class Index extends AbstractEntity
{
    public static function getEntityCode(): string
    {
        return 'indices';
    }

    public function __construct(
        private Metadata $metadata,
        private LocalizedCatalog $localizedCatalog,
        int $id = null,
    ) {
        $this->id = $id;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function __toJson(): array
    {
        return [
            'entityType' => $this->getMetadata()->getEntity(),
            'localizedCatalog' => $this->getLocalizedCatalog()->getCode(),
        ];
    }
}
