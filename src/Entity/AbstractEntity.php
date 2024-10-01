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

    protected int|string|null $id;

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string $id): void
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->getId() ? '/' . static::getEntityCode() . '/' . $this->getId() : '';
    }

    abstract public function __toJson(): array;
}
