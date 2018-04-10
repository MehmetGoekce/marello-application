<?php

namespace Marello\Bundle\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation as Oro;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Marello\Bundle\OrderBundle\Model\ExtendOrder;
use Marello\Bundle\SalesBundle\Entity\SalesChannel;
use Marello\Bundle\TaxBundle\Model\TaxAwareInterface;
use Marello\Bundle\AddressBundle\Entity\MarelloAddress;
use Marello\Bundle\LocaleBundle\Model\LocalizationTrait;
use Marello\Bundle\ShippingBundle\Entity\HasShipmentTrait;
use Marello\Bundle\LocaleBundle\Model\LocaleAwareInterface;
use Marello\Bundle\SalesBundle\Model\ChannelAwareInterface;
use Marello\Bundle\OrderBundle\Model\DiscountAwareInterface;
use Marello\Bundle\PricingBundle\Model\CurrencyAwareInterface;
use Marello\Bundle\CoreBundle\Model\EntityCreatedUpdatedAtTrait;
use Marello\Bundle\ShippingBundle\Integration\ShippingAwareInterface;
use Marello\Bundle\PricingBundle\Subtotal\Model\SubtotalAwareInterface;
use Marello\Bundle\PricingBundle\Subtotal\Model\LineItemsAwareInterface;
use Marello\Bundle\CoreBundle\DerivedProperty\DerivedPropertyAwareInterface;

