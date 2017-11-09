<?php

namespace BatchBundle\Command;

use BatchBundle\Job\Job;
use BatchBundle\Processor\BeerProcessor;
use BatchBundle\Reader\CsvReader;
use BatchBundle\Writer\EntityWriter;
use BeerBundle\Entity\Repository\IdentifiableRepositoryInterface;
use BeerBundle\Utils\CommandLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Command to import beer
 *
 * @author Romain Monceau <romain@akeneo.com>
 */
class ImportBeerCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('batch:import:beer')
            ->addArgument('filepath', InputArgument::REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        CommandLogger::setOutput($output);

        $filepath = $input->getArgument('filepath');
        if (!file_exists($filepath)) {
            throw new \Exception(sprintf('File "%s" not found', $filepath));
        }

        $reader = new CsvReader($filepath);
        $processor = new BeerProcessor(
            $this->getBeerRepository(),
            $this->getBreweryRepository(),
            $this->getCategoryRepository()
        );
        $writer = new EntityWriter($this->getEntityManager(), $this->getValidator());

        $job = new Job($reader, $processor, $writer);
        $job->setBatchSize(100);

        $job->execute();
    }

    /**
     * @return ValidatorInterface
     */
    private function getValidator()
    {
        return $this->getContainer()->get('validator');
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * @return IdentifiableRepositoryInterface
     */
    private function getBeerRepository()
    {
        return $this->getEntityManager()->getRepository('BeerBundle\Entity\Beer');
    }

    private function getBreweryRepository()
    {
        return $this->getEntityManager()->getRepository('BeerBundle\Entity\Brewery');
    }

    private function getCategoryRepository()
    {
        return $this->getEntityManager()->getRepository('BeerBundle\Entity\Category');
    }

}
