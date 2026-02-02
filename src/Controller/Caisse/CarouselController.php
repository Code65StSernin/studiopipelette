<?php

namespace App\Controller\Caisse;

use App\Entity\Carousel;
use App\Form\CarouselType;
use App\Repository\CarouselRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/caisse/carousel')]
class CarouselController extends AbstractController
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    #[Route('/', name: 'app_caisse_carousel_index', methods: ['GET'])]
    public function index(CarouselRepository $carouselRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->get('q');
        $queryBuilder = $carouselRepository->createQueryBuilder('c')
            ->orderBy('c.dateDebut', 'DESC');

        if ($q) {
            $queryBuilder->andWhere('c.grandTitre LIKE :q OR c.petitTitre LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('caisse/carousel/index.html.twig', [
            'carousels' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_caisse_carousel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $carousel = new Carousel();
        $form = $this->createForm(CarouselType::class, $carousel, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/assets/img/carousel',
                        $newFilename
                    );
                    $carousel->setImage($newFilename); // Assuming Entity stores just filename
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->persist($carousel);
            $entityManager->flush();

            $this->addFlash('success', 'Le slide a bien été créé.');
            return $this->redirectToRoute('app_caisse_carousel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/carousel/new.html.twig', [
            'carousel' => $carousel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_caisse_carousel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Carousel $carousel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CarouselType::class, $carousel, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile instanceof UploadedFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/assets/img/carousel',
                        $newFilename
                    );
                    
                    // Optional: Delete old file
                    if ($carousel->getImage()) {
                        $oldFile = $this->getParameter('kernel.project_dir') . '/public/assets/img/carousel/' . $carousel->getImage();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $carousel->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le slide a bien été modifié.');
            return $this->redirectToRoute('app_caisse_carousel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/carousel/edit.html.twig', [
            'carousel' => $carousel,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_carousel_delete', methods: ['POST'])]
    public function delete(Request $request, Carousel $carousel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $carousel->getId(), $request->request->get('_token'))) {
            if ($carousel->getImage()) {
                $file = $this->getParameter('kernel.project_dir') . '/public/assets/img/carousel/' . $carousel->getImage();
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            
            $entityManager->remove($carousel);
            $entityManager->flush();
            $this->addFlash('success', 'Le slide a bien été supprimé.');
        }

        return $this->redirectToRoute('app_caisse_carousel_index', [], Response::HTTP_SEE_OTHER);
    }
}
