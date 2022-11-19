<?php

namespace Shopware\Components\Api\Resource;

use Doctrine\ORM\AbstractQuery;
use Shopware\Models\Customer\Customer as CustomerModel;
use Shopware\Models\Order\Order as OrderModel;
use Shopware\Models\Customer\Group;

/**
 * @package Shopware\Components\Api\Resource
 */
class Freshdesk extends Resource
{
    /**
     * @param string|null $email
     * @param string|null $orderId
     * @return array
     */
    public function getInfo($email, $orderId)
    {
        $this->checkPrivilege('read');
        $data = ['customer_list' => [], 'order_list' => []];

        if ($orderId) {
            $customerId = $this->getCustomerIdFromOrder($orderId);
        } else {
            $customerId = $this->getCustomerIdFromEmail($email);
        }

        if (!$customerId) {
            return $data;
        }

        $data['customer_list'] = $this->getCustomerData($customerId);
        $data['order_list'] = $this->getOrderData($customerId);

        return $data;
    }

    protected function getCustomerIdFromEmail($email)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['customer.id'])
            ->from(CustomerModel::class, 'customer')
            ->where('customer.email = ?1')
            ->setParameter(1, $email);

        try {
            $id = $builder->getQuery()->getOneOrNullResult();
            $id = $id['id'];
        } catch (\Exception $e) {
            $id = null;
        }
        return $id;
    }

    protected function getCustomerIdFromOrder($orderId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(['orders.customerId'])
            ->from(OrderModel::class, 'orders')
            ->where('orders.number = ?1')
            ->setParameter(1, $orderId);

        try {
            $id = $builder->getQuery()->getOneOrNullResult();
            $id = $id['customerId'];
        } catch (\Exception $e) {
            $id = null;
        }
        return $id;
    }

    protected function getCustomerData($customerId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select([
            'customer',
            'customergroup',
            'billing',
            'billingCountry',
            'billingState',
            'shipping',
            'shippingCountry',
            'shippingState',
            'paymentData',
            'doneOrders',
        ]);
        $builder->from(CustomerModel::class, 'customer')
            ->leftJoin('customer.defaultBillingAddress', 'billing')
            ->leftJoin('customer.group', 'customergroup')
            ->leftJoin('billing.country', 'billingCountry')
            ->leftJoin('billing.state', 'billingState')
            ->leftJoin('customer.defaultShippingAddress', 'shipping')
            ->leftJoin('shipping.country', 'shippingCountry')
            ->leftJoin('shipping.state', 'shippingState')
            ->leftJoin('customer.orders', 'doneOrders', \Doctrine\ORM\Query\Expr\Join::WITH, 'doneOrders.status <> -1 AND doneOrders.status <> 4')
            ->leftJoin('customer.paymentData', 'paymentData', \Doctrine\ORM\Query\Expr\Join::WITH, 'paymentData.paymentMean = customer.paymentId')
            ->where('customer.id = :customerId')
            ->setParameter('customerId', $customerId);

        $builder->groupBy('customer.id');

        try {
            $data = $builder->getQuery()->getArrayResult();
        } catch (\Exception $e) {
            $data = [];
        }
        return $this->prepareCustomerData($data);
    }

    protected function prepareCustomerData($data)
    {
        $result = [];

        foreach ($data as $customer) {
            $result[] = [
                'customer_number' => $customer['number'],
                'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                'email' => $customer['email'],
                'group' => $customer['group']['name'],
                'country' => $customer['defaultBillingAddress']['country']['name'],
                'total_sales' => $this->getTotalSales($customer['orders']),
                'created_at' => $customer['firstLogin']->format('Y-m-d'),
                'billing_address' => $this->formatAddress($customer['defaultBillingAddress']),
                'shipping_address' => $this->formatAddress($customer['defaultShippingAddress']),
            ];
        }

        return $result;
    }

    protected function getTotalSales($orderList)
    {
        $total = [];
        if (!$orderList) {
            return '0';
        }
        foreach ($orderList as $order) {
            $key = $order['currency'];
            if (!array_key_exists($key, $total)) {
                $total[$key] = 0;
            }
            $total[$key] += $order['invoiceAmount'];
        }
        $result = [];
        foreach ($total as $key => $value) {
            $result[] = $value . ' ('.$key.')';
        }
        return implode(', ', $result);
    }

    protected function getOrderData($customerId)
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select([
            'orders',
            'details',
            'documents',
            'payment',
            'paymentInstances',
            'shipping',
            'billing',
            'billingCountry',
            'shippingCountry',
            'billingState',
            'shippingState',
            'dispatch',
            'paymentStatus',
            'orderStatus',
            'documentType',
            'billingAttribute',
            'attribute',
            'detailAttribute',
            'documentAttribute',
            'shippingAttribute',
            'paymentAttribute',
            'dispatchAttribute',
            'shop',
        ]);

        $builder->from(OrderModel::class, 'orders');
        $builder->leftJoin('orders.details', 'details')
            ->leftJoin('orders.documents', 'documents')
            ->leftJoin('documents.type', 'documentType')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->leftJoin('orders.paymentInstances', 'paymentInstances')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('billing.country', 'billingCountry')
            ->leftJoin('billing.state', 'billingState')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('orders.dispatch', 'dispatch')
            ->leftJoin('payment.attribute', 'paymentAttribute')
            ->leftJoin('dispatch.attribute', 'dispatchAttribute')
            ->leftJoin('billing.attribute', 'billingAttribute')
            ->leftJoin('shipping.attribute', 'shippingAttribute')
            ->leftJoin('details.attribute', 'detailAttribute')
            ->leftJoin('documents.attribute', 'documentAttribute')
            ->leftJoin('orders.attribute', 'attribute')
            ->leftJoin('shipping.country', 'shippingCountry')
            ->leftJoin('shipping.state', 'shippingState')
            ->leftJoin('orders.shop', 'shop');

        $builder->andWhere('orders.status != -1');
        $builder->andWhere('orders.number IS NOT NULL');
        $builder->andWhere('orders.customerId = :customerId')
            ->setParameter('customerId', $customerId);

        try {
            $data = $builder->getQuery()->getArrayResult();
        } catch (\Exception $e) {
            $data = [];
        }
        return $this->prepareOrderData($data);
    }

    protected function prepareOrderData($data)
    {
        $result = [];
        foreach ($data as $order) {
            $orderItemInfo = [];

            foreach ($order['details'] as $orderItem) {
                $orderItemInfo[] = [
                    'product_number' => $orderItem['number'],
                    'name' => $orderItem['articleName'],
                    'sku' => $orderItem['articleNumber'],
                    'price' => $this->formatPrice($orderItem['price'], $order['currency']),
                    'tax_rate' => (int)$orderItem['taxRate'],
                    'ordered_qty' => (int)$orderItem['quantity'],
                ];
            }

            $result[] = [
                'order_id' => $order['id'],
                'increment_id' => $order['number'],
                'store' => $order['shop']['name'],
                'created_at' => $order['orderTime']->format('Y-m-d H:i:s'),
                'billing_address' => $this->formatAddress($order['billing']),
                'shipping_address' => $this->formatAddress($order['shipping']),
                'payment_method' => $order['payment']['description'],
                'shipping_method' => $order['dispatch']['name'],
                'currency' => $order['currency'],
                'state' => $order['orderStatus']['name'],
                'order_status' => $this->getOrderStatusByCode($order['orderStatus']['name']),
                'payment_status' => $this->getPaymentStatusByCode($order['paymentStatus']['name']),
                'totals' => [
                    'shipping' => $this->formatPrice($order['invoiceShipping'], $order['currency']),
                    'grand_total' => $this->formatPrice($order['invoiceAmount'], $order['currency']),
                ],
                'items' => $orderItemInfo
            ];
        }

        return $result;
    }

    protected function formatPrice($price, $currency)
    {
        return $price . ' (' . $currency . ')';
    }

    protected function formatAddress($address)
    {
        $result = [];
        if ($address['firstname'] || $address['lastname']) {
            $result[] = $address['firstname'] . ' ' . $address['lastname'];
        }
        $address['street'] && $result[] = $address['street'];
        $address['additionalAddressLine1'] && $result[] = $address['additionalAddressLine1'];
        $address['additionalAddressLine2'] && $result[] = $address['additionalAddressLine2'];
        $address['country'] && $address['country']['name'] && $result[] = $address['country']['name'];
        $address['state'] && $address['state']['name'] && $result[] = $address['country']['name'];
        $address['zipcode'] && $result[] = $address['zipcode'];
        $address['phone'] && $result[] = $address['phone'];

        return implode(', ', $result);
    }

    protected function getOrderStatusByCode($code)
    {
        $statusList = [
            'cancelled' => "Cancelled",
            'cancelled_rejected' => "Cancelled/rejected",
            'clarification_required' => "Clarification required",
            'completed' => "Completed",
            'completely_delivered' => "Completely delivered",
            'in_process' => "In progress",
            'open' => "Open",
            'partially_completed' => "Partially completed",
            'partially_delivered' => "Partially delivered",
            'ready_for_delivery' => "Ready for delivery",
        ];
        $result = $code;
        if (array_key_exists($code, $statusList)) {
            $result = $statusList[$code];
        }
        return $result;
    }

    protected function getPaymentStatusByCode($code)
    {
        $statusList = [
            '1st_reminder' => "1st reminder",
            '2nd_reminder' => "2nd reminder",
            '3rd_reminder' => "3rd reminder",
            'a_time_extension_has_been_registered' => "A time extension has been registered.",
            'completely_invoiced' => "Completely invoiced",
            'completely_paid' => "Completely paid",
            'delayed' => "Delayed",
            'encashment' => "Encashment",
            'no_credit_approved' => "No credit approved",
            'open' => "Open",
            'partially_invoiced' => "Partially invoiced",
            'partially_paid' => "Partially paid",
            're_crediting' => "Re-crediting",
            'reserved' => "Reserved",
            'review_necessary' => "Review necessary",
            'the_credit_has_been_accepted' => "The credit has been accepted.",
            'the_credit_has_been_preliminarily_accepted' => "The credit has been preliminarily accepted.",
            'the_payment_has_been_ordered' => "The payment has been ordered.",
            'the_process_has_been_cancelled' => "The process has been cancelled.",
        ];
        $result = $code;
        if (array_key_exists($code, $statusList)) {
            $result = $statusList[$code];
        }
        return $result;
    }
}