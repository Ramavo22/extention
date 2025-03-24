<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DashboardController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(Request $request): Response
    {
       try {
          $jwt = $request->getSession()->get('jwt');
          dump($jwt);
          $response = $this->client->request('GET', 'http://crm-backend:8080/api/dashboard', [
              'headers' => [
                  'Authorization' => 'Bearer ' . $jwt,
                  'Accept' => 'application/json',
              ],
          ]);

          if ($response->getStatusCode() === 200) {
             $data = $response->toArray(); // convertit le JSON en array PHP

             // Exemple : récupération des clients créés par mois
             $clientsParMois = $data['data']['clientCreatedPerMonth'] ?? [];
             $expenseParMois = $data['data']['leadExpensePerMonth'] ?? [];
             $ticketParMois = $data['data']['ticketExpensePerYear'] ?? [];
             $totalLeadYear = $data['data']['totalLeadYear'] ?? 0;
             $totalTicketYear = $data['data']['totalTicketYear'] ?? 0;
             return $this->render('dashboard/index.html.twig', [
                  'clientsParMois' => $clientsParMois,
                  'expenseParMois' => $expenseParMois,
                  'ticketParMois' => $ticketParMois,
                  'totalLeadYear' => $totalLeadYear,
                  'totalTicketYear' => $totalTicketYear,
             ]);
          }
          // Si l’API ne retourne pas 200
          return $this->render('login/index.html.twig', [
              'erreur' => 'Accès refusé à l’API.',
              'statut' => 'Erreur : ' . $response->getStatusCode(),
          ]);

       } catch (\Exception $e) {
          return $this->render('login/index.html.twig', [
              'erreur' => 'Connexion au serveur échouée.',
              'statut' => 'Erreur : ' . $e->getMessage(),
          ]);
       }
    }
}
