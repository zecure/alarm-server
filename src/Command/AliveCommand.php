<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\Entity\User;

class AliveCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('as:alive:notify')
            ->setDescription('Notifies admins about dead clients.')
            ->addOption(
                'seconds',
                'S',
                InputOption::VALUE_REQUIRED,
                'Sets the number of seconds since the last ping when a user is considered dead.',
                300
            )
            ->addOption(
                'users',
                'U',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Manually set the users that should be checked.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $doctrine->getRepository(User::class);

        $deadUsers = $userRepository->findDead($input->getOption('seconds'), $input->getOption('users'));
        $adminUsers = $userRepository->findAdmins();

        if ($deadUsers) {
            foreach ($adminUsers as $adminUser) {
                if (!$this->notify($adminUser, $deadUsers)) {
                    $output->writeln('Could not notify ' . $adminUser->getUsername());
                }
            }
        }

        return 0;
    }

    /**
     * @param User $adminUser
     * @param User[] $deadUsers
     * @return bool
     */
    private function notify(User $adminUser, array $deadUsers) : bool
    {
        /** @var \Swift_Mailer $mailer */
        $mailer = $this->getContainer()->get('mailer');
        /** @var \Symfony\Component\Templating\EngineInterface $templating */
        $templating = $this->getContainer()->get('templating');
        $message = (new \Swift_Message($this->getContainer()->getParameter('mail_subject_alive')))
            ->setFrom($this->getContainer()->getParameter('mailer_from'))
            ->setTo($adminUser->getEmail())
            ->setBody(
                $templating->render(
                    'alive/email.txt.twig',
                    ['users' => $deadUsers]
                ),
                'text/plain'
            )
        ;
        return ($mailer->send($message) > 0);
    }
}