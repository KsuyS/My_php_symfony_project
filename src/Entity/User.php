<?php
declare(strict_types=1);

namespace App\Entity;
class User
{
    private ?int $id;
    private string $firstName;
    private string $lastName;
    private ?string $middleName;
    private string $gender; 
    private ?\DateTimeImmutable $birthDate;
    private string $email;
    private ?string $phone;
    private ?string $avatarPath;

    public function __construct(
        ?int $id, 
        string $firstName, 
        string $lastName,
        ?string $middleName,
        string $gender,    
        ?\DateTimeImmutable $birthDate,
        string $email,
        ?string $phone,
        ?string $avatarPath)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->middleName = $middleName;
        $this->gender = $gender;
        $this->birthDate = $birthDate;
        $this->email = $email;
        $this->phone = $phone;
        $this->avatarPath = $avatarPath;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName($first_name): void 
    {
        $this->firstName = $first_name;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName($last_name): void
    {
        $this->lastName = $last_name;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName($middle_name): void
    {
        $this->middleName = $middle_name;
    }

    public function getGender(): string{
        return $this->gender;
    }

    public function setGender($gender): void
    {
        $this->gender = $gender;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone($phone): void
    {
        $this->phone = $phone;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): void
    {
        $this->avatarPath = $avatarPath;
    }
}