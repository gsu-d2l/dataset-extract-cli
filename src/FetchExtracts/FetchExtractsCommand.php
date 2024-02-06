<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\FetchExtracts;

use GSU\D2L\DataHub\Extract\Model\ExtractBaseInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use GSU\D2L\DataHub\Schema\Model\DatasetSchema;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:fetch',
    description: 'Fetch list of available BDS extracts'
)]
final class FetchExtractsCommand extends Command
{
    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param AvailableExtractsRepositoryInterface $availableExtractsRepo
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private AvailableExtractsRepositoryInterface $availableExtractsRepo
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
            description: "Force storage of all available datasets"
        );

        $this->addArgument('datasets', InputArgument::IS_ARRAY);
    }


    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $force = $input->getOption('force') === true;
        /** @var string[] $datasets */
        $datasets = $input->getArgument('datasets');

        list(
            $available,
            $skipped,
            $errors
        ) = $this->getAvailableExtracts(
            $input,
            $datasets
        );

        $stored = $this->extractRepo->storeExtracts(
            $available,
            $force
        );

        $deleted = $this->extractRepo->deleteExtracts(array_diff(
            $this->extractRepo->getExtracts($datasets),
            $available
        ));

        $this->logger?->info('(fetch) ' . $this->formatLogResults([
            'availabe' => count($available),
            'skipped'  => $skipped,
            'errors'   => $errors,
            'stored'   => $stored,
            'deleted'  => $deleted
        ]));

        return static::SUCCESS;
    }


    /**
     * @param InputInterface $input
     * @param string[] $datasets
     * @return array{0:ExtractInfo[],1:int,2:int}
     */
    private function getAvailableExtracts(
        InputInterface $input,
        array $datasets
    ): array {
        $datasets = array_map(
            fn (string $datasetName): string => strtoupper(DatasetSchema::getName($datasetName)),
            $datasets
        );

        $available = array_map(
            function (ExtractBaseInfo $extract) use ($input, $datasets): ExtractInfo|null|false {
                try {
                    return in_array(strtoupper($extract->datasetName), $datasets, true)
                        ? $this->extractRepo->createExtract($extract)
                        : null;
                } catch (\Throwable $t) {
                    $this->logError(
                        $input,
                        new \RuntimeException(
                            "Unable to create extract: {$extract->extractName}",
                            0,
                            $t
                        )
                    );
                    return false;
                }
            },
            $this->availableExtractsRepo->getAvailableExtract(),
        );

        return [
            array_filter($available, fn($e) => $e instanceof ExtractInfo),
            count(array_filter($available, fn($e) => $e === null)),
            count(array_filter($available, fn($e) => $e === false))
        ];
    }
}
