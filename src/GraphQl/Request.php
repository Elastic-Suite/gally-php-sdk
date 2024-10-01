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

use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;

final class Request
{
    public const FILTER_OPERATOR_EQ = 'eq';
    public const FILTER_OPERATOR_IN = 'in';
    public const FILTER_OPERATOR_MATCH = 'match';
    public const FILTER_OPERATOR_EXISTS = 'exists';
    public const FILTER_OPERATOR_LT = 'lt';
    public const FILTER_OPERATOR_LTE = 'lte';
    public const FILTER_OPERATOR_GT = 'gt';
    public const FILTER_OPERATOR_GTE = 'gte';

    public const FILTER_TYPE_BOOLEAN = 'boolFilter';
    public const FILTER_TYPE_EQUAL = 'equalFilter';
    public const FILTER_TYPE_MATCH = 'matchFilter';
    public const FILTER_TYPE_RANGE = 'rangeFilter';
    public const FILTER_TYPE_EXIST = 'existFilter';

    public const SORT_RELEVANCE_FIELD = '_score';

    public const SORT_DIRECTION_ASC = 'asc';
    public const SORT_DIRECTION_DESC = 'desc';

    public static function getFilterTypeByOperator(string $operator): string
    {
        return match ($operator) {
            self::FILTER_OPERATOR_MATCH => self::FILTER_TYPE_MATCH,
            self::FILTER_OPERATOR_LT, self::FILTER_OPERATOR_LTE,
            self::FILTER_OPERATOR_GT, self::FILTER_OPERATOR_GTE => self::FILTER_TYPE_RANGE,
            self::FILTER_OPERATOR_EXISTS => self::FILTER_TYPE_EXIST,
            default => self::FILTER_TYPE_EQUAL,
        };
    }

    public function __construct(
        private LocalizedCatalog $localizedCatalog,
        private Metadata $metadata,
        private bool $isAutocomplete,
        private array $selectedFields,
        private int $currentPage,
        private int $pageSize,
        private ?string $categoryId,
        private ?string $searchQuery,
        private array $filters,
        private ?string $sortField = null,
        private ?string $sortDirection = null,
        private ?string $priceGroupId = null,
    ) {
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getEndpoint(): string
    {
        return 'product' === $this->metadata->getEntity() ? 'products' : 'documents';
    }

    public function getRequestType(): ?string
    {
        if ('product' === $this->metadata->getEntity()) {
            return $this->isAutocomplete
                ? 'product_autocomplete'
                : ($this->searchQuery ? 'product_search' : 'product_catalog');
        }

        return null;
    }

    public function getLocalizedCatalog(): LocalizedCatalog
    {
        return $this->localizedCatalog;
    }

    public function getSelectedFields(): array
    {
        return $this->selectedFields;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function getSortDirection(): ?string
    {
        return $this->sortDirection;
    }

    public function getPriceGroupId(): ?string
    {
        return $this->priceGroupId;
    }

    public function buildSearchQuery(): string
    {
        $isProductQuery = 'product' === $this->metadata->getEntity();
        $hasSelectedFields = !empty($this->selectedFields);
        $endpoint = $this->getEndpoint();
        $entityType = "entityType: \"{$this->metadata->getEntity()}\"";
        $selectedFields = ['id', 'data'];
        $typePrefix = '';
        $specificVars = '';
        $specificFields = '';

        if ($isProductQuery) {
            $selectedFields = $this->getSelectedFields();
            $entityType = '';
            $selectedFields[] = 'price { price }';
            $selectedFields[] = 'stock { status }';
            $typePrefix = 'Product';
            $specificVars = '$currentCategoryId: String, $requestType: ProductRequestTypeEnum!';
            $specificFields = 'currentCategoryId: $currentCategoryId, requestType: $requestType';
        }

        $selectedFields = implode(' ', $selectedFields);
        $collection = $hasSelectedFields ? "collection { $selectedFields }" : '';

        return <<<GQL
            query searchQuery (
              \$localizedCatalog: String!,
              \$currentPage: Int,
              \$pageSize: Int,
              \$search: String,
              \$sort: {$typePrefix}SortInput,
              \$filter: [{$typePrefix}FieldFilterInput],
              $specificVars,
            ) {
              $endpoint (
                $entityType,
                localizedCatalog: \$localizedCatalog,
                currentPage: \$currentPage,
                pageSize: \$pageSize,
                search: \$search,
                sort: \$sort,
                filter: \$filter,
                $specificFields,
              ) {
                $collection
                paginationInfo { lastPage itemsPerPage totalCount }
                sortInfo { current { field direction } }
                aggregations {
                  type
                  field
                  label
                  count
                  hasMore
                  options { count label value }
                }
            }
          }
        GQL;
    }

    public function getVariables(): array
    {
        $isProductQuery = 'product' === $this->metadata->getEntity();
        $variables = [
            'requestType' => $this->getRequestType(),
            'localizedCatalog' => $this->getLocalizedCatalog()->getCode(),
            'currentCategoryId' => $this->getCategoryId(),
            'search' => $this->getSearchQuery(),
            'currentPage' => $this->getCurrentPage(),
            'pageSize' => $this->getPageSize(),
            'filter' => $this->getFilters(),
        ];

        if ($this->sortField) {
            $variables['sort'] = $isProductQuery
                ? [$this->getSortField() => $this->getSortDirection()]
                : ['field' => $this->getSortField(), 'direction' => $this->getSortDirection()];
        }

        return array_filter($variables);
    }
}
