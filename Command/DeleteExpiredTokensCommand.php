<?php

namespace Sh4bang\UserBundle\Command;

use Sh4bang\UserBundle\Service\TokenManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredTokensCommand extends Command
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(
        TokenManager $tokenManager
    ) {
        $this->tokenManager = $tokenManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('sh4bang:token:delete-expired')
            ->setDescription('Delete all expired tokens')
            ->setHelp('This command allows you to delete expired tokens')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln([
                '=========================',
                'Delete all expired tokens',
                '=========================',
            ]);
            $numberDeleted = $this->tokenManager->cleanAllExpiredTokens();
            $output->writeln('Done : '.$numberDeleted.' token(s) deleted !');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

    }
}
