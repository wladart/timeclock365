<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{
    /**
     * @Route("/", name="app_book_index", methods={"GET"})
     */
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
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
			$coverFilePath = $this->getCoverPath($form, $book);
			$book->setCover($coverFilePath);

            $bookRepository->add($book);
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
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
     * @Route("/{id}/edit", name="app_book_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
		{
			$coverFilePath = $this->getCoverPath($form, $book);
			$book->setCover($coverFilePath);

            $bookRepository->add($book);
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_book_delete", methods={"POST"})
     */
    public function delete(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $bookRepository->remove($book);
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

	private function getCoverPath(FormInterface $form, Book $book): string
	{
		$uploadDir = $this->getParameter('app.upload_dir');
		$coverFile = $form->get('cover')->getData();
		$coverDelete = $form->get('cover_delete')->getData();

		if ($book->getId())
		{
			$existingValue = $book->getCover();
			$coverFilePath = $existingValue;

			if ($existingValue && $coverDelete)
			{
				try
				{
					$filesystem = new Filesystem();
					$filesystem->remove($uploadDir . '/' . $existingValue);

					[ $subFolder, ] = explode('/', $existingValue);
					$subFolder = $uploadDir . '/' . $subFolder;

					$scanResult = scandir($subFolder);
					$scanResult = array_filter($scanResult, function ($val) {
						return !in_array($val, [ '.', '..'], true);
					});
					if (empty($scanResult))
					{
						$filesystem->remove($subFolder);
					}
				}
				catch (IOException $e)
				{
				}

				$coverFilePath = '';
			}
		}

		if ($coverFile)
		{
			$filename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
			$filename = mb_strtolower(preg_replace('/[^A-Za-z0-9_]/u', '_', $filename));
			$newFilename = $filename . '-' . uniqid() . '.' . $coverFile->guessExtension();
			$subFolder = substr(md5($newFilename), 0, 3);

			try
			{
				$coverFile->move(
					$uploadDir . '/' . $subFolder,
					$newFilename
				);

				$coverFilePath = $subFolder . '/' . $newFilename;
			}
			catch (FileException $e)
			{
			}
		}

		return $coverFilePath;
	}
}
