<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Constant\Event\ObjectKind;
use App\Dto\Comment\User;
use App\Entity\Author;
use App\Entity\Comment;
use App\Entity\Event\EventObject;
use App\Entity\Event\EventObjectAttributes;
use App\Entity\MergeRequest;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class CommentArgumentValueResolver implements ArgumentValueResolverInterface
{
    private const CONTEXT = [UnwrappingDenormalizer::UNWRAP_PATH => '[object_attributes]'];
    private const MERGE_REQUEST_CONTEXT = [UnwrappingDenormalizer::UNWRAP_PATH => '[merge_request]'];
    private const PROJECT_CONTEXT = [UnwrappingDenormalizer::UNWRAP_PATH => '[project]'];
    private const USER_CONTEXT = [UnwrappingDenormalizer::UNWRAP_PATH => '[user]'];

    private $serializer;

    private $entityArgumentValueResolverHelper;

    public function __construct(
        SerializerInterface $serializer,
        PersistentEntityArgumentValueResolverHelper $entityArgumentValueResolverHelper
    ) {
        $this->serializer = $serializer;
        $this->entityArgumentValueResolverHelper = $entityArgumentValueResolverHelper;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== Comment::class) {
            return false;
        }

        /** @var EventObject $eventObject */
        $eventObject = $this->serializer->deserialize($request->getContent(), EventObject::class, 'json');

        return $eventObject->getObjectKind() === ObjectKind::NOTE;
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $comment = $this->setComment($request);
        $this->setCommentAuthor($request, $comment);

        if (!$this->isMergeRequestAttached($request)) {
            return [$comment];
        }

        $mergeRequest = $this->setMergeRequest($request, $comment);
        $this->setProject($request, $mergeRequest, $comment);

        return [$comment];
    }

    private function isMergeRequestAttached(Request $request): bool
    {
        /** @var MergeRequest $mergeRequest */
        $mergeRequest = $this->serializer->deserialize(
            $request->getContent(),
            MergeRequest::class,
            'json',
            self::MERGE_REQUEST_CONTEXT
        );

        return $mergeRequest->getId() !== null;
    }

    protected function setComment(Request $request): Comment
    {
        /** @var Comment $comment */
        $comment = $this->entityArgumentValueResolverHelper->deserializeAndPersist(
            $request->getContent(),
            Comment::class,
            self::CONTEXT
        );

        return $comment;
    }

    protected function setMergeRequest(Request $request, Comment $comment): MergeRequest
    {
        /** @var MergeRequest $mergeRequest */
        $mergeRequest = $this->entityArgumentValueResolverHelper->deserializeAndPersist(
            $request->getContent(),
            MergeRequest::class,
            self::MERGE_REQUEST_CONTEXT
        );

        $this->setMergeRequestAuthor($request, $mergeRequest);

        $comment->setMergeRequest($mergeRequest);

        return $mergeRequest;
    }

    protected function setProject(Request $request, MergeRequest $mergeRequest, Comment $comment): void
    {
        /** @var Project $project */
        $project = $this->entityArgumentValueResolverHelper->deserializeAndPersist(
            $request->getContent(),
            Project::class,
            self::PROJECT_CONTEXT
        );

        $mergeRequest->setProject($project);
        $comment->setProject($project);
    }

    protected function setCommentAuthor(Request $request, Comment $comment): void
    {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            self::USER_CONTEXT
        );

        /** @var EventObjectAttributes $eventObjectAttributes */
        $eventObjectAttributes = $this->serializer->deserialize(
            $request->getContent(),
            EventObjectAttributes::class,
            'json',
            self::CONTEXT
        );

        $author = new Author();
        $author->setId((int) $eventObjectAttributes->getAuthorId());
        $author->setEmail($user->getEmail());
        $author->setUsername($user->getUsername());

        /** @var Author $author */
        $author = $this->entityArgumentValueResolverHelper->persist(Author::class, $author);
        $comment->setAuthor($author);
    }

    protected function setMergeRequestAuthor(Request $request, MergeRequest $mergeRequest): void
    {
        $event = json_decode($request->getContent(), true);

        $author = new Author();
        $author->setId($event['merge_request']['author_id']);

        /** @var Author $author */
        $author = $this->entityArgumentValueResolverHelper->persist(Author::class, $author);
        $mergeRequest->setAuthor($author);
    }
}
