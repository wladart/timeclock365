<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{
	private FileUploader $fileUploader;

	public function __construct(FileUploader $fileUploader)
	{
		$this->fileUploader = $fileUploader;
	}

	private function getFileUploader(): FileUploader
	{
		return $this->fileUploader;
	}

    /**
     * @Route("/", name="app_book_index", methods={"GET"})
     */
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findBy([], [
				'id' => 'ASC',
			]),
        ]);
    }

	/**
	 * @Route("/{id}/record", name="app_book_record", methods={"GET"})
	 */
	public function record(string $id, BookRepository $bookRepository): Response
	{
		$id = (int)$id;
		if ($id <= 0)
		{
			throw $this->createNotFoundException();
		}

		return new JsonResponse([
			'output' => $this->renderView('book/index.html.twig', [
				'books' => $bookRepository->findBy([
					'id' => $id,
				]),
			]),
		], 200);
	}

	/**
	 * @Route("/filter", name="app_book_filter", methods={"POST"})
	 */
	public function filter(Request $request, BookRepository $bookRepository): Response
	{
		if ($content = $request->getContent())
		{
			$payload = json_decode($content, true);
		}

		$qb = $bookRepository->createQueryBuilder('b')
			->where('1 = 1')
			->orderBy('b.id', 'ASC');

		if (
			isset($payload['title'])
			&& ($title = trim($payload['title']))
			&& mb_strlen($title) > 0
		)
		{
			$qb->andWhere('b.title LIKE :title')
				->setParameter('title', '%' . $title . '%');
		}

		if (
			isset($payload['description'])
			&& ($description = trim($payload['description']))
			&& mb_strlen($description) > 0
		)
		{
			$qb
				->andWhere('b.description LIKE :description')
				->setParameter('description', '%' . $description . '%');
		}

		if (
			isset($payload['author'])
			&& ($author = trim($payload['author']))
			&& mb_strlen($author) > 0
		)
		{
			$qb->innerJoin('b.author', 'a')
				->andWhere('a.name LIKE :author OR a.lastName LIKE :author')
				->setParameter('author', '%' . $author . '%');
		}

		if (
			isset($payload['hasCover'])
			&& $payload['hasCover'] === 'Y'
		)
		{
			$qb->andWhere('b.cover IS NOT NULL AND b.cover <> :empty')
				->setParameter('empty', '');
		}

		if (
			isset($payload['year'])
			&& ($year = (int)$payload['year'])
			&& $year > 0
		)
		{
			$qb
				->andWhere('b.year = :year')
				->setParameter('year', $year);
		}

		return new JsonResponse([
			'output' => $this->renderView('book/index.html.twig', [
				'books' => $qb->getQuery()->getResult(),
			]),
		], 200);
	}

    /**
     * @Route("/new", name="app_book_new", methods={"GET", "POST"})
     */
    public function new(Request $request, BookRepository $bookRepository): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
		{
			$this->manageFileUpload($book, $request);
            $bookRepository->add($book);
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
			'coverDeleteDisplayValue' => 'none',
        ]);
    }

    /**
     * @Route("/{id}", name="app_book_show", methods={"GET"})
     */
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
			'authors' => $book->getAuthor(),
        ]);
    }

	/**
	 * @Route("/{id}/editform", name="app_book_editform", methods={"GET"})
	 */
	public function editForm(
		string $id,
		Request $request,
		BookRepository $bookRepository
	): Response
	{
		$id = (int)$id;
		if ($id <= 0)
		{
			throw $this->createNotFoundException();
		}

		$book = $bookRepository->find($id);
		if (!$book)
		{
			throw $this->createNotFoundException();
		}

		$form = $this->createForm(BookType::class, $book);
		$form->handleRequest($request);

		return new JsonResponse([
			'output' => $this->renderView('book/edit.html.twig', [
				'book' => $book,
				'form' => $form->createView(),
				'coverDeleteDisplayValue' => ($book->getCover() ? 'block' : 'none'),
			]),
		], 200);
	}

    /**
     * @Route("/{id}/edit", name="app_book_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
		{
			$this->manageFileUpload($book, $request);
            $bookRepository->add($book);

			if ($request->request->get('inline') === 'Y')
			{
				return new JsonResponse([
					'success' => 'Y',
				], 200);
			}
			else
			{
				return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
			}
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
			'coverDeleteDisplayValue' => ($book->getCover() ? 'block' : 'none'),
        ]);
    }

    /**
     * @Route("/{id}", name="app_book_delete", methods={"POST"})
     */
    public function delete(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token')))
		{
            $bookRepository->remove($book);
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

	private function manageFileUpload(object $book, Request $request): void
	{
		$requestFiles = $request->files->get('book');
		$requestFields = $request->request->get('book');

		$coverImage = $requestFiles['coverImage'];
		$coverDelete = (bool)($requestFields['coverDelete'] ?? false);

		if ($coverImage || $coverDelete)
		{
			$book->setUpdated(new \DateTime());

			if ($coverImage)
			{
				$book->setCoverImage($coverImage);
			}

			if ($coverDelete)
			{
				$book->setCoverDelete(true);
			}
		}
	}
}
