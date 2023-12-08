<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\CorsBundle\Annotation\Cors;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use DateTime;
use DateTimeImmutable;

class OrdersController extends AbstractController
{
    private $customersRepository;
    private $ordersRepository;

    public function __construct(CustomerRepository $customersRepository, OrderRepository $ordersRepository)
    {
        $this->customersRepository = $customersRepository;
        $this->ordersRepository = $ordersRepository;
    }

    #[Route('/api/orders/import', name: 'orders_import', methods: ['POST'])]
    public function ordersImport(): JsonResponse
    {
        //Loads orders.json file
        $filePath = $this->getParameter('kernel.project_dir') . '/assets/orders.json';

        $jsonData = file_get_contents($filePath);

        $data = json_decode($jsonData, true);

        //Try to insert data on database
        try {
            $customers = array_map(function ($dataElement) {
                return [
                    'name' => $dataElement['customer'],
                    'address' => $dataElement['address1'],
                    'city' => $dataElement['city'],
                    'postcode' => $dataElement['postcode'],
                    'country' => $dataElement['country'],
                    'createdAt' => new DateTimeImmutable($dataElement['date']),
                    'updatedAt' => new DateTime($dataElement['last_modified'])
                ];
            }, $data);

            $ordersId = array_map(function ($dataElement) {
                return $dataElement['id'];
            }, $data);

            //Check if data already exists on database
            $existOrders = $this->ordersRepository->checkOrders($ordersId);

            //If data already exists on database, return error
            if (!$existOrders) {
                return new JsonResponse('Data already inserted on database.', Response::HTTP_CONFLICT);
            }

            //Insert customers on database
            $customersArray = $this->customersRepository->insertCustomers($customers);

            $orders = array_map(function ($dataElement, $index) use ($customersArray) {

                $customer = isset($customersArray[$index]) ? $customersArray[$index] : null;

                return [
                    'id' => $dataElement['id'],
                    'amount' => $dataElement['amount'],
                    'status' => $dataElement['status'],
                    'deleted' => $dataElement['deleted'],
                    'createdAt' => new DateTimeImmutable($dataElement['date']),
                    'updatedAt' => new DateTime($dataElement['last_modified']),
                    'customer' => $customer
                ];
            }, $data, array_keys($data));

            //Insert orders on database
            $this->ordersRepository->insertOrders($orders);
        } catch (\Exception $e) {
            //If error on database, return error
            if ($e->getCode() == 2002) {
                return new JsonResponse('Error on database.', Response::HTTP_INTERNAL_SERVER_ERROR);
                //If data already exists on database, return error
            } else if ($e->getCode() == 1062) {
                return new JsonResponse('Data already inserted on database.', Response::HTTP_CONFLICT);
                //If any error, return error
            } else {
                return new JsonResponse('Unknow error.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        //If no error, return success
        return new JsonResponse('Data inserted in database.', Response::HTTP_CREATED);
    }

    #[Route('/api/orders/{id}', name: 'orders_cancel', methods: ['DELETE'])]
    public function orderCancel($id): JsonResponse
    {
        //Convert id to int
        $id = intval($id);
        //If id is not int or is less than 0, return error
        if (!is_int($id) || $id <= 0) {
            return new JsonResponse('Invalid ID.', Response::HTTP_BAD_REQUEST);
        }
        try {
            //Try to cancel order
            $result = $this->ordersRepository->markCancelled($id);
            //If order was cancelled, return success
            if ($result) {
                return new JsonResponse('Item cancelled successfully.', Response::HTTP_OK);
                //If order was not cancelled, return error
            } else {
                return new JsonResponse('Order not found.', Response::HTTP_BAD_REQUEST);
            }
            //If any error, return error
        } catch (\Exception $e) {
            return new JsonResponse('Unknow error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders', name: 'orders_list', methods: ['GET'])]
    public function ordersList(Request $request): JsonResponse
    {
        //Get page from query string
        $page = $request->query->get('page');
        //Convert page to int
        $page = intval($page);
        //If page is not int or is less than 0, return error
        if (!is_int($page) || $page <= 0) {
            return new JsonResponse('Invalid page.', Response::HTTP_BAD_REQUEST);
        }
        //Try to list orders
        try {
            //List orders
            $result = $this->ordersRepository->listOrders($page);
            //Count Total
            $total = $this->ordersRepository->countOrders($page);
            //If orders was not found, return error
            if ($result) {
                //If orders was found, return success
                return new JsonResponse(['result' => $result, 'total' => $total], Response::HTTP_OK);
            } else {
                //If orders was not found, return error
                return new JsonResponse(['result' => null, 'total' => $total], Response::HTTP_OK);
            }
            //If any error, return error
        } catch (\Exception $e) {
            //If any error, return error
            return new JsonResponse('Unknow error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/name', name: 'orders_filter_name', methods: ['GET'])]
    public function ordersFilterName(Request $request): JsonResponse
    {
        $name = $request->query->get('name');
        //Get page from query string
        $page = $request->query->get('page');
        //Convert page to int
        $page = intval($page);
        //If page is not int or is less than 0, return error
        if (!is_int($page) || $page <= 0) {
            return new JsonResponse('Invalid page.', Response::HTTP_BAD_REQUEST);
        }
        //Try to list orders
        try {
            $result = $this->ordersRepository->filterName($name, $page);

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            //If any error, return error
            return new JsonResponse('Unknow error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/orders/status', name: 'orders_filter_status', methods: ['GET'])]
    public function ordersFilterStatus(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        //Get page from query string
        $page = $request->query->get('page');
        //Convert page to int
        $page = intval($page);
        //If page is not int or is less than 0, return error
        if (!is_int($page) || $page <= 0) {
            return new JsonResponse('Invalid page.', Response::HTTP_BAD_REQUEST);
        }
        //Try to list orders
        try {
            $result = $this->ordersRepository->filterStatus($status, $page);

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            //If any error, return error
            return new JsonResponse('Unknow error.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
