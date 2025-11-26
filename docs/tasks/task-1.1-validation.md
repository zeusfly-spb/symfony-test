# Задание 1.1: Валидация и DTO

## Цель
Научиться использовать Symfony Validator Component и создавать DTO классы для валидации входных данных API.

## Теория (5-10 минут чтения)
- [Symfony Validator Component](https://symfony.com/doc/current/validation.html)
- [Validation Constraints](https://symfony.com/doc/current/reference/constraints.html)

## Задание

### Шаг 1: Установка компонента (если не установлен)
```bash
composer require symfony/validator
```

### Шаг 2: Создание DTO класса

Создайте класс `src/DTO/RegisterRequest.php`:

```php
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Email is not valid")]
    public ?string $email = null;

    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(
        min: 8,
        minMessage: "Password must be at least {{ limit }} characters long"
    )]
    #[Assert\Regex(
        pattern: "/\d/",
        message: "Password must contain at least one digit"
    )]
    #[Assert\Regex(
        pattern: "/[a-zA-Z]/",
        message: "Password must contain at least one letter"
    )]
    public ?string $password = null;

    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(
        min: 2,
        minMessage: "Name must be at least {{ limit }} characters long"
    )]
    public ?string $name = null;
}
```

### Шаг 3: Обновление AuthController

Обновите метод `apiRegister` в `src/Controller/AuthController.php`:

```php
use App\DTO\RegisterRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// В методе apiRegister:
public function apiRegister(
    Request $request,
    EntityManagerInterface $entityManager,
    UserPasswordHasherInterface $passwordHasher,
    ValidatorInterface $validator
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    
    if (!$data && $request->isMethod('POST')) {
        $data = [
            'email' => $request->request->get('email'),
            'password' => $request->request->get('password'),
            'name' => $request->request->get('name')
        ];
    }

    // Создаем DTO и заполняем данными
    $registerRequest = new RegisterRequest();
    $registerRequest->email = $data['email'] ?? null;
    $registerRequest->password = $data['password'] ?? null;
    $registerRequest->name = $data['name'] ?? null;

    // Валидация
    $errors = $validator->validate($registerRequest);
    
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
    }

    // Проверка существующего пользователя
    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $registerRequest->email]);
    if ($existingUser) {
        return $this->json(['error' => 'User with this email already exists'], 409);
    }

    // Создание пользователя
    $user = new User();
    $user->setEmail($registerRequest->email);
    $user->setName($registerRequest->name);
    $user->setPassword($passwordHasher->hashPassword($user, $registerRequest->password));

    $entityManager->persist($user);
    $entityManager->flush();

    return $this->json([
        'message' => 'User registered successfully',
        'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]
    ], 201);
}
```

### Шаг 4: Создание DTO для Login

Создайте `src/DTO/LoginRequest.php`:

```php
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
```

Обновите метод `apiLogin` аналогично `apiRegister`.

## Проверка

Протестируйте валидацию:

1. **Успешная регистрация:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123","name":"Test User"}'
```

2. **Ошибка валидации (короткий пароль):**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123","name":"Test User"}'
```

Должна вернуться ошибка валидации.

3. **Ошибка валидации (невалидный email):**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"invalid-email","password":"password123","name":"Test User"}'
```

## Дополнительные задания (опционально)

1. Создайте DTO для обновления профиля пользователя
2. Добавьте кастомный валидатор для проверки силы пароля
3. Создайте DTO для создания товара с валидацией

## Критерии успешного выполнения

- ✅ Создан класс `RegisterRequest` с валидацией
- ✅ Создан класс `LoginRequest` с валидацией
- ✅ Методы `apiRegister` и `apiLogin` используют DTO и валидатор
- ✅ При ошибках валидации возвращается понятный JSON с ошибками
- ✅ Все тесты проходят успешно

## Полезные ссылки

- [Symfony Validator Documentation](https://symfony.com/doc/current/validation.html)
- [Available Constraints](https://symfony.com/doc/current/reference/constraints.html)
- [Custom Validation Constraints](https://symfony.com/doc/current/validation/custom_constraint.html)

