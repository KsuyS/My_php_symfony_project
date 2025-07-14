<?php
namespace App\Service;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\UserRole;
use App\Service\Data\UserData;
use App\Service\UserServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class UserService implements UserServiceInterface
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;

    public function __construct(UserRepository $userRepository, PasswordHasher $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function registerUser(string $firstName, string $lastName, ?string $middleName, string $gender, ?string $birthDate, string $email, ?string $phone, ?string $avatarPath, string $password, string $role, ): int
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            throw new \InvalidArgumentException("User with email " . $email . " already has been registered");
        }
        if (!UserRole::isValid($role)) {
            throw new \InvalidArgumentException("Role is not valid " . $role);
        }

        $user = new User(
            null,
            $firstName,
            $lastName,
            $middleName,
            $gender,
            $birthDate,
            $email,
            $phone,
            $avatarPath,
            $this->passwordHasher->hash($password),
            $role,
        );

        return $this->userRepository->store($user);
    }

    public function authentication(string $email, string $password): ?UserData
    {
        $user = $this->userRepository->findByEmail($email);
        $existingUser = new UserData(
            $user->getId(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getMiddleName(),
            $user->getGender(),
            $user->getBirthDate(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getAvatarPath(),
            $user->getRole(),
        );
        $checkPassword = $this->passwordHasher->hash($password);
        $rightPassword = $user->getPassword();
        if (($existingUser !== null) and ($checkPassword === $rightPassword)) {
            return $existingUser;
        }
        return null;
    }

    public function viewUser($email): ?UserData
    {
        $user = $this->userRepository->findByEmail($email);
        return ($user === null) ? null : new UserData(
            $user->getId(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getMiddleName(),
            $user->getGender(),
            $user->getBirthDate(),
            $user->getEmail(),
            $user->getPhone(),
            $user->getRole(),
            $user->getAvatarPath(),
        );
    }

    public function deleteUser($userData): void
    {
        $id = $userData->getUserId();
        $user = $this->userRepository->findById($id);
        $this->userRepository->delete($user);
    }

    public function editUser(string $id, string $firstName, string $lastName, ?string $middleName, string $gender, ?string $birth_date, string $email, ?string $phone, ?string $newAvatar): void
    {
        $user = $this->userRepository->findById($id);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setMiddleName($middleName);
        $user->setGender($gender);
        $user->setBirthDate($birth_date);
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setAvatarPath($newAvatar); 
        $this->userRepository->store($user);       
    }

    public function view_all_users(): array
    {
        $users = $this->userRepository->listAll();
        $usersList = [];
        foreach ($users as $user) {
            $usersList[] = [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'middle_name' => $user->getMiddleName(),
                'gender' => $user->getGender(),
                'birth_date' => $user->getBirthDate(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'avatar_path' => $user->getAvatarPath(),
                'role' => $user->getAvatarPath(),
            ];
        }

        return $usersList;
    }
}
?>