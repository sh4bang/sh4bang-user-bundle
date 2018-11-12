<?php

namespace Sh4bang\UserBundle\Command;

use Sh4bang\UserBundle\Entity\AbstractUser;
use Sh4bang\UserBundle\Entity\Token;
use Sh4bang\UserBundle\Service\Mailer\TwigSwiftMailer;
use Sh4bang\UserBundle\Service\TokenManager;
use Sh4bang\UserBundle\Service\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateUserCommand extends Command
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var TokenManager
     */
    private $tokenManager;

//    /**
//     * @var TwigSwiftMailer
//     */
//    private $swiftMailer;

    /**
     * @var array
     */
    private $parameters;

    public function __construct(
        UserManager $userManager,
        TokenManager $tokenManager,
//        TwigSwiftMailer $swiftMailer,
        array $parameters
    ) {
        $this->userManager = $userManager;
        $this->tokenManager = $tokenManager;
//        $this->swiftMailer = $swiftMailer;
        $this->parameters = $parameters;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('sh4bang:user:update')
            ->setDescription('Update a new user.')
            ->setHelp('This command allows you to update a user')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                "Users's email that identify a user"
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                "User's password"
            )
            ->addOption(
                'add-role',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Add one or more specific roles to the user'
            )
            ->addOption(
                'set-role',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Set one or more specific roles to the user (delete all actual roles)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ConsoleSectionOutput $sectionHeader */
        $sectionHeader = $output->section();
        /** @var ConsoleSectionOutput $sectionBody */
        $sectionBody = $output->section();
        /** @var ConsoleSectionOutput $sectionFooter */
        $sectionFooter = $output->section();

        $sectionHeader->writeln([
            '===========',
            'Update user',
            '===========',
        ]);

        try {
            $user = $this->userManager->getUserByEmail($input->getArgument('email'));
            if (null === $user) {
                throw new CommandNotFoundException('User not found');
            }

            if ($input->getOption('password')) {
                // Change user's password
                $this->userManager->changePassword($user, $input->getOption('password'));

//                // Send email
//                $this->swiftMailer->sendPasswordChangedEmail(
//                    $user,
//                    $input->getOption('password')
//                );
            }

            if ($input->getOption('set-role')) {
                $user->setRoles($input->getOption('set-role'));
            }

            if ($input->getOption('add-role')) {
                $roles = $input->getOption('add-role');
                foreach ($roles as $role) {
                    $user->addRole($role);
                }
            }

            // Save the user!
            $this->userManager->save($user);

            $sectionBody->writeln('Email: ' . $user->getEmail());

            $sectionFooter->writeln([
                '-------------',
                'User updated!',
            ]);
        } catch (\Exception $e) {
            $sectionBody->clear();
            $sectionBody->writeln($e->getMessage());
        }

    }
}
