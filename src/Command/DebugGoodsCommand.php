<?php

namespace App\Command;

use App\Repository\GoodRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\JsonResponse;

#[AsCommand(
    name: 'debug:goods',
    description: 'Debug goods data',
)]
class DebugGoodsCommand extends Command
{
    public function __construct(
        private GoodRepository $goodRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $goods = $this->goodRepository->findAll();

        $io->title('Debug Goods Data');
        $io->text('Total goods found: ' . count($goods));

        if (empty($goods)) {
            $io->warning('No goods found in database!');
            return Command::FAILURE;
        }

        foreach ($goods as $good) {
            $io->writeln(sprintf(
                'ID: %d, Name: %s, Comment: %s, Count: %d',
                $good->getId(),
                $good->getName(),
                $good->getComment() ?: 'null',
                $good->getCount()
            ));
        }

        // Test what the controller would return (original way - broken)
        $io->section('Testing JSON Response (broken way - entities directly)');
        $jsonResponse = new JsonResponse($goods);
        $jsonContent = $jsonResponse->getContent();

        $io->writeln('JSON Response Content (before fix):');
        $io->writeln($jsonContent);

        // Test the fixed way
        $data = array_map(function($good) {
            return [
                'id' => $good->getId(),
                'name' => $good->getName(),
                'comment' => $good->getComment(),
                'count' => $good->getCount(),
            ];
        }, $goods);

        $jsonResponseFixed = new JsonResponse($data);
        $jsonContentFixed = $jsonResponseFixed->getContent();

        $io->writeln('JSON Response Content (fixed way - arrays):');
        $io->writeln($jsonContentFixed);

        // Decode and show structure for fixed version
        $decodedFixed = json_decode($jsonContentFixed, true);
        $io->writeln('Decoded JSON structure (fixed):');
        $io->writeln(json_encode($decodedFixed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
