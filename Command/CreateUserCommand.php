<?php

namespace Sh4bang\UserBundle\Command;

use Sh4bang\UserBundle\Entity\Token;
use Sh4bang\UserBundle\Service\TokenManager;
use Sh4bang\UserBundle\Service\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
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
            ->setName('sh4bang:user:create')
            ->setDescription('Create a new user.')
            ->setHelp('This command allows you to create a user')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                "Users's email"
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                "User's password"
            )
            ->addOption(
                'role',
                'r',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Add a specific role to a user'
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
            '=============',
            'Create a user',
            '=============',
        ]);

        try {
            $user = $this->userManager->createUser(
                $input->getArgument('email'),
                $input->getArgument('password'),
                $input->getOption('role')
            );

            // Create a new token and update the User
            $token = $this->tokenManager->generateToken(
                Token::ACCOUNT_CONFIRMATION_TYPE,
                $this->parameters['token_confirmation_ttl']
            );
            $user->addToken($token);

//            // Send email
//            $this->swiftMailer->sendConfirmationEmail(
//                $user,
//                $token->getHash(),
//                $input->getArgument('password')
//            );

            // Save the user!
            $this->userManager->save($user);

            $sectionBody->writeln('Email: ' . $user->getEmail());
            $sectionBody->writeln('Token generated: ' . $token->getHash());

            $sectionFooter->writeln([
                '-------------',
                'User created!',
            ]);
        } catch (\Exception $e) {
            $sectionBody->clear();
            $sectionBody->writeln($e->getMessage());
        }

    }
}
