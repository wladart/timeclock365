<?php

namespace App\EventListener;

use App\Entity\Author;
use App\Entity\Book;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

class BookListener
{
	private array $authors = [];
	private FileUploader $fileUploader;

	public function __construct(FileUploader $fileUploader)
	{
		$this->fileUploader = $fileUploader;
	}

	public function onFlush(OnFlushEventArgs $eventArgs): void
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();

		$collections = array_merge(
			$uow->getScheduledCollectionUpdates(),
			$uow->getScheduledCollectionDeletions(),
		);

		foreach ($collections as $collection)
		{
			if (!($collection->getOwner() instanceof Book))
			{
				continue;
			}

			$diff = array_merge(
				$collection->getDeleteDiff(),
				$collection->getInsertDiff(),
			);

			foreach ($diff as $item)
			{
				if (!($item instanceof Author))
				{
					continue;
				}

				$this->authors[] = $item;
			}
		}
	}

	public function postFlush(PostFlushEventArgs $eventArgs): void
	{
		$em = $eventArgs->getEntityManager();

		$authors = $this->authors;
		$this->authors = [];

		foreach ($authors as $author)
		{
			$this->recalcBooksCount($author, $em);
		}
	}

	public function recalcBooksCount($author, EntityManagerInterface $em): void
	{
		$bookRepository = $em->getRepository(Book::class);

		$result = $bookRepository->createQueryBuilder('b')
			->where('1 = 1')
			->innerJoin('b.author', 'a')
			->andWhere('a.id = :authorId')
			->setParameter('authorId', $author->getId())
			->select('COUNT(b.id) as booksCount')
			->getQuery()->getSingleScalarResult();

		$author->setBooksCount($result);

		$em->persist($author);
		$em->flush();
	}

	public function prePersist(LifecycleEventArgs $eventArgs): void
	{
		$this->lifecycleCoverImageUpload($eventArgs);
	}

	public function preUpdate(LifecycleEventArgs $eventArgs): void
	{
		$this->lifecycleCoverImageUpload($eventArgs);
	}

	public function preRemove(LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (
			!$entity instanceof Book
			|| (string)$entity->getCover() === ''
		)
		{
			return;
		}

		$this->fileUploader->delete($entity->getCover());
	}

	private function lifecycleCoverImageUpload(LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (!$entity instanceof Book)
		{
			return;
		}

		$existingValue = (string)$entity->getCover();
		$coverFilePath = $existingValue;

		if (
			$existingValue !== ''
			&& $entity->getCoverDelete() === true
		)
		{
			$this->fileUploader->delete($existingValue);
			$coverFilePath = '';
		}

		$newCoverImage = $entity->getCoverImage();
		if ($newCoverImage)
		{
			try
			{
				if ($existingValue !== '')
				{
					$this->fileUploader->delete($existingValue);
				}

				$coverFilePath = $this->fileUploader->upload($newCoverImage);
			}
			catch (\Exception $e)
			{
			}
		}

		$entity->setCover($coverFilePath);
		$entity->setCoverImage(null);
		$entity->setCoverDelete(null);
	}
}
