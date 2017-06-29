<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Marello\Bundle\InventoryBundle\Entity\InventoryLevel;
use Marello\Bundle\InventoryBundle\Model\InventoryLevelCalculator;
use MarelloEnterprise\Bundle\InventoryBundle\Form\Type\WarehouseSelectType;

class InventoryLevelType extends AbstractType
{
    const NAME = 'marello_inventory_inventorylevel';

    /** @var EventSubscriberInterface $subscriber */
    protected $subscriber;

    public function __construct(EventSubscriberInterface $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('warehouse', WarehouseSelectType::class)
            ->add('adjustmentOperator', ChoiceType::class, [
                'choices'            => [
                    InventoryLevelCalculator::OPERATOR_INCREASE => 'increase',
                    InventoryLevelCalculator::OPERATOR_DECREASE => 'decrease',
                ],
                'translation_domain' => 'MarelloInventoryChangeDirection',
                'mapped'      => false
            ])
            ->add('quantity', NumberType::class, [
                'constraints' => new GreaterThanOrEqual(0),
                'data'  => 0,
                'mapped'      => false
            ])
            ->add('desiredInventoryQty', NumberType::class, [
                'constraints' => new GreaterThanOrEqual(0)
            ])
            ->add('purchaseInventoryQty', NumberType::class, [
                'constraints' => new GreaterThanOrEqual(0)
            ]);

        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InventoryLevel::class,
            'cascade_validation' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
