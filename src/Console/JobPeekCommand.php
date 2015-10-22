<?php

namespace Phlib\Beanstalk\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobPeekCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('job:peek')
            ->setDescription('View information about a specific job.')
            ->addArgument('job-id', InputArgument::REQUIRED, 'The ID of the job.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobId = $input->getArgument('job-id');
        $job = $this->getBeanstalk()->peek($jobId);
        $output->writeln("Found Job '$jobId'.");
        $output->writeln(var_export($job['body'], true));
    }
}
