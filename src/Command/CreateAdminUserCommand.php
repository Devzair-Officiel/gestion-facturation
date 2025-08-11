<?php

namespace App\Command;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Crée un utilisateur administrateur (choix de la Company, email unique, mot de passe confirmé)'
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1) Sélection (ou création) de la Company
        $companyRepo = $this->em->getRepository(Company::class);
        $companies = $companyRepo->findBy([], ['title' => 'ASC']);

        if (count($companies) === 0) {
            $io->warning("Aucune Company n'existe encore. Création d'une Company par défaut.");
            $title = $io->ask("Nom public de la société", 'Ma Société');
            $legalName = $io->ask("Nom juridique (raison sociale)", $title . ' SARL');
            $currency = $io->ask("Devise par défaut (ISO 4217)", 'EUR');

            $company = new Company();
            // Adapte ces setters selon ton entité Company
            $company->setTitle($title);
            if (method_exists($company, 'setLegalName')) {
                $company->setLegalName($legalName);
            }
            if (method_exists($company, 'setDefaultCurrency')) {
                $company->setDefaultCurrency($currency);
            }

            $this->em->persist($company);
            $this->em->flush();

            $io->success("Company créée : {$company->getTitle()}");
        } elseif (count($companies) === 1) {
            $company = $companies[0];
            $io->text("Company sélectionnée : <info>{$company->getTitle()}</info>");
        } else {
            $choices = [];
            foreach ($companies as $c) {
                $choices[] = $c->getTitle();
            }
            $choice = $io->choice('Sélectionne la Company', $choices, $choices[0]);
            // retrouver l'entité choisie
            $company = $companies[array_search($choice, $choices, true)];
        }

        // 2) Saisie des informations User
        $email = $io->ask('Email (identifiant de connexion)', null, function (?string $value) use ($company) {
            $v = trim((string) $value);
            if ($v === '') {
                throw new \RuntimeException('Email obligatoire.');
            }
            if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email invalide.');
            }
            return $v;
        });

        // Vérification unicité email au sein de la Company
        $existing = $this->em->getRepository(User::class)->findOneBy([
            'email'   => $email,
            'company' => $company,
        ]);
        if ($existing) {
            $io->error("Cet email est déjà utilisé pour cette Company.");
            return Command::FAILURE;
        }

        $lastName = $io->ask('Nom (lastName)', null, function (?string $v) {
            $v = trim((string) $v);
            if ($v === '') {
                throw new \RuntimeException('Nom obligatoire.');
            }
            return $v;
        });
        $firstName = $io->ask('Prénom (optionnel)', '') ?? '';

        // 3) Mot de passe masqué + confirmation
        $plainPassword = null;
        while (true) {
            $p1 = $io->askHidden('Mot de passe (saisi masquée)');
            if ($p1 === null || $p1 === '') {
                $io->warning('Le mot de passe ne peut pas être vide.');
                continue;
            }
            $p2 = $io->askHidden('Confirme le mot de passe');
            if ($p1 !== $p2) {
                $io->warning('Les mots de passe ne correspondent pas, recommence.');
                continue;
            }
            $plainPassword = $p1;
            break;
        }

        // 4) Création et persistance du User
        // Si ton entité User a un constructeur User(Company $company)
        $user = new User($company);
        // Adapte ces setters selon ton entité User
        $user->setEmail($email);
        if (method_exists($user, 'setLastName')) {
            $user->setLastName($lastName);
        }
        if (method_exists($user, 'setFirstName')) {
            $user->setFirstName($firstName);
        }
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf(
            "Utilisateur admin créé : %s %s <%s> — Company: %s",
            method_exists($user, 'getFirstName') ? $user->getFirstName() : '',
            method_exists($user, 'getLastName') ? $user->getLastName() : '',
            $user->getEmail(),
            $company->getTitle()
        ));

        return Command::SUCCESS;
    }
}
