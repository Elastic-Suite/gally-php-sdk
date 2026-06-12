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

namespace Gally\Sdk\Service;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Client\Configuration;
use Gally\Sdk\Client\TokenCacheManagerInterface;
use Gally\Sdk\Entity\LocalizedCatalog;

/**
 * Recommender manager service.
 */
class RecommenderManager
{
    public const MAX_COUNT_PRODUCT_RECOMMENDATIONS = 20;

    protected Client $client;
    /** @var array<string, array> */
    private array $recommendationCache = [];

    public function __construct(Configuration $configuration, ?TokenCacheManagerInterface $tokenCacheManager = null)
    {
        $this->client = new Client($configuration, $tokenCacheManager);
    }

    /**
     * Get product recommendations for the given products from Gally.
     *
     * @param string           $recommendationType Recommender type code (ex: "related", "upsell", "crosssell")
     * @param LocalizedCatalog $localizedCatalog   Current localized catalog
     * @param string[]         $productSkus        Sku of the products to get recommendations for
     * @param int|null         $productCount       Max number of recommended products (capped to 20 by Gally)
     *
     * @return array<array{id: string, sku: string, name: string}> Recommended products data
     */
    public function getProductRecommendations(
        string $recommendationType,
        LocalizedCatalog $localizedCatalog,
        array $productSkus,
        ?int $productCount = null,
    ): array {
        if (empty($productSkus)) {
            return [];
        }

        $productCount = min($productCount ?? self::MAX_COUNT_PRODUCT_RECOMMENDATIONS, self::MAX_COUNT_PRODUCT_RECOMMENDATIONS);

        $variables = [
            'recommendationType' => $recommendationType,
            'localizedCatalog' => $localizedCatalog->getCode(),
            'productSkus' => array_values($productSkus),
            'productCount' => $productCount,
        ];

        $cacheKey = md5(json_encode($variables) ?: '');

        if (!isset($this->recommendationCache[$cacheKey])) {
            $query = <<<GQL
                query getProductRecommendations (
                    \$recommendationType: String!,
                    \$localizedCatalog: String!,
                    \$productSkus: [String!]!,
                    \$productCount: Int,
                ) {
                    productRecommendations (
                        recommendationType: \$recommendationType,
                        localizedCatalog: \$localizedCatalog,
                        productSkus: \$productSkus,
                        productCount: \$productCount,
                    ) {
                        id
                        sku
                        name
                    }
                }
            GQL;

            $response = $this->client->graphql($query, $variables, [], false);
            $this->recommendationCache[$cacheKey] = $response['data']['productRecommendations'] ?? [];
        }

        return $this->recommendationCache[$cacheKey];
    }
}
