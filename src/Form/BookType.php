<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\Image;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('cover', FileType::class, [
				'mapped' => false,
				'required' => false,
				'constraints' => [
					new Image([
						'maxWidth' => '5000',
						'maxHeight' => '5000',
					]),
				],
			])
			->add('cover_delete', CheckboxType::class, [
				'mapped' => false,
				'required' => false,
				'label' => 'delete',
			])
            ->add('year')
            ->add('author')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
