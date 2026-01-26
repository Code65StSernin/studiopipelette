<?php
namespace App\Controller\Admin;

use App\Entity\UnavailabilityRule;
use App\Form\UnavailabilityRuleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UnavailabilityRuleAdminController extends AbstractController
{
    #[Route('/admin/unavailability', name: 'admin_unavailability_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(UnavailabilityRule::class);
        $rules = $repo->findAll();

        return $this->render('admin/unavailability/list.html.twig', ['rules' => $rules]);
    }

    #[Route('/admin/unavailability/create', name: 'admin_unavailability_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $rule = new UnavailabilityRule();
        $form = $this->createForm(UnavailabilityRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Build recurrence JSON from form fields
            $data = [];
            $data['type'] = $form->get('recurrenceType')->getData();
            $sd = $form->get('startDate')->getData();
            $ed = $form->get('endDate')->getData();
            if ($sd) $data['startDate'] = $sd->format('Y-m-d');
            if ($ed) $data['endDate'] = $ed->format('Y-m-d');
            $days = $form->get('daysOfWeek')->getData();
            if ($days) $data['daysOfWeek'] = array_values($days);
            $ts = $form->get('timeStart')->getData();
            $te = $form->get('timeEnd')->getData();
            if ($ts) $data['timeStart'] = $ts->format('H:i');
            if ($te) $data['timeEnd'] = $te->format('H:i');

            $rule->setRecurrence(json_encode($data));
            $rule->setActive($form->get('active')->getData() ?? true);

            $em->persist($rule);
            $em->flush();

            $this->addFlash('success', 'Règle créée');
            return $this->redirectToRoute('admin_unavailability_list');
        }

        return $this->render('admin/unavailability/form.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/unavailability/{id}/edit', name: 'admin_unavailability_edit')]
    public function edit(UnavailabilityRule $rule, Request $request, EntityManagerInterface $em): Response
    {
        // pre-populate form fields from stored recurrence JSON
        $rec = $rule->getRecurrence();
        $initial = [];
        if ($rec) {
            $parsed = json_decode($rec, true);
            if (isset($parsed['type'])) $initial['recurrenceType'] = $parsed['type'];
            if (isset($parsed['startDate'])) $initial['startDate'] = new \DateTime($parsed['startDate']);
            if (isset($parsed['endDate'])) $initial['endDate'] = new \DateTime($parsed['endDate']);
            if (isset($parsed['daysOfWeek'])) $initial['daysOfWeek'] = $parsed['daysOfWeek'];
            if (isset($parsed['timeStart'])) $initial['timeStart'] = new \DateTime($parsed['timeStart']);
            if (isset($parsed['timeEnd'])) $initial['timeEnd'] = new \DateTime($parsed['timeEnd']);
        }

        $form = $this->createForm(UnavailabilityRuleType::class, $rule);
        // set initial data for unmapped fields
        $form->setData($rule);
        foreach ($initial as $k => $v) {
            if ($form->has($k)) {
                $form->get($k)->setData($v);
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [];
            $data['type'] = $form->get('recurrenceType')->getData();
            $sd = $form->get('startDate')->getData();
            $ed = $form->get('endDate')->getData();
            if ($sd) $data['startDate'] = $sd->format('Y-m-d');
            if ($ed) $data['endDate'] = $ed->format('Y-m-d');
            $days = $form->get('daysOfWeek')->getData();
            if ($days) $data['daysOfWeek'] = array_values($days);
            $ts = $form->get('timeStart')->getData();
            $te = $form->get('timeEnd')->getData();
            if ($ts) $data['timeStart'] = $ts->format('H:i');
            if ($te) $data['timeEnd'] = $te->format('H:i');

            $rule->setRecurrence(json_encode($data));
            $rule->setActive($form->get('active')->getData() ?? true);

            $em->flush();

            $this->addFlash('success', 'Règle mise à jour');
            return $this->redirectToRoute('admin_unavailability_list');
        }

        return $this->render('admin/unavailability/form.html.twig', ['form' => $form->createView(), 'rule' => $rule]);
    }
}
