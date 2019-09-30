<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Media;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\MigrationContextInterface;

class MediaFileService implements MediaFileServiceInterface
{
    protected $writeArray = [];

    protected $uuids = [];

    /**
     * @var EntityRepositoryInterface
     */
    private $mediaFileRepo;

    /**
     * @var EntityWriterInterface
     */
    private $entityWriter;

    /**
     * @var EntityDefinition
     */
    private $mediaFileDefinition;

    public function __construct(
        EntityRepositoryInterface $mediaFileRepo,
        EntityWriterInterface $entityWriter,
        EntityDefinition $mediaFileDefinition
    ) {
        $this->mediaFileRepo = $mediaFileRepo;
        $this->entityWriter = $entityWriter;
        $this->mediaFileDefinition = $mediaFileDefinition;
    }

    public function writeMediaFile(Context $context): void
    {
        $this->checkMediaIdsForDuplicates($context);

        if (empty($this->writeArray)) {
            return;
        }

        $this->entityWriter->insert(
            $this->mediaFileDefinition,
            $this->writeArray,
            WriteContext::createFromContext($context)
        );

        $this->writeArray = [];
        $this->uuids = [];
    }

    public function saveMediaFile(array $mediaFile): void
    {
        $mediaId = $mediaFile['mediaId'];
        if (isset($this->uuids[$mediaId])) {
            return;
        }

        $this->uuids[$mediaId] = $mediaId;
        $this->writeArray[] = $mediaFile;
    }

    public function setWrittenFlag(array $converted, MigrationContextInterface $migrationContext, Context $context): void
    {
        $dataSet = $migrationContext->getDataSet();

        $mediaUuids = [];
        foreach ($converted as $data) {
            if ($dataSet::getEntity() === DefaultEntities::MEDIA) {
                $mediaUuids[] = $data['id'];
                continue;
            }

            if ($dataSet::getEntity() === DefaultEntities::PRODUCT) {
                if (isset($data['media'])) {
                    foreach ($data['media'] as $media) {
                        if (!isset($media['media'])) {
                            continue;
                        }

                        $mediaUuids[] = $media['media']['id'];
                    }
                }

                if (isset($data['manufacturer']['media']['id'])) {
                    $mediaUuids[] = $data['manufacturer']['media']['id'];
                }
            }

            if ($dataSet::getEntity() === DefaultEntities::PROPERTY_GROUP_OPTION) {
                if (!isset($data['media']['id'])) {
                    continue;
                }

                $mediaUuids[] = $data['media']['id'];
            }

            if ($dataSet::getEntity() === DefaultEntities::CATEGORY) {
                if (!isset($data['media']['id'])) {
                    continue;
                }

                $mediaUuids[] = $data['media']['id'];
            }

            if ($dataSet::getEntity() === DefaultEntities::ORDER_DOCUMENT) {
                if (!isset($data['documentMediaFile']['id'])) {
                    continue;
                }

                $mediaUuids[] = $data['documentMediaFile']['id'];
            }
        }

        if (empty($mediaUuids)) {
            return;
        }

        $this->saveWrittenFlag($mediaUuids, $migrationContext, $context);
    }

    private function checkMediaIdsForDuplicates(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $runId = null;
        $files = [];
        $mediaIds = [];
        foreach ($this->writeArray as $mediaFile) {
            if ($runId === null) {
                $runId = $mediaFile['runId'];
            }

            $files[$mediaFile['mediaId']] = $mediaFile;
            $mediaIds[] = $mediaFile['mediaId'];
        }

        if (empty($mediaIds)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_OR,
            [
                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    [
                        new EqualsAnyFilter('mediaId', $mediaIds),
                        new EqualsFilter('written', true),
                        new EqualsFilter('processed', true),
                    ]
                ),

                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    [
                        new EqualsAnyFilter('mediaId', $mediaIds),
                        new EqualsFilter('runId', $runId),
                    ]
                ),
            ]
        ));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        /** @var SwagMigrationMediaFileEntity $mediaFile */
        foreach ($mediaFiles->getElements() as $mediaFile) {
            unset($files[$mediaFile->getMediaId()]);
        }

        $this->writeArray = array_values($files);
    }

    private function saveWrittenFlag(array $mediaUuids, MigrationContextInterface $migrationContext, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('mediaId', $mediaUuids));
        $criteria->addFilter(new EqualsFilter('runId', $migrationContext->getRunUuid()));
        $mediaFiles = $this->mediaFileRepo->search($criteria, $context);

        $updateWrittenMediaFiles = [];
        foreach ($mediaFiles->getElements() as $data) {
            /* @var SwagMigrationMediaFileEntity $data */
            $value = $data->getId();
            $updateWrittenMediaFiles[] = [
                'id' => $value,
                'written' => true,
            ];
        }

        if (empty($updateWrittenMediaFiles)) {
            return;
        }

        $this->entityWriter->update(
            $this->mediaFileDefinition,
            $updateWrittenMediaFiles,
            WriteContext::createFromContext($context)
        );
    }
}
