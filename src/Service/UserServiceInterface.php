<?php
namespace App\Service;

use App\Service\Data\UserData;

interface UserServiceInterface
{
    public function registerUser(string $firstName, string $lastName, ?string $middleName, string $gender, ?string $birth_date, string $email, ?string $phone, ?string $avatarPath, string $password, string $role,): int; 
    public function authentication(string $email, string $password): ?UserData;
    public function viewUser($email): ?UserData;
    public function view_all_users(): array;
    public function deleteUser($user): void;
    public function editUser(string $id, string $firstName, string $lastName, ?string $middleName, string $gender, ?string $birth_date, string $email, ?string $phone, ?string $avatarPath): void;
}