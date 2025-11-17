<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Command;

use Elandlord\NatsPhpBundle\Connection\NatsConnectionFactory;
use Elandlord\NatsPhpBundle\Consumer\SymfonyEventConsumer;
use Elandlord\NatsPhpBundle\Registry\EventHandlerRegistry;
use Exception;
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
    protected static $defaultDescription = 'Consume events from a NATS JetStream consumer.';

    public function __construct(
        protected NatsConnectionFactory $connectionFactory,
        protected EventHandlerRegistry $registry
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('stream', InputArgument::REQUIRED, 'JetStream stream name')
            ->addArgument('consumer', InputArgument::REQUIRED, 'JetStream consumer name');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $streamName   = (string) $input->getArgument('stream');
        $consumerName = (string) $input->getArgument('consumer');

        $consumer = new SymfonyEventConsumer(
            $this->connectionFactory,
            $this->registry,
            $streamName,
            $consumerName
        );

        $output->writeln(sprintf('Consuming NATS stream="%s", consumer="%s"...', $streamName, $consumerName));

        $consumer->consume();
        return Command::SUCCESS;
    }
}
