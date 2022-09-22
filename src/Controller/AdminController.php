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
use Symfony\Component\HttpFoundation\Request;
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
    public function seeUserArticles(UsersRepository $user_repository, Users $user, int $id, ArticlesRepository $article_repository, CommentsRepository $comment_repository): Response
    {
        $this_user = $user_repository->findOneBy(['id' => $id]);

        if($this_user) {
            $articles = $user->getArticles();
            $get_articles = $article_repository->findBy(['user' => $id]);
            $get_comments = $comment_repository->findBy(['user' => $id]);
        }
    
        return $this->render('admin/user_articles.html.twig', [
            'articles' => $get_articles,
            'comments' => $get_comments,
        ]);
    }

    #[Route('/deleteuser/{id}', name: 'delete_user')]
    public function deleteOneUser(EntityManagerInterface $entityManager, UsersRepository $user_repository, Users $user, ArticlesRepository $article_repository, Articles $articles, int $id): Response
    {
        $user_to_remove = $user_repository->findOneBy(['id' => $id]);
        // $article_to_remove = $article_repository->findBy(['user' => $id]);
        // $article_to_remove = $user->getArticles();

        if ($user_to_remove) {
            $article_to_remove = $user->getArticles();
            if ($article_to_remove) {
                $entityManager->remove($articles);
                $entityManager->flush();
                // $entityManager->remove($user);
                // $entityManager->flush();
                $this->addFlash("success", "L'utilisateur à été supprimé");
                return $this->redirectToRoute('app_admin_index');
            }
        }
    }

    #[Route('/deleteuserarticle/{id}', name: 'delete_user_article')]
    public function deleteUserArticle(EntityManagerInterface $entityManager, ArticlesRepository $articles_repository, Articles $articles, CommentsRepository $comments_repository, Comments $comments, int $id): Response
    {
        $article_to_remove = $articles_repository->findOneBy(['id' => $id]);
        $comment_to_remove = $comments_repository->findBy(['article' => $id]);
        if($article_to_remove) {
            $entityManager->remove($articles, $comments);
            $entityManager->flush();
            $this->addFlash("success", "L'article à bien été supprimé");
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

    #[Route('/deleteusercomment/{id}', name: 'delete_user_comment')]
    public function deleteUserComment(EntityManagerInterface $entityManager, CommentsRepository $comments_repository, Comments $comments, int $id): Response
    {
        $comment_to_remove = $comments_repository->findOneBy(['id' => $id]);
        if($comment_to_remove) {
            $entityManager->remove($comments);
            $entityManager->flush();
            $this->addFlash("success", "Le commentaire à bien été supprimé");
            return $this->redirectToRoute('app_dashboard_index');
        }
    }

}
