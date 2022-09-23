<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Users;
use App\Repository\ArticlesRepository;
use App\Repository\CommentsRepository;
use App\Form\AddCommentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(ArticlesRepository $repository): Response
    {
        $articles = $repository->findBy(array(), array('created_at' => 'DESC'));
        return $this->render('homepage/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/article/{id}', name: 'app_homepage_detail')]
    public function detail(ArticlesRepository $repository, int $id, Request $request, EntityManagerInterface $entityManager, CommentsRepository $comment_repository): Response
    {
        $article = $repository->find($id);
        $article_hashtag = $repository->findBy(['hashtag' => $article->getHashtag()]);

        $comments = $comment_repository->findBy(['article' => $article->getId()]);

        /** @var Users $user */
        $user = $this->getUser();

        $comment = new Comments();
        $form = $this->createForm(AddCommentFormType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTimeImmutable('now'));
            $comment->setUser($user)
                    ->setArticle($article);
            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash("success", "Votre commentaire à bien été enregistré !");
    
            return $this->redirect($request->getUri());
        }

        return $this->render('homepage/index_detail.html.twig', [
            'article' => $article,
            'article_hashtag' => $article_hashtag,
            'comments' => $comments,
            'commentForm' => $form->createView(),
        ]);
    }

        #[Route('/deletecomment/{id}', name: 'delete_comment')]
        public function deleteArticle(EntityManagerInterface $entityManager, int $id, CommentsRepository $repository, Comments $comment): Response
        {
            /** @var Users $user */
            $user = $this->getUser();

            $comment_from_user = $repository->findOneBy([
                'user' => $user->getId(),
                'id' => $id,
            ]);

            if($comment_from_user) {
                $entityManager->remove($comment);
                $entityManager->flush();
                $this->addFlash("success", "Le commentaire à été supprimé");
                return $this->redirectToRoute('app_homepage');
            }
        }
}
