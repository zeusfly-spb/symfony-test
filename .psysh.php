<?php

/**
 * PsySH configuration file for Symfony
 * This file is automatically loaded when you start psysh
 * 
 * This file should return an array of variables that will be available in the shell
 */

// Инициализация Symfony Kernel
$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? true);

$kernel = new \App\Kernel($env, $debug);
$kernel->boot();
$container = $kernel->getContainer();

// Получение EntityManager для работы с базой данных
$em = $container->get('doctrine.orm.entity_manager');

// Получение репозиториев через EntityManager (правильный способ)
$userRepo = $em->getRepository(\App\Entity\User::class);
$goodRepo = $em->getRepository(\App\Entity\Good::class);

// Вывод приветственного сообщения
echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║  Symfony PsySH Shell - Ready!                             ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n";
