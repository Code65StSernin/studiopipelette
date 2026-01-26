<?php

namespace App\Command;

use App\Service\CreneauFinderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug:disponibilite',
    description: 'Vérifie la disponibilité d\'un créneau avec un rapport de débogage détaillé.',
)]
class DebugDisponibiliteCommand extends Command
{
    public function __construct(private CreneauFinderService $creneauFinderService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::REQUIRED, 'Date du créneau (YYYY-MM-DD)')
            ->addArgument('heure', InputArgument::REQUIRED, 'Heure du créneau (HH:MM)')
            ->addArgument('duree', InputArgument::REQUIRED, 'Durée en minutes')
            ->addArgument('tarifIds', InputArgument::REQUIRED, 'IDs des tarifs séparés par des virgules');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $date = $input->getArgument('date');
        $heure = $input->getArgument('heure');
        $duree = (int)$input->getArgument('duree');
        $tarifIds = explode(',', $input->getArgument('tarifIds'));
        $tarifIds = array_filter(array_map('intval', $tarifIds));

        $io->title('Vérification de la disponibilité d\'un créneau');
        $io->writeln("Date: {$date}");
        $io->writeln("Heure: {$heure}");
        $io->writeln("Durée: {$duree} minutes");
        $io->writeln("Tarif IDs: " . implode(', ', $tarifIds));
        $io->newLine();

        $resultat = $this->creneauFinderService->estCreneauDisponible($date, $heure, $duree, $tarifIds);

        if ($resultat['disponible']) {
            $io->success('Le créneau est DISPONIBLE.');
        } else {
            $io->error('Le créneau est NON DISPONIBLE.');
            $io->note('Raison : ' . $resultat['raison']);
        }

        return Command::SUCCESS;
    }
}
