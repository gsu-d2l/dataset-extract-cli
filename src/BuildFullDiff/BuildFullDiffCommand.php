<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\BuildFullDiff;

use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerInterface;
use GSU\D2L\DataHub\Extract\Logger\ProgressLoggerTrait;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:build-fulldiff',
    description: 'Build fulldiff extract from two sequential full extracts'
)]
final class BuildFullDiffCommand extends Command implements ProgressLoggerInterface
{
    use ProgressLoggerTrait;


    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param FullDiffBuilderInterface $fullDiffBuilder
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private FullDiffBuilderInterface $fullDiffBuilder
    ) {
        parent::__construct(false, true);
        $this->fullDiffBuilder->setProgressLogger($this);
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

        if (
            $extract->type === ExtractType::FULL
            && $extract->downloadPath->exists()
            && ($force || !$extract->fullDiffPath->exists())
        ) {
            $bytes = $this->fullDiffBuilder->buildFullDiff(
                $extract,
                $force
            );

            if ($bytes > 0) {
                $this->logger?->info('(fulldiff) ' . $this->formatLogResults([
                    'extract' => $extract->extractName,
                    'bytes' => $bytes
                ]));
            }
        }

        return static::SUCCESS;
    }
}
