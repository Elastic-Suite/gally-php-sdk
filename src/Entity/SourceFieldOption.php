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

class SourceFieldOption extends AbstractEntity
{
    public static function getEntityCode(): string
    {
        return 'source_field_options';
    }

    /**
     * @param Label[] $labels
     */
    public function __construct(
        private SourceField $sourceField,
        private string $code,
        private int $position,
        private string $defaultLabel,
        private array $labels,
        int $id = null,
    ) {
        $this->id = $id;
    }

    public function getSourceField(): SourceField
    {
        return $this->sourceField;
    }

    public function setSourceField(SourceField $sourceField): void
    {
        $this->sourceField = $sourceField;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getDefaultLabel(): string
    {
        return $this->defaultLabel;
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function __toJson(): array
    {
        return [
            'sourceField' => (string) $this->getSourceField(),
            'code' => $this->getCode(),
            'position' => $this->getPosition(),
            'defaultLabel' => $this->getDefaultLabel(),
            'labels' => array_map(
                function ($label) {
                    return $label->__toJson();
                },
                $this->getLabels()
            ),
        ];
    }
}
