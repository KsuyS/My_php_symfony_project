<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Service\PasswordHasher;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private const DATE_TIME_FORMAT = 'Y-m-d';
    private const SUPPORT_MIME_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpeg',
        'image/gif' => 'gif',
    ];

    public function __construct(UserRepository $userRepository, PasswordHasher $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function index(): Response
    {
        return $this->render('/home.html.twig');
    }

    public function signUp(): Response
    {
        return $this->render('/register_user_form.html.twig');
    }
    private function getAvatarExtension(string $mimeType): ?string
    {
        return self::SUPPORT_MIME_TYPES[$mimeType] ?? null;
    }

    public function registerUser(Request $data): ?Response
    {

        try {
            $birthDate = Utils::parseDateTime($_POST['birth_date'], self::DATE_TIME_FORMAT) ?? null;

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
            $data->get('password'),
            $data->get('role'),
        );

        if ($this->userRepository->findByEmail($data->get('email')) != null) {
            $mess = 'Пользователь с таким email уже существует!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($this->userRepository->findByPhone($data->get('phone')) != null) {
            $mess = 'Пользователь с таким телефоном уже существует!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        $userId = $this->userRepository->store($user);
        $file = $this->downloadImage($userId);

        if ($file === null && isset($_FILES['avatar_path']) && $_FILES['avatar_path']['error'] === UPLOAD_ERR_OK) {
            $mess = 'Ошибка с расширением загружаемого файла!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($file != null) {
            $user->setAvatarPath($file);
            $this->userRepository->store($user);
        }

        //return $this->redirectToRoute('view_user', ['userId' => $userId], Response::HTTP_SEE_OTHER);
        return $this->render('/user/login.html.twig');
    }

    public function userAuthenticate(Request $request, SessionInterface $session): Response
    {
        $id = (int) $request->get('user_id');
        $email = $request->get('email');
        $password = $request->get('password');
        $exist = $this->authentication($email, $password);
        $user = $this->viewUser($email);
        $session->set('user_mail', $email);
        $session->set('user_id', $id);
        if ($exist === 1) {
            return $this->redirect('/assortment');
        } elseif ($exist === 2) {
            return $this->redirect('/admin');
        } else {
            return $this->render('/user/login.html.twig');
        }
    }

    public function authentication(string $email, string $password): int
    {
        $existingUser = $this->userRepository->findByEmail($email);
        $checkPassword = $this->passwordHasher->hash($password);
        $rightPassword = $existingUser->getPassword();
        var_dump($rightPassword, $checkPassword);
        if (($existingUser !== null) and ($checkPassword === $rightPassword)) {
            return $existingUser->getRole();
        }
        return 0;
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
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            $mess = 'Вы не можете обновить пользователя с помощью этого идентификатора!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }

        if ($data->isMethod('post')) {
            try {
                $user = $this->updateUsersData($data);
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
            'password' => $user->getPassword(),
            'role' => $user->getRole(),
        ]);
    }

    private function updateUsersData(Request $data): User
    {
        $id = (int) $data->get('user_id');
        $user = $this->getUserById($id);

        $this->validateUniqueEmailAndPhone($data, $id);

        $birthDate = Utils::parseDateTime($data->get('birth_date'), self::DATE_TIME_FORMAT);
        $birthDate = $birthDate->setTime(0, 0, 0);

        $user->setFirstName($data->get('first_name'));
        $user->setLastName($data->get('last_name'));
        $user->setMiddleName(empty($data->get('middle_name')) ? null : $data->get('middle_name'));
        $user->setGender($data->get('gender'));
        $user->setBirthDate($birthDate);
        $user->setEmail(empty($data->get('email')) ? null : $data->get('email'));
        $user->setPhone(empty($data->get('phone')) ? null : $data->get('phone'));

        $file = $this->downloadImage($id);
        $this->handleAvatarUpload($file);

        if ($file !== null) {
            $user->setAvatarPath($file);
        }

        $this->userRepository->store($user);

        return $user;
    }

    private function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            // throw new \Exception('Пользователь не найден!');
            throw new UnauthorizedHttpException('');
        }
        return $user;
    }

    private function validateUniqueEmailAndPhone(Request $data, int $currentUserId): void
    {
        $email = $data->get('email');
        $phone = $data->get('phone');

        $existingUserByEmail = $this->userRepository->findByEmail($email);
        if ($existingUserByEmail !== null && $existingUserByEmail->getId() !== $currentUserId) {
            throw new \Exception('Пользователь с таким email уже существует!');
        }

        $existingUserByPhone = $this->userRepository->findByPhone($phone);
        if ($existingUserByPhone !== null && $existingUserByPhone->getId() !== $currentUserId) {
            throw new \Exception('Пользователь с таким телефоном уже существует!');
        }
    }

    private function handleAvatarUpload(?string $file): void
    {
        if ($file === null && isset($_FILES['avatar_path']) && $_FILES['avatar_path']['error'] === UPLOAD_ERR_OK) {
            throw new \Exception('Ошибка с расширением загружаемого файла!');
        }
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
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            $mess = 'Такого пользователя нет!';
            return $this->redirectToRoute('pageWithError', ['mess' => $mess]);
        }
        $this->userRepository->delete($user);
        if ($user->getAvatarPath() != null) {
            $this->deleteImage($user);
        }
        return $this->redirectToRoute('view_all_users');
    }

    public function viewUser(int $userId): Response
    {
        $user = $this->userRepository->findById($userId);
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
        $view_all_users = $this->userRepository->listAll();
        return $this->render('view_all_users.html.twig', ['view_all_users' => $view_all_users]);
    }
    public function pageWithError(string $mess): Response
    {
        return $this->render('pageWithError.html.twig', ['mess' => $mess]);
    }
}