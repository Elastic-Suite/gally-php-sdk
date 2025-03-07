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

class Index
{
    public static function getEntityCode(): string
    {
        return 'indices';
    }

    public function __construct(
        private Metadata $metadata,
        private LocalizedCatalog $localizedCatalog,
        private ?string $name = null,
    ) {
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function __toJson(bool $isBulkContext = false): array
    {
        return [
            'entityType' => $this->getMetadata()->getEntity(),
            'localizedCatalog' => $this->getLocalizedCatalog()->getCode(),
        ];
    }
}
