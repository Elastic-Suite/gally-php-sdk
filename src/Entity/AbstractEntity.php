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

abstract class AbstractEntity
{
    abstract public static function getEntityCode(): string;

    protected ?string $uri;

    public function getUri(): string
    {
        return $this->uri ?: '';
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function __toString(): string
    {
        return $this->getUri();
    }

    abstract public function __toJson(bool $isBulkContext = false): array;

    protected function cleanApiPrefix(string $uri): string
    {
        return preg_replace('#^.*(/[^/]+/[^/]+$)#', '$1', $uri) ?: '';
    }
}
