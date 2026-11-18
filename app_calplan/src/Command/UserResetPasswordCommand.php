<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Reutiliza el mismo hasher que el resto del sistema; no toca sesión ni email.
#[AsCommand(name: 'app:user:reset-password', description: 'Genera una contraseña temporal para un usuario por su email')]
class UserResetPasswordCommand extends Command
{
    // Caracteres sin ambigüedad: 0/O, 1/I/l, se excluyen letras confundibles.
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email del usuario');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            $output->writeln('<error>No existe un usuario con email ' . $email . '</error>');

            return Command::FAILURE;
        }

        // Contraseña temporal de 14 caracteres, fácil de dictar.
        $password = '';
        for ($i = 0; $i < 14; ++$i) {
            $password .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->flush();

        $output->writeln('<info>Contraseña temporal para ' . $user->getUserIdentifier() . ': ' . $password . '</info>');

        return Command::SUCCESS;
    }
}
