<?php
namespace App\Command;

use App\Entity\Calendrier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-calendrier-days')]
class CreateCalendrierDaysCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = new \DateTime('2026-01-01');
        $end = new \DateTime('2050-12-31');

        // Determine where to start: if table already has entries, continue after the last date
        $qb = $this->em->createQueryBuilder();
        $qb->select('MAX(c.date)')->from(Calendrier::class, 'c');
        $maxDateResult = $qb->getQuery()->getSingleScalarResult();

        if ($maxDateResult) {
            $current = new \DateTime($maxDateResult);
            // advance one day to continue
            $current->add(new \DateInterval('P1D'));
        } else {
            $current = clone $start;
        }
        $batchSize = 100;
        $i = 0;

        $repo = $this->em->getRepository(Calendrier::class);

        $output->writeln('Création des enregistrements Calendrier de 2026 à 2050...');

        while ($current->getTimestamp() <= $end->getTimestamp()) {
            // skip if already exists (use date-only string to be safe)
            $checkDate = new \DateTime($current->format('Y-m-d'));
            $exists = $repo->findOneBy(['date' => $checkDate]);
            if (!$exists) {
                $cal = new Calendrier();
                $cal->setDate(new \DateTime($current->format('Y-m-d')));
                // init empty slots
                $cal->setCreneaux([]);
                $this->em->persist($cal);
                $i++;
            }

            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
                // re-get repo after clear
                $repo = $this->em->getRepository(Calendrier::class);
                $output->writeln("Inserted {$i} records so far...");
            }

            // advance one day
            $current->add(new \DateInterval('P1D'));
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeln(sprintf('Done. %d new Calendrier records created.', $i));

        return Command::SUCCESS;
    }
}
