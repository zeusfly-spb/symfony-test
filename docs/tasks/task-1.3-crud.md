# Задание 1.3: Полный CRUD для товаров

## Цель
Реализовать полный набор CRUD операций (Create, Read, Update, Delete) для товаров с правильными HTTP методами и кодами ответов.

## Теория (10-15 минут)
- [HTTP Methods](https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)
- [HTTP Status Codes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)
- [RESTful API Design](https://restfulapi.net/)

## Текущее состояние

Сейчас у вас есть только `GET /api/goods` для получения списка товаров.

## Задание

### Шаг 1: Создание товара (POST)

Добавьте метод в `GoodController`:

```php
#[Route('/api/goods', name: 'app_api_goods_create', methods: ['POST'])]
public function create(
    Request $request,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): JsonResponse {
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    $data = json_decode($request->getContent(), true);
    
    // Валидация данных
    $good = new Good();
    $good->setName($data['name'] ?? '');
    $good->setComment($data['comment'] ?? null);
    $good->setCount($data['count'] ?? 0);
    
    // TODO: Добавьте валидацию через DTO (как в задании 1.1)
    
    $errors = $validator->validate($good);
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
    }

    $entityManager->persist($good);
    $entityManager->flush();

    return $this->json([
        'id' => $good->getId(),
        'name' => $good->getName(),
        'comment' => $good->getComment(),
        'count' => $good->getCount(),
    ], 201);
}
```

### Шаг 2: Получение одного товара (GET)

```php
#[Route('/api/goods/{id}', name: 'app_api_goods_show', methods: ['GET'])]
public function show(int $id, GoodRepository $goodRepository): JsonResponse
{
    $good = $goodRepository->find($id);
    
    if (!$good) {
        return $this->json(['error' => 'Good not found'], 404);
    }

    return $this->json([
        'id' => $good->getId(),
        'name' => $good->getName(),
        'comment' => $good->getComment(),
        'count' => $good->getCount(),
    ]);
}
```

### Шаг 3: Обновление товара (PUT)

```php
#[Route('/api/goods/{id}', name: 'app_api_goods_update', methods: ['PUT'])]
public function update(
    int $id,
    Request $request,
    GoodRepository $goodRepository,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): JsonResponse {
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    $good = $goodRepository->find($id);
    
    if (!$good) {
        return $this->json(['error' => 'Good not found'], 404);
    }

    // TODO: Проверка прав доступа (только владелец или админ)
    // if ($good->getUser() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
    //     return $this->json(['error' => 'Forbidden'], 403);
    // }

    $data = json_decode($request->getContent(), true);
    
    if (isset($data['name'])) {
        $good->setName($data['name']);
    }
    if (isset($data['comment'])) {
        $good->setComment($data['comment']);
    }
    if (isset($data['count'])) {
        $good->setCount($data['count']);
    }

    $errors = $validator->validate($good);
    if (count($errors) > 0) {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }
        return $this->json(['error' => 'Validation failed', 'errors' => $errorMessages], 400);
    }

    $entityManager->flush();

    return $this->json([
        'id' => $good->getId(),
        'name' => $good->getName(),
        'comment' => $good->getComment(),
        'count' => $good->getCount(),
    ]);
}
```

### Шаг 4: Удаление товара (DELETE)

```php
#[Route('/api/goods/{id}', name: 'app_api_goods_delete', methods: ['DELETE'])]
public function delete(
    int $id,
    GoodRepository $goodRepository,
    EntityManagerInterface $entityManager
): JsonResponse {
    $user = $this->getUser();
    if (!$user) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    $good = $goodRepository->find($id);
    
    if (!$good) {
        return $this->json(['error' => 'Good not found'], 404);
    }

    // TODO: Проверка прав доступа

    $entityManager->remove($good);
    $entityManager->flush();

    return $this->json(['message' => 'Good deleted successfully'], 204);
}
```

### Шаг 5: Пагинация для списка товаров

Обновите метод `index`:

```php
#[Route('/api/goods', name: 'app_api_goods', methods: ['GET'])]
public function index(
    Request $request,
    GoodRepository $goodRepository
): JsonResponse {
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = max(1, min(100, (int) $request->query->get('limit', 10)));
    $sort = $request->query->get('sort', 'id');
    $order = $request->query->get('order', 'ASC');

    $offset = ($page - 1) * $limit;

    $goods = $goodRepository->findBy(
        [],
        [$sort => $order],
        $limit,
        $offset
    );

    $total = $goodRepository->count([]);
    $totalPages = ceil($total / $limit);

    $data = array_map(fn($good) => [
        'id' => $good->getId(),
        'name' => $good->getName(),
        'comment' => $good->getComment(),
        'count' => $good->getCount(),
    ], $goods);

    return $this->json([
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
        ]
    ]);
}
```

## Проверка

Протестируйте все endpoints:

1. **Создание товара:**
```bash
curl -X POST http://localhost:8000/api/goods \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Test Good","comment":"Test comment","count":10}'
```

2. **Получение товара:**
```bash
curl http://localhost:8000/api/goods/1
```

3. **Обновление товара:**
```bash
curl -X PUT http://localhost:8000/api/goods/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Updated Good","count":20}'
```

4. **Удаление товара:**
```bash
curl -X DELETE http://localhost:8000/api/goods/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

5. **Список с пагинацией:**
```bash
curl "http://localhost:8000/api/goods?page=1&limit=5&sort=name&order=ASC"
```

## Дополнительные задания

1. Добавьте фильтрацию по имени товара: `?name=test`
2. Добавьте валидацию через DTO для создания/обновления
3. Реализуйте проверку прав доступа (только владелец может редактировать/удалять)
4. Добавьте мягкое удаление (soft delete) вместо физического удаления

## Критерии успешного выполнения

- ✅ Реализован POST /api/goods (создание)
- ✅ Реализован GET /api/goods/{id} (получение одного)
- ✅ Реализован PUT /api/goods/{id} (обновление)
- ✅ Реализован DELETE /api/goods/{id} (удаление)
- ✅ Добавлена пагинация для списка товаров
- ✅ Правильные HTTP коды ответов (201, 200, 404, 401, 400)
- ✅ Все endpoints требуют авторизации (кроме GET списка, если нужно)
- ✅ Валидация входных данных

## Полезные ссылки

- [Symfony Routing](https://symfony.com/doc/current/routing.html)
- [HTTP Status Codes](https://httpstatuses.com/)
- [REST API Tutorial](https://restfulapi.net/)

