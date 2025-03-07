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

class Label
{
    public function __construct(
        private LocalizedCatalog $localizedCatalog,
        private string $label,
    ) {
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function setLocalizedCatalog(LocalizedCatalog $localizedCatalog): void
    {
        $this->localizedCatalog = $localizedCatalog;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function __toJson(bool $isBulkContext = false): array
    {
        return [
            'localizedCatalog' => $isBulkContext
                ? $this->cleanApiPrefix((string) $this->getLocalizedCatalog())
                : (string) $this->getLocalizedCatalog(),
            'label' => $this->getLabel(),
        ];
    }

    protected function cleanApiPrefix(string $prefix): string
    {
        return preg_replace('#^.*(/[^/]+/[^/]+$)#', '$1', $prefix) ?: '';
    }
}
