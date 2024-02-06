<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\DownloadExtract;

use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:download',
    description: 'Download BDS extract'
)]
final class DownloadExtractCommand extends Command
{
    /**
     * @param ExtractRepositoryInterface $extractRepo
     * @param ExtractDownloaderInterface $extractDownloader
     */
    public function __construct(
        private ExtractRepositoryInterface $extractRepo,
        private ExtractDownloaderInterface $extractDownloader
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
            description: "Force download of extract"
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

        $bytes = ($force || !$extract->downloadPath->exists())
            ? $this->extractDownloader->downloadExtract(
                $extract,
                $force
            )
            : false;

        if ($bytes !== false) {
            $this->logger?->info('(download) ' . $this->formatLogResults([
                'extract' => $extract->extractName,
                'bytes'   => $bytes
            ]));
        }

        return static::SUCCESS;
    }
}
