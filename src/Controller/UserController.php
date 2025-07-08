<?php
declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Database\ConnectionProvider;
use App\Model\User;
use App\Model\UserTable;
use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class UserController extends AbstractController
{
    private const DATE_TIME_FORMAT = 'Y-m-d';
    private const SUPPORT_MIME_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
    ];
    private UserTable $table;

    public function __construct()
    {
        $connection = ConnectionProvider::connectDatabase();
        $this->table = new UserTable($connection);
    }

    public function index(): Response
    {
        return $this->render(view: 'register_user_form.html.twig');
    }
    private function getAvatarExtension(string $mimeType): ?string
    {
        return self::SUPPORT_MIME_TYPES[$mimeType] ?? null;
    }

    public function registerUser(Request $data): ?Response
    {

        try {
            $birthDate = Utils::parseDateTime($_POST['birth_date'], self::DATE_TIME_FORMAT);

            if ($birthDate !== null) {
                $birthDate = $birthDate->setTime(0, 0, 0);
            }
        } catch (\InvalidArgumentException $e) {
            $mess = 'Перепроверьте поля формы!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        $user = new User(
            null,
            $data->get('first_name'),
            $data->get('last_name'),
            empty($data->get('middle_name')) ? null : $data->get('middle_name'),
            $data->get('gender'),
            $birthDate,
            $data->get('email'),
            empty($data->get('phone')) ? null : $data->get('phone'),
            null,
        );

        if ($this->table->findByEmail($data->get('email')) != null) {
            $mess = 'Пользователь с таким email уже существует!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($this->table->findByPhone($data->get('phone')) != null) {
            $mess = 'Пользователь с таким телефоном уже существует!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        $userId = $this->table->saveUserToDatabase($user);
        $file = $this->downloadImage($userId);

        if ($file === null && isset($_FILES['avatar_path']) && $_FILES['avatar_path']['error'] === UPLOAD_ERR_OK) {
            $mess = 'Ошибка с расширением загружаемого файла!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($file != null) {
            $this->table->saveAvatarToDatabase($userId, $file);
        }

        return $this->redirectToRoute('view_user', ['userId' => $userId], Response::HTTP_SEE_OTHER);
    }

    private function downloadImage(int $id): ?string
    {
        $uploadDir = __DIR__ . '/../../public/uploads/avatar';
        $file = null;

        if (!isset($_FILES['avatar_path'])) {
            return $file;
        }

        switch ($_FILES['avatar_path']['error']) {
            case UPLOAD_ERR_OK:
                $extension = $this->getAvatarExtension($_FILES['avatar_path']['type']);
                if ($extension === null) {
                    return null;
                }
                $destination = $uploadDir . $id . '.' . $extension;
                if (move_uploaded_file($_FILES['avatar_path']['tmp_name'], $destination)) {
                    $file = 'avatar' . $id . '.' . $extension;
                    return $file;
                }
                break;

            case UPLOAD_ERR_NO_FILE:
                return null;
            default:
                return null;
        }
    }

    public function updateUser(int $userId, Request $data): Response
    {
        $user = $this->table->find($userId);
        if (!$user) {
            $mess = 'Вы не можете обновить пользователя с помощью этого идентификатора!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($data->isMethod('post')) {
            try {
                $user = $this->updateUsersData($data);
                $this->table->updateUser($user);
                return $this->redirectToRoute('view_user', ['userId' => $user->getId()]);
            } catch (\Exception $e) {
                return $this->redirectToRoute('pageWithError', ['mess' => $e->getMessage()]);
            }
        }

        return $this->render('update_user_form.html.twig', [
            'userId' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'middleName' => $user->getMiddleName(),
            'gender' => $user->getGender(),
            'birthDate' => Utils::convertDateTimeToStringForm($user->getBirthDate()),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'avatarPath' => $user->getAvatarPath(),
        ]);
    }

    private function updateUsersData(Request $data): User
    {
        $id = (int) $data->get('user_id');
        $user = $this->table->find($id);

        if ($user === null) {
            throw new \Exception('Пользователь не найден!');
        }

        $email = $data->get('email');
        $phone = $data->get('phone');

        $existingUserByEmail = $this->table->findByEmail($email);
        if ($existingUserByEmail !== null && $existingUserByEmail->getId() !== $id) {
            throw new \Exception('Пользователь с таким email уже существует!');
        }

        $existingUserByPhone = $this->table->findByPhone($phone);
        if ($existingUserByPhone !== null && $existingUserByPhone->getId() !== $id) {
            throw new \Exception('Пользователь с таким телефоном уже существует!');
        }

        $birthDate = Utils::parseDateTime($data->get('birth_date'), self::DATE_TIME_FORMAT);
        $birthDate = $birthDate->setTime(0, 0, 0);

        $user->setFirstName($data->get('first_name'));
        $user->setLastName($data->get('last_name'));
        $user->setMiddleName(empty($data->get('middle_name')) ? null : $data->get('middle_name'));
        $user->setGender($data->get('gender'));
        $user->setBirthDate($birthDate);
        $user->setEmail(empty($email) ? null : $email);
        $user->setPhone(empty($phone) ? null : $phone);

        $file = $this->downloadImage($id);

        if ($file === null && isset($_FILES['avatar_path']) && $_FILES['avatar_path']['error'] === UPLOAD_ERR_OK) {
            throw new \Exception('Ошибка с расширением загружаемого файла!');
        }

        if ($file !== null) {
            $user->setAvatarPath($file);
        }

        return $user;
    }

    private function deleteImage(User $user): void
    {
        $avatarPath = $user->getAvatarPath();
        $filePath = __DIR__ . '/../../public/uploads/' . $avatarPath;
        if (file_exists($filePath)) {
            unlink($filePath);
            echo "Файл успешно удален!";

        } else {
            echo "Файл не существует!";
        }
    }
    public function deleteUser(int $userId): Response
    {
        $user = $this->table->find($userId);

        if (!$user) {
            $mess = 'Такого пользователя нет!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }
        $this->table->deleteUser($user);
        if ($user->getAvatarPath() != null) {
            $this->deleteImage($user);
        }
        return $this->redirectToRoute('view_all_users');
    }

    public function viewUser(int $userId): Response
    {
        $user = $this->table->find($userId);
        if (!$user) {
            $mess = 'Пользователя с таким идентификатором нет!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        return $this->render('view_user.html.twig', [
            'userId' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'middleName' => $user->getMiddleName(),
            'gender' => $user->getGender(),
            'birthDate' => Utils::convertDateTimeToStringForm($user->getBirthDate()),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'avatarPath' => $user->getAvatarPath(),
        ]);
    }

    public function viewAllUsers(): Response
    {
        $view_all_users = $this->table->getAllUsers();
        return $this->render('view_all_users.html.twig', ['view_all_users' => $view_all_users]);
    }
    public function pageWithError(string $mess): Response
    {
        return $this->render('pageWithError.html.twig', ['mess' => $mess]);
    }
}