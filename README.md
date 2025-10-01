# KO Embedding Service

Микросервис для работы с эмбеддингами и классификации предложений на основе векторных представлений.

## Описание

Сервис предоставляет API для:
- Классификации предложений по категориям с использованием векторных эмбеддингов
- Поиска похожих предложений на основе косинусного сходства
- Интеграции с GigaChat для AI-функциональности
- Построения и управления векторными представлениями

## Технологии

- **PHP 8.4**
- **Laravel 12** - PHP фреймворк
- **PostgreSQL + pgvector** - база данных с поддержкой векторных операций
- **OpenAI API клиент** - для работы с OpenAI API
- **GigaChat** - AI генерация ответов на обращения
- **Docker** - контейнеризация

## Быстрый старт

### 1. Клонирование
```bash
git clone https://git.eltc.ru/capp-kuzbass/ai-search.git
cd ai-search
```

### 2. Настройка окружения
```bash
cp .env.example .env
# Отредактируйте .env файл
```

### 3. Запуск через Docker
```bash
docker-compose up -d
```

### 4. Установка зависимостей и миграции
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

## Основные команды

```bash
# Построение векторов категорий
php artisan embeddings:build-categories

# Построение векторов предложений
php artisan embeddings:build-proposals

# Отладка классификации
php artisan embeddings:classify-single

# Валидация классификатора
php artisan embeddings:validate
```

## Docker команды

```bash
# Запуск
docker-compose up -d

# Логи
docker-compose logs -f

# Остановка
docker-compose down

# Пересборка
docker-compose build --no-cache
```
