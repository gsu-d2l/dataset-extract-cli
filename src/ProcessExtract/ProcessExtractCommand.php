<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ProcessExtract;

use GSU\D2L\DataHub\Extract\Exception\FileNotFoundException;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerInterface;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractContext;
use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:process',
    description: 'Process BDS extract'
)]
final class ProcessExtractCommand extends Command implements ProgressLoggerInterface
{
    use ProgressLoggerTrait;


    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param ExtractProcessorInterface $extractProcessor
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private ExtractProcessorInterface $extractProcessor
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
            description: "Force process of extract"
        );

        $this->addArgument('extract', InputArgument::REQUIRED);
    }


    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        /** @var string $extractName */
        $extractName = $input->getArgument('extract');
        $force = $input->getOption('force') === true;
        $extract = $this->extractRepo->getExtract($extractName);

        $processContext = $extract->downloadPath->exists()
            ? $this->processExtract(
                $extract,
                $force
            )
            : null;

        if ($processContext !== null) {
            $extract->processContext = $processContext;
            $this->extractRepo->storeExtracts($extract, true);
            if (!$extract->processPath->exists()) {
                throw new FileNotFoundException($extract->processPath);
            }

            $this->logger?->info('(process) ' . $this->formatLogResults([
                'extract' => $extract->extractName,
                'records' => $extract->processContext->recordCount
            ]));
        }

        return static::SUCCESS;
    }


    /**
     * @param ExtractInfo $extract
     * @param bool $force
     * @return ExtractContext|null
     */
    private function processExtract(
        ExtractInfo $extract,
        bool $force
    ): ExtractContext|null {
        $this->extractProcessor->setProgressLogger($this);

        return match (true) {
            $extract->type === ExtractType::DIFF => $this->extractProcessor->processDiff($extract, $force),
            $extract->fullDiffPath->exists() => $this->extractProcessor->processFullDiff($extract, $force),
            default => $this->extractProcessor->processFull($extract, $force),
        };
    }
}
