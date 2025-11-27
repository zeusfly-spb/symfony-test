<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class GoodCreateRequest
{
    #[Assert\NotBlank(message: "Name is required")]
    public ?string $name = null;

    public ?string $comment = null;

    #[Assert\NotBlank(message: "Count is required")]
    public ?int $count = null;
}