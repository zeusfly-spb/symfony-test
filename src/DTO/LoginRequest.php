<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Email is not valid")]
    public ?string $email = null;

    #[Assert\NotBlank(message: "Password is required")]
    public ?string $password = null;
}