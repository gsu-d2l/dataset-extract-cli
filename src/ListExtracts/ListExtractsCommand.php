<?php

declare(strict_types=1);

namespace GSU\D2L\DataHub\Extract\ListExtracts;

use GSU\D2L\DataHub\Extract\Model\ExtractInfo;
use GSU\D2L\DataHub\Extract\Model\ExtractStatus;
use GSU\D2L\DataHub\Extract\Model\ExtractType;
use GSU\D2L\DataHub\Extract\Repository\ExtractRepositoryInterface;
use mjfklib\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(
    name: 'extracts:list',
    description: 'Show list of extracts'
)]
final class ListExtractsCommand extends Command
{
    /**
     * @param ExtractRepositoryInterface $extractRepo
     */
    public function __construct(private ExtractRepositoryInterface $extractRepo)
    {
        parent::__construct(false, false);
    }


    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->addOption(
            name: 'show-datasets-only',
            mode: InputOption::VALUE_NONE,
            description: "Only show the dataset name"
        );

        $this->addOption(
            name: 'start-date',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Show extracts after start date. Format is YYYYMMDD'
        );

        $this->addOption(
            name: 'end-date',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Show extracts before end date. Format is YYYYMMDD'
        );

        $this->addOption(
            name: 'type',
            mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            description: 'Extract type. Valid values are '
                . implode(", ", array_map(fn ($t) => "'{$t->value}'", ExtractType::cases()))
        );

        $this->addOption(
            name: 'status',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Extract status. Valid values are '
                . implode(", ", array_map(fn ($t) => "'{$t->value}'", ExtractStatus::cases()))
        );

        $this->addArgument(
            name: 'datasets',
            mode: InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            description: 'Dataset(s)'
        );
    }


    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $params = ListExtractsParams::create([
            'datasets'         => $input->getArgument('datasets'),
            'startDate'        => $input->getOption('start-date'),
            'endDate'          => $input->getOption('end-date'),
            'types'            => $input->getOption('type'),
            'status'           => $input->getOption('status'),
            'showDatasetsOnly' => $input->getOption('show-datasets-only') === true,
        ]);

        $extracts = $this->extractRepo->getExtracts(
            $params->datasets ?? [],
            $params->startDate,
            $params->endDate,
            $params->types ?? [],
            $params->status ?? []
        );

        $output->writeln(
            $params->showDatasetsOnly
                ? array_unique(array_map(
                    fn (ExtractInfo $extract): string => $extract->datasetName,
                    $extracts
                ))
                : array_map(
                    fn (ExtractInfo $extract): string => $extract->extractName,
                    $extracts
                )
        );

        return static::SUCCESS;
    }
}
