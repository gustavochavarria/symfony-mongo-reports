<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use MongoDB\Client as Mongo;

const CLIENTURI = "mongodb+srv://tavo:LMoPUco6LFRFCKQb@cluster-demo-db.bj8gw.mongodb.net/test?authSource=admin&replicaSet=atlas-jibfjg-shard-0&readPreference=primary&appname=MongoDB%20Compass&ssl=true";

class ReportsController extends AbstractController
{
    /**
     * @Route("/reports", name="reports")
     */
    public function index(): Response
    {

        $client = new Mongo(CLIENTURI);
        $collection = $client->selectCollection('demo-db', 'Accounts');

        $accounts = $collection
            ->aggregate([
                [
                    '$match' => [
                        'status' => 'ACTIVE'
                    ],
                ],
                [
                    '$lookup' => [
                        'from' => 'Metrics',
//                        'localField' => 'accountId',
//                        'foreignField' => 'accountId',
                        'as' => 'metrics',
                        'let' => ['accountId' => '$accountId'],
                        'pipeline' => [
                              [
                                  '$match' => [
                                      '$expr' => [
                                          '$and' => [
                                              ['$eq' => ['$accountId', '$$accountId']],
                                              ['$gte' => ['$impressions', 0]]
                                          ]
                                      ]
                                  ]
                              ],
                            [
                                '$group' => [
                                    '_id' => [
                                        ['accountId' => '$accountId']
                                    ],
                                    'totalImpressions' => [
                                        '$sum' => '$impressions'
                                    ],
                                    'totalClicks' => [
                                        '$sum' => '$clicks'
                                    ],
                                    'totalCtr' => [
                                        '$sum' => '$ctr'
                                    ],
                                    'totalConversions' => [
                                        '$sum' => '$conversions'
                                    ],
                                    'totalCostPerClick' => [
                                        '$sum' => '$conversions'
                                    ],
                                    'totalSpend' => [
                                        '$sum' => '$spend'
                                    ]
                                ]
                            ],
                            [
                                '$addFields' => [
                                    'costPerClick' => [
                                        '$divide' => ['$totalSpend', '$totalClicks']
                                    ]
                                ]
                            ]


                        ]
                    ]
                ],
                [
                    '$sort' => [
                        'metrics.0.costPerClick' => -1
                    ]
                ]
            ])
            ->toArray();

//        dd($accounts[0]['metrics'][0]);


        return $this->render('reports/index.html.twig', [
            'accounts' => $accounts,
        ]);
    }
}
