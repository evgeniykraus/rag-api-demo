# Frontend - Система управления обращениями граждан

Vue 3 SPA приложение с AI-классификацией и семантическим поиском обращений граждан.

## Технологии

- **Vue 3** + TypeScript + Composition API
- **Tailwind CSS** + адаптивный дизайн
- **Pinia** для состояния + **Vue Router**
- **Axios** для API + **Chart.js** для графиков

## Быстрый старт

### Локальная разработка
```bash
npm install
npm run dev
```

### Docker
```bash
docker-compose up frontend
```

## Основные функции

- **Управление обращениями**: создание, редактирование, поиск
- **AI-классификация**: автоматическое определение категорий
- **Семантический поиск**: поиск по смыслу текста
- **Аналитика**: графики и статистика
- **Темная/светлая тема**

## API интеграция

- `GET /api/v1/proposals` - список обращений
- `POST /api/v1/proposals` - создание
- `GET /api/v1/proposals/search` - поиск
- `GET /api/v1/dictionary/*` - справочники

## Конфигурация

```env
VITE_API_BASE_URL=http://localhost:8088
VITE_API_KEY=your_api_key_here
```

