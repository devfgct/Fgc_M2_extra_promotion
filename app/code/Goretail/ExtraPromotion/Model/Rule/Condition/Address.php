<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Goretail\ExtraPromotion\Model\Rule\Condition;

class Address extends \Magento\SalesRule\Model\Rule\Condition\Address {
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Directory\Model\Config\Source\Country $directoryCountry,
        \Magento\Directory\Model\Config\Source\Allregion $directoryAllregion,
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingAllmethods,
        \Magento\Payment\Model\Config\Source\Allmethods $paymentAllmethods,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\CatalogRule\Helper\Data $ruleHelper,
        array $data = []
    ) {
        parent::__construct($context, $directoryCountry, $directoryAllregion, $shippingAllmethods, $paymentAllmethods);
        $this->_coreRegistry = $coreRegistry;
        $this->_ruleHelper = $ruleHelper;
    }
    /**
     * Load attribute options
     *
     * @return $this
     */
    public function loadAttributeOptions() {
        $attributes = [
            'base_subtotal' => __('Subtotal'),
            'base_grand_total' => __('Grand Total'),
            'total_qty' => __('Total Items Quantity'),
            'weight' => __('Total Weight'),
            'shipping_method' => __('Shipping Method'),
            'postcode' => __('Shipping Postcode'),
            'region' => __('Shipping Region'),
            'region_id' => __('Shipping State/Province'),
            'country_id' => __('Shipping Country'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * Get input type
     *
     * @return string
     */
    public function getInputType() {
        switch ($this->getAttribute()) {
            case 'base_subtotal':
            case 'base_grand_total':
            case 'weight':
            case 'total_qty':
                return 'numeric';

            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }
        return 'string';
    }

    /**
     * Validate Address Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model) {
        //$this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$coreRegistry = $this->_objectManager->get(\Magento\Framework\Registry::class);
        $address = $model;
        if (!$address instanceof \Magento\Quote\Model\Quote\Address) {
            if ($model->getQuote()->isVirtual()) {
                $address = $model->getQuote()->getBillingAddress();
            } else {
                $address = $model->getQuote()->getShippingAddress();
            }
        }

        if ('payment_method' == $this->getAttribute() && !$address->hasPaymentMethod()) {
            $address->setPaymentMethod($model->getQuote()->getPayment()->getMethod());
        }

        $result = parent::validate($address);

        $rule = $this->getRule();

        $attribute = $this->getAttribute();
        $subtotal = $address->getBaseSubtotal();
        $grandtotal = $address->getBaseGrandTotal();
        
        $discountAmountRegistry = $this->_coreRegistry->registry('extra_promo_discount_amount_rule') ?: 0;

        $ruleAmount = $rule->getDiscountAmount();

        $priceTotal = $attribute == 'base_grand_total' ? $grandtotal : $subtotal;

        // $priceRule = $this->_ruleHelper->calcPriceRule($rule->getSimpleAction(), $ruleAmount, $price);

        switch ($rule->getSimpleAction()) {
            case 'by_percent': // Percent of product price discount
                $ruleAmount = ($ruleAmount / 100) * $priceTotal;
                break;
            case 'buy_x_get_y': // Buy X get Y free (discount amount is Y)
                break;
            case 'by_fixed': // Fixed amount discount
                break;
            case 'cart_fixed': // Fixed amount discount for whole cart
                break;
            default:
                break;
        }

        if($result) {
            if($attribute=='base_grand_total') {
                $discountAmountTotal = $discountAmountRegistry;
            } else {
                $discountAmountTotal = $ruleAmount + $discountAmountRegistry;
            }
        } else {
            $discountAmountTotal = $discountAmountRegistry;
        }

        $this->_coreRegistry->register('extra_promo_discount_amount_rule', $discountAmountTotal, true);

        $grandTotal = $address->getBaseSubtotal() + (-$discountAmountTotal) + $address->getBaseShippingAmount() + $address->getBaseTaxAmount();
        $address->setBaseGrandTotal($grandTotal);


        $result = parent::validate($address);

        return $result;
    }
}
