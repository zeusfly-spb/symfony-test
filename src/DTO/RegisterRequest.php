<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email is not valid')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        minMessage: "Password must be at least {{ limit }} characters long"
    )]
    public ?string $password = null;

    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(
        min: 2,
        minMessage: "Name must be at least {{ limit }} characters long"
    )]
    public ?string $name = null;

}