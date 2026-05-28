<?php
/**
 * NakoPay XenForo add-on setup.
 *
 * Handles install, upgrade, and uninstall lifecycle.
 *
 * @package NakoPay/Payments
 */

namespace NakoPay\Payments;

use XF\AddOn\AbstractSetup;
use XF\Db\Schema\Create;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    public function install(array $stepParams = [])
    {
        $this->schemaManager()->createTable('xf_nakopay_orders', function (Create $table) {
            $table->addColumn('order_id', 'int')->autoIncrement();
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('item_id', 'varchar', 128)->setDefault('');
            $table->addColumn('upgrade_id', 'int')->setDefault(0);
            $table->addColumn('nakopay_invoice_id', 'varchar', 64)->setDefault('');
            $table->addColumn('address', 'varchar', 128)->setDefault('');
            $table->addColumn('coin', 'varchar', 16)->setDefault('BTC');
            $table->addColumn('currency', 'varchar', 8)->setDefault('USD');
            $table->addColumn('amount_fiat', 'varchar', 32)->setDefault('0');
            $table->addColumn('amount_crypto', 'varchar', 32)->setDefault('0');
            $table->addColumn('status', 'varchar', 32)->setDefault('pending');
            $table->addColumn('tx_hash', 'varchar', 128)->setDefault('');
            $table->addColumn('checkout_url', 'text');
            $table->addColumn('bip21', 'text');
            $table->addColumn('created_at', 'int')->setDefault(0);
            $table->addColumn('updated_at', 'int')->setDefault(0);
            $table->addPrimaryKey('order_id');
            $table->addUniqueKey('nakopay_invoice_id', 'idx_invoice');
            $table->addKey('user_id', 'idx_user');
            $table->addKey('status', 'idx_status');
        });
    }

    public function upgrade(array $stepParams = [])
    {
        // Future schema changes go here.
    }

    public function uninstall(array $stepParams = [])
    {
        $this->schemaManager()->dropTable('xf_nakopay_orders');
    }
}
