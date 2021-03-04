<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionObject;
use Symfony\Component\Serializer\SerializerInterface;

class PersistentEntityArgumentValueResolverHelper
{
    private const SETTER_PREFIX = 'set';

    private $serializer;

    private $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ) {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    public function deserializeAndPersist(string $content, string $className, array $context)
    {
        $entity = $this->serializer->deserialize(
            $content,
            $className,
            'json',
            $context
        );

        return $this->persist($className, $entity);
    }

    public function persist(string $className, $entity)
    {
        $existingEntity = $this->entityManager->find($className, $entity->getId());
        if ($existingEntity !== null) {
            $reflectedEntity = new ReflectionObject($existingEntity);
            foreach ($reflectedEntity->getMethods() as $method) {
                if (stripos($method->getName(), self::SETTER_PREFIX) !== 0) {
                    continue;
                }

                $getterName = sprintf('get%s', substr($method->getName(), strlen(self::SETTER_PREFIX)));
                $newValue = $entity->{$getterName}();
                if (empty($newValue)) {
                    continue;
                }

                $setterName = $method->getName();
                $existingEntity->{$setterName}($newValue);
            }

            return $existingEntity;
        }

        $this->entityManager->persist($entity);

        return $entity;
    }
}
