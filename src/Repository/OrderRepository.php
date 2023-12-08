<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method void insertOrders($ordersData)
 * @method bool checkOrders($ordersIds)
 * @method bool markCancelled($orderId)
 * @method Order[] listOrders($page)
 * @method int countOrders()
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * insert customer
     */
    public function insertOrders($ordersData): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($ordersData as $orderData) {
            $order = new Order();
            $metadata = $entityManager->getClassMetaData(get_class($order));
            $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            $order->setId($orderData['id']);
            $order->setAmount($orderData['amount']);
            $order->setStatus($orderData['status']);
            $order->setDeleted($orderData['deleted']);
            $order->setCreatedAt($orderData['createdAt']);
            $order->setUpdatedAt($orderData['updatedAt']);
            $order->setCustomer($orderData['customer']);
            $entityManager->persist($order);
        }

        $entityManager->flush();
    }

    /**
     * @return bool chek if an order exists
     */
    public function checkOrders($ordersIds): bool
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->where('o.id IN (:orderIds)')
            ->setParameter('orderIds', $ordersIds);

        $results = $queryBuilder->getQuery()->getResult();

        return count($results) == 0;
    }

    /**
     * @return bool Mark an order as cancelled
     */
    public function markCancelled($orderId): bool
    {
        $product = $this->find($orderId);

        if (!$product) {
            return false;
        }

        $product = $product->setStatus('cancelled');
        $product = $product->setUpdatedAt(new \DateTime());

        $entityManager = $this->getEntityManager();

        $entityManager->flush();

        return true;
    }

    /**
     * @return Order[] Returns an array of Order objects
     */
    public function listOrders($page): array
    {
        $product = $this->findBy([], null, 10, ($page - 1) * 10);

        return $product;
    }

    /**
     * @return int Returns total of orders
     */
    public function countOrders(): int
    {
        $total = $this->count([]);

        return $total;
    }

    /**
     * @return Order[] Returns an array of Order objects
     */
    public function filterName($name, $page): array
    {
        $entityManager = $this->getEntityManager();

        $queryBuilder = $entityManager->createQueryBuilder();

        $queryBuilder->select('o')
            ->from(Order::class, 'o')
            ->leftJoin('o.customer', 'c')
            ->where($queryBuilder->expr()->like('c.name', ':customerName'))
            ->setParameter('customerName', '%' . $name . '%')
            ->setMaxResults(10)
            ->setFirstResult(($page - 1) * 10);

        $result = $queryBuilder->getQuery()->getResult();

        if ($result) {
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder
                ->select('COUNT(o)')
                ->from(Order::class, 'o')
                ->leftJoin('o.customer', 'c')
                ->where($queryBuilder->expr()->like('c.name', ':customerName'))
                ->setParameter('customerName', '%' . $name . '%');

            $count = $queryBuilder->getQuery()->getSingleScalarResult();
        } else {
            $count = 0;
        }

        return ['result' => $result, 'total' => $count];
    }

    /**
     * @return Order[] Returns an array of Order objects
     */
    public function filterStatus($status, $page): array
    {
        $entityManager = $this->getEntityManager();

        $queryBuilder = $entityManager->createQueryBuilder();

        $queryBuilder->select('o')
            ->from(Order::class, 'o')
            ->where($queryBuilder->expr()->like('o.status', ':statusFilter'))
            ->setParameter('statusFilter', '%' . $status . '%')
            ->setMaxResults(10)
            ->setFirstResult(($page - 1) * 10);

        $result = $queryBuilder->getQuery()->getResult();

        if ($result) {
            $queryBuilder = $entityManager->createQueryBuilder();
            $queryBuilder
                ->select('COUNT(o)')
                ->from(Order::class, 'o')
                ->where($queryBuilder->expr()->like('o.status', ':statusFilter'))
                ->setParameter('statusFilter', '%' . $status . '%');

            $count = $queryBuilder->getQuery()->getSingleScalarResult();
        } else {
            $count = 0;
        }

        return ['result' => $result, 'total' => $count];
    }
}
