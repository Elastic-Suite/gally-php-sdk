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

class Catalog extends AbstractEntity
{
    public static function getEntityCode(): string
    {
        return 'catalogs';
    }

    public function __construct(
        private string $code,
        private string $name,
        int $id = null,
    ) {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toJson(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
    }
}
