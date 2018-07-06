<?php

namespace App\Command;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class JiraImportCustomersCommand extends Command
{
    protected $entityManager;

    protected static $defaultName = 'jira:import_customers';

    /**
     * JiraImportCustomersCommand constructor.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Imports the customers from a csv file')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to csv file');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');

        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);

        $data = $serializer->decode(file_get_contents($path), 'csv');

        foreach ($data as $element) {
            $customer = new Customer();
            $customer->setTitle($element['name']);
            $customer->setPricePerHour(floatval($element['Timepris']));
            $customer->setEAN($element['EAN']);
            $customer->setDebitor($element['Debitornummer']);

            $this->entityManager->persist($customer);
        }

        $this->entityManager->flush();
    }
}
