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

namespace Gally\Sdk\GraphQl;

final class Response
{
    public const FILTER_TYPE_CATEGORY = 'category';
    public const FILTER_TYPE_CHECKBOX = 'checkbox';
    public const FILTER_TYPE_BOOLEAN = 'boolean';
    public const FILTER_TYPE_SLIDER = 'slider';

    private array $collection;
    private array $aggregations;
    private int $totalCount;
    private int $lastPage;
    private int $itemsPerPage;
    private string $sortField;
    private string $sortDirection;

    public function __construct(
        Request $request,
        array $rawResponse,
    ) {
        $rawResponse = $rawResponse[$request->getEndpoint()] ?? [];
        $this->collection = array_map(
            fn ($item) => \array_key_exists('data', $item)
                ? array_intersect_key($item['data']['_source'], array_flip($request->getSelectedFields()))
                : $item,
            $rawResponse['collection'] ?? []
        );
        $this->aggregations = $rawResponse['aggregations'] ?? [];
        $this->totalCount = $rawResponse['paginationInfo']['totalCount'];
        $this->lastPage = $rawResponse['paginationInfo']['lastPage'];
        $this->itemsPerPage = $rawResponse['paginationInfo']['itemsPerPage'];
        $currentSortOrder = reset($rawResponse['sortInfo']['current']);
        $this->sortField = $currentSortOrder['field'];
        $this->sortDirection = $currentSortOrder['direction'];
    }

    public function getCollection(): array
    {
        return $this->collection;
    }

    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getSortField(): string
    {
        return $this->sortField;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }
}
