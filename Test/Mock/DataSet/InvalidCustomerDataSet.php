<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Test\Mock\DataSet;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

#[Package('services-settings')]
class InvalidCustomerDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'customerInvalid';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return true;
    }
}
