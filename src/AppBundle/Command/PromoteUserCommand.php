<?php


namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PromoteUserCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:promote')
            ->setDescription('Promotes a user by adding a role')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('role', InputArgument::REQUIRED, 'Set the users role'),
            ))
            ->setHelp(<<<EOT
The <info>user:promote</info> command promotes a user by adding a role

  <info>php app/console fos:user:promote matthieu ROLE_CUSTOM</info>
  <info>php app/console fos:user:promote matthieu@email.com ROLE_CUSTOM</info>
EOT
            );
    }
    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username   = $input->getArgument('username');
        $role      = $input->getArgument('role');
        
        $manipulator = $this->getContainer()->get('user.util');
        
        if ($manipulator->promote($username, $role)) {
                $output->writeln(sprintf('Role "%s" has been added to user "%s".', $role, $username));
            } else {
                $output->writeln(sprintf('User "%s" did already have "%s" role.', $username, $role));
            }
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username:',
                function($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }

        if (!$input->getArgument('role')) {
            $role = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an role:',
                function($role) {
                    if (empty($role)) {
                        throw new \Exception('Role can not be empty');
                    }

                    return $role;
                }
            );
            $input->setArgument('role', $role);
        }
    }
}
