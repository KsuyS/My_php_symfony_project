<?php
declare(strict_types=1);
namespace App\Controller;

use App\Service\ImageServiceInterface;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserController extends AbstractController
{
    private const DATE_TIME_FORMAT = 'Y-m-d';
    private UserServiceInterface $userService;
    private ImageServiceInterface $imageService;
    public function __construct(UserServiceInterface $userService, ImageServiceInterface $imageService)
    {
        $this->userService = $userService;
        $this->imageService = $imageService;
    }

    public function index(): Response
    {
        return $this->render('/home.html.twig');
    }

    public function signUp(): Response
    {
        return $this->render('/register_user_form.html.twig');
    }


    public function registerUser(Request $request): Response
    {
        $avatarPath = $this->imageService->moveImageToUploads($request->files->get('avatar_path'));
        
        $id = $this->userService->registerUser(
            $request->get('first_name'),
            $request->get('last_name'),
            $request->get('middle_name'),
            $request->get(key: 'gender'),
            $request->get(key: 'birthdate'),
            $request->get('email'),
            $request->get('phone'),
            $avatarPath,
            $request->get('password'),
            $request->get('role'),
        );
        return $this->render('/login.html.twig');
    }

    public function userAuthenticate(Request $request, SessionInterface $session): Response
    {
        $email = $request->get('email');
        $password = $request->get('password');
        $user = $this->userService->authentication($email, $password);

        if ($user === null) {
            return $this->render('/login.html.twig', [
                'error' => 'Неверный email или пароль',
            ]);
        }

        $session->set('user_mail', $email);
        $session->set('user_id', $user->getId());

        if (intval($user->getRole()) === 1) {
            return $this->redirect('/view_all_users');
        } elseif (intval($user->getRole()) === 2) {
            return $this->redirect('/view_all_users');
        } else {
            return $this->render('/login.html.twig');
        }
    }

    public function updateForm(Request $request): Response
    {
        $user = $this->userService->viewUser($request->get('id'));
        $dateString = $user->getBirthDate();
        $newDate = date("Y-m-d", strtotime($dateString));
        return $this->render('update_user_form.html.twig', [
            'user' => $user,
            'new_date' => $newDate,
        ]);
    }
    public function edit(Request $request): Response
    {
        $id = $request->get('id');
        $user = $this->userService->viewUser($id);
        if (!$user) {
            throw $this->createNotFoundException();
        }
        $avatarPath = $user->getAvatarPath();
        if ($request->files->get('avatar_path') !== null) {
            $newAvatar = $this->imageService->moveImageToUploads($request->files->get('avatar_path'));
            $this->userService->editUser(
                $request->get('id'),
                $request->get('first_name'),
                $request->get('last_name'),
                $request->get('middle_name'),
                $request->get('gender'),
                $request->get('birth_date'),
                $request->get('email'),
                $request->get('phone'),
                $newAvatar,
            );
        } else {
            $this->userService->editUser(
                $request->get('id'),
                $request->get('first_name'),
                $request->get('last_name'),
                $request->get('middle_name'),
                $request->get('gender'),
                $request->get('birth_date'),
                $request->get('email'),
                $request->get('phone'),
                $avatarPath,
            );
        }
        return $this->redirectToRoute('view_user', ['id' => $id], Response::HTTP_SEE_OTHER);
    }


    public function viewUser(SessionInterface $session): Response
    {
        $email = $session->get('email');
        $user = $this->userService->viewUser($email);
        if (!$user) {
            throw $this->createNotFoundException();
        } elseif (intval($user->getRole()) === 2) {
            return $this->render('view_all_users.html.twig', [
                'user' => $user
            ]);
        }
        return $this->render('view_all_users.html.twig', [
            'user' => $user
        ]);
    }

    public function view_all_users(): Response
    {
        $users = $this->userService->view_all_users();
        return $this->render('view_all_users.html.twig', [
            'user_list' => $users
        ]);
    }

    public function deleteUser(Request $request): Response
    {
        $user = $this->userService->viewUser($request->get('id'));
        if (!$user)
        {
            throw $this->createNotFoundException();
        }
        $this->imageService->deleteFileFromUploads($request->get('avatar_path'));
        $this->userService->deleteUser($user);
        return $this->redirectToRoute('user_list', ['id' => $request->get('id')], Response::HTTP_SEE_OTHER);
    }

}