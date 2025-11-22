<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Command;

use Elandlord\NatsPhpBundle\Consumer\SymfonyEventConsumerFactory;
use Elandlord\NatsPhpBundle\Registry\ConsumerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
class NatsConsumeCommand extends Command
{
    protected static $defaultName = 'nats:consume';

    public function __construct(
        private readonly ConsumerRegistry $consumerRegistry,
        private readonly SymfonyEventConsumerFactory $consumerFactory
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('consumer', InputArgument::REQUIRED, 'Consumer key from config (e.g. "magento")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = (string)$input->getArgument('consumer');

        $definition = $this->consumerRegistry->get($key);
        $consumer = $this->consumerFactory->create($definition);

        $output->writeln(sprintf(
            'Consuming NATS consumer="%s" on stream="%s"...',
            $definition['name'],
            $definition['stream']
        ));

        $consumer->consume();

        return Command::SUCCESS;
    }
}
