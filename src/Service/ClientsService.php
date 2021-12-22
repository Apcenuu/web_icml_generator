<?php

namespace App\Service;

use Http\Discovery\Exception\NotFoundException;
use RetailCrm\Api\Client;
use RetailCrm\Api\Factory\SimpleClientFactory;
use RetailCrm\Api\Model\Entity\Customers\Customer;
use RetailCrm\Api\Model\Filter\Customers\CustomerFilter;
use RetailCrm\Api\Model\Request\Customers\CustomersCreateRequest;
use RetailCrm\Api\Model\Request\Customers\CustomersEditRequest;
use RetailCrm\Api\Model\Request\Customers\CustomersRequest;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClientsService
{
    public SymfonyStyle $io;
    private ExcelService $excelService;
    private Client $client;

    public function __construct(ExcelService $excelService)
    {
        $this->excelService = $excelService;
        $this->client = SimpleClientFactory::createClient('', '');
    }

    public function subscribe($filename)
    {
        $rows = $this->excelService->readFile($filename);
        $this->findAndSubscribeCustomers($rows);

        return $rows;
    }

    private function findCustomerByEmail($email)
    {
        $request = new CustomersRequest();
        $filter = new CustomerFilter();
        $filter->email = $email;

        $request->filter = $filter;
        $response = $this->client->customers->list($request);
        if (isset($response->customers[0])) {
            return $response->customers[0];
        }
        throw new NotFoundException('Customer '. $email .' not found');
    }

    private function findAndSubscribeCustomers(array $rows)
    {
        foreach ($rows as $row) {
            try {
                $customer = $this->findCustomerByEmail($row[0]);

                if ($customer->subscribed !== false && $customer->emailMarketingUnsubscribedAt === null) {
                    $this->io->success('Customer '. $customer->id . ' ('. $customer->email .') skipped');
                    continue;
                }

                $customer->subscribed = true;
                $customer->emailMarketingUnsubscribedAt = null;
                $editRequest = new CustomersEditRequest();
                $editRequest->by = 'id';
                $editRequest->customer = $customer;
                $response = $this->client->customers->edit($customer->id, $editRequest);

                $newCustomer = $this->findCustomerByEmail($row[0]);
                if (is_null($newCustomer->emailMarketingUnsubscribedAt)) {
                    $this->io->success('Customer '. $customer->id . ' ('. $customer->email .') subscribed');
                } else {
                    $this->io->error('Customer '. $customer->id . ' is not subscribed');
                }


            } catch (NotFoundException $exception) {

                $customer = new Customer();
                $customer->email = $row[0];
                $customer->subscribed = true;
                $customer->emailMarketingUnsubscribedAt = null;
                $request = new CustomersCreateRequest();
                $request->customer = $customer;
                $response = $this->client->customers->create($request);

                if (isset($response->id) && $response->success) {
                    $this->io->success('Customer '. $response->id . ' ('. $customer->email .') created & subscribed');
                } else {
                    $this->io->error('Customer '. $customer->id . ' is not subscribed');
                }
            }
        }

    }
}
