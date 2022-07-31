<?php

namespace App\Admin;

use App\Entity\Book;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class BookAdmin extends AbstractAdmin
{
	protected function configureFormFields(FormMapper $form): void
	{
		$form
			->add('title', TextType::class)
			->add('description', TextAreaType::class, [
				'required' => false,
			])
			->add('author', ModelAutocompleteType::class, [
				'property' => 'author',
				'multiple' => true,
				'callback' => static function (AdminInterface $admin, string $property, $value): void {
					$datagrid = $admin->getDatagrid();
					$query = $datagrid->getQuery();
					$query
						->andWhere($query->getRootAlias() . '.name LIKE :value OR ' . $query->getRootAlias() . '.lastName LIKE :value')
						->setParameter('value', '%' . $value . '%')
					;
					$datagrid->setValue($property, null, $value);
				},
			])
			->add('coverImage', FileType::class, [
				'required' => false,
				'label' => 'Upload cover image',
			])
			->add('cover', TextType::class, [
				'required' => false,
				'disabled' => true,
				'label' => 'Current cover file path',
			])
			->add('coverDelete', CheckboxType::class, [
				'required' => false,
				'label' => 'Delete cover',
			])
			->add('year', NumberType::class, [
				'required' => false,
			]);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid): void
	{
		$datagrid
			->add('title')
			->add('description')
			->add('author')
			->add('hasCover', CallbackFilter::class, array(
				'callback' => static function($queryBuilder, $alias, $field, FilterData $data): bool
					{
						if (!$data->hasValue())
						{
							return false;
						}

						$value = $data->getValue();
						if (!in_array($value, [ 'Y', 'N' ], true))
						{
							return false;
						}

						if ($value === 'Y')
						{
							$queryBuilder
								->where($alias . '.cover IS NOT NULL AND ' . $alias .'.cover <> :empty')
								->setParameter('empty', '');
						}
						else
						{
							$queryBuilder
								->where($alias . '.cover IS NULL OR ' . $alias .'.cover = :empty')
								->setParameter('empty', '');
						}

						return true;
					},
				'field_type' => ChoiceType::class,
				'field_options' => [
					'choices' => [
						'No matter' => 'A',
						'Yes' => 'Y',
						'No' => 'N',
					],
				],
				'label' => 'Has cover',
			))
			->add('year');
	}

	protected function configureListFields(ListMapper $list): void
	{
		$list
			->addIdentifier('title')
			->addIdentifier('description')
			->add('cover')
			->addIdentifier('author')
			->addIdentifier('year');
	}

	protected function configureShowFields(ShowMapper $show): void
	{
		$show
			->add('title')
			->add('description')
			->add('cover')
			->add('author')
			->add('year');
	}

	public function toString(object $book): string
	{
		return (
			$book instanceof Book
				? $book->getTitle()
				: 'Book'
		);
	}

	public function prePersist(object $book): void
	{
		$this->manageFileUpload($book);
	}

	public function preUpdate(object $book): void
	{
		$this->manageFileUpload($book);
	}

	private function manageFileUpload(object $book): void
	{
		if (
			$book->getCoverImage()
			|| $book->getCoverDelete()
		)
		{
			$book->setUpdated(new \DateTime());
		}
	}
}
