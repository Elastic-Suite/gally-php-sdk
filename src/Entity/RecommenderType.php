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

namespace Gally\Sdk\Entity;

class RecommenderType extends AbstractEntity
{
    public static function getEntityCode(): string
    {
        return 'recommender_types';
    }

    public function __construct(
        private string $name,
        private string $code,
        ?string $uri = null,
    ) {
        $this->uri = $uri;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function __toJson(): array
    {
        return $this->addUriToJson(
            [
                'name' => $this->getName(),
                'code' => $this->getCode(),
            ]
        );
    }
}
