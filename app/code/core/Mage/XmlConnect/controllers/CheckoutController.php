<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_XmlConnect
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * XmlConnect checkout controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_XmlConnect_CheckoutController extends Mage_XmlConnect_Controller_Action
{

    /**
     * Make sure customer is logged in
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!Mage::getSingleton('customer/session')->isLoggedIn()
            && !Mage::getSingleton('checkout/session')->getQuote()->isAllowedGuestCheckout()) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $this->_message(Mage::helper('xmlconnect')->__('Customer not logged in.'), self::MESSAGE_STATUS_ERROR);
            return ;
        }
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Onepage Checkout page
     */
    public function indexAction()
    {
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            $this->_message(Mage::helper('xmlconnect')->__('Onepage checkout is disabled.'), self::MESSAGE_STATUS_ERROR);
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if ($quote->getHasError()) {
            $this->_message(Mage::helper('xmlconnect')->__('Cart has some errors.'), self::MESSAGE_STATUS_ERROR);
            return;
        } else if (!$quote->hasItems()) {
            $this->_message(Mage::helper('xmlconnect')->__('Cart is empty.'), self::MESSAGE_STATUS_ERROR);
            return;
        } else if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            $this->_message($error, self::MESSAGE_STATUS_ERROR);
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        $this->getOnepage()->initCheckout();

        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Display customer new billing addrress form
     */
    public function newBillingAddressFormAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Display customer new shipping addrress form
     */
    public function newShippingAddressFormAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Billing addresses list action
     */
    public function billingAddressAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Save billing address to current quote using onepage model
     */
    public function saveBillingAddressAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_message(Mage::helper('xmlconnect')->__('Specified invalid data.'), self::MESSAGE_STATUS_ERROR);
            return;
        }

        $data = $this->getRequest()->getPost('billing', array());
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        if (isset($data['email'])) {
            $data['email'] = trim($data['email']);
        }
        $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
        if (!isset($result['error'])) {
            $this->_message(Mage::helper('xmlconnect')->__('Billing address has been set.'), self::MESSAGE_STATUS_SUCCESS);
        } else {
            if (!is_array($result['message'])) {
                $result['message'] = array($result['message']);
            }
            $this->_message(implode('. ', $result['message']), self::MESSAGE_STATUS_ERROR);
        }
    }

    /**
     * Shipping addresses list action
     */
    public function shippingAddressAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Save shipping address to current quote using onepage model
     */
    public function saveShippingAddressAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_message(Mage::helper('xmlconnect')->__('Specified invalid data.'), self::MESSAGE_STATUS_ERROR);
            return;
        }

        $data = $this->getRequest()->getPost('shipping', array());
        $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
        $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
        if (!isset($result['error'])) {
            $this->_message(Mage::helper('xmlconnect')->__('Shipping address has been set.'), self::MESSAGE_STATUS_SUCCESS);
        } else {
            if (!is_array($result['message'])) {
                $result['message'] = array($result['message']);
            }
            $this->_message(implode('. ', $result['message']), self::MESSAGE_STATUS_ERROR);
        }
    }

    /**
     * Get shipping methods for current quote
     */
    public function shippingMethodsAction()
    {
        try {
            $result = array('error' => Mage::helper('xmlconnect')->__('Error.'));
            $this->getOnepage()->getQuote()->getShippingAddress()->setCollectShippingRates(true);
            $this->getOnepage()->getQuote()->collectTotals()->save();
            $this->loadLayout(false);
            $this->renderLayout();
            return;
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        }
        $this->_message($result['error'], self::MESSAGE_STATUS_ERROR);
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_message(Mage::helper('xmlconnect')->__('Specified invalid data.'), self::MESSAGE_STATUS_ERROR);
            return;
        }

        $data = $this->getRequest()->getPost('shipping_method', '');
        $result = $this->getOnepage()->saveShippingMethod($data);
        if (!isset($result['error'])) {
            $this->_message(Mage::helper('xmlconnect')->__('Shipping method has been set.'), self::MESSAGE_STATUS_SUCCESS);
        } else {
            if (!is_array($result['message'])) {
                $result['message'] = array($result['message']);
            }
            Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));
            $this->_message(implode('. ', $result['message']), self::MESSAGE_STATUS_ERROR);
        }
    }


    /**
     * Save checkout method
     */
    public function saveMethodAction()
    {
        if ($this->getRequest()->isPost()) {
            $method = (string) $this->getRequest()->getPost('method');
            $result = $this->getOnepage()->saveCheckoutMethod($method);
            if (!isset($result['error'])) {
                $this->_message(Mage::helper('xmlconnect')->__('Payment Method has been set.'), self::MESSAGE_STATUS_SUCCESS);
            } else {
                if (!is_array($result['message'])) {
                    $result['message'] = array($result['message']);
                }
                $this->_message(implode('. ', $result['message']), self::MESSAGE_STATUS_ERROR);
            }
        }
    }

    /**
     * Get payment methods action
     */
    public function paymentMethodsAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Save payment action
     */
    public function savePaymentAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_message(Mage::helper('xmlconnect')->__('Specified invalid data.'), self::MESSAGE_STATUS_ERROR);
            return;
        }
        try {
            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);
            $this->_message(Mage::helper('xmlconnect')->__('Payment method was successfully set.'), self::MESSAGE_STATUS_SUCCESS);
            return;
        } catch (Mage_Payment_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = Mage::helper('xmlconnect')->__('Unable to set payment method.');
        }
        $this->_message($result['error'], self::MESSAGE_STATUS_ERROR);
    }

    /**
     * Order summary info action
     */
    public function orderReviewAction()
    {
        $this->getOnepage()->getQuote()->collectTotals()->save();
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Create order action
     */
    public function saveOrderAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_message(Mage::helper('xmlconnect')->__('Specified invalid data.'), self::MESSAGE_STATUS_ERROR);
            return;
        }

        try {
            if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                    $error = Mage::helper('xmlconnect')->__('Please agree to all the terms and conditions before placing the order.');
                    $this->_message($error, self::MESSAGE_STATUS_ERROR);
                    return;
                }
            }
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }
            $this->getOnepage()->saveOrder();

            $message = new Mage_XmlConnect_Model_Simplexml_Element('<message></message>');
            $message->addChild('status', self::MESSAGE_STATUS_SUCCESS);

            $orderId = $this->getOnepage()->getLastOrderId();

            $text = Mage::helper('xmlconnect')->__('Thank you for your purchase! ');
            $text .= Mage::helper('xmlconnect')->__('Your order # is: %s. ', $orderId);
            $text .= Mage::helper('xmlconnect')->__('You will receive an order confirmation email with details of your order and a link to track its progress.');
            $message->addChild('text', $text);

            $message->addChild('order_id', $orderId);

            $this->getOnepage()->getQuote()->save();
            $this->getOnepage()->getCheckout()->clear();

            $this->getResponse()->setBody($message->asNiceXml());
            return;
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $error = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $error = Mage::helper('xmlconnect')->__('An error occurred while processing your order. Please contact us or try again later.');
        }
        $this->getOnepage()->getQuote()->save();

        $this->_message($error, self::MESSAGE_STATUS_ERROR);
    }
}