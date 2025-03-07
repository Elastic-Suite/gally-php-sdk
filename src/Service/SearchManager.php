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

namespace Gally\Sdk\Service;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Client\TokenCacheManagerInterface;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\Entity\SourceField;
use Gally\Sdk\GraphQl\Request;
use Gally\Sdk\GraphQl\Response;
use Gally\Sdk\Repository\MetadataRepository;
use Gally\Sdk\Repository\SourceFieldRepository;

/**
 * Search manager service.
 */
class SearchManager
{
    protected Client $client;
    /** @var SourceField[] */
    protected array $productSortingOptions;
    protected SourceFieldRepository $sourceFieldRepository;

    public function __construct(Configuration $configuration, ?TokenCacheManagerInterface $tokenCacheManager = null)
    {
        $client = new Client($configuration, $tokenCacheManager);
        $this->client = $client;
        $this->sourceFieldRepository = new SourceFieldRepository($client, new MetadataRepository($client));
    }

    /**
     * @return SourceField[]
     */
    public function getProductSortingOptions(): array
    {
        if (!isset($this->productSortingOptions)) {
            $query = <<<GQL
              {
                productSortingOptions {
                    code
                    label
                    type
                  }
                }
            GQL;
            $response = $this->client->graphql($query, [], [], false);
            $metadata = new Metadata('product');

            $this->productSortingOptions = array_map(
                fn ($productSortingOption) => new SourceField(
                    $metadata,
                    $productSortingOption['code'],
                    $productSortingOption['type'],
                    $productSortingOption['label'],
                    []
                ),
                $response['data']['productSortingOptions']
            );
        }

        return $this->productSortingOptions;
    }

    /**
     * @return SourceField[]
     */
    public function getFilterableSourceField(Metadata $metadata): array
    {
        return $this->sourceFieldRepository->findBy(
            [
                'metadata.entity' => $metadata->getEntity(),
                'isFilterable' => true,
            ]
        );
    }

    /**
     * @return SourceField[]
     */
    public function getSelectSourceField(Metadata $metadata): array
    {
        return $this->sourceFieldRepository->findBy(
            [
                'metadata.entity' => $metadata->getEntity(),
                'type' => SourceField::TYPE_SELECT,
            ]
        );
    }

    public function search(Request $request): Response
    {
        $priceGroup = $request->getPriceGroupId();

        $response = $this->client->graphql(
            $request->buildSearchQuery(),
            $request->getVariables(),
            $priceGroup ? ['price-group-id' => $priceGroup] : [],
            false
        );

        return new Response($request, $response['data']);
    }

    public function viewMoreProductFilterOption(Request $request, string $aggregationField): array // todo response ?
    {
        $query = <<<GQL
            query viewMoreProductFacetOptions (
                \$localizedCatalog: String!,
                \$search: String,
                \$currentCategoryId: String,
                \$filter: [ProductFieldFilterInput],
                \$aggregation: String!,
            ) {
                viewMoreProductFacetOptions (
                    localizedCatalog: \$localizedCatalog,
                    search: \$search,
                    currentCategoryId: \$currentCategoryId,
                    filter: \$filter,
                    aggregation: \$aggregation,
                ) {
                    value
                    label
                    count
                }
            }
        GQL;

        $variables = $request->getVariables();
        $response = $this->client->graphql(
            $query,
            array_filter([
                'aggregation' => $aggregationField,
                'localizedCatalog' => $variables['localizedCatalog'],
                'search' => $variables['search'] ?? null,
                'filter' => $variables['filter'] ?? null,
                'currentCategoryId' => $variables['currentCategoryId'] ?? null,
            ]),
            [],
            false
        );

        return $response['data']['viewMoreProductFacetOptions'];
    }
}
