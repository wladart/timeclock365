<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AppFixtures extends Fixture implements FixtureInterface, ContainerAwareInterface
{
	private $container;

	public function setContainer(ContainerInterface $container = null)
	{
		$this->container = $container;
	}

    public function load(ObjectManager $manager): void
    {
		$userAdmin = new User();
		$userAdmin->setUsername('admin');
		$userAdmin->setEmail('test@example.com');
		$userAdmin->setEnabled(true);
		$hash = $this->container->get('security.password_encoder')->encodePassword($userAdmin, '123456');
		$userAdmin->setPassword($hash);
		$userAdmin->setSuperAdmin(true);
		$manager->persist($userAdmin);

		// Pushkin
		$author = new Author();
		$author->setName('Alexander');
		$author->setLastName('Pushkin');
		$manager->persist($author);

		$book = new Book();
		$book->setTitle('The Queen of Spades');
		$book->addAuthor($author);
		$book->setYear('2020');
		$manager->persist($book);

		$book = new Book();
		$book->setTitle('The Blizzard');
		$book->addAuthor($author);
		$book->setYear('2021');
		$manager->persist($book);

		// Tolstoy
		$author = new Author();
		$author->setName('Leo');
		$author->setLastName('Tolstoy');
		$manager->persist($author);

		$book = new Book();
		$book->setTitle('War and Peace');
		$book->addAuthor($author);
		$book->setYear('2022');
		$manager->persist($book);

		// Dostoyevsky
		$author = new Author();
		$author->setName('Fyodor');
		$author->setLastName('Dostoyevsky');
		$manager->persist($author);

		$book = new Book();
		$book->setTitle('Crime and Punishment');
		$book->addAuthor($author);
		$book->setYear('2010');
		$manager->persist($book);

		// Ilf && Petrov
		$author1 = new Author();
		$author1->setName('Ilya');
		$author1->setLastName('Ilf');
		$manager->persist($author1);

		$author2 = new Author();
		$author2->setName('Yevgeny');
		$author2->setLastName('Petrov');
		$manager->persist($author2);

		$book = new Book();
		$book->setTitle('The Twelve Chairs');
		$book->addAuthor($author1);
		$book->addAuthor($author2);
		$book->setYear('2000');
		$manager->persist($book);

		$book = new Book();
		$book->setTitle('The Little Golden Calf');
		$book->addAuthor($author1);
		$book->addAuthor($author2);
		$book->setYear('2000');
		$manager->persist($book);

		// Strugatskys
		$author1 = new Author();
		$author1->setName('Arkady');
		$author1->setLastName('Strugatsky');
		$manager->persist($author1);

		$author2 = new Author();
		$author2->setName('Boris');
		$author2->setLastName('Strugatsky');
		$manager->persist($author2);

		$book = new Book();
		$book->setTitle('Snail on the Slope');
		$book->addAuthor($author1);
		$book->addAuthor($author2);
		$book->setYear('1990');
		$manager->persist($book);

		$book = new Book();
		$book->setTitle('Ugly Swans');
		$book->addAuthor($author1);
		$book->addAuthor($author2);
		$book->setYear('1990');
		$manager->persist($book);

		$manager->flush();
    }
}
