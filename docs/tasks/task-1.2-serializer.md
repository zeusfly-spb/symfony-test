# Задание 1.2: Serializer и группы сериализации

## Цель
Научиться использовать Symfony Serializer Component для автоматической сериализации объектов в JSON и применять группы сериализации для контроля того, какие данные возвращаются в разных endpoints.

## Теория (10-15 минут)
- [Symfony Serializer Component](https://symfony.com/doc/current/serializer.html)
- [Serialization Groups](https://symfony.com/doc/current/serializer.html#using-serialization-groups-annotations)
- [Context в Serializer](https://symfony.com/doc/current/serializer.html#using-serialization-context)

## Зачем это нужно?

Сейчас в вашем коде вы вручную создаете массивы для JSON ответов:
```php
$response = array_map(fn($user) => [
    'id' => $user->getId(),
    'name' => $user->getName(),
    'email' => $user->getEmail(),
    'roles' => $user->getRoles()
], $users);
```

С Serializer это можно делать автоматически, и еще лучше - контролировать, какие поля возвращать в разных ситуациях (например, не показывать email в списке пользователей, но показывать в детальной информации).

## Задание

### Шаг 1: Установка компонента (если не установлен)

Symfony Serializer обычно уже установлен, но проверим:
```bash
composer require symfony/serializer
```

### Шаг 2: Проверка установки Serializer

Symfony Serializer уже установлен (видно в `composer.json`). **Ничего добавлять в `services.yaml` не нужно!** 

Symfony автоматически регистрирует `SerializerInterface` через autowiring. Вы можете просто использовать его в контроллерах через dependency injection.

Если хотите настроить кастомные опции Serializer (например, формат дат, глубина сериализации), можно добавить в `config/packages/framework.yaml`:

```yaml
framework:
    serializer:
        # Опциональные настройки (обычно не нужны)
        # default_context:
        #     groups: ['default']
```

Но для базового использования это не требуется.

### Шаг 3: Добавление групп сериализации в Entity

Обновите `src/Entity/User.php`, добавив атрибуты для групп сериализации:

```php
use Symfony\Component\Serializer\Annotation\Groups;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user:detail', 'user:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:detail', 'user:write'])]  // НЕ в user:read
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:detail', 'user:write'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['user:detail'])]  // Роли только в детальной информации
    private array $roles = [];

    // password не включаем ни в какие группы - никогда не возвращаем пароль!
    #[ORM\Column]
    private ?string $password = null;

    // ... остальные методы
}
```

### Шаг 4: Обновление UserController

Обновите `src/Controller/UserController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use App\Repository\UserRepository;

final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/api/users', name: 'app_api_user')]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        // Используем группу user:read (без email)
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('user:read')
            ->toArray();
        
        $json = $this->serializer->serialize($users, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200, [], true);
    }

    #[Route('/api/users/{id}', name: 'app_api_user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }
        
        // Используем группу user:detail (с email и roles)
        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('user:detail')
            ->toArray();
        
        $json = $this->serializer->serialize($user, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200, [], true);
    }
}
```

### Шаг 5: Обновление AuthController

Обновите методы в `src/Controller/AuthController.php` для использования Serializer:

```php
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

class AuthController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    // В методе apiLogin, после создания токена:
    $context = (new ObjectNormalizerContextBuilder())
        ->withGroups('user:detail')
        ->toArray();
    
    $userJson = $this->serializer->serialize($user, 'json', $context);
    $userData = json_decode($userJson, true);

    return $this->json([
        'token' => $token,
        'user' => $userData
    ]);

    // Аналогично в apiRegister и apiProfile
}
```

### Шаг 6: Обновление GoodController

Добавьте группы сериализации в `src/Entity/Good.php`:

```php
use Symfony\Component\Serializer\Annotation\Groups;

class Good
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['good:read', 'good:detail', 'good:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['good:read', 'good:detail', 'good:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['good:read', 'good:detail', 'good:write'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['good:read', 'good:detail', 'good:write'])]
    private ?int $count = null;
}
```

И обновите `GoodController`:

```php
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;

final class GoodController extends AbstractController
{
    public function __construct(
        private GoodRepository $goodRepository,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/api/goods', name: 'app_api_goods', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $goods = $this->goodRepository->findAll();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('good:read')
            ->toArray();
        
        $json = $this->serializer->serialize($goods, 'json', $context);
        
        return new JsonResponse(json_decode($json, true), 200, [], true);
    }
}
```

## Альтернативный способ (более простой)

Если `ObjectNormalizerContextBuilder` кажется сложным, можно использовать более простой способ:

```php
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

// В контроллере:
$json = $this->serializer->serialize($users, 'json', [
    'groups' => 'user:read'
]);

return new JsonResponse(json_decode($json, true), 200, [], true);
```

Или еще проще - использовать метод `json()` с контекстом:

```php
return $this->json($users, 200, [], [
    'groups' => 'user:read'
]);
```

## Проверка

Протестируйте endpoints:

1. **Список пользователей (без email):**
```bash
curl http://localhost:8000/api/users
```
Должен вернуть пользователей БЕЗ поля `email`.

2. **Детальная информация пользователя (с email):**
```bash
curl http://localhost:8000/api/users/1
```
Должен вернуть пользователя С полем `email` и `roles`.

3. **Список товаров:**
```bash
curl http://localhost:8000/api/goods
```
Должен использовать Serializer вместо ручного `array_map`.

## Дополнительные задания (опционально)

1. Создайте группу `good:list` для списка товаров (только id и name)
2. Создайте группу `good:detail` для детальной информации (все поля)
3. Добавьте в User связь с Goods и используйте группу `user:detail` для включения списка товаров пользователя
4. Используйте `@MaxDepth` для ограничения глубины сериализации связанных объектов

## Критерии успешного выполнения

- ✅ Добавлены атрибуты `Groups` в Entity классы (User, Good)
- ✅ Созданы группы: `user:read`, `user:detail`, `user:write`
- ✅ `UserController` использует Serializer вместо ручного `array_map`
- ✅ `GoodController` использует Serializer
- ✅ `AuthController` использует Serializer для ответов
- ✅ Список пользователей не содержит email
- ✅ Детальная информация пользователя содержит email и roles

## Полезные ссылки

- [Symfony Serializer Documentation](https://symfony.com/doc/current/serializer.html)
- [Serialization Groups](https://symfony.com/doc/current/serializer.html#using-serialization-groups-annotations)
- [Serializer Best Practices](https://symfony.com/doc/current/serializer.html#serializer-best-practices)

## Примечания

- Пароль пользователя НИКОГДА не должен быть в группах сериализации
- Группы сериализации помогают контролировать, какие данные видят разные клиенты API
- Serializer автоматически обрабатывает связанные объекты (если правильно настроены группы)

