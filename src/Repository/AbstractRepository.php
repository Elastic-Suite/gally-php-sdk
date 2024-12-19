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

namespace Gally\Sdk\Repository;

use Gally\Sdk\Client\Client;
use Gally\Sdk\Entity\AbstractEntity;

/**
 * Abstract entity repository.
 */
abstract class AbstractRepository
{
    protected const FETCH_PAGE_SIZE = 50;

    protected array $entityByIdentity = [];
    protected array $entityByUri = [];

    public function __construct(
        protected Client $client,
    ) {
    }

    abstract public function getEntityCode(): string;

    abstract public function getIdentity(AbstractEntity $entity): string;

    public function findByUri(string $uri): AbstractEntity
    {
        if (\array_key_exists($uri, $this->entityByUri)) {
            return $this->entityByUri[$uri];
        }

        $rawEntity = $this->client->get($uri);
        $entity = $this->buildEntityObject($rawEntity);
        $this->saveInCache($entity);

        return $entity;
    }

    public function findByIdentity(AbstractEntity $entity): ?AbstractEntity
    {
        $identity = $this->getIdentity($entity);
        if (\array_key_exists($identity, $this->entityByIdentity)) {
            return $this->entityByIdentity[$identity];
        }

        return null;
    }

    public function findBy(array $criteria, bool $saveInCache = false): array
    {
        $currentPage = 1;
        $entities = [];
        do {
            $rawEntities = $this->client->get(
                "{$this->getEntityCode()}",
                array_merge(
                    $criteria,
                    ['currentPage' => $currentPage, 'pageSize' => self::FETCH_PAGE_SIZE],
                )
            );

            $rawEntities = array_key_exists('hydra:member', $rawEntities) ? $rawEntities['hydra:member'] : [];
            foreach ($rawEntities as $rawEntity) {
                $entity = $this->buildEntityObject($rawEntity);
                $entities[$this->getIdentity($entity)] = $entity;
                if ($saveInCache) {
                    $this->saveInCache($entity);
                }
            }
            ++$currentPage;
        } while (\count($rawEntities) >= self::FETCH_PAGE_SIZE);

        return $entities;
    }

    public function findAll(): array
    {
        return $this->findBy([], true);
    }

    public function createOrUpdate(AbstractEntity $entity): AbstractEntity
    {
        $identity = $this->getIdentity($entity);

        $existingEntity = $this->entityByIdentity[$identity] ?? null;
        $uri = $existingEntity ? (string) $existingEntity : (string) $entity;
        $result = $uri
            ? $this->client->put($uri, $entity->__toJson())
            : $this->client->post($this->getEntityCode(), $entity->__toJson());

        $entity->setUri($result['@id']);
        $this->saveInCache($entity);

        return $entity;
    }

    public function delete(AbstractEntity $entity): void
    {
        $identity = $this->getIdentity($entity);
        $existingEntity = $this->entityByIdentity[$identity] ?? null;

        if (!$existingEntity) {
            throw new \RuntimeException(sprintf('Entity %s not found.', $identity));
        }

        $this->client->delete((string) $existingEntity);
    }

    protected function saveInCache(AbstractEntity $entity): void
    {
        $this->entityByIdentity[$this->getIdentity($entity)] = $entity;
        $this->entityByUri[(string) $entity] = $entity;
    }

    abstract protected function buildEntityObject(array $rawEntity): AbstractEntity;
}
