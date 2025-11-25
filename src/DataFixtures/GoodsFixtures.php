<?php

namespace App\DataFixtures;

use App\Entity\Good;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GoodsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $goods = [
            ["name" => "Ноутбук Dell XPS 13", "comment" => "Ультрабук для повседневного использования", "count" => 25],
            ["name" => "Смартфон Samsung Galaxy S23", "comment" => "Флагманский смартфон с камерой на 50МП", "count" => 50],
            ["name" => "Мышь Logitech MX Master 3S", "comment" => "Эргономичная беспроводная мышь", "count" => 100],
            ["name" => "Клавиатура Keychron K8", "comment" => "Механическая клавиатура с подсветкой", "count" => 75],
            ["name" => "Монитор LG 27UK650-W", "comment" => null, "count" => 30],
            ["name" => "Наушники Sony WH-1000XM4", "comment" => "Беспроводные наушники с шумоподавлением", "count" => 40],
            ["name" => "Внешний SSD Samsung T7", "comment" => "Быстрый накопитель USB 3.2", "count" => 60],
            ["name" => "Игровая мышь Razer DeathAdder V3", "comment" => "Оптическая игровая мышь", "count" => 45],
            ["name" => "Зарядное устройство Anker", "comment" => null, "count" => 200],
            ["name" => "Веб-камера Logitech C920", "comment" => "HD веб-камера для видеоконференций", "count" => 35],
        ];

        foreach ($goods as $i => $goodData) {
            $good = new Good();
            $good->setName($goodData["name"]);
            $good->setComment($goodData["comment"]);
            $good->setCount($goodData["count"]);
            $manager->persist($good);
            $this->addReference("good-{$i}", $good);
        }

        $manager->flush();
    }
}
