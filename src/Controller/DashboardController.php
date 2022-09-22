<?php

namespace App\Controller;

use App\Entity\Articles;
use App\Entity\Users;
use App\Form\AddArticleFormType;
use App\Form\EditUsernameFormType;
use App\Form\EditEmailFormType;
use App\Repository\ArticlesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dashboard', name: 'app_dashboard_')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ArticlesRepository $repository): Response
    {
        /** @var Users $user */
        $user = $this->getUser();

        if($user->hasRole("ROLE_ADMIN")) {
            return $this->redirectToRoute('app_admin_index');
        }

        $articles = $repository->findBy(['user' => $user->getId()], ['created_at' => 'DESC']);

        return $this->render('dashboard/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/addarticle', name: 'add_article')]
    public function addArticle(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Articles();
        $form = $this->createForm(AddArticleFormType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCreatedAt(new \DateTimeImmutable('now'));
            /** @var Users $user */
            $user = $this->getUser();

            if($user) {
                $article->setUser($user);
                $entityManager->persist($article);
                $entityManager->flush();
                $this->addFlash("success", "Votre article à bien été enregistré !");

                return $this->redirectToRoute('app_dashboard_index');
            }
        }

        return $this->render('dashboard/form_article.html.twig', [
            'addArticleForm' => $form->createView(),
        ]);
    }

    #[Route('/updatearticle/{id}', name: 'update_article')]
    public function updateArticle(Request $request, EntityManagerInterface $entityManager, Articles $article): Response
    {
        $formUpdate = $this->createForm(AddArticleFormType::class, $article);
        $formUpdate->handleRequest($request);

        if($formUpdate->isSubmitted() && $formUpdate->isValid()) {
            /** @var Users $user */
            $user = $this->getUser();

            if($user) {
                $entityManager->persist($article);
                $entityManager->flush();
                $this->addFlash("success", "Les modifications apportés à votre article ont été enregistrées !");

                return $this->redirectToRoute('app_dashboard_index');
            }
        }

        return $this->render('dashboard/form_article.html.twig', [
            'formUpdate' => $formUpdate->createView(),
            'update' => true,
        ]);
    }

    #[Route('/deletearticle/{id}', name: 'delete_article')]
    public function deleteArticle(EntityManagerInterface $entityManager, ArticlesRepository $repository, Articles $article): Response
    {
        /** @var Users $user */
        $user = $this->getUser();
        $article_from_user = $repository->findOneBy(['user' => $user->getId()]);

        if($article_from_user) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash("success", "L'article à bien été supprimé");
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

    #[Route('/editprofile/{id}', name: 'edit_profile')]
    public function editUser(Users $users, UsersRepository $users_repository, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $users) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        $username_form = $this->createForm(EditUsernameFormType::class, $users);
        $username_form->handleRequest($request);

        $email_form = $this->createForm(EditEmailFormType::class, $users);
        $email_form->handleRequest($request);


        if($username_form->isSubmitted() && $username_form->isValid()) {
            $user = $username_form->getData();
            $entityManager->persist($users);
            $entityManager->flush();
            $this->addFlash("success", "Votre profil à été modifié");
            return $this->redirectToRoute('app_dashboard_index');
        }

        if($email_form->isSubmitted() && $email_form->isValid()) {
            $user = $email_form->getData();

            $email_already_exist = $users_repository->findOneBy(['email' => $user->getEmail()]);

            if(!$email_already_exist) {
                $entityManager->persist($users);
                $entityManager->flush();
                $this->addFlash("success", "Votre profil à été modifié");
                return $this->redirectToRoute('app_dashboard_index');
            } else {
                $this->addFlash("success", "L'adresse email saisie est déjà utilisée");
                return $this->redirect($request->getUri());
            }
        }

        return $this->render('dashboard/edit_profile.html.twig', [
            'usernameForm' => $username_form->createView(),
            'emailForm' => $email_form->createView(),
        ]);
    }

}
