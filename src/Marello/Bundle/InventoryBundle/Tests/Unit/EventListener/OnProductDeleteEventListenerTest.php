<?php

namespace Marello\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;

use Marello\Bundle\InventoryBundle\EventListener\OnProductDeleteEventListener;
use Marello\Bundle\InventoryBundle\Manager\InventoryItemManagerInterface;
use Marello\Bundle\ProductBundle\Entity\Product;

class OnProductDeleteEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OnProductDeleteEventListener $listener */
    protected $listener;

    /** @var InventoryItemManagerInterface $inventoryItemManager */
    protected $inventoryItemManager;

    /** @var EntityManagerInterface $entityManager */
    protected $entityManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->entityManager = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryItemManager = $this
            ->getMockBuilder(InventoryItemManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OnProductDeleteEventListener($this->inventoryItemManager);
    }

    /**
     * {@inheritdoc}
     */
    public function testIfInventoryItemIsDeletedWhenProductIsDeleted()
    {
        $event = $this->prepareEvent();
        $uowMock = $this->createMock(UnitOfWork::class);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getUnitOfWork')
            ->willReturn($uowMock);

        $productMock = $this->createMock(Product::class);

        $uowMock->expects($this->atLeastOnce())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$productMock]);

        $this->inventoryItemManager->expects($this->atLeastOnce())
            ->method('getInventoryItemToDelete')
            ->with($productMock);

        $this->listener->onFlush($event);
    }

    /**
     * {@inheritdoc}
     */
    public function testNoProductsScheduledForDeletion()
    {
        $event = $this->prepareEvent();
        $uowMock = $this->createMock(UnitOfWork::class);

        $this->entityManager->expects($this->atLeastOnce())
            ->method('getUnitOfWork')
            ->willReturn($uowMock);

        $uowMock->expects($this->atLeastOnce())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->inventoryItemManager->expects($this->never())
            ->method('getInventoryItemToDelete');

        $this->listener->onFlush($event);
    }

    /**
     * @return OnFlushEventArgs
     */
    protected function prepareEvent()
    {
        return new OnFlushEventArgs($this->entityManager);
    }
}