<?php
declare(strict_types=1);

namespace App\Model;

use App\Utils;

class UserTable
{
    private const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private \PDO $connection)
    {

    }

    public function saveAvatarToDatabase(int $userId, string $avatarPath): void
    {
        $query = <<<SQL
            UPDATE user SET
            avatar_path = :avatarPath
            WHERE user_id = :userId
        SQL;
        $statement = $this->connection->prepare($query);
        try {
            $statement->execute([
                ':userId' => $userId,
                ':avatarPath' => $avatarPath
            ]);
        } catch (\PDOException $exception) {
            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function saveUserToDatabase(User $user): int
    {
        $query = <<<SQL
            INSERT INTO user (first_name, last_name, middle_name, gender, birth_date, email, phone, avatar_path) 
            VALUES (:firstName, :lastName, :middleName, :gender, :birthDate, :email, :phone, :avatarPath)
        SQL;
        $statement = $this->connection->prepare($query);
        try {
            $statement->execute([
                ':firstName' => $user->getFirstName(),
                ':lastName' => $user->getLastName(),
                ':middleName' => $user->getMiddleName(),
                ':gender' => $user->getGender(),
                ':birthDate' => $this->convertDateTimeToString($user->getBirthDate()),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':avatarPath' => $user->getAvatarPath(),
            ]);
            return (int) $this->connection->lastInsertId();
        } catch (\PDOException $exception) {
            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function updateUser(User $user): void
    {
        $query = <<<SQL
            UPDATE user SET
            first_name = :firstName, 
            last_name = :lastName,
            middle_name = :middleName,
            gender = :gender,
            birth_date = :birthDate,
            email = :email,
            phone = :phone,
            avatar_path =  :avatarPath
            WHERE user_id = :userId
        SQL;
        $statement = $this->connection->prepare($query);
        try {
            $statement->execute([
                ':userId' => $user->getId(),
                ':firstName' => $user->getFirstName(),
                ':lastName' => $user->getLastName(),
                ':middleName' => $user->getMiddleName(),
                ':gender' => $user->getGender(),
                ':birthDate' => $this->convertDateTimeToString($user->getBirthDate()),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':avatarPath' => $user->getAvatarPath(),
            ]);
        } catch (\PDOException $exception) {
            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    public function find(int $id): ?User
    {
        $query = <<<SQL
            SELECT user_id, first_name, last_name, middle_name, gender, birth_date, email, phone, avatar_path
            FROM user 
            WHERE user_id = :userId
        SQL;
        $statement = $this->connection->prepare($query);
        $statement->execute([':userId' => $id]);

        if ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $this->createUserFromRow($row);
        }

        return null;
    }


    private function createUserFromRow(array $row): User
    {
        return new User(
            (int) $row['user_id'],
            $row['first_name'],
            $row['last_name'],
            $row['middle_name'] ?? null,
            $row['gender'],
            Utils::parseDateTime($row['birth_date'], self::MYSQL_DATETIME_FORMAT),
            $row['email'],
            $row['phone'] ?? null,
            $row['avatar_path'] ?? null,
        );
    }

    private function convertDateTimeToString(\DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }
        return $date->format(format: self::MYSQL_DATETIME_FORMAT);
    }

    public function findByEmail(string $email): ?User
    {
        $query = <<<SQL
            SELECT user_id, first_name, last_name, middle_name, gender, birth_date, email, phone, avatar_path
            FROM user 
            WHERE email = :email
        SQL;
        $statement = $this->connection->prepare($query);
        $statement->execute([':email' => $email]);

        if ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $this->createUserFromRow($row);
        }
        return null;
    }

    public function findByPhone(string $phone): ?User
    {
        $query = <<<SQL
            SELECT user_id, first_name, last_name, middle_name, gender, birth_date, email, phone, avatar_path
            FROM user 
            WHERE phone = :phone
        SQL;
        $statement = $this->connection->prepare($query);
        $statement->execute([':phone' => $phone]);

        if ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $this->createUserFromRow($row);
        }
        return null;
    }

    public function deleteUser(User $user): void
    {
        $query = <<<SQL
            DELETE FROM user
            WHERE user_id = :userId
        SQL;
        $statement = $this->connection->prepare($query);
        try {
            $statement->execute([
                ':userId' => $user->getId(),
            ]);
        } catch (\PDOException $exception) {
            throw new \RuntimeException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }
    
    public function getAllUsers(): array
    {
        $userList = [];
        $query = <<<SQL
            SELECT user_id, first_name, last_name, middle_name, gender, birth_date, email, phone, avatar_path
            FROM user
        SQL;
        $statement = $this->connection->query($query);
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            array_push($userList, $this->createUserFromRow($row));
        }
        return $userList;
    }
}