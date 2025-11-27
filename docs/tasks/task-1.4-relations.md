# Задание 1.4: Связи между сущностями и проверка прав доступа

## Что было сделано

### 1. Добавлена связь User -> Good

В `src/Entity/Good.php` добавлено:
- Свойство `$user` с типом `User`
- Связь `ManyToOne` (много товаров принадлежат одному пользователю)
- Методы `getUser()` и `setUser()`

### 2. Обновлен GoodController

Добавлены методы:
- `show(int $id)` - получение одного товара
- `update(int $id, ...)` - обновление товара с проверкой прав
- `delete(int $id, ...)` - удаление товара с проверкой прав
- `my()` - получение товаров текущего пользователя

### 3. Проверка прав доступа

В методах `update()` и `delete()` добавлена проверка:
```php
// Только владелец товара или админ может редактировать/удалять
if ($good->getUser() !== $user && !\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
    return $this->json(['error' => 'Forbidden: You can only edit your own goods'], 403);
}
```

## Что нужно сделать дальше

### Шаг 1: Создать миграцию

Выполните команду для создания миграции:
```bash
php bin/console make:migration
```

Или через Docker:
```bash
docker compose exec php php bin/console make:migration
```

### Шаг 2: Применить миграцию

```bash
php bin/console doctrine:migrations:migrate
```

Или через Docker:
```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

### Шаг 3: Обновить существующие товары (опционально)

Если у вас уже есть товары в базе, нужно будет либо:
1. Удалить их
2. Или назначить им владельца через SQL:
```sql
UPDATE good SET user_id = 1; -- где 1 - ID существующего пользователя
```

## Проверка

### 1. Создание товара (автоматически привязывается к текущему пользователю)
```bash
curl -X POST http://localhost:8000/api/goods \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Test Good","comment":"Test","count":10}'
```

### 2. Получение товаров текущего пользователя
```bash
curl http://localhost:8000/api/goods/my \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Попытка редактирования чужого товара (должна вернуть 403)
```bash
curl -X PUT http://localhost:8000/api/goods/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ANOTHER_USER_TOKEN" \
  -d '{"name":"Updated"}'
```

### 4. Удаление товара (только владелец)
```bash
curl -X DELETE http://localhost:8000/api/goods/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Дополнительные улучшения (опционально)

### 1. Добавить обратную связь в User

В `src/Entity/User.php` можно добавить:
```php
#[ORM\OneToMany(targetEntity: Good::class, mappedBy: 'user')]
private Collection $goods;

public function __construct()
{
    $this->goods = new ArrayCollection();
}

public function getGoods(): Collection
{
    return $this->goods;
}
```

### 2. Добавить фильтрацию по пользователю в репозитории

В `src/Repository/GoodRepository.php`:
```php
public function findByUser(User $user): array
{
    return $this->createQueryBuilder('g')
        ->andWhere('g.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}
```

### 3. Добавить индексы для производительности

В миграции можно добавить индекс:
```php
$this->addSql('CREATE INDEX IDX_GOOD_USER ON good (user_id)');
```

## Критерии успешного выполнения

- ✅ Связь ManyToOne добавлена в Good -> User
- ✅ При создании товара автоматически привязывается к текущему пользователю
- ✅ Методы update и delete проверяют права доступа
- ✅ Только владелец или админ может редактировать/удалять товар
- ✅ Endpoint `/api/goods/my` возвращает товары текущего пользователя
- ✅ Миграция создана и применена

## Полезные ссылки

- [Doctrine Relations](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html)
- [Symfony Security](https://symfony.com/doc/current/security.html)

