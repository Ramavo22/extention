<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BudgetAlertConfigurationController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }


    #[Route('budget-alert', name: 'alert_configuration')]
    public function index(Request $request): Response
    {
       $jwt = $request->getSession()->get('jwt');

       if (!$jwt) {
          return $this->redirectToRoute('app_login');
       }

        return $this->render('budget_alert_configuration/index.html.twig', [
            'controller_name' => 'BudgetAlertConfigurationController',
            'erreur' => null,
            'details' => null,
            'message' => null
        ]);
    }

    #[Route('budget-alert-confg', name: 'traitement_configuration', methods: ['POST'])]
    public function alertConfiguration(Request $request): Response
    {
        $config = $request->request->get('budget-alert');
        $jwt = $request->getSession()->get('jwt');
        try {
            $pourcentage = (string)$config;
            $date = (new \DateTime())->format('Y-m-d H:i:s');

            $response = $this->client->request('POST', 'http://crm-backend:8080/api/budget-alert/save', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $jwt,
                    'Accept' => 'application/json',  // facultatif, mais bon à ajouter
                ],
                'json' => [
                    'pourcentage' => $pourcentage,
                    'date' => $date,
                ]
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray();  // ➤ Maintenant OK car backend renvoie du JSON valide

            if ($status === 200) {
                return $this->render('budget_alert_configuration/index.html.twig', [
                    'erreur' => null,
                    'details' => $data['statusCode'],  // 201
                    'message' => $data['message']     // "Success"
                ]);
            } else {
                return $this->render('budget_alert_configuration/index.html.twig', [
                    'erreur' => 'Erreur côté serveur',
                    'details' => $data,
                    'message' => null
                ]);
            }

        } catch (\Exception $e) {
            return $this->render('budget_alert_configuration/index.html.twig', [
                'erreur' => $e->getMessage(),
                'details' => null,
                'message' => null,
            ]);
        }
    }

}
