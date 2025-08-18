<?php

namespace App\Controller\Admin;

use App\Entity\User as AppUser;
use App\Entity\User;
use App\Entity\Company;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserCrudController extends AbstractCrudController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setSearchFields(['email', 'firstName', 'lastName'])
            ->setDefaultSort(['lastName' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $id     = IdField::new('id')->onlyOnIndex();
        $email  = EmailField::new('email', 'Email');
        $first  = TextField::new('firstName', 'Prénom');
        $last   = TextField::new('lastName', 'Nom');
        $active = BooleanField::new('isActive', 'Actif');

        // Rôles (multi-choix)
        $roles = ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Admin'       => 'ROLE_ADMIN'
            ])
            ->allowMultipleChoices()
            ->renderAsBadges();

        // Mot de passe (plain), non mappé Doctrine mais PROPRIÉTÉ présente sur l'entité
        $password = TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->onlyOnForms();

        if ($pageName === Crud::PAGE_NEW) {
            $password->setFormTypeOption('required', true);
        } else {
            $password->setFormTypeOption('required', false);
            $password->setHelp('Laisser vide pour conserver le mot de passe actuel.');
        }

        // Company (requis). Ici on affiche toujours le champ, mais on le rend modifiable
        // uniquement pour les super-admins.
        // NOTE : si ta Company a "name" et pas "title", remplace 'title' par 'name'.
        $company = AssociationField::new('company', 'Société')
            ->onlyOnForms()
            ->setFormTypeOption('choice_label', 'title');
            
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            // Visible mais non modifiable pour les admins classiques
            $company->setFormTypeOption('disabled', true);
        }

        return match ($pageName) {
            Crud::PAGE_INDEX  => [$id, $email, $first, $last, $active, $roles],
            Crud::PAGE_DETAIL => [$id, $email, $first, $last, $active, $roles, AssociationField::new('company', 'Société')],
            default           => [$email, $first, $last, $active, $roles, $password, $company],
        };
    }

    /**
     * Pré-affecte la Company de l'utilisateur connecté lors d'une création
     * (utile si l'admin n'est pas super-admin).
     */
    public function createEntity(string $entityFqcn)
    {
        $new = new AppUser();

        $currentUser = $this->getUser();
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $currentUser instanceof AppUser && null !== $currentUser->getCompany()) {
            $new->setCompany($currentUser->getCompany());
        }

        if (empty($new->getRoles())) {
            $new->setRoles(['ROLE_USER']);
        }

        return $new;
    }

    /** Création : force la company si besoin + hash le mot de passe si saisi */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof AppUser) {
            // Multi-tenant : impose la company de l'utilisateur courant si non super-admin
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $currentUser = $this->getUser();
                if ($currentUser instanceof AppUser && null !== $currentUser->getCompany()) {
                    $entityInstance->setCompany($currentUser->getCompany());
                }
            }

            // Hash si un mot de passe a été saisi
            $plain = $entityInstance->getPlainPassword();
            if (is_string($plain) && $plain !== '') {
                $hash = $this->passwordHasher->hashPassword($entityInstance, $plain);
                $entityInstance->setPassword($hash);
                $entityInstance->setPlainPassword(null);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /** Édition : même logique (hash si saisie) */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof AppUser) {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $currentUser = $this->getUser();
                if ($currentUser instanceof AppUser && null !== $currentUser->getCompany()) {
                    $entityInstance->setCompany($currentUser->getCompany());
                }
            }

            $plain = $entityInstance->getPlainPassword();
            if (is_string($plain) && $plain !== '') {
                $hash = $this->passwordHasher->hashPassword($entityInstance, $plain);
                $entityInstance->setPassword($hash);
                $entityInstance->setPlainPassword(null);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
