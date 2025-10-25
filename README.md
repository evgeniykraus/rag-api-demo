# Система управления обращениями граждан с AI-классификацией

Веб-приложение для управления обращениями граждан с автоматической классификацией, семантическим поиском и AI-генерацией ответов. Система использует векторные представления для интеллектуальной обработки текстовых данных.

## Быстрый старт

### Требования
- **Docker** и **Docker Compose**
- **Git**

### Установка
1. **Клонируйте репозиторий**:
   ```bash
   git clone https://github.com/evgeniykraus/rag-api-demo.git
   cd rag-api-demo
   ```

2. **Настройте .env** в `backend/.env`:
    ```bash
   # Создайте .env файл
   cp .env.example .env
   ```
   Обязательно заполните эти переменные
   ```env
   # Модель для создания эмбеддингов
   EMBEDDINGS_MODEL=text-embedding-3-small (по-умолчанию)
   
   # Модель для генерации ответов
   OPENAI_MODEL=gpt-4o
   
   # API ключи
   OPENAI_API_KEY=your_openai_api_key
   
   # URL API (необходимо OpenAI совместимое API)
   OPENAI_BASE_URL=your_openai_url
   EMBEDDINGS_BASE_URL=your_openai_url
   ```

3. **Запустите приложение**:
   ```bash
   docker-compose up -d
   ```

4. **Настройте Laravel** (выполните один раз):
   ```bash
   # Создайте .env файл
   docker-compose exec app cp .env.example .env
   
   # Сгенерируйте ключ приложения
   docker-compose exec app php artisan key:generate
   
   # Выполните миграции
   docker-compose exec app php artisan migrate
   
   # Заполните базовые данные (опционально)
   docker-compose exec app php artisan db:seed
   ```

## Доступ к приложению

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8088
- **База данных**: localhost:5435 (PostgreSQL)

## Основные команды

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

## Технологии

- **Backend**: Laravel 12 + PHP 8.4
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS
- **База данных**: PostgreSQL + pgvector
- **AI**: OpenAI API
- **Контейнеризация**: Docker + Docker Compose

## Документация API
В проекте установлен пакет [knuckleswtf/scribe](https://scribe.knuckles.wtf/laravel/), который используется для
автоматической генерации документации в формате Swagger, OpenAPI, а также для создания коллекций для Postman.

Сгенерированные документы сохраняются в папке [scribe](backend/docs/scribe)

### Для генерации документации нужно выполнить команду:
```bash
   docker-compose exec app php artisan scribe:generate
   ```
