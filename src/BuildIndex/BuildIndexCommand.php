<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildIndex;

use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerInterface;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerTrait;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:build-index',
    description: 'Build full extract index'
)]
final class BuildIndexCommand extends Command implements ProgressLoggerInterface
{
    use ProgressLoggerTrait;


    /** @var int */
    public const DEFAULT_CHUNK_SIZE = 500000;


    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param IndexBuilderInterface $indexBuilder
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private IndexBuilderInterface $indexBuilder
    ) {
        parent::__construct(false, true);
        $this->indexBuilder->setProgressLogger($this);
    }


    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setHidden(true);

        $this->addOption(
            name: 'force',
            shortcut: 'f',
            mode: InputOption::VALUE_NONE,
            description: "Force index generation of extract"
        );

        $this->addOption(
            name: 'chunk_size',
            mode: InputOption::VALUE_OPTIONAL,
            description: "Number of records per index chunk"
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
        $chunkSize = $input->getOption('chunk_size');
        $extract = $this->extractRepo->getExtract($extractName);

        $recordCount = $this->indexBuilder->buildIndex(
            $this->extractRepo->getExtract($extractName),
            $force,
            is_scalar($chunkSize) ? intval($chunkSize) : self::DEFAULT_CHUNK_SIZE
        );

        if ($recordCount > 0) {
            $this->logger?->info('(index) ' . $this->formatLogResults([
                'extract' => $extract->extractName,
                'records' => $recordCount
            ]));
        }

        return static::SUCCESS;
    }
}
