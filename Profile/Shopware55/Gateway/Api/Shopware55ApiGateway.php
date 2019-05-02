<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api;

use GuzzleHttp\Client;
use SwagMigrationNext\Migration\DataSelection\DefaultEntities;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55ApiGateway extends AbstractGateway
{
    public const GATEWAY_NAME = 'api';

    public function read(): array
    {
        $reader = new Shopware55ApiReader($this->getClient(), $this->migrationContext);

        return $reader->read();
    }

    public function readEnvironmentInformation(): EnvironmentInformation
    {
        $reader = new Shopware55ApiEnvironmentReader($this->getClient(), $this->migrationContext);
        $environmentData = $reader->read();
        $environmentDataArray = $environmentData['environmentInformation'];

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '',
                [],
                [],
                $environmentData['warning']['code'],
                $environmentData['warning']['detail'],
                $environmentData['error']['code'],
                $environmentData['error']['detail']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        $updateAvailable = false;
        if (isset($environmentData['environmentInformation']['updateAvailable'])) {
            $updateAvailable = $environmentData['environmentInformation']['updateAvailable'];
        }

        $totals = [
            DefaultEntities::CATEGORY => $environmentDataArray['categories'],
            DefaultEntities::PRODUCT => $environmentDataArray['products'],
            DefaultEntities::CUSTOMER => $environmentDataArray['customers'],
            DefaultEntities::ORDER => $environmentDataArray['orders'],
            DefaultEntities::MEDIA => $environmentDataArray['assets'],
            DefaultEntities::CUSTOMER_GROUP => $environmentDataArray['customerGroups'],
            DefaultEntities::PROPERTY_GROUP_OPTION => $environmentDataArray['configuratorOptions'],
            DefaultEntities::TRANSLATION => $environmentDataArray['translations'],
            DefaultEntities::NUMBER_RANGE => $environmentDataArray['numberRanges'],
            DefaultEntities::CURRENCY => $environmentDataArray['currencies'],
        ];
        $credentials = $this->migrationContext->getConnection()->getCredentialFields();

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $credentials['endpoint'],
            $environmentDataArray['structure'],
            $totals,
            $environmentData['warning']['code'],
            $environmentData['warning']['detail'],
            $environmentData['error']['code'],
            $environmentData['error']['detail'],
            $updateAvailable
        );
    }

    private function getClient(): Client
    {
        $credentials = $this->migrationContext->getConnection()->getCredentialFields();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'verify' => false,
        ];

        return new Client($options);
    }
}
