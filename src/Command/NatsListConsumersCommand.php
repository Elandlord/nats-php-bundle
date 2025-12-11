<?php
declare(strict_types=1);

namespace Elandlord\NatsPhpBundle\Command;

use Elandlord\NatsPhpBundle\Registry\ConsumerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @copyright    2025, Eric Landheer
 * @license      MIT License
 */
#[AsCommand(
    name: 'nats:consumers:list',
    description: 'List all configured NATS consumers.'
)]
class NatsListConsumersCommand extends Command
{
    protected static $defaultName = self::COMMAND_NAME;
    protected static $defaultDescription = self::COMMAND_DESCRIPTION;

    protected const COMMAND_NAME = 'nats:consumers:list';
    protected const COMMAND_DESCRIPTION = 'List all configured NATS consumers.';
    protected const EMPTY_MESSAGE = 'No consumers configured.';

    protected const HEADER_KEY = 'Key';
    protected const HEADER_STREAM = 'Stream';
    protected const HEADER_CONSUMER_NAME = 'Consumer Name';
    protected const HEADER_SUBJECT = 'Subject Filter';
    protected const HEADER_MAX_DELIVER = 'Max Deliver';
    protected const HEADER_ACK_WAIT_MS = 'Ack Wait (ms)';
    protected const EVENTS = 'Associated Events';

    protected const DEF_STREAM = 'stream';
    protected const DEF_SUBJECT_FILTER = 'subject_filter';
    protected const DEF_MAX_DELIVER = 'max_deliver';
    protected const DEF_ACK_WAIT_MS = 'ack_wait_ms';

    protected const MISSING_VALUE = '(missing)';
    protected const NONE_VALUE = '(none)';
    protected const DEFAULT_VALUE = '(default)';
    protected const ALL_EVENTS = '(all)';

    public function __construct(
        protected readonly ConsumerRegistry $consumerRegistry,
        protected readonly array            $eventMap = []
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $definitions = $this->consumerRegistry->all();

        if (count($definitions) === 0) {
            $output->writeln(sprintf('<comment>%s</comment>', self::EMPTY_MESSAGE));
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders([
            self::HEADER_KEY,
            self::HEADER_STREAM,
            self::HEADER_CONSUMER_NAME,
            self::HEADER_SUBJECT,
            self::HEADER_MAX_DELIVER,
            self::HEADER_ACK_WAIT_MS,
            self::EVENTS
        ]);

        foreach ($definitions as $key => $definition) {
            $subjectFilter = $definition[self::DEF_SUBJECT_FILTER] ?? null;
            $events = $this->resolveEventsForConsumer($subjectFilter);

            $table->addRow([
                $key,
                $definition[self::DEF_STREAM] ?? self::MISSING_VALUE,
                $key,
                $definition[self::DEF_SUBJECT_FILTER] ?? self::NONE_VALUE,
                $definition[self::DEF_MAX_DELIVER] ?? self::DEFAULT_VALUE,
                $definition[self::DEF_ACK_WAIT_MS] ?? self::DEFAULT_VALUE,
                $events
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }

    protected function resolveEventsForConsumer(?string $subjectFilter): string
    {
        if (empty($this->eventMap)) {
            return self::NONE_VALUE;
        }

        if ($subjectFilter === null || $subjectFilter === '') {
            return self::ALL_EVENTS;
        }

        $prefix = $this->subjectFilterToPrefix($subjectFilter);

        if ($prefix === null) {
            return self::ALL_EVENTS;
        }

        $matched = [];
        foreach (array_keys($this->eventMap) as $eventName) {
            if (str_starts_with($eventName, $prefix)) {
                $matched[] = $eventName;
            }
        }

        if (!$matched) {
            return self::NONE_VALUE;
        }

        return implode("\n", $matched);
    }

    protected function subjectFilterToPrefix(string $filter): ?string
    {
        $filter = trim($filter);

        if ($filter === '>' || $filter === '*') {
            return null;
        }

        $filter = rtrim($filter, '>*');

        if ($filter === '') {
            return null;
        }

        return $filter;
    }
}