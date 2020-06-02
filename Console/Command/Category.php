<?php

namespace Xigen\Benchmark\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Stopwatch\Stopwatch;
use Xigen\Benchmark\Helper\Data;

/**
 * Xigen Benchmark Console Command Category class
 */
class Category extends Command
{
    const RUN_ARGUMENT = 'run';
    const LIMIT_OPTION = 'limit';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;

    /**
     * @var \Xigen\DeleteOrder\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var ProgressBarFactory
     */
    protected $progressBarFactory;

    /**
     * Category constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \Xigen\AutoShipment\Helper\Shipment $shipmentHelper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param ProgressBarFactory $progressBarFactory
     */
    public function __construct(
        LoggerInterface $logger,
        State $state,
        Data $helper,
        DateTime $dateTime,
        ProgressBarFactory $progressBarFactory
    ) {
        $this->logger = $logger;
        $this->state = $state;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->progressBarFactory = $progressBarFactory;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->state->setAreaCode(Area::AREA_GLOBAL);

        $run = $input->getArgument(self::RUN_ARGUMENT) ?: false;
        $limit = $this->input->getOption(self::LIMIT_OPTION) ?: 5;

        if ($run) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'You are about to run a benchmark test which alters keyword values on categories. Are you sure? [y/N]',
                false
            );

            if (!$helper->ask($this->input, $this->output, $question) && $this->input->isInteractive()) {
                return Cli::RETURN_FAILURE;
            }

            $this->output->writeln((string) __('%1 Start Category Benchmark', $this->dateTime->gmtDate()));

            $stopwatch = new Stopwatch();
            $stopwatch->start('category');

            $categoryIds = $this->helper->getRandomCategoryId($limit);

            /** @var ProgressBar $progress */
            $progress = $this->progressBarFactory->create(
                [
                    'output' => $this->output,
                    'max' => count($categoryIds)
                ]
            );

            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            $progress->start();

            foreach ($categoryIds as $categoryId) {
                $this->helper->updateCategoryKeywords(
                    $categoryId,
                    $this->helper->getRandomKeyword(),
                    Store::DEFAULT_STORE_ID,
                    $this->output
                );
                $progress->setMessage((string) __('Category ID: %1', $categoryId));
                $progress->advance();
            }

            $event = $stopwatch->stop('category');

            $progress->finish();
            $this->output->writeln('');
            $this->output->writeln((string) __('%1 Finish Category Benchmark', $this->dateTime->gmtDate()));
            $this->output->writeln((string) __('%1', (string) $event));
        }
    }

    /**
     * {@inheritdoc}
     * xigen:benchmark:category [-l|--limit [LIMIT]] [--] <run>
     */
    protected function configure()
    {
        $this->setName("xigen:benchmark:category");
        $this->setDescription("Category keywords update benchmark");
        $this->setDefinition([
             new InputArgument(self::RUN_ARGUMENT, InputArgument::REQUIRED, 'Run'),
             new InputOption(self::LIMIT_OPTION, '-l', InputOption::VALUE_OPTIONAL, 'Limit'),
        ]);
        parent::configure();
    }
}
