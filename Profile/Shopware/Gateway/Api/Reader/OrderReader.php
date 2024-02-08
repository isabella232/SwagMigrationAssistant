<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Api\Reader;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('services-settings')]
class OrderReader extends ApiReader
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        $dataSet = $migrationContext->getDataSet();
        if ($dataSet === null) {
            return false;
        }

        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $dataSet::getEntity() === DefaultEntities::ORDER;
    }

    protected function getApiRoute(): string
    {
        return 'SwagMigrationOrders';
    }
}
