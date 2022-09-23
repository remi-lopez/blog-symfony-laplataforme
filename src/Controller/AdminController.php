<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Articles;
use App\Entity\Comments;
use App\Repository\ArticlesRepository;
use App\Repository\CommentsRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/admin', name: 'app_admin_')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(UsersRepository $users_repository): Response
    {        
        $users = $users_repository->findAll();

        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }
        
    #[Route('/user/{id}', name: 'read_user')]
    public function seeUserArticles(UsersRepository $user_repository, Users $user, ArticlesRepository $article_repository, CommentsRepository $comment_repository): Response
    {
        $this_user = $user_repository->findOneBy(['id' => $user]);

        if($this_user) {
            $get_articles = $article_repository->findBy(['user' => $user]);
            $get_comments = $comment_repository->findBy(['user' => $user]);
        } else {
            return $this->redirectToRoute('app_dashboard_index');
        }
    
        return $this->render('admin/user_articles.html.twig', [
            'articles' => $get_articles,
            'comments' => $get_comments,
        ]);
    }

    #[Route('/deleteuser/{id}', name: 'delete_user')]
    public function deleteOneUser(EntityManagerInterface $entityManager, UsersRepository $user_repository, Users $user): Response
    {
        $user_to_remove = $user_repository->findOneBy(['id' => $user]);

        if($user_to_remove) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash("success", "L'utilisateur à été supprimé");
            return $this->redirectToRoute('app_admin_index');
        } else {
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

    #[Route('/deleteuserarticle/{id}', name: 'delete_user_article')]
    public function deleteUserArticle(EntityManagerInterface $entityManager, ArticlesRepository $repository, Articles $article): Response
    {
        $article_to_remove = $repository->findOneBy(['id' => $article]);

        if($article_to_remove) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash("success", "L'article et ses commentaires ont été supprimés");
            return $this->redirectToRoute('app_dashboard_index');
        } else {
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

    #[Route('/deleteusercomment/{id}', name: 'delete_user_comment')]
    public function deleteUserComment(EntityManagerInterface $entityManager, CommentsRepository $comments_repository, Comments $comments, int $id): Response
    {
        // Query for user's comment by ID then remove and flush
        $comment_to_remove = $comments_repository->findOneBy(['id' => $id]);
        if($comment_to_remove) {
            $entityManager->remove($comments);
            $entityManager->flush();
            $this->addFlash("success", "Le commentaire à bien été supprimé");
            return $this->redirectToRoute('app_dashboard_index');
        } else {
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

}
