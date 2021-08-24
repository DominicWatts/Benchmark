<?php

namespace Xigen\Benchmark\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Stdlib\DateTime\DateTime;
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
 * Xigen Benchmark Console Command Customer class
 */
class Customer extends Command
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
     * Customer constructor.
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
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);

        $run = $input->getArgument(self::RUN_ARGUMENT) ?: false;
        $limit = $this->input->getOption(self::LIMIT_OPTION) ?: 5;

        if ($run) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'You are about to run a benchmark test which alters tax vat values on customers. Are you sure? [y/N]',
                false
            );

            if (!$helper->ask($this->input, $this->output, $question) && $this->input->isInteractive()) {
                return Cli::RETURN_FAILURE;
            }

            $stopwatch = new Stopwatch();
            $stopwatch->start('customer');

            $this->output->writeln((string) __('%1 Start Customer Benchmark', $this->dateTime->gmtDate()));

            $customerIds = $this->helper->getRandomCustomerId($limit);

            /** @var ProgressBar $progress */
            $progress = $this->progressBarFactory->create(
                [
                    'output' => $this->output,
                    'max' => count($customerIds)
                ]
            );

            $progress->setFormat(
                "%current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s% \t| <info>%message%</info>"
            );

            if ($output->getVerbosity() !== OutputInterface::VERBOSITY_NORMAL) {
                $progress->setOverwrite(false);
            }

            foreach ($customerIds as $customerId) {
                $this->helper->updateCustomerTaxVat($customerId, $this->helper->getRandomTaxVat(), $this->output);
                $progress->setMessage((string) __('Customer ID: %1', $customerId));
                $progress->advance();
            }

            $event = $stopwatch->stop('customer');

            $progress->finish();
            $this->output->writeln('');
            $this->output->writeln((string) __('%1 Finish Customer Benchmark', $this->dateTime->gmtDate()));
            $this->output->writeln((string) __('%1', (string) $event));

            return Cli::RETURN_SUCCESS;
        }
        return Cli::RETURN_FAILURE;
    }

    /**
     * {@inheritdoc}
     * xigen:benchmark:customer [-l|--limit [LIMIT]] [-v|--verbose [VERBOSE]] [--] <run>
     */
    protected function configure()
    {
        $this->setName("xigen:benchmark:customer");
        $this->setDescription("Customer taxvat update benchmark");
        $this->setDefinition([
             new InputArgument(self::RUN_ARGUMENT, InputArgument::REQUIRED, 'Run'),
             new InputOption(self::LIMIT_OPTION, '-l', InputOption::VALUE_OPTIONAL, 'Limit'),
        ]);
        parent::configure();
    }
}
