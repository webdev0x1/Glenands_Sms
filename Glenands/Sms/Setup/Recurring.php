<?php
namespace Glenands\Sms\Setup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Model\Customer;

class Recurring implements \Magento\Framework\Setup\InstallSchemaInterface
{

	/**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
		//$eavSetup = $this->eavSetupFactory->create();
		//$eavSetup->removeAttribute(Customer::ENTITY, InstallData::PHONE_NUMBER);
		if (!$installer->tableExists('glenands_sms')) {
			$table2 = $installer->getConnection()->newTable(
				$installer->getTable('glenands_sms')
			)
				->addColumn(
					'id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'Id'
				)
				->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					1,
					[],
					'Sms Status'
				)
				->addColumn(
					'otp_for',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Otp For'
				)
				->addColumn(
					'pass',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'password'
				)
				->addColumn(
					'type',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Sms type'
				)
				->addColumn(
					'user_name',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'User name'
				)
				->addColumn(
					'hashlogin',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Login Hash'
				)
                ->addColumn(
					'otp_text',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Otp'
				)
				->addColumn(
					'firstname',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'First Name'
				)
				->addColumn(
					'lastname',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Last Name'
				)
				->addColumn(
					'is_subscribed',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					[],
					'Is Subscribed'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Created At'
				)->addColumn(
					'updated_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Updated At')
				->setComment('Glenands OTP/Sms Notification');
				$installer->getConnection()->createTable($table2);
		}
		$installer->endSetup();
	}
}