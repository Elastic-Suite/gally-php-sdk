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

class Metadata extends AbstractEntity
{
    public static function getEntityCode(): string
    {
        return 'metadata';
    }

    public function __construct(
        private string $entity,
        ?string $uri = null,
    ) {
        $this->uri = $uri;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function __toJson(): array
    {
        return ['entity' => $this->getEntity()];
    }
}