/**
 * @ORM\Entity(repositoryClass="Marello\Bundle\OrderBundle\Entity\Repository\OrderRepository")
 * @Oro\Config(
 *      routeView="marello_order_order_view",
 *      routeName="marello_order_order_index",
 *      routeCreate="marello_order_order_create",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-shopping-cart"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 * @ORM\Table(
 *      name="marello_order_order",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"order_reference", "saleschannel_id"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Order extends ExtendOrder implements
    DerivedPropertyAwareInterface,
    CurrencyAwareInterface,
    DiscountAwareInterface,
    ShippingAwareInterface,
    SubtotalAwareInterface,
    TaxAwareInterface,
    LineItemsAwareInterface,
    LocaleAwareInterface,
    ChannelAwareInterface
{
    use HasShipmentTrait;
    use LocalizationTrait;
    use EntityCreatedUpdatedAtTrait;
    
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_number", type="string", unique=true, nullable=true)
     */
    protected $orderNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="order_reference", type="string", nullable=true)
     */
    protected $orderReference;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_reference", type="string", nullable=true)
     */
    protected $invoiceReference;

    /**
     * @var int
     *
     * @ORM\Column(name="subtotal", type="money")
     */
    protected $subtotal = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="total_tax", type="money")
     */
    protected $totalTax = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="grand_total", type="money")
     */
    protected $grandTotal = 0;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=10, nullable=true)
     */
    protected $currency;

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string", length=255, nullable=true)
     */
    protected $paymentMethod;

    /**
     * @var string
     * @ORM\Column(name="payment_reference", type="string", length=255, nullable=true)
     */
    protected $paymentReference;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_details", type="text", nullable=true)
     */
    protected $paymentDetails;

    /**
     * @var double
     *
     * @ORM\Column(name="shipping_amount_incl_tax", type="money", nullable=true)
     */
    protected $shippingAmountInclTax;

    /**
     * @var double
     *
     * @ORM\Column(name="shipping_amount_excl_tax", type="money", nullable=true)
     */
    protected $shippingAmountExclTax;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_method", type="string", nullable=true)
     */
    protected $shippingMethod;

    /**
     * @var double
     *
     * @ORM\Column(name="discount_amount", type="money", nullable=true)
     */
    protected $discountAmount;

    /**
     * @var float
     *
     * @ORM\Column(name="discount_percent", type="percent", nullable=true)
     */
    protected $discountPercent;

    /**
     * @var string
     * @ORM\Column(name="coupon_code", type="string", length=255, nullable=true)
     */
    protected $couponCode;

    /**
     * @var Collection|OrderItem[]
     *
     * @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order", cascade={"persist"}, orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     * @Oro\ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=true
     *          }
     *      }
     * )
     */
    protected $items;

    /**
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\OrderBundle\Entity\Customer", cascade={"persist"})
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     *
     * @var Customer
     */
    protected $customer;

    /**
     * @var AbstractAddress
     *
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\AddressBundle\Entity\MarelloAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id")
     */
    protected $billingAddress;

    /**
     * @var AbstractAddress
     *
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\AddressBundle\Entity\MarelloAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id")
     */
    protected $shippingAddress;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invoiced_at", type="datetime", nullable=true)
     */
    protected $invoicedAt;

    /**
     * @var SalesChannel
     *
     * @ORM\ManyToOne(targetEntity="Marello\Bundle\SalesBundle\Entity\SalesChannel")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "full"=true
     *          }
     *      }
     * )
     */
    protected $salesChannel;

    /**
     * @var string
     *
     * @ORM\Column(name="saleschannel_name",type="string", nullable=false)
     */
    protected $salesChannelName;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", nullable=false)
     */
    protected $organization;

    /**
     * @var array $data
     *
     * @ORM\Column(name="data", type="json_array", nullable=true)
     * @Oro\ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $data;

    /**
     * @param AbstractAddress|null $billingAddress
     * @param AbstractAddress|null $shippingAddress
     */
    public function __construct(
        AbstractAddress $billingAddress = null,
        AbstractAddress $shippingAddress = null
    ) {
        parent::__construct();
        
        $this->items           = new ArrayCollection();
        $this->billingAddress  = $billingAddress;
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->salesChannelName = $this->salesChannel->getName();
    }

    /**
     * @return int
     */
    public function getOrderReference()
    {
        return $this->orderReference;
    }

    /**
     * @param int $orderReference
     *
     * @return $this
     */
    public function setOrderReference($orderReference)
    {
        $this->orderReference = $orderReference;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceReference()
    {
        return $this->invoiceReference;
    }

    /**
     * @param string $invoiceReference
     *
     * @return $this
     */
    public function setInvoiceReference($invoiceReference)
    {
        $this->invoiceReference = $invoiceReference;

        return $this;
    }

    /**
     * @return int
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * @param int $subtotal
     *
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalTax()
    {
        return $this->totalTax;
    }

    /**
     * @return int
     */
    public function getTax()
    {
        return $this->getTotalTax();
    }

    /**
     * @param int $totalTax
     *
     * @return $this
     */
    public function setTotalTax($totalTax)
    {
        $this->totalTax = $totalTax;

        return $this;
    }

    /**
     * @return int
     */
    public function getGrandTotal()
    {
        return $this->grandTotal;
    }

    /**
     * @param int $grandTotal
     *
     * @return $this
     */
    public function setGrandTotal($grandTotal)
    {
        $this->grandTotal = $grandTotal;

        return $this;
    }

    /**
     * @return MarelloAddress
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param MarelloAddress $shippingAddress
     *
     * @return $this
     */
    public function setShippingAddress($shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * @return MarelloAddress
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @param MarelloAddress $billingAddress
     *
     * @return $this
     */
    public function setBillingAddress($billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * @return Collection|OrderItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param OrderItem $item
     *
     * @return $this
     */
    public function addItem(OrderItem $item)
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    /**
     * @param OrderItem $item
     *
     * @return $this
     */
    public function removeItem(OrderItem $item)
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SalesChannel
     */
    public function getSalesChannel()
    {
        return $this->salesChannel;
    }

    /**
     * @param SalesChannel $salesChannel
     *
     * @return $this
     */
    public function setSalesChannel($salesChannel)
    {
        $this->salesChannel = $salesChannel;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     *
     * @return $this
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalesChannelName()
    {
        return $this->salesChannelName;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     *
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentDetails()
    {
        return $this->paymentDetails;
    }

    /**
     * @param string $paymentDetails
     *
     * @return $this
     */
    public function setPaymentDetails($paymentDetails)
    {
        $this->paymentDetails = $paymentDetails;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }

    /**
     * @param string $shippingMethod
     *
     * @return $this
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * @param float $discountAmount
     *
     * @return $this
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountPercent()
    {
        return $this->discountPercent;
    }

    /**
     * @param float $discountPercent
     *
     * @return $this
     */
    public function setDiscountPercent($discountPercent)
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    /**
     * @return string
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @param string $couponCode
     */
    public function setCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;
    }

    /**
     * @return \DateTime
     */
    public function getInvoicedAt()
    {
        return $this->invoicedAt;
    }

    /**
     * @param \DateTime $invoicedAt
     *
     * @return $this
     */
    public function setInvoicedAt($invoicedAt)
    {
        $this->invoicedAt = $invoicedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentReference()
    {
        return $this->paymentReference;
    }

    /**
     * @param string $paymentReference
     *
     * @return $this
     */
    public function setPaymentReference($paymentReference)
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return $this
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @param int $id
     */
    public function setDerivedProperty($id)
    {
        if (!$this->orderNumber) {
            $this->setOrderNumber(sprintf('%09d', $id));
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('#%s', $this->orderNumber);
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     *
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Set shippingAmountInclTax
     *
     * @param float $shippingAmountInclTax
     *
     * @return Order
     */
    public function setShippingAmountInclTax($shippingAmountInclTax)
    {
        $this->shippingAmountInclTax = $shippingAmountInclTax;

        return $this;
    }

    /**
     * Get shippingAmountInclTax
     *
     * @return float
     */
    public function getShippingAmountInclTax()
    {
        return $this->shippingAmountInclTax;
    }

    /**
     * Set shippingAmountExclTax
     *
     * @param float $shippingAmountExclTax
     *
     * @return Order
     */
    public function setShippingAmountExclTax($shippingAmountExclTax)
    {
        $this->shippingAmountExclTax = $shippingAmountExclTax;

        return $this;
    }

    /**
     * Get shippingAmountExclTax
     *
     * @return float
     */
    public function getShippingAmountExclTax()
    {
        return $this->shippingAmountExclTax;
    }

    /**
     * Set salesChannelName
     *
     * @param string $salesChannelName
     *
     * @return Order
     */
    public function setSalesChannelName($salesChannelName)
    {
        $this->salesChannelName = $salesChannelName;

        return $this;
    }

    /**
     * @Assert\Callback
     *
     * @param ExecutionContextInterface $context
     */
    public function checkDiscountLowerThanSubTotal(ExecutionContextInterface $context)
    {
        if ($this->getDiscountAmount() > $this->getSubtotal()) {
            $context->buildViolation('marello.order.discount_amount.validation.discount_lower_than_subtotal')
                ->atPath('discountAmount')
                ->addViolation();
        }
    }

    /**
     * @param array $data
     *
     * @return Order
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
