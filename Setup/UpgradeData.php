<?php

namespace Monext\Payline\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;
use Monext\Payline\Helper\Constants as HelperConstants;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Customer\Model\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    protected $attributeSetFactory;

    public function __construct(
        \Magento\Customer\Model\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
    )
    {
        $this->attributeFactory = $attributeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $data = [];
            $statuses = [
                HelperConstants::ORDER_STATUS_PAYLINE_WAITING_CAPTURE => __('Payline Waiting Capture'),
                HelperConstants::ORDER_STATUS_PAYLINE_CAPTURED => __('Payline Captured'),
                HelperConstants::ORDER_STATUS_PAYLINE_CANCELED => __('Payline Canceled'),
            ];
            foreach ($statuses as $code => $info) {
                $data[] = ['status' => $code, 'label' => $info];
            }
            
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status'), 
                ['status', 'label'], 
                $data
            );
            
            $data = [];
            foreach ($statuses as $code => $info) {
                $data[] = ['status' => $code, 'state' => Order::STATE_PROCESSING, 'default' => 0, 'visible_on_front' => 1];
            }
            
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
            );
        }
        
        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $data = [];
            $statuses = [
                HelperConstants::ORDER_STATUS_PAYLINE_PENDING => __('Payline Pending'),
            ];
            foreach ($statuses as $code => $info) {
                $data[] = ['status' => $code, 'label' => $info];
            }
            
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status'), 
                ['status', 'label'], 
                $data
            );
            
            $data = [];
            foreach ($statuses as $code => $info) {
                $data[] = ['status' => $code, 'state' => Order::STATE_NEW, 'default' => 0, 'visible_on_front' => 1];
            }
            
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
            );
        }
        
        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                ['state' => Order::STATE_CANCELED],
                ['status = ?' => HelperConstants::ORDER_STATUS_PAYLINE_CANCELED]
            );
        }
        
        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            $data = [];
            $statuses = [
                HelperConstants::ORDER_STATUS_PAYLINE_ABANDONED => __('Payline Abandoned'),
                HelperConstants::ORDER_STATUS_PAYLINE_REFUSED => __('Payline Refused'),
                HelperConstants::ORDER_STATUS_PAYLINE_FRAUD => __('Payline Fraud'),
                HelperConstants::ORDER_STATUS_PAYLINE_WAITING_ACCEPTANCE => __('Payline Waiting Acceptance'),
            ];
            foreach ($statuses as $code => $info) {
                $data[] = ['status' => $code, 'label' => $info];
            }

            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status'), 
                ['status', 'label'], 
                $data
            );

            $data = [];
            foreach ($statuses as $code => $info) {
                $data[] = [
                    'status' => $code, 
                    'state' => $code == HelperConstants::ORDER_STATUS_PAYLINE_WAITING_ACCEPTANCE ? Order::STATE_PROCESSING : Order::STATE_CANCELED, 
                    'default' => 0, 
                    'visible_on_front' => $code == HelperConstants::ORDER_STATUS_PAYLINE_FRAUD ? 0 : 1
                ];
            }

            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
            );
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $attribute = $this->attributeFactory->create();

            $attribute->setData(array(
                'entity_type_id' => \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
                'attribute_code' => 'wallet_id',
                'type' => 'static',
                'frontend_label' => 'Wallet Id',
                'frontend_input' => 'text',
                'sort_order' => 200,
                'position' => 200,
                'is_user_defined' => 1,
                'is_unique' => 1,
                'is_visible' => 1,
            ));

            $attribute->save();

            $data = array(
                array('form_code' => 'adminhtml_customer', 'attribute_id' => $attribute->getId())
            );

            $setup->getConnection()
                ->insertMultiple($setup->getTable('customer_form_attribute'), $data);

            $attributeSet = $this->attributeSetFactory->create()->load(\Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);

            $attribute
                ->setAttributeGroupId($attributeSet->getDefaultGroupId())
                ->setAttributeSetId($attributeSet->getId())
                ->setEntityTypeId(\Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER)
                ->save();
        }

        $setup->endSetup();
    }
}

