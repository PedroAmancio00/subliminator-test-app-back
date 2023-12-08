<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 *
 * @method void insertCustomers($customersData)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * insert customer
     */
    public function insertCustomers($customersData): array
    {
        $customerArray = [];

        $entityManager = $this->getEntityManager();

        foreach ($customersData as $customerData) {
            $customer = new Customer();
            $customer->setName($customerData['name']);
            $customer->setAddress($customerData['address']);
            $customer->setCity($customerData['city']);
            $customer->setPostcode($customerData['postcode']);
            $customer->setCountry($customerData['country']);
            $customer->setCreatedAt($customerData['createdAt']);
            $customer->setUpdatedAt($customerData['updatedAt']);
            $entityManager->persist($customer);

            $customerArray[] = $customer;
        }

        $entityManager->flush();

        return $customerArray;
    }
}
