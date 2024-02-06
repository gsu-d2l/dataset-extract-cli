<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\UploadExtract;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use mjfklib\Utils\FileMethods;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:upload',
    description: 'Upload BDS extract'
)]
final class UploadExtractCommand extends Command
{
    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param ExtractUploaderInterface $extractUploader
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private ExtractUploaderInterface $extractUploader
    ) {
        parent::__construct(false, true);
    }


    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->addOption(
            name: 'force',
            shortcut: 'f',
            mode: InputOption::VALUE_NONE,
            description: "Force index generation of extract"
        );

        $this->addArgument('dataset', InputArgument::REQUIRED);
    }


    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        /** @var string $dataset */
        $dataset = $input->getArgument('dataset');
        $force = $input->getOption('force') === true;

        $extracts = $this->extractRepo->getExtracts(
            datasets: $dataset,
            status: $force
                ? [ExtractStatus::PROCESSED, ExtractStatus::UPLOADED]
                : [ExtractStatus::PROCESSED]
        );

        foreach ($extracts as $extract) {
            if (!($force || $this->previousExtractIsUploaded($extract))) {
                $this->logger?->warning(
                    "Skipping {$extract} - previous extract is not uploaded; Use -f/--force to force the upload"
                );
                continue;
            }

            $uploadContext = $this->extractUploader->upload(
                $extract,
                $force
            );

            if ($uploadContext !== null) {
                $extract->uploadContext = $uploadContext;
                $this->extractRepo->storeExtracts($extract, true);
                if (!$extract->uploadPath->exists()) {
                    throw new FileNotFoundException($extract->uploadPath);
                }

                $this->logger?->info('(upload) ' . $this->formatLogResults([
                    'extract' => $extract->extractName,
                    'records' => $extract->uploadContext->recordCount
                ]));
            }
        }

        return static::SUCCESS;
    }


    /**
     * @param ExtractInfo $extract
     * @return bool
     */
    private function previousExtractIsUploaded(ExtractInfo $extract): bool
    {
        $previousExtracts = $this->extractRepo->getExtracts(
            datasets: $extract->datasetName,
            endDate: \DateTimeImmutable::createFromInterface($extract->timestamp)->modify('-1 second'),
        );
        $previous = array_pop($previousExtracts);
        return $previous?->getStatus() === ExtractStatus::UPLOADED;
    }
}
