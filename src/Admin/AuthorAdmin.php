<?php

namespace App\Admin;

use App\Entity\Author;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

final class AuthorAdmin extends AbstractAdmin
{
	protected function configureFormFields(FormMapper $form): void
	{
		$form->add('name', TextType::class);
		$form->add('lastName', TextType::class);
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid): void
	{
		$datagrid->add('name');
		$datagrid->add('lastName');
	}

	protected function configureListFields(ListMapper $list): void
	{
		$list->addIdentifier('name');
		$list->addIdentifier('lastName');
	}

	protected function configureShowFields(ShowMapper $show): void
	{
		$show->add('name');
		$show->add('lastName');
	}

	public function toString(object $object): string
	{
		return $object instanceof Author
			? $object->getName() . '  ' . $object->getLastName()
			: 'Author';
	}
}
