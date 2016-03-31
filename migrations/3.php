<?php

/**
 * Migration:   3
 * Started:     30/03/2016
 * Finalised:
 *
 * @package     Nails
 * @subpackage  module-invoice
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleInvoice;

use Nails\Common\Console\Migrate\Base;

class Migration3 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}invoice_payment` CHANGE `status` `status` ENUM('PENDING','PROCESSING','COMPLETE','FAILED','REFUNDED','REFUNDED_PARTIAL')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'PENDING';");

        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}invoice_payment_refund` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `payment_id` int(11) unsigned NOT NULL,
                `ref` char(15) NOT NULL DEFAULT '',
                `reason` varchar(255) DEFAULT NULL,
                `status` enum('PENDING','PROCESSING','COMPLETE','FAILED','REFUNDED') NOT NULL DEFAULT 'PENDING',
                `txn_id` varchar(255) DEFAULT NULL,
                `fail_msg` varchar(255) DEFAULT NULL,
                `fail_code` int(11) DEFAULT NULL,
                `currency` char(3) NOT NULL DEFAULT '',
                `amount` int(11) NOT NULL DEFAULT '0',
                `fee` int(11) DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `payment_id` (`payment_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_payment_refund_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_payment_refund_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}invoice_payment_refund_ibfk_4` FOREIGN KEY (`payment_id`) REFERENCES `{{NAILS_DB_PREFIX}}invoice_payment` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}